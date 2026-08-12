{{--
    What is about to be written, model by model.

    A single sentence with three counts in it was true and unreadable: nobody hands out
    abilities by counting them. The design reads the choice back the way it was made —
    one line per subject, the granted in green and the forbidden in red beside it — and
    leaves the arithmetic for the foot, where a total belongs.

    A subject nobody said anything about still gets its line, and says so. Leaving it out
    would make the review a list of what was chosen instead of a reading of the whole
    catalogue, and the difference matters on the screen that writes it.
--}}
<div class="fb fb-review">
    @foreach ($subjects as $subject)
        <div class="fb-rev-row">
            <div class="fb-rev-subject">{{ $subject['label'] }}</div>
            <div class="fb-rev-chips">
                @forelse ($subject['chips'] as $chip)
                    <span class="fb-rev-chip fb-rev-chip-{{ $chip['stance'] }}">
                        @if ($chip['stance'] === 'forbidden')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="10" /><path d="m4.9 4.9 14.2 14.2" /></svg>
                        @endif
                        {{ $chip['label'] }}
                    </span>
                @empty
                    <span class="fb-rev-silent">{{ $silent }}</span>
                @endforelse
            </div>
        </div>
    @endforeach

    <p class="fb-rev-total">{{ $total }}</p>
</div>
