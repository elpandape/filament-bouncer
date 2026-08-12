<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Resources\Roles\Schemas;

use Closure;
use ElPandaPe\FilamentBouncer\Catalog\Ability;
use ElPandaPe\FilamentBouncer\Catalog\CatalogRegistry;
use ElPandaPe\FilamentBouncer\Filament\Forms\AbilityGrid;
use ElPandaPe\FilamentBouncer\Filament\Resources\Roles\RoleResource;
use ElPandaPe\FilamentBouncer\Store\PrivilegedRole;
use ElPandaPe\FilamentBouncer\Store\Restriction;
use ElPandaPe\FilamentBouncer\Store\RoleAbilities;
use ElPandaPe\FilamentBouncer\Store\Stance;
use ElPandaPe\FilamentBouncer\Support\Labels;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

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

    public static function configure(Schema $schema, bool $submitsFromSummary = false): Schema
    {
        return $schema->components([
            Section::make(__('filament-bouncer::roles.form.role'))
                ->schema(self::identity())
                ->columns(2)
                ->columnSpanFull(),
            // Headless on purpose. The catalogue names itself on every line of it, so a
            // heading and a sentence above would push the first subject a screenful
            // down to say what the rows underneath already say.
            Section::make()
                ->schema([self::grid($submitsFromSummary)])
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
            TextInput::make('title')
                ->label(__('filament-bouncer::roles.form.title'))
                ->placeholder(__('filament-bouncer::roles.form.title_placeholder'))
                ->helperText(__('filament-bouncer::roles.form.title_help'))
                ->maxLength(150),
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
    public static function grid(bool $submitsFromSummary = false, bool $requiresAStance = false): AbilityGrid
    {
        $grid = AbilityGrid::make(self::ABILITIES)
            ->hiddenLabel()
            ->catalog(app(CatalogRegistry::class)->current())
            ->notes(self::notes(...));

        if ($submitsFromSummary) {
            $grid->submitsFromSummary(RoleResource::getUrl('index'));
        }

        if ($requiresAStance) {
            $grid->requiresAStance();
        }

        return $grid;
    }

    /**
     * What is about to be written, read back the way it was chosen.
     *
     * One line per subject with its granted and its forbidden beside it, and the counting
     * left for the foot. A sentence with three numbers in it was true and unreadable:
     * nobody hands out abilities by counting them, and this is the last screen before
     * they are handed out.
     *
     * @return array<string, mixed>
     */
    public static function reviewData(Get $get): array
    {
        $labels = app(Labels::class);
        $catalog = app(CatalogRegistry::class)->current();

        /** @var array<string, array<string, string>> $state */
        $state = is_array($get(self::ABILITIES)) ? $get(self::ABILITIES) : [];

        $subjects = [];
        $granted = 0;
        $forbidden = 0;
        $total = 0;

        foreach ($catalog->subjects as $key => $subject) {
            $chips = [];

            foreach (array_keys($subject->cells()) as $action) {
                $total++;
                $stance = Stance::tryFrom($state[$key][$action] ?? '') ?? Stance::Neutral;

                if ($stance === Stance::Neutral) {
                    continue;
                }

                $stance === Stance::Granted ? $granted++ : $forbidden++;

                $chips[] = [
                    'stance' => $stance->value,
                    'label' => $action === Ability::MANAGE_ACTION
                        ? __('filament-bouncer::roles.form.manage')
                        : $labels->action($action),
                ];
            }

            $subjects[] = ['label' => $subject->label, 'chips' => $chips];
        }

        return [
            'subjects' => $subjects,
            'silent' => __('filament-bouncer::roles.review.silent'),
            // The three counts are read as one line, and each of them has to agree with
            // its own number: "1 prohibidas" is the sort of thing a screen says when the
            // plural was decided once for all three.
            'total' => implode(' · ', [
                trans_choice('filament-bouncer::roles.summary.granted', $granted, ['count' => $granted]),
                trans_choice('filament-bouncer::roles.summary.forbidden', $forbidden, ['count' => $forbidden]),
                trans_choice('filament-bouncer::roles.summary.neutral', $total - $granted - $forbidden, ['count' => $total - $granted - $forbidden]),
            ]),
        ];
    }

    /**
     * What a cell says once its stance has said all it can.
     *
     * Three things a stance cannot express on its own, and each of them is a way for a
     * grid read at face value to be wrong about the role in front of it:
     *
     * - the role answers yes to an ability it holds no rule for, because something
     *   broader reaches it — the wildcard being the obvious one;
     * - the role was granted the ability right here and still answers no, because a
     *   denial beats every grant reaching the same ability;
     * - the role holds rules the grid neither writes nor removes: the ones about a
     *   single record and the ones covering only what their holder owns. Saying
     *   nothing about those would leave the cell reading "not granted" about a role
     *   that can plainly delete its own posts.
     *
     * @return array<string, array<string, string>>
     */
    private static function notes(Model $role): array
    {
        $abilities = app(RoleAbilities::class);
        $state = $abilities->toFormState($role);
        $restrictions = $abilities->restrictions($role);
        $notes = [];

        foreach (app(CatalogRegistry::class)->current()->subjects as $key => $subject) {
            foreach ($subject->cells() as $action => $ability) {
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
