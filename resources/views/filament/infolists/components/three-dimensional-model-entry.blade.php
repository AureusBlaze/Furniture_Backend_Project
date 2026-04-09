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
            <script type="module" src="https://ajax.googleapis.com/ajax/libs/model-viewer/4.0.0/model-viewer.min.js"></script>
            <model-viewer 
                src="{{ $url }}" 
                alt="3D Model Preview" 
                auto-rotate 
                camera-controls 
                style="width: 100%; height: 400px; background-color: #888; border-radius: 0.5rem;"
            ></model-viewer>
        @else
            <div class="text-gray-500 italic">No 3D model uploaded yet.</div>
        @endif
    </div>
</x-dynamic-component>
