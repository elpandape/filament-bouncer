{{--
    What the role says, and only that.

    No grid and no state: a row carries the actions holding a stance and none other, so its
    length grows with what the role does rather than with what the panel declares.
--}}
@php
    $labels = $getStanceLabels();
    $rows = $getRows();
    $doors = $getDoors();
    $narrowed = $getNarrowed();
    $silent = $getSilent();
@endphp

<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">
    <div class="fb">
        @if ($rows === [] && $doors === [] && $narrowed === [])
            <p class="fb-tg-empty">{{ __('filament-bouncer::roles.record.tags_empty') }}</p>
        @endif

        @if ($rows !== [])
            <ul class="fb-tg">
                @foreach ($rows as $row)
                    <li class="fb-tg-row">
                        <span class="fb-tg-entity">
                            @if ($row['icon'] !== null)
                                <x-filament::icon :icon="$row['icon']" class="fb-tg-icon" />
                            @endif
                            <span>
                                <b>{{ $row['label'] }}</b>
                                @if ($row['policy'] !== null)
                                    <code>{{ $row['policy'] }}</code>
                                @endif
                            </span>
                        </span>

                        @include('filament-bouncer::roles.ability-tag-list', ['tags' => $row['tags']])
                    </li>
                @endforeach
            </ul>
        @endif

        @foreach ($doors as $door)
            <ul class="fb-tg fb-tg-apart">
                <li class="fb-tg-name">{{ $door['label'] }}</li>

                @foreach ($door['rows'] as $row)
                    <li class="fb-tg-row">
                        <span class="fb-tg-entity"><b>{{ $row['label'] }}</b></span>
                        @include('filament-bouncer::roles.ability-tag-list', ['tags' => $row['tags']])
                    </li>
                @endforeach
            </ul>
        @endforeach

        @if ($narrowed !== [])
            <ul class="fb-tg fb-tg-apart">
                <li class="fb-tg-name">{{ __('filament-bouncer::roles.record.narrowed_heading') }}</li>

                @foreach ($narrowed as $rule)
                    <li class="fb-tg-row">
                        <span class="fb-tg-entity">
                            <span>
                                <b>{{ $rule['label'] }}</b> · {{ $rule['entity'] }}
                                <code>{{ $rule['action'] }}</code>
                            </span>
                        </span>

                        <span class="fb-tg-tags">
                            @if ($rule['owned'])
                                <span class="fb-tg-tag fb-tg-tag-granted">{{ __('filament-bouncer::roles.record.owned') }}</span>
                            @endif

                            @foreach ($rule['records'] as $record)
                                <span class="fb-tg-tag @if ($record['missing']) fb-tg-tag-gone @else fb-tg-tag-record @endif">
                                    {{ $record['title'] }}
                                    @if ($record['missing'])
                                        <code>{{ __('filament-bouncer::roles.record.record_gone') }}</code>
                                    @endif
                                </span>
                            @endforeach
                        </span>
                    </li>
                @endforeach

                <li class="fb-tg-foot">{{ __('filament-bouncer::roles.record.narrowed_note') }}</li>
            </ul>
        @endif

        {{-- Over a big catalogue the list of what the role is silent about is longer than
             what it says, so past a point it is counted by group and the names are folded
             away. `details` and not Alpine: the record page holds no state and neither
             does this. --}}
        @if ($silent !== [])
            @if ($spellsSilent())
                <p class="fb-tg-silent">{{ $getSilentLabel() }}</p>
            @else
                <details class="fb-tg-silent fb-tg-fold">
                    <summary>{{ $getSilentLabel() }} <span>{{ __('filament-bouncer::roles.record.silent_more') }}</span></summary>

                    @foreach ($getSilentGroups() as $group)
                        <p><b>{{ $group['label'] }}</b> · {{ implode(', ', $group['names']) }}</p>
                    @endforeach
                </details>
            @endif
        @endif
    </div>
</x-dynamic-component>
