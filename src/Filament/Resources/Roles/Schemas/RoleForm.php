<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Schemas;

use Closure;
use ElPandaPe\FilamentBouncer\Catalog\CatalogRegistry;
use ElPandaPe\FilamentBouncer\Filament\Forms\AbilityGrid;
use ElPandaPe\FilamentBouncer\Filament\Forms\DerivedTitle;
use ElPandaPe\FilamentBouncer\Store\PrivilegedRole;
use ElPandaPe\FilamentBouncer\Store\Restriction;
use ElPandaPe\FilamentBouncer\Store\RoleAbilities;
use ElPandaPe\FilamentBouncer\Store\Stance;
use ElPandaPe\FilamentBouncer\Support\Tenancy;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Silber\Bouncer\Database\Models;
use Silber\Bouncer\Database\Titles\RoleTitle;

/**
 * What a role is called, and everything it is allowed to say.
 *
 * The grid is a single field rather than a column of the role, and that is the one thing
 * to know before touching either end of this screen: nothing named here exists on the
 * roles table, so the pages have to take the key out of the data before the record is
 * written and hand it to the store afterwards. The concerns beside this file do that.
 */
final class RoleForm
{
    /**
     * Where the whole grid lives in the form state.
     *
     * Named here and read from here by the two concerns, because a string repeated in
     * three files is a rename waiting to lose a screen's worth of grants in silence.
     */
    public const string ABILITIES = 'abilities';

