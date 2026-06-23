@php
    $state = $getState();
    $url = $state ? \Illuminate\Support\Facades\Storage::disk('public')->url($state) : null;
@endphp

<x-dynamic-component
    :component="$getEntryWrapperView()"
    :entry="$entry"
>
    <div {{ $getExtraAttributeBag() }}>
        @if($url)
            <div class="italic">{{ $url }}</div>
        @else
            <div class="text-gray-500 italic">No 3D model uploaded yet.</div>
        @endif
    </div>
</x-dynamic-component>
