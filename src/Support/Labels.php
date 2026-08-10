<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Support;

use ElPandaPe\FilamentBouncer\Catalog\AbilityScope;
use ElPandaPe\FilamentBouncer\Store\Stance;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Support\Str;

/**
 * The words the grid is read in.
 *
 * Three sources, asked in this order, and the order is the whole design. What the
 * consuming application put in its configuration wins, because it knows what its own
 * people call these things and it should not have to publish a language file to say so.
 * Failing that, the package's own translations. Failing those — an action nobody has a
 * word for, which is any action an application invented on its own policy — the name of
 * the method, made readable.
 *
 * The last step matters more than it looks: it means a policy method added this morning
 * shows up on the screen this afternoon reading sensibly, with nothing to translate first.
 */
final readonly class Labels
{
    public function __construct(
        private Repository $config,
        private Translator $translator,
    ) {}

    public function action(string $action): string
    {
        return $this->resolve('actions', $action, Str::headline($action));
    }

    public function scope(AbilityScope $scope): string
    {
        return $this->resolve('scopes', $scope->value, Str::headline($scope->value));
    }

    public function stance(Stance $stance): string
    {
        return $this->resolve('stances', $stance->value, Str::headline($stance->value));
    }

    /**
     * The three stances, ready to hand to a set of buttons.
     *
     * @return array<string, string>
     */
    public function stances(): array
    {
        $stances = [];

        foreach (Stance::cases() as $stance) {
            $stances[$stance->value] = $this->stance($stance);
        }

        return $stances;
    }

    private function resolve(string $group, string $key, string $fallback): string
    {
        /** @var string|null $configured */
        $configured = $this->config->get('filament-bouncer.labels.'.$group.'.'.$key);

        if (filled($configured)) {
            return $configured;
        }

        $line = 'filament-bouncer::'.$group.'.'.$key;

        $translated = $this->translator->get($line);

        // A missing line comes back as the key that was asked for, which is how the
        // fallback gets its turn.
        return is_string($translated) && $translated !== $line ? $translated : $fallback;
    }
}