    public static function configure(Schema $schema, bool $requiresAStance = false): Schema
    {
        return $schema
            ->columns(4)
            ->components([
                Group::make()
                    ->schema([
                        Section::make(__('filament-bouncer::roles.record.identity'))
                            ->description(__('filament-bouncer::roles.record.identity_note'))
                            ->icon('heroicon-o-shield-check')
                            ->schema(self::identity(protectedNotice: $requiresAStance)),
                    ])
                    // Composing a role has nothing on the right yet — there is no record to
                    // say when it was created — so the identity takes the width instead of
                    // leaving a quarter of the screen empty.
                    ->columnSpan(fn (?Model $record): array => ['lg' => $record instanceof Model ? 3 : 4]),

                Group::make()
                    ->schema([
                        // The tenant is read and not set: it is the store's own column, and
                        // writing it by hand makes rows the rest of the system does not
                        // expect — and lets two roles share a name.
                        Section::make(__('filament-bouncer::roles.record.scope'))
                            ->visible(fn (): bool => resolve(Tenancy::class)->inUse())
                            ->schema([
                                TextEntry::make('scope')
                                    ->hiddenLabel()
                                    ->numeric()
                                    ->placeholder(__('filament-bouncer::roles.record.scope_global')),
                            ]),

                        Section::make(__('filament-bouncer::roles.record.metadata'))
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label(__('filament-bouncer::roles.record.created'))
                                    ->isoDate('lll'),

                                TextEntry::make('updated_at')
                                    ->label(__('filament-bouncer::roles.record.updated'))
                                    ->isoDate('lll'),
                            ]),
                    ])
                    ->hidden(fn (?Model $record): bool => ! $record instanceof Model),

                Section::make(__('filament-bouncer::roles.record.abilities_heading'))
                    ->description(__('filament-bouncer::roles.form.abilities_note'))
                    ->icon('heroicon-o-key')
                    ->schema([self::grid($requiresAStance)])
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @return array<int, Component|TextInput>
     */
    public static function identity(bool $protectedNotice = false): array
    {
        $fields = [
            TextInput::make('name')
                ->label(__('filament-bouncer::roles.form.name'))
                ->live(onBlur: true)
                ->afterStateUpdated(DerivedTitle::follow(self::titleFromState(...)))
                ->placeholder(__('filament-bouncer::roles.form.name_placeholder'))
                ->helperText(new HtmlString(__('filament-bouncer::roles.form.name_help')))
                ->required()
                ->maxLength(150)
                ->unique(ignoreRecord: true)
                // The privileged role is the way back in, and this screen refuses to edit
                // it. Left alone, that refusal is also a way to take the name hostage:
                // creating or renaming a role to it grants nobody anything and leaves a
                // role nobody can ever edit or delete from here. The reconciliation is
                // what creates that role, so the screen has no business writing its name.
                ->rule(static fn (): Closure => static function (string $attribute, mixed $value, Closure $fail): void {
                    if (app(PrivilegedRole::class)->isNamed(is_string($value) ? $value : '')) {
                        $fail(__('filament-bouncer::roles.form.reserved'));
                    }
                }),
            ...DerivedTitle::make(
                fromState: self::titleFromState(...),
                fromRecord: self::titleOf(...),
            ),
        ];

        $reserved = app(PrivilegedRole::class)->name();

        // The warning is only worth its room where the name is being chosen, and only
        // when there is a reserved name to warn about.
        if ($protectedNotice && $reserved !== null) {
            // The analyser works out `view-string` by looking for the file among the
            // paths the application renders from, and a package's namespaced view is
            // never among them.
            /** @var view-string $notice */
            $notice = 'filament-bouncer::forms.protected-role-notice';

            $fields[] = View::make($notice)->viewData(['name' => $reserved]);
        }

        return $fields;
    }

    /**
     * The catalogue, offered whole.
     *
     * Everything the panel declares is on it, whether or not the person filling it in
     * holds any of it. Narrowing the grid to what the editor already has would be a
     * second answer to a question the policy has already answered: whoever may work
     * this screen hands out all of it, including to themselves.
     */
    public static function grid(bool $requiresAStance = false): AbilityGrid
    {
        $grid = AbilityGrid::make(self::ABILITIES)
            ->hiddenLabel()
            ->catalog(app(CatalogRegistry::class)->current())
            ->notes(self::notes(...));

        if ($requiresAStance) {
            $grid->requiresAStance();
        }

        return $grid;
    }

    /**
     * The title the name on screen composes right now, asked of Bouncer's own generator on a row
     * that is never saved — which is what the model itself does on `creating`.
     */
    private static function titleFromState(Get $get): string
    {
        $role = Models::role();

        $role->forceFill(['name' => is_scalar($get('name')) ? (string) $get('name') : '']);

        return self::titleOf($role);
    }

    private static function titleOf(Model $role): string
    {
        return filled($role->getAttribute('name'))
            ? RoleTitle::from($role)->toString()
            : '';
    }

    /**
     * What a cell says once its stance has said all it can.
     *
     * Three things a stance cannot express, each of them a way for the grid to be wrong at
     * face value: the role answers yes through something broader, it answers no because a
     * denial beats every grant, or it holds a narrowed rule the grid neither writes nor
     * removes — which would leave the cell reading "not granted" about a role that can
     * plainly delete its own posts.
     *
     * @return array<string, array<string, string>>
     */
    private static function notes(Model $role): array
    {
        $abilities = app(RoleAbilities::class);
        $state = $abilities->toFormState($role);
        $restrictions = $abilities->restrictions($role);
        $notes = [];

        foreach (app(CatalogRegistry::class)->current()->entities as $key => $entity) {
            foreach ($entity->cells() as $action => $ability) {
                $stance = $state[$key][$action] ?? Stance::Neutral->value;
                $holds = $abilities->holds($role, $ability);

                $lines = [];

                if ($stance === Stance::Neutral->value && $holds) {
                    $lines[] = __('filament-bouncer::roles.form.inherited');
                }

                if ($stance === Stance::Granted->value && ! $holds) {
                    $lines[] = __('filament-bouncer::roles.form.overruled');
                }

                $restriction = $restrictions[$ability->identity()] ?? null;

                if ($restriction instanceof Restriction) {
                    $lines = [...$lines, ...self::narrowed($restriction)];
                }

                if ($lines !== []) {
                    $notes[$key][$action] = implode(' ', $lines);
                }
            }
        }

        return $notes;
    }

    /**
     * @return array<int, string>
     */
    private static function narrowed(Restriction $restriction): array
    {
        $lines = [];

        if ($restriction->owned) {
            $lines[] = __('filament-bouncer::roles.form.restricted_owned');
        }

        if ($restriction->records > 0) {
            $lines[] = trans_choice(
                'filament-bouncer::roles.form.restricted_records',
                $restriction->records,
                ['count' => $restriction->records],
            );
        }

        return $lines;
    }
}
