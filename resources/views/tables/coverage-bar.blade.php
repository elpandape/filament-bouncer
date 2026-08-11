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
    {{-- The bar is decoration with a caption beside it, so it is read once and not
         twice: the numbers below say the same thing in words. --}}
    <span class="fb-cov-bar" aria-hidden="true">
        <span class="fb-cov-part fb-cov-granted" style="inline-size: {{ $reach['shares']['granted'] }}%"></span>
        <span class="fb-cov-part fb-cov-forbidden" style="inline-size: {{ $reach['shares']['forbidden'] }}%"></span>
        <span class="fb-cov-part fb-cov-neutral" style="inline-size: {{ $reach['shares']['neutral'] }}%"></span>
    </span>

    <span class="fb-cov-reading">{{ $reach['reading'] }}</span>
</div>
