<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament;

use ElPandaPe\FilamentBouncer\Exceptions\UnguardedComponent;
use Filament\Panel;
use Filament\Resources\Resource as FilamentResource;
use Filament\Widgets\WidgetConfiguration;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\Gate;
use ReflectionMethod;

/**
 * Refuses to let a panel finish booting with a page or a widget that decides nothing.
 *
 * It throws in production too, deliberately: a component nobody authorises is open to everybody
 * and looks exactly like one meant to be open. A deployment that falls over loudly gets reverted
 * within the hour; this kind of hole is found by whoever goes looking for it.
 *
 * The way to say "this one really is for everybody" is the ignore list.
 */
final readonly class PanelGuard
{
    /**
     * Filament's own answers, which say yes to everybody. A component whose answer is
     * still declared inside this namespace has not decided anything.
     */
    private const string VENDOR = 'Filament\\';

    public function __construct(private Repository $config) {}

    public function check(Panel $panel): void
    {
        $ignored = $this->ignored();

        foreach ($panel->getPages() as $page) {
            if (in_array($page, $ignored, true)) {
                continue;
            }

            if (! $this->decides($page, 'canAccess')) {
                throw UnguardedComponent::page($page);
            }
        }

        foreach ($panel->getWidgets() as $widget) {
            $class = $widget instanceof WidgetConfiguration ? $widget->widget : $widget;

            if (in_array($class, $ignored, true)) {
                continue;
            }

            if (! $this->decides($class, 'canView')) {
                throw UnguardedComponent::widget($class);
            }
        }
    }

    /**
     * The resources whose model has no policy at all.
     *
     * Filament falls open here, which makes it the quietest hole of the three. Reported by the
     * reconcile command rather than thrown at boot: writing the missing policy is real work, and
     * a deploy that stops dead in the middle of it helps no one.
     *
     * @return array<int, class-string<FilamentResource>>
     */
    public function openResources(Panel $panel): array
    {
        $ignored = $this->ignored();
        $open = [];

        foreach ($panel->getResources() as $resource) {
            if (in_array($resource, $ignored, true)) {
                continue;
            }

            /** @var class-string<FilamentResource> $resource */
            if (Gate::getPolicyFor($resource::getModel()) === null) {
                $open[] = $resource;
            }
        }

        return $open;
    }

    /**
     * PHP flattens a trait's methods into the class that uses one, so a component
     * reaching for this package's trait reports itself as the author and passes, exactly
     * as one that wrote the method by hand does.
     *
     * @param  class-string  $component
     */
    private function decides(string $component, string $method): bool
    {
        return ! str_starts_with(
            new ReflectionMethod($component, $method)->getDeclaringClass()->getName(),
            self::VENDOR,
        );
    }

    /**
     * @return array<int, string>
     */
    private function ignored(): array
    {
        /** @var array<int, string> $ignored */
        $ignored = $this->config->get('filament-bouncer.ignore', []);

        return $ignored;
    }
}
