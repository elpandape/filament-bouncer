<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Widgets;

use ElPandaPe\FilamentBouncer\Catalog\CatalogRegistry;
use ElPandaPe\FilamentBouncer\Filament\Concerns\AuthorizesWidget;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Silber\Bouncer\Database\Models;

/**
 * What the roles screen is worth knowing before any row of it is read.
 *
 * Two of the four are not counts of anything on the table below, and that is why they are
 * here: a denial in force explains a role that looks generous and is not, and an account
 * holding no role at all reaches the panel and finds it empty — a support ticket waiting
 * to be filed, visible from the one screen able to answer it.
 */
final class RoleStats extends StatsOverviewWidget
{
    use AuthorizesWidget;

    /**
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $forbidden = $this->forbidden();

        return [
            Stat::make(__('filament-bouncer::roles.stats.roles'), $this->roles())
                ->description(__('filament-bouncer::roles.stats.roles_note')),
            Stat::make(__('filament-bouncer::roles.stats.abilities'), $this->declared())
                ->description(__('filament-bouncer::roles.stats.abilities_note')),
            Stat::make(__('filament-bouncer::roles.stats.forbidden'), $forbidden)
                ->description(__('filament-bouncer::roles.stats.forbidden_note'))
                ->color($forbidden > 0 ? 'danger' : 'gray'),
            Stat::make(__('filament-bouncer::roles.stats.unassigned'), $this->unassigned())
                ->description(__('filament-bouncer::roles.stats.unassigned_note')),
        ];
    }

    private function roles(): int
    {
        return Models::role()->newQuery()->count();
    }

    private function declared(): int
    {
        return count(app(CatalogRegistry::class)->current()->abilities());
    }

    /**
     * Rules that say no, whoever holds them.
     *
     * A denial beats every grant reaching the same ability, so one of these explains
     * more about what the panel does than a dozen grants do.
     */
    private function forbidden(): int
    {
        return DB::table(Models::table('permissions'))->where('forbidden', true)->count();
    }

    /**
     * Accounts holding no role at all.
     *
     * The pivot is read directly rather than through a relation, because the account
     * model is whatever the application configured and nothing promises the analyser
     * that it carries Bouncer's traits.
     */
    private function unassigned(): int
    {
        $accounts = Models::user();
        $key = $accounts->getKeyName();
        $type = $accounts->getMorphClass();

        return DB::table($accounts->getTable(), 'accounts')
            ->whereNotExists(static function (Builder $query) use ($key, $type): void {
                $query->from(Models::table('assigned_roles'))
                    ->whereColumn('entity_id', 'accounts.'.$key)
                    ->where('entity_type', $type);
            })
            ->count();
    }
}
