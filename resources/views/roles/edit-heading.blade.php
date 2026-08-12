@php
    /*
     * The header's one line of facts. Blank pieces fall away rather than leaving a
     * dangling separator: a role with no title starts at the count, and a record that
     * somehow has no timestamp ends there.
     */
    $facts = array_filter([
        $title,
        trans_choice('filament-bouncer::roles.edit.holders', $holders, ['count' => $holders]),
        $updated === null ? null : __('filament-bouncer::roles.edit.updated', ['when' => $updated->diffForHumans()]),
    ]);
@endphp

<div class="fb-edit-heading">
    <p class="fb-edit-heading-facts">{{ implode(' · ', $facts) }}</p>

    <div class="fb-edit-heading-cov">
        @include('filament-bouncer::roles.coverage', ['coverage' => $coverage])
    </div>
</div>
