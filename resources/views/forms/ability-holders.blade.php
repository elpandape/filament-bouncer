@php
    $holders = $getHolders();
    $words = $getWords();
    $stances = $getStanceWords();
    $disabled = $isDisabled();
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div class="fb">
        @if (! $isOffered())
            <p class="fb-empty">{{ $words['withheld'] }}</p>
        @else
            <div
                x-data="{
                    state: $wire.$entangle('{{ $getStatePath() }}'),
                    order: @js($getOrder()),
                    words: @js($stances),
                    cycle(role) {
                        if (@js($disabled)) {
                            return
                        }

                        const at = this.order.indexOf(this.state[role] ?? this.order[0])

                        this.state[role] = this.order[(at + 1) % this.order.length]
                    },
                    at(role) {
                        return this.state?.[role] ?? @js($getOrder()[0])
                    },
                }"
                class="fb-list"
            >
                @foreach ($holders as $holder)
                    <div class="fb-line">
                        <button
                            type="button"
                            @disabled($disabled)
                            x-on:click="cycle(@js($holder['key']))"
                            x-bind:class="'fb-cell fb-cell-' + at(@js($holder['key'])) + @js($holder['how'] === 'broader' ? ' fb-noted' : '')"
                            x-bind:aria-label="words[at(@js($holder['key']))]"
                            class="fb-cell"
                        >
                            <template x-if="at(@js($holder['key'])) === 'granted'">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5" /></svg>
                            </template>
                            <template x-if="at(@js($holder['key'])) === 'neutral'">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="M5 12h14" /></svg>
                            </template>
                            <template x-if="at(@js($holder['key'])) === 'forbidden'">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18" /><path d="m6 6 12 12" /></svg>
                            </template>
                        </button>

                        <div class="fb-line-body">
                            <span class="fb-row-name">{{ $holder['name'] }}</span>
                            <span class="fb-line-note">
                                @if ($holder['how'] === null)
                                    {{ $words['nobody'] }}
                                @else
                                    {{ $words[$holder['how']] }}
                                @endif
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>

            <p class="fb-legend">{{ $getLegend() }}</p>
        @endif
    </div>
</x-dynamic-component>
