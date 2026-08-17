<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Events;

use ElPandaPe\FilamentBouncer\Catalog\Ability;
use Illuminate\Database\Eloquent\Model;

/**
 * Which rule an event is about, in the one spelling the store uses.
 *
 * `Catalog\Ability` carries the model twice on purpose — as the Gate wants it and as the store
 * writes it — and a stored row can only ever produce the second. Carrying the first here would
 * give one rule two identities the day an application registers a morph map.
 */
final readonly class AbilityRef
{
    public function __construct(
        public string $name,
        public ?string $entityMorphClass,
        public int|string|null $entityId,
        public bool $onlyOwned,
        public ?int $scope,
        public ?string $title,
    ) {}

    public static function fromCatalog(Ability $ability): self
    {
        return new self(
            name: $ability->name,
            entityMorphClass: $ability->entityMorphClass,
            entityId: null,
            onlyOwned: false,
            scope: null,
            title: $ability->title,
        );
    }

    public static function fromRow(Model $ability): self
    {
        $attributes = $ability->getAttributes();

        return new self(
            name: is_string($attributes['name'] ?? null) ? $attributes['name'] : '',
            entityMorphClass: self::text($attributes['entity_type'] ?? null),
            entityId: self::identifier($attributes['entity_id'] ?? null),
            onlyOwned: (bool) ($attributes['only_owned'] ?? false),
            scope: is_numeric($attributes['scope'] ?? null) ? (int) $attributes['scope'] : null,
            title: self::text($attributes['title'] ?? null),
        );
    }

    /**
     * Composed nowhere but in `Catalog\Ability`, which forbids a second spelling of the same
     * idea in as many words.
     */
    public function identity(): string
    {
        return Ability::identityFor($this->name, $this->entityMorphClass);
    }

    public function describe(): string
    {
        return Ability::describeFor($this->name, $this->entityMorphClass);
    }

    private static function text(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * `entity_id` is a raw, un-cast attribute here: the store's driver decides whether it comes
     * back as an int or a numeric string, never anything else this column could hold.
     */
    private static function identifier(mixed $value): int|string|null
    {
        return is_int($value) || is_string($value) ? $value : null;
    }
}
