@php
    /*
     * The same reading as the table's column, drawn at the size a record page has room
     * for. A role reaching everything through the wildcard holds no rule of its own for
     * any cell, so the bar is filled from what it reaches rather than from what it holds
     * — otherwise the widest role in the panel would draw the emptiest bar.
     */
    $total = max($coverage->total, 1);
    $granted = $coverage->reachesAll ? 100.0 : round($coverage->granted / $total * 100, 2);
    $forbidden = $coverage->reachesAll ? 0.0 : round($coverage->forbidden / $total * 100, 2);
@endphp

<div
    class="fb-cov fb-cov-lg"
    data-granted="{{ $coverage->granted }}"
    data-forbidden="{{ $coverage->forbidden }}"
    data-total="{{ $coverage->total }}"
    data-reaches-all="{{ $coverage->reachesAll ? 'true' : 'false' }}"
>
    <span class="fb-cov-bar" aria-hidden="true">
        <span class="fb-cov-part fb-cov-granted" style="inline-size: {{ $granted }}%"></span>
        <span class="fb-cov-part fb-cov-forbidden" style="inline-size: {{ $forbidden }}%"></span>
    </span>

    <span class="fb-cov-reading">
        @if ($coverage->reachesAll)
            {{ __('filament-bouncer::roles.table.reaches_all') }}
        @else
            {{ __('filament-bouncer::roles.table.reading', ['granted' => $coverage->granted, 'total' => $coverage->total]) }}
        @endif
    </span>
</div>
