<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Support;

use ElPandaPe\FilamentBouncer\Console\PanelResolver;
use Illuminate\Contracts\Config\Repository;
use Throwable;

/**
 * Whether this installation scopes Bouncer's rows to a tenant.
 *
 * It decides whether the tenant is part of the screens' vocabulary — a column, a field, an entry —
 * or something that is not there at all. A question about intent, so it is answered by
 * configuration: a column that comes and goes with the first scoped row cannot be learnt.
 *
 * Neither of the two things it could have read instead would do. Whether a tenancy package is
 * installed answers a different question, wrong in both directions; and Bouncer's scope is a global
 * static, whatever the application last set. The default asks the one thing both about this panel
 * and settled at boot: whether Filament's own tenancy is turned on for it.
 */
final readonly class Tenancy
{
    public function __construct(
        private Repository $config,
        private PanelResolver $panels,
    ) {}

    public function inUse(): bool
    {
        $configured = $this->config->get('filament-bouncer.tenancy');

        if (is_bool($configured)) {
            return $configured;
        }

        return $this->panelHasTenancy();
    }

    /**
     * The panel is asked defensively because this is read while screens are built, and a
     * misconfigured panel id would otherwise take the screen down over a question whose honest
     * answer, when it cannot be asked, is "no tenancy".
     */
    private function panelHasTenancy(): bool
    {
        try {
            return $this->panels->resolve()->hasTenancy();
        } catch (Throwable) {
            return false;
        }
    }
}
