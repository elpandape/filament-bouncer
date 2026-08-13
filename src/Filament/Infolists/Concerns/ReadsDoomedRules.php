<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Infolists\Concerns;

use ElPandaPe\FilamentBouncer\Catalog\CatalogRegistry;
use ElPandaPe\FilamentBouncer\Catalog\Subject;
use ElPandaPe\FilamentBouncer\Store\Declaration;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Silber\Bouncer\Database\Ability as StoredAbility;
use Silber\Bouncer\Database\Models;

/**
 * What this role is about to lose to the next `--prune`.
 *
 * It counts only the doomed ones. `Declaration` has three states, and a list showing all
 * three puts under the same alarm the narrowed rules — which are healthy, which `--prune`
 * does not touch and which the record page already names one by one. Reading the same
 * warning over a row on its way out and over one in no danger is how people learn to
 * ignore the warning.
 *
 * The subject is looked up in the catalogue even though the rule has left it: what stopped
 * being declared is the action, not the model, so there is nearly always a label to read
 * and the class name with its namespace is the last resort rather than the first.
 *
 * The action, on the other hand, is said raw. Its readable label would come from
 * humanising the name — nobody translated an action that no longer exists — and that
 * invented title reads as if somebody had written it, when the only certain thing is the
 * identifier stored on the row, which is also what anybody has to search for to find where
 * it came from.
 */
trait ReadsDoomedRules
{
    /**
     * @return list<array{action: string, subject: string|null}>
     */
    public function getDoomed(): array
    {
        $record = $this->roleOrNull();

        if (! $record instanceof Model) {
            return [];
        }

        $subjects = app(CatalogRegistry::class)->current()->subjects;
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
                'subject' => $type === null ? null : ($subjects[Subject::keyFor($type)]->label ?? class_basename($type)),
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
     * The pivot is joined by hand rather than read through the relation because the role
     * model is whichever the application configured, and nothing promises the analyser it
     * carries Bouncer's traits.
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
