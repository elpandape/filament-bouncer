<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Resources\Abilities\Schemas;

use Closure;
use ElPandaPe\FilamentBouncer\Catalog\CatalogRegistry;
use ElPandaPe\FilamentBouncer\Filament\Forms\DerivedTitle;
use ElPandaPe\FilamentBouncer\Store\AbilityStore;
use ElPandaPe\FilamentBouncer\Store\Diagnosis;
use ElPandaPe\FilamentBouncer\Support\Labels;
use ElPandaPe\FilamentBouncer\Support\Tenancy;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Silber\Bouncer\Database\Models;
use Silber\Bouncer\Database\Titles\AbilityTitle;

/**
 * Every column of an ability, all of them writable: this screen is the store's workbench, so a row
 * the catalogue does not declare can be composed here — one `--check` reports and `--prune` sweeps.
 *
 * The name can be taken out of its pull-down and the model cannot, which is not symmetry lost.
 * Composing a rule the catalogue does not yet declare is what this screen is for; naming a model
 * the panel does not declare is the very ailment the health column reports as a ghost. Filament
 * will not let a value be typed into a `Select`, so the name is two components over one state key.
 */
final class AbilityForm
{
    /**
     * Where the posture of the name lives: picked from the list, or written by hand.
     */
    public const string NAME_CUSTOM = 'name_custom';

    public const string NAME_PICKED = 'namePicked';

