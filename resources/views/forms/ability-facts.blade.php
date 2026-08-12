{{--
    The three columns that decide what the rule means, presented as entries and not as
    disabled inputs: nothing here is waiting to be typed into, and a greyed field reads
    as something broken rather than something settled.
--}}
@if ($facts !== null)
    <div class="fb fb-entries">
        <div class="fb-entry">
            <span class="fb-entry-lbl">{{ __('filament-bouncer::abilities.wizard.action') }}</span>
            <span class="fb-entry-val">
                {{ $facts->actionLabel }}
                <code class="fb-code">{{ $facts->actionName }}</code>
            </span>
        </div>

        <div class="fb-entry">
            <span class="fb-entry-lbl">{{ __('filament-bouncer::abilities.form.entity') }}</span>
            <span class="fb-entry-val">
                {{ $facts->subjectLabel }}
                @if ($facts->subjectClass !== null)
                    <code class="fb-code">{{ $facts->subjectClass }}</code>
                @endif
            </span>
        </div>

        <div class="fb-entry">
            <span class="fb-entry-lbl">{{ __('filament-bouncer::abilities.form.reach') }}</span>
            <span class="fb-entry-val">
                <span class="{{ $facts->reachColor() === 'gray' ? 'fb-badge-gray' : 'fb-badge-info' }}">{{ $facts->reachReading }}</span>
                @if ($facts->entityId !== null)
                    <code class="fb-code">entity_id = {{ $facts->entityId }}</code>
                @endif
            </span>
        </div>
    </div>
@endif
