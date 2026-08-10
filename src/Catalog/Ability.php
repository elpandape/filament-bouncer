<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Catalog;

use Illuminate\Database\Eloquent\Model;

/**
 * One ability the panel is able to ask about.
 *
 * This is the only place in the package that decides how an ability is named. Nothing
 * else may compose a name out of its parts: a second spelling of the same idea would
 * store abilities nobody ever checks, and Bouncer never complains about a name that
 * does not exist (it answers `false`, or `true` for anyone holding the wildcard).
 */
final readonly class Ability
{
    /**
     * What separates a component prefix from the component it names.
     */
    public const string SEPARATOR = ':';

    public const string PAGE_PREFIX = 'page';

    public const string WIDGET_PREFIX = 'widget';

    /**
     * The single action a page or a widget offers: you either reach it or you do not.
     */
    public const string ACCESS_ACTION = 'view';

    /**
     * The single action an ability declared in configuration offers.
     */
    public const string CUSTOM_ACTION = 'use';

    /**
     * Joins the two halves of the stored identity. A NUL byte cannot occur in a class
     * name nor in an ability name, so no pair of abilities can collide into one key.
     */
    private const string IDENTITY_SEPARATOR = "\0";

    /**
     * @param  class-string<Model>|null  $entityType  the model the ability is about, as the Gate wants it
     * @param  string|null  $entityMorphClass  the same model as the store spells it
     */
    private function __construct(
        public string $name,
        public ?string $entityType,
        public ?string $entityMorphClass,
        public string $action,
        public string $title,
        public AbilityScope $scope,
    ) {}

    /**
     * @param  class-string<Model>  $model
     */
    public static function forModel(string $model, string $action, string $title, AbilityScope $scope): self
    {
        return new self($action, $model, (new $model)->getMorphClass(), $action, $title, $scope);
    }

    public static function forPage(string $key, string $title): self
    {
        return new self(self::qualify(self::PAGE_PREFIX, $key), null, null, self::ACCESS_ACTION, $title, AbilityScope::Read);
    }

    public static function forWidget(string $key, string $title): self
    {
        return new self(self::qualify(self::WIDGET_PREFIX, $key), null, null, self::ACCESS_ACTION, $title, AbilityScope::Read);
    }

    public static function custom(string $name, string $title, AbilityScope $scope): self
    {
        return new self($name, null, null, self::CUSTOM_ACTION, $title, $scope);
    }

    /**
     * The same identity, composed from a stored row rather than from a declaration.
     */
    public static function identityFor(string $name, ?string $entityMorphClass): string
    {
        return $name.self::IDENTITY_SEPARATOR.($entityMorphClass ?? '');
    }

    public static function describeFor(string $name, ?string $entityMorphClass): string
    {
        return $entityMorphClass === null ? $name : $name.' on '.$entityMorphClass;
    }

    /**
     * What tells this ability apart from every other one in the store.
     */
    public function identity(): string
    {
        return self::identityFor($this->name, $this->entityMorphClass);
    }

    /**
     * How an ability reads when a human has to recognise it in a report.
     */
    public function describe(): string
    {
        return self::describeFor($this->name, $this->entityMorphClass);
    }

    /**
     * The row this ability is, as the store keeps it.
     *
     * `entity_id` and `only_owned` are pinned to their empty values because the
     * catalogue only ever describes blanket abilities. Rows that carry either are
     * about one record or one owner, so they belong to the application and the
     * reconciliation has no business touching them.
     *
     * @return array<string, mixed>
     */
    public function attributes(): array
    {
        return [
            'name' => $this->name,
            'title' => $this->title,
            'entity_id' => null,
            'entity_type' => $this->entityMorphClass,
            'only_owned' => false,
        ];
    }

    private static function qualify(string $prefix, string $key): string
    {
        return $prefix.self::SEPARATOR.$key;
    }
}
