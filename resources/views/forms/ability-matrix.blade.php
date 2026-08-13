@php
    $sections = $getSections();
    $columns = $getColumnGroups();
    $presets = $getPresets();
    $stances = $getStances();
    $notes = $getNotes();
    $broader = $getBroader();
    $disabled = $isDisabled();
    $offered = [$columns['manage']['action'], ...array_column(array_merge([], ...array_column($columns['groups'], 'actions')), 'action')];
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    @if ($isEmptyCatalog())
        <p class="fb-empty">{{ $getEmptyLabel() }}</p>
    @else
        <div x-data="{@include('filament-bouncer::forms.ability-alpine')}" class="fb">
            {{-- One tab per group, hidden when there is only one: a single tab reads as if
                 something were missing. The count is highlighted the moment its group
                 grants anything. --}}
            <div class="fb-tabs" role="tablist" @if (count($sections) < 2) hidden @endif>
                @foreach ($sections as $key => $section)
                    @php($keys = array_column($section['rows'], 'key'))
                    <button
                        type="button"
                        role="tab"
                        class="fb-tab"
                        data-tab="{{ $key }}"
                        x-bind:class="tab === @js($key) ? 'fb-tab-on' : ''"
                        x-bind:aria-selected="tab === @js($key) ? 'true' : 'false'"
                        x-on:click="tab = @js($key)"
                    >
                        {{ $section['label'] }}
                        <span class="fb-tab-count" x-bind:class="grantedIn(@js($keys)) > 0 ? 'fb-tab-count-on' : ''" x-text="tallyIn(@js($keys))"></span>
                    </button>
                @endforeach
            </div>

            @foreach ($sections as $key => $section)
                @continue($section['rows'] === [])

                <div role="tabpanel" x-show="tab === @js($key)" x-cloak>
                    @include('filament-bouncer::forms.ability-matrix-section', ['section' => $section])
                </div>
            @endforeach

            <div class="fb-summary">
                <span class="fb-summary-chip fb-summary-chip-granted">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5" /></svg>
                    <span x-text="say('granted', tally().granted)"></span>
                </span>
                <span class="fb-summary-chip fb-summary-chip-forbidden">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18" /><path d="m6 6 12 12" /></svg>
                    <span x-text="say('forbidden', tally().forbidden)"></span>
                </span>
                <span class="fb-summary-chip fb-summary-chip-neutral">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="M5 12h14" /></svg>
                    <span x-text="say('neutral', tally().neutral)"></span>
                </span>

                @if ($doesSubmitFromSummary())
                    <span class="fb-summary-spacer"></span>
                    <a href="{{ $getSummaryCancelUrl() }}" class="fb-btn fb-btn-out">{{ __('filament-bouncer::roles.form.cancel') }}</a>
                    <button type="button" class="fb-btn fb-btn-pri fb-summary-save" x-on:click="$wire.call('save')">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5" /></svg>
                        {{ __('filament-bouncer::roles.form.save') }}
                    </button>
                @endif
            </div>

            <p class="fb-hint">{{ __('filament-bouncer::roles.grid.hint') }}</p>

            @if ($notes !== [])
                <p class="fb-hint fb-hint-note">{{ $getNoteLegend() }}</p>
            @endif
        </div>
    @endif
</x-dynamic-component>
