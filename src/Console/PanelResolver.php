<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Console;

use Filament\Panel;
use Filament\PanelRegistry;
use Illuminate\Contracts\Config\Repository;
use InvalidArgumentException;

/**
 * Finds the panel a command is meant to work on.
 *
 * A command runs with no panel of its own, so the one to walk is either named on the
 * command line, named in configuration, or the panel the application marked default.
 *
 * The registry is asked directly rather than through the `Filament` facade, whose
 * annotation for this call promises a panel and whose implementation returns null.
 */
final readonly class PanelResolver
{
    public function __construct(
        private Repository $config,
        private PanelRegistry $panels,
    ) {}

    public function resolve(?string $id = null): Panel
    {
        $id ??= $this->configured();

        if (blank($id)) {
            return $this->panels->getDefault();
        }

        // Left alone, an unknown id would quietly reconcile the default panel instead
        // and report success over a catalogue nobody asked for.
        return $this->panels->get($id) ?? throw new InvalidArgumentException("There is no Filament panel with the id [{$id}].");
    }

    private function configured(): ?string
    {
        /** @var string|null $id */
        $id = $this->config->get('filament-bouncer.panel');

        return $id;
    }
}
