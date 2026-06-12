@extends('layouts.app')

@section('title', $venue->name)

@section('content')
<section class="section">
    <div class="container">
        <div class="breadcrumb"><a href="{{ route('venues.index') }}">Venues</a> / {{ $venue->city }}</div>

        <div class="page-head">
            <div class="page-head__eyebrow">{{ $venue->country_flag }} {{ $venue->city }}</div>
            <h1 class="page-head__title">{{ $venue->name }}</h1>
            <div class="team-hero__tags">
                @if ($venue->capacity)<span class="pill">🪑 {{ number_format($venue->capacity) }} capacity</span>@endif
                @if ($venue->timezone)<span class="pill">🕓 {{ $venue->timezone }}</span>@endif
                <span class="pill">⚽ {{ $fixtures->count() }} matches hosted</span>
            </div>
        </div>

        <div class="section__head"><h2 class="section__title">Matches at this venue</h2></div>

        <div class="grid grid--2">
            @forelse ($fixtures as $f)
                <a class="match-card" href="{{ route('fixtures.show', $f) }}">
                    <div class="match-card__top">
                        <span class="badge badge--stage">{{ $f->stage_label }}@if ($f->group) · {{ $f->group->name }}@endif</span>
                        @if ($f->is_live)
                            <span class="badge badge--live">LIVE</span>
                        @elseif ($f->is_finished)
                            <span class="badge badge--finished">FT</span>
                        @else
                            <span class="badge badge--scheduled">{{ $f->match_date->format('j M') }}</span>
                        @endif
                    </div>
                    <div class="match-card__teams">
                        <div class="match-card__team match-card__team--home">
                            <span class="match-card__flag">{{ $f->team1?->flag_emoji ?? '🏳️' }}</span>
                            <span class="match-card__name {{ $f->team1 ? '' : 'match-card__name--tbd' }}">{{ $f->team1_label }}</span>
                        </div>
                        <div class="match-card__mid">
                            @if ($f->has_score)
                                <span class="match-card__score">{{ $f->team1_score }} - {{ $f->team2_score }}</span>
                            @else
                                <span class="match-card__time">
                                    <span data-localtime="{{ $f->kickoff_at?->toIso8601String() }}">{{ $f->kickoff_at?->format('H:i') }} UTC</span>
                                </span>
                            @endif
                        </div>
                        <div class="match-card__team match-card__team--away">
                            <span class="match-card__flag">{{ $f->team2?->flag_emoji ?? '🏳️' }}</span>
                            <span class="match-card__name {{ $f->team2 ? '' : 'match-card__name--tbd' }}">{{ $f->team2_label }}</span>
                        </div>
                    </div>
                </a>
            @empty
                <div class="empty-state"><div class="empty-state__icon">🏟️</div><p>No matches scheduled here.</p></div>
            @endforelse
        </div>
    </div>
</section>
@endsection
