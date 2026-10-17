@props(['name'])

{{-- Brand icons come from Simple Icons (CC0); unknown platforms get a generic note. --}}
@php
    $platform = preg_replace('/[^a-z0-9]/', '', Str::lower($name));
    $iconPath = resource_path('svg/platforms/'.$platform.'.svg');
    // Full class names, so Tailwind finds them when scanning this file. TIDAL's brand black would vanish on the dark background, so it keeps the text color.
    $brandColor = [
        'spotify' => 'text-[#1ED760]',
        'deezer' => 'text-[#EF5466]',
        'youtubemusic' => 'text-[#FF0000]',
        'applemusic' => 'text-[#FA243C]',
        'soundcloud' => 'text-[#FF5500]',
        'bandcamp' => 'text-[#408294]',
    ][$platform] ?? null;
@endphp

@if (is_file($iconPath))
    {!! str_replace('<svg ', '<svg aria-hidden="true" '.$attributes->class($brandColor)->toHtml().' ', trim(file_get_contents($iconPath))) !!}
@else
    <x-heroicon-s-musical-note {{ $attributes }} />
@endif
