{{-- A row's tags. The word of the stance goes in the title because the colour does not
     say it to anybody who cannot tell them apart. --}}
<span class="fb-tg-tags">
    @foreach ($tags as $tag)
        <span class="fb-tg-tag fb-tg-tag-{{ $tag['stance'] }}" title="{{ $labels[$tag['stance']] ?? $tag['stance'] }}">
            {{ $tag['label'] }}
            <code>{{ $tag['action'] }}</code>
        </span>
    @endforeach
</span>
