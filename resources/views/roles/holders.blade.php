@if ($holders === [])
    <p class="fb fb-empty">{{ __('filament-bouncer::roles.record.holders_empty') }}</p>
@else
    <div class="fb fb-holders">
        @foreach ($holders as $holder)
            <div class="fb-holder">
                <span class="fb-avatar" aria-hidden="true">{{ $holder['initials'] }}</span>
                <div class="fb-row-body">
                    <span class="fb-row-name">{{ $holder['name'] }}</span>
                    @if (filled($holder['email']))
                        <span class="fb-row-note">{{ $holder['email'] }}</span>
                    @endif
                </div>
                @if ($holder['removable'])
                    <button
                        type="button"
                        class="fb-btn fb-btn-out fb-btn-sm"
                        wire:click="mountAction('retractRole', { holder: {{ \Illuminate\Support\Js::from($holder['key']) }} })"
                    >
                        {{ __('filament-bouncer::roles.record.retract') }}
                    </button>
                @else
                    <span class="fb-holder-locked" title="{{ __('filament-bouncer::roles.record.last_holder') }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" /><path d="M7 11V7a5 5 0 0 1 10 0v4" /></svg>
                    </span>
                @endif
            </div>
        @endforeach
    </div>
@endif
