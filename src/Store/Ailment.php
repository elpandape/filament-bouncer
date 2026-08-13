<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Store;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

/**
 * What can be wrong with a row of the abilities table, and that no other screen sees.
 *
 * The four have the same shape: they are true in the store, nothing reports them, and the abilities
 * screen is the only place they can be mended — because it is the only one that reads without the
 * tenant scope and the only one that lists every row. That is the whole reason the health column
 * exists: the roles screen is for handing abilities out, this one is for keeping the store sound.
 *
 * None of them is the reconciliation's business. A row the catalogue no longer declares is *healthy*
 * — `--prune` takes it and says how many it took — and `Declaration` already reports that. What is
 * here is what nothing else reports.
 */
enum Ailment: string implements HasColor, HasIcon, HasLabel
{
    /**
     * Another row says exactly the same thing.
     *
     * The table carries no unique index and the real uniqueness is the whole quintuple, so nothing
     * in the database prevents it. The two are granted and withdrawn separately, and a listing
     * shows both without saying they are one rule: whoever withdraws one walks away believing the
     * rule is gone.
     */
    case Twin = 'twin';

    /**
     * The model it speaks of can no longer be loaded.
     *
     * It survives any model rename. Bouncer never complains: it answers no, for ever, to a rule
     * pointing at a class that is not there.
     */
    case GhostModel = 'ghost-model';

    /**
     * It is fenced to a record that has been deleted.
     *
     * The rule stands, pointing at nothing. Should that id ever be reused, the rule wakes up
     * pointing at something else.
     */
    case GhostRecord = 'ghost-record';

    /**
     * It carries a tenant, so the rest of the system cannot see it.
     *
     * `TenantScope` adds a `where scope is null` to every query while no tenant is set. On an
     * installation that does not use Bouncer's multi-tenancy that row is unreachable by everything
     * — the roles grid, the Gate, this screen's own siblings — and only the abilities screen shows
     * it, because it reads without the global scope. Where tenancy *is* used the check is not run
     * at all: a row belonging to another tenant is hidden on purpose.
     */
    case Invisible = 'invisible';

    /**
     * @return array<int, self>
     */
    public static function all(): array
    {
        return self::cases();
    }

    public function getLabel(): string
    {
        return __('filament-bouncer::abilities.health.'.$this->value.'.label');
    }

    /**
     * Whether the row is already answering wrongly, or merely out of sight.
     *
     * A twin and a ghost lie today; an invisible one simply does not answer. It is the distinction
     * that decides the colour, and the one that decides which icon sums a row up in the listing.
     */
    public function isSevere(): bool
    {
        return $this !== self::Invisible;
    }

    public function getColor(): string
    {
        return $this->isSevere() ? 'danger' : 'warning';
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Twin => 'heroicon-m-document-duplicate',
            self::GhostModel => 'heroicon-m-question-mark-circle',
            self::GhostRecord => 'heroicon-m-document-minus',
            self::Invisible => 'heroicon-m-eye-slash',
        };
    }

    /**
     * The question the screen asks, rather than the name of the ailment.
     *
     * "Ghost model" has to be learnt; "does the model load?" is understood the first time. The name
     * goes on carrying the listing's badge, where a question would not fit.
     */
    public function question(): string
    {
        return __('filament-bouncer::abilities.health.'.$this->value.'.question');
    }

    /**
     * What it means and what has to be done about it, which a label alone never says.
     */
    public function note(): string
    {
        return __('filament-bouncer::abilities.health.'.$this->value.'.note');
    }
}
