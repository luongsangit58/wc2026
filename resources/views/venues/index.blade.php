@extends('layouts.app')

@section('title', 'Venues')
@section('meta_description', 'The 16 host stadiums of the FIFA World Cup 2026 across the United States, Canada and Mexico.')

@section('content')
<section class="section">
    <div class="container">
        <div class="page-head">
            <div class="page-head__eyebrow">Host Cities</div>
            <h1 class="page-head__title">Venues</h1>
            <p class="page-head__sub">{{ $venues->count() }} stadiums across the United States, Canada and Mexico host all 104 matches.</p>
        </div>

        <div class="stat-strip" style="margin-bottom: 26px">
            <div class="stat"><div class="stat__num">{{ $venues->count() }}</div><div class="stat__label">Stadiums</div></div>
            <div class="stat"><div class="stat__num">{{ number_format($totalCapacity) }}</div><div class="stat__label">Total Capacity</div></div>
            <div class="stat"><div class="stat__num">3</div><div class="stat__label">Host Nations</div></div>
            <div class="stat"><div class="stat__num">104</div><div class="stat__label">Matches</div></div>
        </div>

        <div class="grid grid--auto">
            @foreach ($venues as $venue)
                <a class="venue-card" href="{{ route('venues.show', $venue) }}">
                    <span class="venue-card__name">{{ $venue->country_flag }} {{ $venue->name }}</span>
                    <span class="venue-card__city">{{ $venue->city }}</span>
                    <div class="venue-card__stats">
                        <span><b>{{ $venue->capacity ? number_format($venue->capacity) : '—' }}</b> seats</span>
                        <span><b>{{ $venue->fixtures_count }}</b> matches</span>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endsection
