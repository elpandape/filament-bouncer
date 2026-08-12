{{--
    The listing's figures, worn as chips above the table: how many rules there are, how
    many were narrowed by hand, how many are the wildcard, and — in red, because a
    denial in force outweighs everything else on the screen — how many rules somebody
    is forbidden.
--}}
<div class="fb fb-chips">
    <span class="fb-badge-gray">
        {{ __('filament-bouncer::abilities.chips.all') }} · <b>{{ $all }}</b>
    </span>
    <span class="fb-badge-info">
        {{ __('filament-bouncer::abilities.chips.narrowed') }} · <b>{{ $narrowed }}</b>
    </span>
    <span class="fb-badge-pri">
        {{ __('filament-bouncer::abilities.chips.wildcard') }} · <b>{{ $wildcard }}</b>
    </span>
    <span class="fb-badge-dng">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10" /><line x1="4.9" y1="4.9" x2="19.1" y2="19.1" /></svg>
        {{ __('filament-bouncer::abilities.chips.forbidden') }} · <b>{{ $forbidden }}</b>
    </span>
</div>