    public const string NAME_TYPED = 'nameTyped';

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(4)
            ->components([
                Group::make()
                    ->schema([
                        Section::make(__('filament-bouncer::abilities.form.rule'))
                            ->description(__('filament-bouncer::abilities.form.rule_note'))
                            ->icon('heroicon-o-key')
                            ->columns(2)
                            ->schema([
                                ...self::name(),
                                ...DerivedTitle::make(
                                    fromState: self::titleFromState(...),
                                    fromRecord: self::titleOf(...),
                                ),
                            ]),

                        Section::make(__('filament-bouncer::abilities.form.reach'))
                            ->description(__('filament-bouncer::abilities.form.reach_note'))
                            ->icon('heroicon-o-viewfinder-circle')
                            ->columns(2)
                            ->schema(self::reach()),

                        Section::make(__('filament-bouncer::abilities.form.restrictions'))
                            ->description(__('filament-bouncer::abilities.form.restrictions_note'))
                            ->icon('heroicon-o-code-bracket')
                            ->collapsed()
                            ->schema([
                                KeyValue::make('options')
                                    ->hiddenLabel()
                                    ->keyLabel(__('filament-bouncer::abilities.form.options_key'))
                                    ->valueLabel(__('filament-bouncer::abilities.form.options_value'))
                                    ->helperText(__('filament-bouncer::abilities.form.options_note')),
                            ]),
                    ])
                    ->columnSpan(['lg' => 3]),

                Group::make()
                    ->schema([
                        // The narrow column is not hidden while composing, unlike the roles screen's:
                        // there it carries only metadata, which a new row has none of, while here it
                        // also carries the tenant, which is written. Stretching the wide group over
                        // all four columns left fields a thousand pixels wide for the word "view".
                        Section::make(__('filament-bouncer::abilities.form.tenant'))
                            ->icon('heroicon-o-building-office-2')
                            ->visible(fn (): bool => resolve(Tenancy::class)->inUse())
                            ->schema([
                                TextInput::make('scope')
                                    ->hiddenLabel()
                                    ->numeric()
                                    ->placeholder(__('filament-bouncer::abilities.form.scope_global'))
                                    ->helperText(__('filament-bouncer::abilities.form.scope_note')),
                            ]),

                        Section::make(__('filament-bouncer::abilities.form.metadata'))
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label(__('filament-bouncer::abilities.form.created'))
                                    ->isoDate('lll'),

                                TextEntry::make('updated_at')
                                    ->label(__('filament-bouncer::abilities.form.updated'))
                                    ->isoDate('lll'),
                            ])
                            ->hidden(fn (?Model $record): bool => ! $record instanceof Model),
                    ])
                    ->columnSpan(['lg' => 1]),
            ]);
    }

    /**
     * @return array<int, Hidden|Select|TextInput>
     */
    private static function name(): array
    {
        return [
            Select::make('name')
                // A key of its own, because both components answer to the same name: without it,
                // looking the component up hands back whichever Filament reaches first.
                ->key(self::NAME_PICKED)
                ->label(__('filament-bouncer::abilities.form.name'))
                ->options(self::actionOptions(...))
                ->searchable()
                ->required()
                ->live()
                ->afterStateUpdated(DerivedTitle::follow(self::titleFromState(...)))
                ->rule(self::noTwin())
                ->visible(fn (Get $get): bool => ! $get(self::NAME_CUSTOM))
                ->hintAction(self::handOver(true))
                ->helperText(__('filament-bouncer::abilities.form.name_from_list')),

            TextInput::make('name')
                ->key(self::NAME_TYPED)
                ->label(__('filament-bouncer::abilities.form.name'))
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(DerivedTitle::follow(self::titleFromState(...)))
                ->rule(self::noTwin())
                ->visible(fn (Get $get): bool => (bool) $get(self::NAME_CUSTOM))
                ->hintAction(self::handOver(false))
                ->helperText(__('filament-bouncer::abilities.form.name_by_hand')),

            // Worked out when a stored row is opened: an action that is not on the list was written
            // by somebody, so that row opens out of the list.
            Hidden::make(self::NAME_CUSTOM)
                ->dehydrated(false)
                ->afterStateHydrated(function (Set $set, ?Model $record): void {
                    $set(self::NAME_CUSTOM, $record instanceof Model
                        && ! array_key_exists(self::text($record->getAttribute('name')) ?? '', self::actionOptions()));
                }),
        ];
    }

    /**
     * @return array<int, Select|TextInput|Toggle>
     */
    private static function reach(): array
    {
        return [
            Select::make('entity_type')
                ->label(__('filament-bouncer::abilities.form.model'))
                ->options(self::modelOptions(...))
                ->searchable()
                ->placeholder(__('filament-bouncer::abilities.form.model_none'))
                ->live()
                ->afterStateUpdated(DerivedTitle::follow(self::titleFromState(...)))
                ->helperText(__('filament-bouncer::abilities.form.model_note')),

            TextInput::make('entity_id')
                ->label(__('filament-bouncer::abilities.form.record'))
                ->numeric()
                ->live(onBlur: true)
                ->afterStateUpdated(DerivedTitle::follow(self::titleFromState(...)))
                // A number does not say who the rule is fenced to, and fencing it to the wrong
                // record is indistinguishable from getting it right until somebody suffers it. The
                // panel's resource is asked, since it already knows how to title a row of that model.
                ->hint(self::recordName(...))
                ->hintColor(static fn (Get $get): string => self::recordExists($get) ? 'success' : 'danger')
                ->helperText(__('filament-bouncer::abilities.form.record_note')),

            Toggle::make('only_owned')
                ->label(__('filament-bouncer::abilities.form.owned'))
                ->required()
                ->columnSpanFull()
                ->live()
                ->afterStateUpdated(DerivedTitle::follow(self::titleFromState(...)))
                ->helperText(__('filament-bouncer::abilities.form.owned_note')),
        ];
    }

    /**
     * The button that moves the name from the list to the hand and back.
     */
    private static function handOver(bool $custom): Action
    {
        return Action::make($custom ? 'typeName' : 'pickName')
            ->label(__($custom
                ? 'filament-bouncer::abilities.form.take_name'
                : 'filament-bouncer::abilities.form.pick_name'))
            ->icon($custom ? 'heroicon-m-pencil' : 'heroicon-m-list-bullet')
            ->action(function (Set $set) use ($custom): void {
                $set(self::NAME_CUSTOM, $custom);
            });
    }

    /**
     * The same refusal for both halves of the name: written one way or the other, a twin is a twin.
     */
    private static function noTwin(): Closure
    {
        return static fn (Get $get, ?Model $record): Closure => static function (string $attribute, mixed $value, Closure $fail) use ($get, $record): void {
            $duplicates = resolve(Diagnosis::class)->wouldDuplicate([
                'name' => self::text($get('name')),
                'entity_type' => self::text($get('entity_type')),
                'entity_id' => self::text($get('entity_id')),
                'only_owned' => (bool) $get('only_owned'),
                'scope' => self::text($get('scope')),
            ], $record?->getKey());

            if ($duplicates) {
                $fail(__('filament-bouncer::abilities.form.twin'));
            }
        };
    }

    /**
     * The actions the panel declares today, as a list.
     *
     * From the catalogue and not from configuration, so an action added this morning is on the
     * list this afternoon. The reading leads and the identifier follows in brackets: the reading
     * is what the list is scanned by, the identifier is what gets stored.
     *
     * @return array<string, string>
     */
    private static function actionOptions(): array
    {
        $labels = resolve(Labels::class);
        $options = [];

        foreach (array_keys(resolve(CatalogRegistry::class)->current()->actions) as $action) {
            $options[$action] = $labels->action($action).' ('.$action.')';
        }

        $options[AbilityStore::WILDCARD] = __('filament-bouncer::abilities.form.any_action', [
            'wildcard' => AbilityStore::WILDCARD,
        ]);

        return $options;
    }

    /**
     * The models by their name, never by their class.
     *
     * The other way round from the actions: a class is written by nobody and searched by nobody,
     * and repeated in every option it turns the list into a wall of paths.
     *
     * @return array<string, string>
     */
    private static function modelOptions(): array
    {
        $options = [];

        foreach (resolve(CatalogRegistry::class)->current()->entities as $entity) {
            if ($entity->entityType !== null) {
                $options[$entity->entityType] = $entity->label;
            }
        }

        $options[AbilityStore::WILDCARD] = __('filament-bouncer::abilities.form.any_model');

        return $options;
    }

    /**
     * The title the fields on screen compose right now, asked of Bouncer's own generator on a row
     * that is never saved — which is what the model itself does on `creating`.
     */
    private static function titleFromState(Get $get): string
    {
        $ability = Models::ability();

        $ability->forceFill([
            'name' => self::text($get('name')),
            // Blank has to arrive as null and not as an empty string: the generator tells a rule
            // about no model by `is_null`, and with «» it would recognise none of its shapes.
            'entity_type' => self::text($get('entity_type')),
            'entity_id' => self::text($get('entity_id')),
            'only_owned' => (bool) $get('only_owned'),
        ]);

        return self::titleOf($ability);
    }

    private static function titleOf(Model $ability): string
    {
        return filled($ability->getAttribute('name'))
            ? AbilityTitle::from($ability)->toString()
            : '';
    }

    private static function recordName(Get $get): ?string
    {
        $type = self::text($get('entity_type'));
        $id = self::text($get('entity_id'));

        if ($type === null || $id === null || $type === AbilityStore::WILDCARD) {
            return null;
        }

        $record = self::fencedRecord($type, $id);

        if (! $record instanceof Model) {
            return __('filament-bouncer::abilities.form.record_missing');
        }

        $resource = Filament::getModelResource($record::class);
        $title = $resource !== null && is_subclass_of($resource, Resource::class)
            ? $resource::getRecordTitle($record)
            : null;

        return $title instanceof Htmlable ? $title->toHtml() : ($title ?? '#'.$id);
    }

    private static function recordExists(Get $get): bool
    {
        $type = self::text($get('entity_type'));
        $id = self::text($get('entity_id'));

        return $type !== null && $id !== null && self::fencedRecord($type, $id) instanceof Model;
    }

    private static function fencedRecord(string $type, string $id): ?Model
    {
        /** @var class-string<Model>|null $class */
        $class = Relation::getMorphedModel($type) ?? (is_a($type, Model::class, true) ? $type : null);

        return $class === null ? null : $class::query()->find($id);
    }

    private static function text(mixed $value): ?string
    {
        return is_scalar($value) && filled($value) ? (string) $value : null;
    }
}
