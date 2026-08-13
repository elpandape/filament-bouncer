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

            <p class="fb-hint">{{ __('filament-bouncer::roles.grid.hint') }}</p>

            @if ($notes !== [])
                <p class="fb-hint fb-hint-note">{{ $getNoteLegend() }}</p>
            @endif
        </div>
    @endif
</x-dynamic-component>
