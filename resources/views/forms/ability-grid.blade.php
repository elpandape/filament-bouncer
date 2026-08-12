@php
    $sections = $getSections();
    $presets = $getPresets();
    $stances = $getStances();
    $disabled = $isDisabled();
    $collapse = $getCollapseLabel();
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
                forbidden(subject) {
                    return Object.values(this.state?.[subject] ?? {}).filter((s) => s === 'forbidden').length
                },
                {{-- The count is only known here, and the words for one and for many are
                     only known there, so both forms come along and this picks. --}}
                words: {
                    granted: { one: @js(trans_choice('filament-bouncer::roles.summary.granted', 1)), many: @js(trans_choice('filament-bouncer::roles.summary.granted', 2, ['count' => '%n'])) },
                    forbidden: { one: @js(trans_choice('filament-bouncer::roles.summary.forbidden', 1)), many: @js(trans_choice('filament-bouncer::roles.summary.forbidden', 2, ['count' => '%n'])) },
                    neutral: { one: @js(trans_choice('filament-bouncer::roles.summary.neutral', 1)), many: @js(trans_choice('filament-bouncer::roles.summary.neutral', 2, ['count' => '%n'])) },
                    badge: { one: @js(trans_choice('filament-bouncer::roles.form.forbidden_count', 1)), many: @js(trans_choice('filament-bouncer::roles.form.forbidden_count', 2, ['count' => '%n'])) },
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
                                        <span class="fb-row-note{{ $row['kind'] === 'forbidden' ? ' fb-row-note-forbidden' : '' }}">{{ $row['note'] }}</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @else
                        @foreach ($section['subjects'] as $subjectKey => $subject)
                            <div class="fb-subject" x-bind:class="(open[@js($subjectKey)] ?? open.all) ? 'fb-subject-open' : ''">
                                {{-- The head is a div and not the button it used to be, because it now
                                     carries a menu of its own and a button may not hold another. The
                                     toggle takes the whole elastic middle, so the reachable target
                                     barely shrinks. --}}
                                <div class="fb-subject-head">
                                    <button
                                        type="button"
                                        class="fb-subject-toggle"
                                        x-on:click="open[@js($subjectKey)] = ! (open[@js($subjectKey)] ?? open.all)"
                                        x-bind:aria-expanded="(open[@js($subjectKey)] ?? open.all) ? 'true' : 'false'"
                                        aria-label="{{ $collapse }}"
                                    >
                                        <svg class="fb-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6" /></svg>
                                        @if ($subject['icon'] !== null)
                                            <x-filament::icon :icon="$subject['icon']" class="fb-subject-icon" />
                                        @endif
                                        <span class="fb-subject-name">{{ $subject['label'] }}</span>
                                        @if ($subject['class'] !== null)
                                            <code class="fb-subject-class">{{ $subject['class'] }}</code>
                                        @endif
                                    </button>
                                    <span class="fb-subject-forbidden" x-show="forbidden(@js($subjectKey)) > 0" x-text="say('badge', forbidden(@js($subjectKey)))" x-cloak></span>
                                    <span class="fb-subject-count" x-text="@js(__('filament-bouncer::roles.form.model_count', ['granted' => '%g', 'total' => count($subject['rows'])])).replace('%g', granted(@js($subjectKey)))"></span>
                                    <details class="fb-presets">
                                        <summary class="fb-presets-toggle" aria-label="{{ __('filament-bouncer::roles.presets.label') }}" x-on:click.stop>
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 21v-7" /><path d="M4 10V3" /><path d="M12 21v-9" /><path d="M12 8V3" /><path d="M20 21v-5" /><path d="M20 12V3" /><path d="M2 14h4" /><path d="M10 8h4" /><path d="M18 16h4" /></svg>
                                        </summary>
                                        <div>
                                            <button type="button" class="fb-preset" @disabled($disabled) x-on:click="preset(@js($subjectKey), @js($presets['read']), 'granted')">{{ __('filament-bouncer::roles.presets.read') }}</button>
                                            <button type="button" class="fb-preset" @disabled($disabled) x-on:click="preset(@js($subjectKey), null, 'granted')">{{ __('filament-bouncer::roles.presets.all') }}</button>
                                            <button type="button" class="fb-preset" @disabled($disabled) x-on:click="preset(@js($subjectKey), null, 'neutral')">{{ __('filament-bouncer::roles.presets.none') }}</button>
                                        </div>
                                    </details>
                                </div>

                                <div class="fb-rows" x-show="open[@js($subjectKey)] ?? open.all" x-cloak>
                                    @foreach ($subject['rows'] as $row)
                                        <div class="fb-row" data-action="{{ $row['action'] }}">
                                            <div class="fb-row-body">
                                                <span class="fb-row-name">{{ $row['label'] }}</span>
                                                <span class="fb-row-action">{{ $row['action'] }}</span>
                                                @if (filled($row['note']))
                                                    <span class="fb-row-note{{ $row['kind'] === 'forbidden' ? ' fb-row-note-forbidden' : '' }}">{{ $row['note'] }}</span>
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
        </div>
    @endif
</x-dynamic-component>
