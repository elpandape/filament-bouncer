<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Store;

use ElPandaPe\FilamentBouncer\Support\Tenancy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Silber\Bouncer\Database\Ability;
use Silber\Bouncer\Database\Models;

/**
 * What is wrong with a row of the abilities table.
 *
 * A service with a memory, not a bag of static methods: asked row by row the twins cost a query
 * each, and the listing's column asks twice per row. The twins come back in one query grouped by
 * the quintuple, which works because `GROUP BY` groups nulls together where `=` does not.
 *
 * Bound as `scoped`, so the memory lasts one request; a write inside that request leaves it stale,
 * which is what `forget()` is for.
 */
final class Diagnosis
{
    public const string HEALTHY = 'healthy';

    /** Out of sight, not broken. */
    public const string HIDDEN = 'warning';

    /** Already answering wrongly today. */
    public const string SEVERE = 'danger';

    /**
     * @var array<string, true>|null
     */
    private ?array $duplicates = null;

    /**
     * @var array<string, bool>
     */
    private array $records = [];

    /**
     * @var array{total: int, ailing: int, counts: array<string, int>}|null
     */
    private ?array $census = null;

    public function __construct(private readonly Tenancy $tenancy) {}

    /**
     * Goes after any write that can change a diagnosis.
     */
    public function forget(): void
    {
        $this->duplicates = null;
        $this->records = [];
        $this->census = null;
    }

    /**
     * @return array<int, Ailment>
     */
    public function of(Model $ability): array
    {
        $ailments = [];

        if ($this->hasTwin($ability)) {
            $ailments[] = Ailment::Twin;
        }

        $class = $this->modelOf($ability);

        if ($this->namesAModel($ability) && $class === null) {
            $ailments[] = Ailment::GhostModel;
        } elseif ($class !== null && $this->pointsAtNothing($ability, $class)) {
            // The record can only be asked about where the class loads: over a ghost model there is
            // nobody to ask, and reporting both would say the same thing twice.
            $ailments[] = Ailment::GhostRecord;
        }

        // Where the installation uses Bouncer's tenancy a scoped row is not an anomaly but the
        // point, and the screen respects the scope rather than reading past it.
        if (! $this->tenancy->inUse() && $ability->getAttribute('scope') !== null) {
            $ailments[] = Ailment::Invisible;
        }

        return $ailments;
    }

    public function isHealthy(Model $ability): bool
    {
        return $this->of($ability) === [];
    }

    /**
     * What is wrong with the row in a single word: the listing answers "is there anything, and how
     * bad", the record page answers "what exactly".
     */
    public function severity(Model $ability): string
    {
        $ailments = $this->of($ability);

        if ($ailments === []) {
            return self::HEALTHY;
        }

        foreach ($ailments as $ailment) {
            if ($ailment->isSevere()) {
                return self::SEVERE;
            }
        }

        return self::HIDDEN;
    }

    /**
     * The four checks with the fact inside each answer: "twin" without saying which one sends the
     * reader off to look for it. The only place that asks *which* the twin is, which it can afford
     * because a record page reads one row where the listing reads ten.
     *
     * @return array<int, array{ailment: Ailment, failed: bool, reading: string}>
     */
    public function checks(Model $ability): array
    {
        $twin = $this->twin($ability);
        $type = $ability->getAttribute('entity_type');
        $class = $this->modelOf($ability);
        $id = $ability->getAttribute('entity_id');
        $scope = $ability->getAttribute('scope');

        $checks = [
            [
                'ailment' => Ailment::Twin,
                'failed' => $twin instanceof Model,
                'reading' => $twin instanceof Model
                    ? __('filament-bouncer::abilities.health.twin.yes', ['id' => $this->text($twin->getKey())])
                    : __('filament-bouncer::abilities.health.twin.no'),
            ],
            [
                'ailment' => Ailment::GhostModel,
                'failed' => $this->namesAModel($ability) && $class === null,
                'reading' => match (true) {
                    ! is_string($type) => __('filament-bouncer::abilities.health.ghost-model.none'),
                    $type === AbilityStore::WILDCARD => __('filament-bouncer::abilities.health.ghost-model.any'),
                    $class === null => __('filament-bouncer::abilities.health.ghost-model.no', ['class' => $type]),
                    default => __('filament-bouncer::abilities.health.ghost-model.yes', ['class' => $class]),
                },
            ],
            [
                'ailment' => Ailment::GhostRecord,
                'failed' => $class !== null && $this->pointsAtNothing($ability, $class),
                'reading' => match (true) {
                    $id === null => __('filament-bouncer::abilities.health.ghost-record.none'),
                    $class === null => __('filament-bouncer::abilities.health.ghost-record.unknown'),
                    $this->pointsAtNothing($ability, $class) => __('filament-bouncer::abilities.health.ghost-record.no', ['id' => $this->text($id)]),
                    default => __('filament-bouncer::abilities.health.ghost-record.yes', ['id' => $this->text($id)]),
                },
            ],
        ];

        if ($this->tenancy->inUse()) {
            return $checks;
        }

        $checks[] = [
            'ailment' => Ailment::Invisible,
            'failed' => $scope !== null,
            'reading' => $scope === null
                ? __('filament-bouncer::abilities.health.invisible.yes')
                : __('filament-bouncer::abilities.health.invisible.no', ['scope' => $this->text($scope)]),
        ];

        return $checks;
    }

