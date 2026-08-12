<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Forms;

use Closure;
use Filament\Forms\Components\Field;

/**
 * The catalogue's actions as cards to choose from, not a pull-down to open.
 *
 * The approved design lays the action step out as a grid of tarjetas — the readable
 * label large, the method's own name in monospace under it — because the choice is the
 * whole step and a select folds it away. The field writes the same state path the
 * select it replaces wrote, so everything that validates or reads the choice is
 * untouched: the unknown-pair refusal, the wizard, the review sentence.
 */
final class ActionCards extends Field
{
    protected string $view = 'filament-bouncer::forms.action-cards';

    /**
     * @var array<string, string>|Closure
     */
    private array|Closure $options = [];

    /**
     * @param  array<string, string>|Closure  $options
     */
    public function options(array|Closure $options): static
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @return array<string, string>
     */
    public function getOptions(): array
    {
        $options = $this->evaluate($this->options);

        /** @var array<string, string> $options */
        $options = is_array($options) ? $options : [];

        return $options;
    }

    public function getEmptyLabel(): string
    {
        return __('filament-bouncer::abilities.wizard.actions_empty');
    }
}
