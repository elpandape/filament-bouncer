{{--
    One intersection: a single button that walks the three stances.

    Three buttons never fitted a column, and stacking them made the grid as tall as the
    catalogue is wide. One fits, and what it says is read from its shape: filled when
    this row is the rule, outlined when a broader rule reaches it, pale when nothing
    says anything at all.

    A cell reached by a broader rule draws the tick rather than the dash, outlined. The
    dash was true of the row and false of the reader's question: a role holding the
    wildcard has no rule of its own for any cell here, and a grid full of dashes says it
    can do nothing. The outline is what keeps the two apart — this cell is not the rule —
    and pressing it fills the tick in, which is the row taking the answer on itself.
--}}
<button
    type="button"
    @disabled($disabled)
    x-on:click="cycle(@js($subject), @js($action))"
    x-bind:class="'fb-cell fb-cell-' + at(@js($subject), @js($action)) + @js(filled($note) ? ' fb-noted' : '')"
    x-bind:aria-label="words[at(@js($subject), @js($action))] + @js($broader ? ' — '.$inherited : '')"
    class="fb-cell"
    @if (filled($note)) title="{{ $note }}" @endif
>
    <template x-if="at(@js($subject), @js($action)) === 'granted' || (@js($broader) && at(@js($subject), @js($action)) === 'neutral')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5" /></svg>
    </template>
    <template x-if="! @js($broader) && at(@js($subject), @js($action)) === 'neutral'">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="M5 12h14" /></svg>
    </template>
    <template x-if="at(@js($subject), @js($action)) === 'forbidden'">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18" /><path d="m6 6 12 12" /></svg>
    </template>
</button>
