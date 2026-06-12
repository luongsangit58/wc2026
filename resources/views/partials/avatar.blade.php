@php $size = $size ?? ''; @endphp
@if ($player->has_photo)
    <img class="avatar {{ $size }}" src="{{ $player->photo_url }}" alt="{{ $player->name }}" loading="lazy">
@else
    <span class="avatar avatar--initials {{ $size }}" aria-hidden="true">{{ $player->initials }}</span>
@endif
