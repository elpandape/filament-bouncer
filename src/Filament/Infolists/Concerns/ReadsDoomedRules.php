<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Infolists\Concerns;

use ElPandaPe\FilamentBouncer\Catalog\CatalogRegistry;
use ElPandaPe\FilamentBouncer\Catalog\Entity;
use ElPandaPe\FilamentBouncer\Store\Declaration;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\Database\Ability as StoredAbility;
use Silber\Bouncer\Database\Models;

/**
 * What this role is about to lose to the next `--prune`.
 *
 * Only the doomed ones: `Declaration` has three states, and listing all three puts the narrowed
 * rules under the same alarm, which is how people learn to ignore it.
 *
 * The entity is looked up in the catalogue even though the rule has left it — what stopped being
 * declared is the action, not the model — while the action is said raw. Humanising a name nobody
 * translated invents a title that reads as if somebody had written it, when the only certain thing
 * is the identifier stored on the row, which is also what has to be searched for.
 */
trait ReadsDoomedRules
{
    /**
     * @return list<array{action: string, entity: string|null}>
     */
    public function getDoomed(): array
    {
        $record = $this->roleOrNull();

        if (! $record instanceof Model) {
            return [];
        }

        $entities = app(CatalogRegistry::class)->current()->entities;
        $doomed = [];

        foreach ($this->doomedRows($record) as $row) {
            if (! Declaration::of($row)->isDoomed()) {
                continue;
            }

            /** @var string $name */
            $name = $row->getAttribute('name');

            /** @var string|null $type */
            $type = $row->getAttribute('entity_type');

            $doomed[] = [
                'action' => $name,
                'entity' => $type === null ? null : ($entities[Entity::keyFor($type)]->label ?? class_basename($type)),
            ];
        }

        return $doomed;
    }

    public function isClean(): bool
    {
        return $this->getDoomed() === [];
    }

    /**
     * Every ability row this role holds, catalogued or not.
     *
     * The pivot is joined by hand because the role model is whichever the application
     * configured, and nothing promises the analyser it carries Bouncer's traits.
     *
     * @return EloquentCollection<int, StoredAbility>
     */
    private function doomedRows(Model $record): EloquentCollection
    {
        $abilities = Models::table('abilities');
        $permissions = Models::table('permissions');

        return Models::ability()->newQuery()
            ->join($permissions, $permissions.'.ability_id', '=', $abilities.'.id')
            ->where($permissions.'.entity_id', $record->getKey())
            ->where($permissions.'.entity_type', $record->getMorphClass())
            ->orderBy($abilities.'.name')
            ->get([$abilities.'.*']);
    }
}
