{{--
    One cell, three stances and two warnings.

    The gap and the empty box say different things and so they do not look alike: an empty
    box is a role abstaining, the dot is an action its policy does not declare and that
    nobody can therefore grant or forbid.

    And there is a fourth drawing which is not a stance: the hollow tick on an unmarked
    box, saying the role answers yes there through a broader rule — the wildcard, for
    one — without holding a row that names it. Without it, a founder reads as a role that
    can do nothing.

    The stance is one control that cycles rather than three buttons side by side: seven
    columns times three would be twenty-one controls a row. The cost is the one the three
    buttons were there to avoid — reaching "forbidden" by passing through "granted", a
    rule that exists on screen for a moment and never on purpose — and it is paid off by
    Shift, which walks the cycle backwards and reaches the denial in one step.

    What the cell is gets composed here and enters the Alpine expression already made.
    Building it inside with PHP's dot concatenates nothing there: it lands in the attribute
    as written and the browser reads the rest as broken JavaScript, once per cell, and no
    server-side test notices.
--}}
@php
    $describes = $row['label'].' · '.$label;
    $bare ??= false;
    $reached = $broader[$row['key']][$action] ?? false;
    $note = $notes[$row['key']][$action] ?? null;
    $says = $note === null ? '' : ' · '.$note;
@endphp

@if (! isset($row['cells'][$action]))
    <td class="fb-cell fb-cell-void fb-scope-{{ $scope }}">
        <span class="fb-void" aria-hidden="true">·</span>
        <span class="fb-sr">{{ $describes }} · {{ __('filament-bouncer::roles.grid.undeclared') }}</span>
    </td>
@else
    @unless ($bare)
        <td class="fb-cell fb-scope-{{ $scope }}">
    @endunless

    <button
        type="button"
        @disabled($disabled)
        class="fb-box"
        data-entity="{{ $row['key'] }}"
        data-action="{{ $action }}"
        x-on:click="cycle(@js($row['key']), @js($action), $event.shiftKey)"
        x-bind:class="'fb-box-' + at(@js($row['key']), @js($action)) + (inherits(@js($row['key']), @js($action), @js($reached)) ? ' fb-box-broader fb-box-broader-' + inherits(@js($row['key']), @js($action), @js($reached)) : '')"
        x-bind:title="@js($describes) + ' · ' + says(@js($row['key']), @js($action)) + saysBroader(inherits(@js($row['key']), @js($action), @js($reached))) + @js($says)"
        x-bind:aria-label="@js($describes) + ' · ' + says(@js($row['key']), @js($action)) + saysBroader(inherits(@js($row['key']), @js($action), @js($reached))) + @js($says)"
    >
        @if ($note !== null)
            <span class="fb-narrowed" aria-hidden="true"></span>
        @endif

        <svg
            x-show="at(@js($row['key']), @js($action)) === 'granted' || inherits(@js($row['key']), @js($action), @js($reached)) === 'granted'"
            x-cloak
            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"
            stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"
        ><path d="M20 6 9 17l-5-5" /></svg>

        <svg
            x-show="at(@js($row['key']), @js($action)) === 'forbidden' || inherits(@js($row['key']), @js($action), @js($reached)) === 'forbidden'"
            x-cloak
            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"
            stroke-linecap="round" aria-hidden="true"
        ><path d="M18 6 6 18" /><path d="m6 6 12 12" /></svg>
    </button>

    @unless ($bare)
        </td>
    @endunless
@endif
