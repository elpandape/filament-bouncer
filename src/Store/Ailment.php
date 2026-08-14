<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Store;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

/**
 * What can be wrong with a row of the abilities table and that nothing else reports.
 *
 * A row the catalogue no longer declares is not here: it is healthy, `--prune` takes it, and
 * `Declaration` already reports it.
 */
enum Ailment: string implements HasColor, HasIcon, HasLabel
{
    /**
     * Another row says exactly the same thing. The table carries no unique index and the real
     * uniqueness is the whole quintuple, so whoever withdraws one walks away believing the rule
     * is gone.
     */
    case Twin = 'twin';

    /**
     * The model it speaks of can no longer be loaded. Bouncer never complains: it answers no,
     * for ever, to a rule pointing at a class that is not there.
     */
    case GhostModel = 'ghost-model';

    /**
     * It is fenced to a record that has been deleted. Should that id ever be reused, the rule
     * wakes up pointing at something else.
     */
    case GhostRecord = 'ghost-record';

    /**
     * It carries a tenant, so `TenantScope` hides it from every query made without one — the
     * roles grid, the Gate, this screen's own siblings. Not checked where tenancy is in use,
     * since there a row belonging to another tenant is hidden on purpose.
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
     * Whether the row is already answering wrongly, or merely out of sight. It decides the
     * colour, and which icon sums a row up in the listing.
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
     * The question the screen asks rather than the name of the ailment: "ghost model" has to be
     * learnt, "does the model load?" is understood the first time.
     */
    public function question(): string
    {
        return __('filament-bouncer::abilities.health.'.$this->value.'.question');
    }

    public function note(): string
    {
        return __('filament-bouncer::abilities.health.'.$this->value.'.note');
    }
}
