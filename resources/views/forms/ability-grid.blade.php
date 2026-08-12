@php
    $sections = $getSections();
    $presets = $getPresets();
    $stances = $getStances();
    $disabled = $isDisabled();
    $collapse = $getCollapseLabel();
    $weight = $getScopeLabel();
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    @if ($isEmptyCatalog())
        <p class="fb-empty">{{ $getEmptyLabel() }}</p>
    @else
        <div
            x-data="{
                state: $wire.$entangle('{{ $getStatePath() }}'),
                open: @js($getOpenByDefault()),
                disabled: @js($disabled),
                set(subject, action, stance) {
                    if (this.disabled) {
                        return
                    }

                    if (! this.state[subject]) {
                        this.state[subject] = {}
                    }

                    this.state[subject][action] = stance
                },
                at(subject, action) {
                    return this.state?.[subject]?.[action] ?? @js($getNeutral())
                },
                preset(subject, actions, stance) {
                    if (this.disabled) {
                        return
                    }

                    for (const action of Object.keys(this.state?.[subject] ?? {})) {
                        if (actions === null || actions.includes(action)) {
                            this.state[subject][action] = stance
                        }
                    }
                },
                granted(subject) {
                    return Object.values(this.state?.[subject] ?? {}).filter((s) => s === 'granted').length
                },
                {{-- The count is only known here, and the words for one and for many are
                     only known there, so both forms come along and this picks. --}}
                words: {
                    granted: { one: @js(trans_choice('filament-bouncer::roles.summary.granted', 1)), many: @js(trans_choice('filament-bouncer::roles.summary.granted', 2, ['count' => '%n'])) },
                    forbidden: { one: @js(trans_choice('filament-bouncer::roles.summary.forbidden', 1)), many: @js(trans_choice('filament-bouncer::roles.summary.forbidden', 2, ['count' => '%n'])) },
                    neutral: { one: @js(trans_choice('filament-bouncer::roles.summary.neutral', 1)), many: @js(trans_choice('filament-bouncer::roles.summary.neutral', 2, ['count' => '%n'])) },
                },
                say(kind, count) {
                    return count === 1 ? this.words[kind].one : this.words[kind].many.replace('%n', count)
                },
                tally() {
                    let granted = 0, forbidden = 0, neutral = 0

                    for (const actions of Object.values(this.state ?? {})) {
                        for (const stance of Object.values(actions ?? {})) {
                            if (stance === 'granted') { granted++ }
                            else if (stance === 'forbidden') { forbidden++ }
                            else { neutral++ }
                        }
                    }

                    return { granted, forbidden, neutral }
                },
            }"
            class="fb"
        >
            @foreach ($sections as $key => $section)
                @continue($section['subjects'] === [])

                <section class="fb-section">
                    <p class="fb-section-name">{{ $section['label'] }}</p>

                    @if ($section['doors'])
                        {{-- A door is reached or it is not: one ability each, so a list
                             reads better than a fold. --}}
                        @foreach ($section['subjects'] as $subjectKey => $subject)
                            @php($row = $subject['rows'][0])
                            <div class="fb-door">
                                @include('filament-bouncer::forms.ability-cell', [
                                    'subject' => $subjectKey,
                                    'action' => $row['action'],
                                    'note' => $row['note'],
                                    'broader' => $row['broader'],
                                    'disabled' => $disabled,
                                    'stances' => $stances,
                                ])
                                <div class="fb-row-body">
                                    <span class="fb-row-name">{{ $subject['label'] }}</span>
                                    @if (filled($row['note']))
                                        <span class="fb-row-note">{{ $row['note'] }}</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @else
                        @foreach ($section['subjects'] as $subjectKey => $subject)
                            <div class="fb-subject" x-bind:class="(open[@js($subjectKey)] ?? open.all) ? 'fb-subject-open' : ''">
                                <button
                                    type="button"
                                    class="fb-subject-head"
                                    x-on:click="open[@js($subjectKey)] = ! (open[@js($subjectKey)] ?? open.all)"
                                    x-bind:aria-expanded="(open[@js($subjectKey)] ?? open.all) ? 'true' : 'false'"
                                    aria-label="{{ $collapse }}"
                                >
                                    <svg class="fb-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6" /></svg>
                                    <span class="fb-subject-name">{{ $subject['label'] }}</span>
                                    @if ($subject['policy'] !== null)
                                        <span class="fb-subject-policy">{{ $subject['policy'] }}</span>
                                    @endif
                                    <span class="fb-subject-count" x-text="@js(__('filament-bouncer::roles.form.model_count', ['granted' => '%g', 'total' => count($subject['rows'])])).replace('%g', granted(@js($subjectKey)))"></span>
                                </button>

                                <div class="fb-rows" x-show="open[@js($subjectKey)] ?? open.all" x-cloak>
                                    <div class="fb-presets">
                                        <button type="button" class="fb-preset" @disabled($disabled) x-on:click="preset(@js($subjectKey), @js($presets['read']), 'granted')">{{ __('filament-bouncer::roles.presets.read') }}</button>
                                        <button type="button" class="fb-preset" @disabled($disabled) x-on:click="preset(@js($subjectKey), null, 'granted')">{{ __('filament-bouncer::roles.presets.all') }}</button>
                                        <button type="button" class="fb-preset" @disabled($disabled) x-on:click="preset(@js($subjectKey), null, 'neutral')">{{ __('filament-bouncer::roles.presets.none') }}</button>
                                    </div>

                                    @foreach ($subject['rows'] as $row)
                                        <div class="fb-row" data-action="{{ $row['action'] }}">
                                            <span class="fb-weight fb-weight-{{ $row['scope'] }}" title="{{ $weight }}" aria-hidden="true"></span>
                                            <div class="fb-row-body">
                                                <span class="fb-row-name">{{ $row['label'] }}</span>
                                                <span class="fb-row-action">{{ $row['action'] }}</span>
                                                @if (filled($row['note']))
                                                    <span class="fb-row-note">{{ $row['note'] }}</span>
                                                @endif
                                            </div>
                                            @include('filament-bouncer::forms.ability-cell', [
                                                'subject' => $subjectKey,
                                                'action' => $row['action'],
                                                'note' => $row['note'],
                                                'broader' => $row['broader'],
                                                'disabled' => $disabled,
                                                'stances' => $stances,
                                            ])
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    @endif
                </section>
            @endforeach

            <div class="fb-summary">
                <span class="fb-summary-granted" x-text="say('granted', tally().granted)"></span>
                <span class="fb-summary-forbidden" x-text="say('forbidden', tally().forbidden)"></span>
                <span class="fb-summary-neutral" x-text="say('neutral', tally().neutral)"></span>
            </div>
        </div>
    @endif
</x-dynamic-component>
