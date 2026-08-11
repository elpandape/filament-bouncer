@php
    $rows = $getRows();
    $stances = $getStances();
    $disabled = $isDisabled();
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    @if ($isWithheld())
        <p class="fb-empty">{{ $getWithheldLabel() }}</p>
    @elseif ($rows === [])
        <p class="fb-empty">{{ $getEmptyLabel() }}</p>
    @else
        <div
            x-data="{
                state: $wire.$entangle('{{ $getStatePath() }}'),
                disabled: @js($disabled),
                set(role, stance) {
                    if (this.disabled) {
                        return
                    }

                    this.state[role] = stance
                },
                at(role) {
                    return this.state?.[role] ?? @js($getNeutral())
                },
            }"
            class="fb"
        >
            @foreach ($rows as $row)
                <div class="fb-holder" data-role="{{ $row['key'] }}">
                    <div class="fb-row-body">
                        <span class="fb-row-name">{{ $row['name'] }}</span>
                        @if (filled($row['title']))
                            <span class="fb-row-note">{{ $row['title'] }}</span>
                        @endif
                    </div>

                    {{-- The same three words the roles grid offers, because a cell here
                         and a cell there are the same row of the same table. --}}
                    <div class="fb-seg" role="group">
                        @foreach (['granted', 'neutral', 'forbidden'] as $stance)
                            <button
                                type="button"
                                @disabled($disabled)
                                x-on:click="set(@js($row['key']), @js($stance))"
                                x-bind:aria-pressed="at(@js($row['key'])) === @js($stance) ? 'true' : 'false'"
                                x-bind:class="at(@js($row['key'])) === @js($stance) ? 'fb-seg-on' : ''"
                                class="fb-seg-btn fb-seg-{{ $stance }}"
                                aria-label="{{ $stances[$stance] ?? $stance }}"
                            >
                                @if ($stance === 'granted')
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5" /></svg>
                                @elseif ($stance === 'neutral')
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="M5 12h14" /></svg>
                                @else
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18" /><path d="m6 6 12 12" /></svg>
                                @endif
                            </button>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-dynamic-component>
