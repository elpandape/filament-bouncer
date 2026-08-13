{{-- The warning at the width of the narrow column: what it is, what is going, and why.
     It is drawn when there is nothing to lose as well, because blank space reads as if
     nobody had looked. --}}
<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">
    <div class="fb fb-or @if ($isClean()) fb-or-clean @endif">
        <p class="fb-or-head">{{ $getHeadline() }}</p>

        @foreach ($getGroups() as $group)
            <div class="fb-or-group">
                <p class="fb-or-group-name">{{ $group['subject'] }}</p>

                <div class="fb-or-tags">
                    @foreach ($group['actions'] as $action)
                        <code class="fb-or-tag">{{ $action }}</code>
                    @endforeach
                </div>
            </div>
        @endforeach

        <p class="fb-or-note">{{ $getNote() }}</p>
    </div>
</x-dynamic-component>
