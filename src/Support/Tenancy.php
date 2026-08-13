<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Support;

use ElPandaPe\FilamentBouncer\Console\PanelResolver;
use Illuminate\Contracts\Config\Repository;
use Throwable;

/**
 * Whether this installation scopes Bouncer's rows to a tenant.
 *
 * It decides one thing: whether the tenant is part of the abilities screen's vocabulary — a column,
 * a field, an entry — or something that simply is not there. That is a question about intent, so it
 * is answered by configuration and not by data: a column that comes and goes with the first scoped
 * row cannot be learnt.
 *
 * Three ways to ask were considered and two were wrong:
 *
 * - **Whether a tenancy package is installed.** It answers a different question. An application
 *   using a database per tenant never needs Bouncer's scope, and one can set that scope by hand
 *   with no package at all. Wrong in both directions.
 * - **Whether a scope is set right now.** Bouncer's scope is a *global static*, not a per-request
 *   value: it is whatever the application last set, which is no basis for deciding that a column
 *   exists.
 *
 * What is left is configuration, with a default that asks the one thing that is both about this
 * panel and settled at boot: whether Filament's own tenancy is turned on for it.
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
