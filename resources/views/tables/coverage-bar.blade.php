@php
    $reach = $getState();
@endphp

<div
    class="fb-cov"
    data-granted="{{ $reach['granted'] }}"
    data-forbidden="{{ $reach['forbidden'] }}"
    data-total="{{ $reach['total'] }}"
    data-reaches-all="{{ $reach['reaches_all'] ? 'true' : 'false' }}"
>
    <span class="fb-cov-bar" aria-hidden="true">
        <span class="fb-cov-part fb-cov-granted" style="inline-size: {{ $reach['shares']['granted'] }}%"></span>
        <span class="fb-cov-part fb-cov-forbidden" style="inline-size: {{ $reach['shares']['forbidden'] }}%"></span>
    </span>

    <span class="fb-cov-reading">
        <b class="fb-cov-num fb-cov-num-ok">{{ $reach['granted'] }}</b>
        ·
        <b class="fb-cov-num fb-cov-num-dng">{{ $reach['forbidden'] }}</b>
        ·
        <b class="fb-cov-num">{{ $reach['neutral'] }}</b>
        @if ($reach['reaches_all'])
            <span class="fb-cov-aside">— {{ __('filament-bouncer::roles.table.reaches_all') }}</span>
        @endif
    </span>
</div>
