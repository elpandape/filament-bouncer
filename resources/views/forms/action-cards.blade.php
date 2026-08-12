@php
    $options = $getOptions();
    $statePath = $getStatePath();
    $disabled = $isDisabled();
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    @if ($options === [])
        <p class="fb fb-empty">{{ $getEmptyLabel() }}</p>
    @else
        <div
            x-data="{ state: $wire.$entangle(@js($statePath)) }"
            class="fb fb-rc-grid"
            role="radiogroup"
        >
            @foreach ($options as $action => $label)
                <label
                    class="fb-rc"
                    x-bind:aria-pressed="state === @js((string) $action) ? 'true' : 'false'"
                >
                    <input
                        type="radio"
                        name="{{ $statePath }}"
                        value="{{ $action }}"
                        x-model="state"
                        @disabled($disabled)
                    />
                    <span class="fb-rc-t">{{ $label }}</span>
                    <span class="fb-rc-d"><code class="fb-code">{{ $action }}</code></span>
                </label>
            @endforeach
        </div>
    @endif
</x-dynamic-component>
