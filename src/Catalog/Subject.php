<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Catalog;

use Illuminate\Database\Eloquent\Model;

/**
 * One row of the grid: something the panel exposes, and what may be done with it.
 */
final readonly class Subject
{
    /**
     * @param  class-string<Model>|null  $entityType
     * @param  array<string, Ability>  $abilities  keyed by action
     */
    public function __construct(
        public string $key,
        public string $label,
        public SubjectKind $kind,
        public ?string $entityType,
        public array $abilities,
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

    public function ability(string $action): ?Ability
    {
        return $this->abilities[$action] ?? null;
    }
}