    /**
     * The other row saying the same thing, so it can be reached.
     */
    public function twin(Model $ability): ?Model
    {
        return $this->hasTwin($ability) ? $this->twins($ability)->first() : null;
    }

    /**
     * How many rows suffer each ailment.
     *
     * @return array{total: int, ailing: int, counts: array<string, int>}
     */
    public function census(): array
    {
        if ($this->census !== null) {
            return $this->census;
        }

        /** @var array<string, int> $counts */
        $counts = [];

        foreach (Ailment::all() as $ailment) {
            $counts[$ailment->value] = 0;
        }

        $total = 0;
        $ailing = 0;

        foreach ($this->all() as $ability) {
            $total++;

            $ailments = $this->of($ability);

            if ($ailments === []) {
                continue;
            }

            $ailing++;

            foreach ($ailments as $ailment) {
                $counts[$ailment->value]++;
            }
        }

        return $this->census = ['total' => $total, 'ailing' => $ailing, 'counts' => $counts];
    }

    /**
     * Whether writing these columns would repeat a row. Here and not on the form because two
     * spellings of what makes rows identical are how the refusal and the report come to disagree.
     *
     * @param  array<string, mixed>  $columns
     */
    public function wouldDuplicate(array $columns, mixed $ignoreKey = null): bool
    {
        $query = $this->query()
            ->where('name', $columns['name'] ?? null)
            ->where('entity_type', $columns['entity_type'] ?? null)
            ->where('entity_id', $columns['entity_id'] ?? null)
            ->where('only_owned', (bool) ($columns['only_owned'] ?? false))
            ->where('scope', $columns['scope'] ?? null);

        if ($ignoreKey !== null) {
            $query->whereKeyNot($ignoreKey);
        }

        return $query->exists();
    }

    /**
     * @return array<int, mixed>
     */
    public function keysWith(Ailment $ailment): array
    {
        $keys = [];

        foreach ($this->all() as $ability) {
            if (in_array($ailment, $this->of($ability), true)) {
                $keys[] = $ability->getKey();
            }
        }

        return $keys;
    }

    private function hasTwin(Model $ability): bool
    {
        return isset($this->duplicateSignatures()[$this->signature($ability)]);
    }

    /**
     * @return array<string, true>
     */
    private function duplicateSignatures(): array
    {
        if ($this->duplicates !== null) {
            return $this->duplicates;
        }

        $columns = ['name', 'entity_type', 'entity_id', 'only_owned', 'scope'];

        $rows = $this->query()
            ->select($columns)
            ->groupBy($columns)
            ->havingRaw('count(*) > 1')
            ->get();

        $signatures = [];

        foreach ($rows as $row) {
            $signatures[$this->signature($row)] = true;
        }

        return $this->duplicates = $signatures;
    }

    /**
     * Encoded rather than concatenated, so a null and an empty string are not written the same:
     * Bouncer tells them apart.
     */
    private function signature(Model $ability): string
    {
        return (string) json_encode([
            $ability->getAttribute('name'),
            $ability->getAttribute('entity_type'),
            $ability->getAttribute('entity_id'),
            (bool) $ability->getAttribute('only_owned'),
            $ability->getAttribute('scope'),
        ]);
    }

    /**
     * @return Builder<Ability>
     */
    private function twins(Model $ability): Builder
    {
        return $this->query()
            ->where('name', $ability->getAttribute('name'))
            ->where('entity_type', $ability->getAttribute('entity_type'))
            ->where('entity_id', $ability->getAttribute('entity_id'))
            ->where('only_owned', (bool) $ability->getAttribute('only_owned'))
            ->where('scope', $ability->getAttribute('scope'))
            ->whereKeyNot($ability->getKey());
    }

    /**
     * @return Collection<int, Ability>
     */
    private function all(): Collection
    {
        return $this->query()->get();
    }

    /**
     * Reads past the tenant scope only where the installation does not use it: there it can reveal
     * nothing but anomalies, whereas under tenancy it would put another tenant's rows on screen.
     *
     * @return Builder<Ability>
     */
    private function query(): Builder
    {
        $query = Models::ability()->newQuery();

        return $this->tenancy->inUse() ? $query : $query->withoutGlobalScopes();
    }

    private function namesAModel(Model $ability): bool
    {
        $type = $ability->getAttribute('entity_type');

        return is_string($type) && $type !== AbilityStore::WILDCARD;
    }

    /**
     * @return class-string<Model>|null
     */
    private function modelOf(Model $ability): ?string
    {
        if (! $this->namesAModel($ability)) {
            return null;
        }

        /** @var string $type */
        $type = $ability->getAttribute('entity_type');

        /** @var class-string<Model>|null $class */
        $class = Relation::getMorphedModel($type) ?? (is_a($type, Model::class, true) ? $type : null);

        return $class;
    }

    /**
     * @param  class-string<Model>  $class
     */
    private function pointsAtNothing(Model $ability, string $class): bool
    {
        $id = $ability->getAttribute('entity_id');

        if ($id === null) {
            return false;
        }

        // Several rows can fence the same record, and the listing's column asks twice per row.
        $key = $class.'|'.$this->text($id);

        return ! ($this->records[$key] ??= $class::query()->whereKey($id)->exists());
    }

    private function text(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }
}
