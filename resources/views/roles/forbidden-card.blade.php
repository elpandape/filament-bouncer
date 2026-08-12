@if ($forbidden === [])
    <div class="fb fb-forbidden-empty">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10" /><line x1="4.9" y1="4.9" x2="19.1" y2="19.1" /></svg>
        <p>{{ __('filament-bouncer::roles.record.forbidden_empty') }}</p>
        <p class="fb-forbidden-note">{{ __('filament-bouncer::roles.record.forbidden_note') }}</p>
    </div>
@else
    <div class="fb fb-forbidden-list">
        @foreach ($forbidden as $row)
            <div class="fb-forbidden-row">
                <span class="fb-badge-dng">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10" /><line x1="4.9" y1="4.9" x2="19.1" y2="19.1" /></svg>
                    {{ $row['action'] }}
                </span>
                <span class="fb-forbidden-subject">{{ $row['subject'] }}</span>
            </div>
        @endforeach
    </div>
@endif
