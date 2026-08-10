@php
    $tabs = $this->getTabs();
    $roles = $this->getRoles();
    $words = $this->getWords();
    $stances = $this->getStanceWords();
    $first = array_key_first($tabs);
@endphp

<x-filament-panels::page>
    <div class="fb" @if (count($tabs) > 1) x-data="{ tab: @js($first) }" @endif>
        @if ($tabs === [] || $roles === [])
            <p class="fb-empty">{{ $words['empty'] }}</p>
        @else
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
                            <span class="fb-tab-count">{{ count($tab['abilities']) }}</span>
                        </button>
                    @endforeach
                </div>
            @endif

            @foreach ($tabs as $key => $tab)
                <div @if (count($tabs) > 1) x-show="tab === @js($key)" x-cloak @endif>
                    <div class="fb-grid-ctn">
                        {{-- The roles screen read sideways: abilities down, roles across. --}}
                        <table class="fb-grid">
                            <thead>
                                <tr class="fb-cols">
                                    <th class="fb-corner">{{ $words['ability'] }}</th>
                                    @foreach ($roles as $role)
                                        <th>
                                            <span class="fb-col-label">{{ $role }}</span>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($tab['abilities'] as $ability)
                                    <tr>
                                        <th class="fb-row-head">
                                            <span class="fb-row-name">
                                                @if ($ability['subject'] !== null)
                                                    <b>{{ $ability['subject'] }}:</b>
                                                @endif
                                                {{ $ability['title'] }}
                                            </span>
                                            <span class="fb-row-policy">{{ $ability['name'] }}</span>
                                        </th>

                                        @foreach ($ability['holders'] as $holder)
                                            <td>
                                                @php
                                                    $note = $holder['how'] === null
                                                        ? $words['nobody']
                                                        : $stances[$holder['stance']].' · '.$words[$holder['how']];
                                                @endphp
                                                <span
                                                    @class([
                                                        'fb-cell',
                                                        'fb-cell-'.$holder['stance'],
                                                        'fb-noted' => $holder['how'] === 'broader',
                                                    ])
                                                    title="{{ $note }}"
                                                    aria-label="{{ $note }}"
                                                >
                                                    @if ($holder['stance'] === 'granted')
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5" /></svg>
                                                    @elseif ($holder['stance'] === 'forbidden')
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18" /><path d="m6 6 12 12" /></svg>
                                                    @else
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="M5 12h14" /></svg>
                                                    @endif
                                                </span>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach

            <p class="fb-legend">{{ $words['broader'] }}</p>
        @endif
    </div>
</x-filament-panels::page>
