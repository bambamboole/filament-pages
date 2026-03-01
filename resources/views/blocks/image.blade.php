@php
    /** @var ?\Spatie\MediaLibrary\MediaCollections\Models\Media $image */
    /** @var string $alt */
@endphp
@if($image)
    {{ $image->img('', ['alt' => $alt ?? '']) }}
@endif
