<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Catalog;

use Illuminate\Database\Eloquent\Model;

/**
 * One row of the grid: something the panel exposes, and what may be done with it.
 */
final readonly class Entity
{
    /**
     * @param  class-string<Model>|null  $entityType
     * @param  array<string, Ability>  $abilities  keyed by action
     * @param  Ability|null  $manage  the grant covering the whole model, kept out of the
     *                                actions because nothing ever checks it: the Gate is
     *                                asked `view` or `delete`, and Bouncer matches this
     *                                row on the way
     */
    public function __construct(
        public string $key,
        public string $label,
        public EntityKind $kind,
        public ?string $entityType,
        public array $abilities,
        public ?Ability $manage = null,
    ) {}

    /**
     * A key that survives being written into a form state path.
     *
     * Livewire splits state paths on dots, so a key carrying one would silently nest
     * the cell under a level that does not exist. Backslashes go the same way: they
     * are legal in the path but read as an escape everywhere the path is printed.
     */
    public static function keyFor(string $value): string
    {
        return mb_strtolower(str_replace(['\\', '.'], '-', $value));
    }

    /**
     * Every ability the grid holds a cell for, keyed by the action its state path uses.
     *
     * The grant covering the whole model comes first and under a key of its own, so it
     * can never be mistaken for a policy method nor sorted in among the columns.
     *
     * @return array<string, Ability>
     */
    public function cells(): array
    {
        return $this->manage instanceof Ability
            ? [Ability::MANAGE_ACTION => $this->manage] + $this->abilities
            : $this->abilities;
    }

    public function ability(string $action): ?Ability
    {
        return $this->abilities[$action] ?? null;
    }
}
