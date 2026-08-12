<?php

declare(strict_types=1);

namespace ElPandaPe\FilamentBouncer\Filament\Widgets;

use ElPandaPe\FilamentBouncer\Catalog\CatalogRegistry;
use ElPandaPe\FilamentBouncer\Filament\Concerns\AuthorizesWidget;
use Filament\Widgets\Widget;
use Illuminate\Contracts\View\View as ViewContract;
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
 *
 * A view of the package's own rather than `StatsOverviewWidget`, because the approved
 * design draws each figure as an icon in a tinted box beside a bare number, and the stock
 * widget cannot be told to. The figures carry no explanatory sentences: the design says
 * the label is enough, and anything longer belongs in the documentation.
 */
final class RoleStats extends Widget
{
    use AuthorizesWidget;

    protected int|string|array $columnSpan = 'full';

    public function render(): ViewContract
    {
        // The analyser works out `view-string` by looking for the file among the paths
        // the application renders from, and a package's namespaced view is never among
        // them — which is also why the parent's property cannot take this name as its
        // default.
        /** @var view-string $view */
        $view = 'filament-bouncer::widgets.role-stats';

        return view($view, $this->getViewData());
    }

    /**
     * @return array{roles: int, declared: int, forbidden: int, unassigned: int}
     */
    protected function getViewData(): array
    {
        return [
            'roles' => $this->roles(),
            'declared' => $this->declared(),
            'forbidden' => $this->forbidden(),
            'unassigned' => $this->unassigned(),
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
