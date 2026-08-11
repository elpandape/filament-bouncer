{{--
    One action, three words.

    The stance used to be a single button walking the three, because three never fitted a
    column. Laid out as a row there is width for all of them, and choosing directly
    removes the trap the cycle carried: reaching "forbidden" meant passing through
    "granted", a rule that existed on screen for a moment and never on purpose.

    The neutral segment of a row reached by a broader rule draws the tick rather than the
    dash, hollow. The dash is true of the row and false of the reader's question: a role
    holding the wildcard holds no rule of its own for any row here, and a screen full of
    dashes would say it can do nothing at all.
--}}
<div class="fb-seg" role="group" @if (filled($note)) title="{{ $note }}" @endif>
    @foreach (['granted', 'neutral', 'forbidden'] as $stance)
        <button
            type="button"
            @disabled($disabled)
            x-on:click="set(@js($subject), @js($action), @js($stance))"
            x-bind:aria-pressed="at(@js($subject), @js($action)) === @js($stance) ? 'true' : 'false'"
            x-bind:class="at(@js($subject), @js($action)) === @js($stance) ? 'fb-seg-on' : ''"
            class="fb-seg-btn fb-seg-{{ $stance }}@if ($stance === 'neutral' && $broader) fb-seg-broader @endif"
            aria-label="{{ $stances[$stance] ?? $stance }}"
        >
            @if ($stance === 'granted' || ($stance === 'neutral' && $broader))
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5" /></svg>
            @elseif ($stance === 'neutral')
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="M5 12h14" /></svg>
            @else
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18" /><path d="m6 6 12 12" /></svg>
            @endif
        </button>
    @endforeach
</div>
