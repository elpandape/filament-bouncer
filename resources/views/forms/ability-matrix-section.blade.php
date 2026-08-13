{{--
    A group, in the shape it deserves.

    Subjects answering several actions read as a matrix; the ones with a single door — a
    page, a widget, an ability declared in configuration — read as a list, because a grid
    one column wide reads worse than a list does. Which is which is not this view's call:
    the catalogue says so.
--}}
@if ($section['grid'])
    <div class="fb-scroll">
        <table class="fb-table">
            <thead>
                <tr>
                    <th class="fb-corner" rowspan="2" scope="col">
                        <span class="fb-corner-name">{{ $getSubjectLabel() }}</span>

                        {{-- The same shortcuts a row offers, aimed at every row of the
                             grid. They live here because the corner is the only part of
                             the table belonging to neither a subject nor an action. --}}
                        @unless ($disabled)
                            <span class="fb-shortcuts fb-shortcuts-all">
                                @foreach ($presets as $preset)
                                    <button
                                        type="button"
                                        class="fb-shortcut"
                                        x-on:click="@js($getGriddedSubjects()).forEach(key => apply(key, @js($preset['actions']), @js($offered)))"
                                    >{{ $preset['label'] }}</button>
                                @endforeach

                                <button
                                    type="button"
                                    class="fb-shortcut"
                                    x-on:click="@js($getGriddedSubjects()).forEach(key => clear(key, @js($offered)))"
                                >{{ $getClearLabel() }}</button>
                            </span>
                        @endunless
                    </th>
                    <th class="fb-all" rowspan="2" scope="col">{{ $columns['manage']['label'] }}</th>
                    @foreach ($columns['groups'] as $group)
                        <th class="fb-group fb-scope-{{ $group['scope'] }}" colspan="{{ count($group['actions']) }}" scope="colgroup">{{ $group['label'] }}</th>
                    @endforeach
                </tr>
                <tr>
                    @foreach ($columns['groups'] as $group)
                        @foreach ($group['actions'] as $column)
                            <th class="fb-action fb-scope-{{ $group['scope'] }}" scope="col">
                                <span class="fb-action-label">{{ $column['label'] }}</span>
                                <code class="fb-action-name">{{ $column['action'] }}</code>
                            </th>
                        @endforeach
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($section['rows'] as $row)
                    <tr>
                        <th class="fb-subject" scope="row">
                            @if ($row['icon'] !== null)
                                <x-filament::icon :icon="$row['icon']" class="fb-subject-icon" />
                            @endif

                            <span class="fb-subject-text">
                                <span class="fb-subject-name">{{ $row['label'] }}</span>
                                @if ($row['policy'] !== null)
                                    <code class="fb-subject-policy">{{ $row['policy'] }}</code>
                                @endif
                            </span>

                            {{-- One shortcut per row, to the right of the name: in a column
                                 repeated once per subject, every extra control is paid for
                                 as many times as there are rows. Clearing belongs to the
                                 corner, which is pressed once. --}}
                            @unless ($disabled)
                                <span class="fb-shortcuts">
                                    @foreach ($presets as $preset)
                                        <button
                                            type="button"
                                            class="fb-shortcut"
                                            title="{{ $preset['label'] }} · {{ $row['label'] }}"
                                            x-on:click="apply(@js($row['key']), @js($preset['actions']), @js(array_keys($row['cells'])))"
                                        >{{ $preset['label'] }}</button>
                                    @endforeach
                                </span>
                            @endunless
                        </th>

                        @include('filament-bouncer::forms.ability-cell', [
                            'row' => $row,
                            'action' => $columns['manage']['action'],
                            'label' => $columns['manage']['label'],
                            'scope' => 'all',
                        ])

                        @foreach ($columns['groups'] as $group)
                            @foreach ($group['actions'] as $column)
                                @include('filament-bouncer::forms.ability-cell', [
                                    'row' => $row,
                                    'action' => $column['action'],
                                    'label' => $column['label'],
                                    'scope' => $group['scope'],
                                ])
                            @endforeach
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <ul class="fb-doors">
        @foreach ($section['rows'] as $row)
            <li class="fb-door">
                @include('filament-bouncer::forms.ability-cell', [
                    'row' => $row,
                    'action' => $row['action'],
                    'label' => $row['label'],
                    'scope' => 'read',
                    'bare' => true,
                ])
                <span class="fb-door-text">
                    <span class="fb-subject-name">{{ $row['label'] }}</span>
                    <code class="fb-action-name">{{ $row['action'] }}</code>
                </span>
            </li>
        @endforeach
    </ul>
@endif
