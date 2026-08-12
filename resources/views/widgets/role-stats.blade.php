<x-filament-widgets::widget>
    <div class="fb-stats">
        <div class="fb-stat">
            <span class="fb-stat-ic fb-stat-ic-pri">
                <x-filament::icon icon="heroicon-m-shield-check" />
            </span>
            <div>
                <p class="fb-stat-n">{{ $roles }}</p>
                <p class="fb-stat-l">{{ __('filament-bouncer::roles.stats.roles') }}</p>
            </div>
        </div>

        <div class="fb-stat">
            <span class="fb-stat-ic">
                <x-filament::icon icon="heroicon-m-key" />
            </span>
            <div>
                <p class="fb-stat-n">{{ $declared }}</p>
                <p class="fb-stat-l">{{ __('filament-bouncer::roles.stats.abilities') }}</p>
            </div>
        </div>

        <div class="fb-stat">
            <span class="fb-stat-ic fb-stat-ic-dng">
                <x-filament::icon icon="heroicon-m-no-symbol" />
            </span>
            <div>
                <p class="fb-stat-n{{ $forbidden > 0 ? ' fb-stat-n-dng' : '' }}">{{ $forbidden }}</p>
                <p class="fb-stat-l">{{ __('filament-bouncer::roles.stats.forbidden') }}</p>
            </div>
        </div>

        <div class="fb-stat">
            <span class="fb-stat-ic fb-stat-ic-warn">
                <x-filament::icon icon="heroicon-m-user-minus" />
            </span>
            <div>
                <p class="fb-stat-n">{{ $unassigned }}</p>
                <p class="fb-stat-l">{{ __('filament-bouncer::roles.stats.unassigned') }}</p>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
