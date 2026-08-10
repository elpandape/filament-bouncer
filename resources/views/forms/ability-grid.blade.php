@php
    $tabs = $getTabs();
    $actions = $getActionColumns();
    $bands = $getBands();
    $stances = $getStances();
    $notes = $getNotes();
    $disabled = $isDisabled();
    $first = array_key_first($tabs);
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    @if ($tabs === [])
        <p class="fb-empty">{{ $getEmptyLabel() }}</p>
    @else
        <div
            x-data="{
                state: $wire.$entangle('{{ $getStatePath() }}'),
                tab: @js($first),
                order: @js($getOrder()),
                words: @js($stances),
                cycle(subject, action) {
                    if (@js($disabled)) {
                        return
                    }

                    if (! this.state[subject]) {
                        this.state[subject] = {}
                    }

                    const at = this.order.indexOf(this.state[subject][action] ?? this.order[0])

                    this.state[subject][action] = this.order[(at + 1) % this.order.length]
                },
                at(subject, action) {
                    return this.state?.[subject]?.[action] ?? @js($getNeutral())
                },
            }"
            class="fb"
        >
            @if (count($tabs) > 1)
                <div class="fb-tabs" role="tablist">
                    @foreach ($tabs as $key => $tab)
                        <button
                            type="button"
                            role="tab"
                            x-on:click="tab = @js($key)"
                            x-bind:aria-selected="tab === @js($key)"
                            x-bind:class="tab === @js($key) ? 'fb-tab fb-tab-on' : 'fb-tab'"
                        >
                            {{ $tab['label'] }}
                            <span class="fb-tab-count">{{ count($tab['subjects']) }}</span>
                        </button>
                    @endforeach
                </div>
            @endif

            @foreach ($tabs as $key => $tab)
                <div x-show="tab === @js($key)" x-cloak>
                    @if ($tab['grid'])
                        <div class="fb-grid-ctn">
                            <table class="fb-grid">
                                <thead>
                                    <tr class="fb-bands">
                                        <th class="fb-corner"></th>
                                        <th class="fb-manage-col"></th>
                                        @foreach ($bands as $band)
                                            <th colspan="{{ $band['span'] }}" @class(['fb-band-'.$band['scope']])>
                                                {{ $band['label'] }}
                                            </th>
                                        @endforeach
                                    </tr>
                                    <tr class="fb-cols">
                                        <th class="fb-corner">{{ $getSubjectHeading() }}</th>
                                        <th class="fb-manage-col fb-band-irreversible">
                                            <span class="fb-col-label">{{ $getManageHeading() }}</span>
                                        </th>
                                        @foreach ($actions as $action => $meta)
                                            <th @class(['fb-band-'.$meta['scope']])>
                                                <span class="fb-col-label">{{ $meta['label'] }}</span>
                                                <span class="fb-col-name">{{ $action }}</span>
                                            </th>
                                        @endforeach
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach ($tab['subjects'] as $subjectKey => $subject)
                                        <tr>
                                            <th class="fb-row-head">
                                                <span class="fb-row-name">{{ $subject['label'] }}</span>
                                                @if ($subject['policy'] !== null)
                                                    <span class="fb-row-policy">{{ $subject['policy'] }}</span>
                                                @endif
                                            </th>

                                            <td class="fb-manage-col">
                                                @if ($subject['manage'] !== null)
                                                    @include('filament-bouncer::forms.ability-cell', [
                                                        'subject' => $subjectKey,
                                                        'action' => $subject['manage'],
                                                        'note' => $notes[$subjectKey][$subject['manage']] ?? null,
                                                        'disabled' => $disabled,
                                                    ])
                                                @else
                                                    <span class="fb-none">·</span>
                                                @endif
                                            </td>

                                            @foreach (array_keys($actions) as $action)
                                                <td>
                                                    @if (in_array($action, $subject['actions'], true))
                                                        @include('filament-bouncer::forms.ability-cell', [
                                                            'subject' => $subjectKey,
                                                            'action' => $action,
                                                            'note' => $notes[$subjectKey][$action] ?? null,
                                                            'disabled' => $disabled,
                                                        ])
                                                    @else
                                                        <span class="fb-none" title="{{ $getUndeclaredLabel() }}">·</span>
                                                    @endif
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        {{-- A door, not a grid: one ability each, so a list reads better than a column. --}}
                        <div class="fb-list">
                            @foreach ($tab['subjects'] as $subjectKey => $subject)
                                @php($action = $subject['actions'][0])
                                <div class="fb-line">
                                    @include('filament-bouncer::forms.ability-cell', [
                                        'subject' => $subjectKey,
                                        'action' => $action,
                                        'note' => $notes[$subjectKey][$action] ?? null,
                                        'disabled' => $disabled,
                                    ])
                                    <div class="fb-line-body">
                                        <span class="fb-row-name">{{ $subject['label'] }}</span>
                                        @if (filled($notes[$subjectKey][$action] ?? null))
                                            <span class="fb-line-note">{{ $notes[$subjectKey][$action] }}</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</x-dynamic-component>
