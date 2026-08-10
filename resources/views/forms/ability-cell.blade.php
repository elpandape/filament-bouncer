{{--
    One intersection: a single button that walks the three stances.

    Three buttons never fitted a column, and stacking them made the grid as tall as the
    catalogue is wide. One fits, and what it says is read from its shape: filled when
    this row is the rule, outlined when a broader rule reaches it, pale when nothing
    says anything at all.
--}}
<button
    type="button"
    @disabled($disabled)
    x-on:click="cycle(@js($subject), @js($action))"
    x-bind:class="'fb-cell fb-cell-' + at(@js($subject), @js($action)) + @js(filled($note) ? ' fb-noted' : '')"
    x-bind:aria-label="words[at(@js($subject), @js($action))]"
    class="fb-cell fb-cell-neutral"
    @if (filled($note)) title="{{ $note }}" @endif
>
    <template x-if="at(@js($subject), @js($action)) === 'granted'">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5" /></svg>
    </template>
    <template x-if="at(@js($subject), @js($action)) === 'neutral'">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="M5 12h14" /></svg>
    </template>
    <template x-if="at(@js($subject), @js($action)) === 'forbidden'">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18" /><path d="m6 6 12 12" /></svg>
    </template>
</button>
