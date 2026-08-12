{{--
    The sentence the rule adds up to, held above everything else on the screen.

    Live on the composing screen — Alpine entangles the three choices and recomposes
    the chips without a round trip — and static on the record pages, where the chips
    are read out of the stored row before the view is handed the words.
--}}
<div class="fb fb-phrase-card">
    @if ($live)
        <div
            class="fb-phrase"
            x-data="{
                subject: $wire.$entangle(@js($paths['subject'])),
                action: $wire.$entangle(@js($paths['action'])),
                reach: $wire.$entangle(@js($paths['reach'])),
                record: $wire.$entangle(@js($paths['record'])),
                maps: @js($maps),
                get actionText() { return (this.maps.actions[this.subject] ?? {})[this.action] ?? null },
                get subjectText() { return this.maps.subjects[this.subject] ?? null },
                get reachText() {
                    if (! this.reach) { return null }

                    if (this.reach === this.maps.recordValue && this.record) {
                        return this.maps.recordReading.replace(':id', this.record)
                    }

                    return this.maps.reaches[this.reach] ?? null
                },
            }"
        >
            <span>{{ __('filament-bouncer::abilities.phrase.can') }}</span>
            <span
                class="fb-phrase-slot"
                x-bind:class="actionText !== null && 'fb-phrase-slot-filled'"
                x-text="actionText ?? @js(__('filament-bouncer::abilities.phrase.action'))"
            >{{ __('filament-bouncer::abilities.phrase.action') }}</span>
            <span
                class="fb-phrase-slot"
                x-bind:class="subjectText !== null && 'fb-phrase-slot-filled'"
                x-text="subjectText ?? @js(__('filament-bouncer::abilities.phrase.subject'))"
            >{{ __('filament-bouncer::abilities.phrase.subject') }}</span>
            <span
                class="fb-phrase-slot"
                x-bind:class="reachText !== null && 'fb-phrase-slot-filled'"
                x-text="reachText ?? @js(__('filament-bouncer::abilities.phrase.reach'))"
            >{{ __('filament-bouncer::abilities.phrase.reach') }}</span>
        </div>

        <p class="fb-phrase-note">{{ __('filament-bouncer::abilities.phrase.note') }}</p>
    @else
        <div class="fb-phrase">
            <span>{{ __('filament-bouncer::abilities.phrase.can') }}</span>
            @foreach ($slots as $slot)
                <span class="fb-phrase-slot fb-phrase-slot-filled">{{ $slot }}</span>
            @endforeach
        </div>
    @endif
</div>
