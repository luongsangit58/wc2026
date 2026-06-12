@extends('layouts.app')

@section('title', 'Knockout Bracket')
@section('meta_description', 'The FIFA World Cup 2026 knockout bracket: Round of 32 through to the Final on July 19 in New York/New Jersey.')

@php
    $labels = [
        'round_of_32' => 'Round of 32',
        'round_of_16' => 'Round of 16',
        'quarter_final' => 'Quarter-finals',
        'semi_final' => 'Semi-finals',
        'final' => 'Final',
    ];
@endphp

@section('content')
<section class="section">
    <div class="container">
        <div class="page-head">
            <div class="page-head__eyebrow">Knockout Stage</div>
            <h1 class="page-head__title">Bracket</h1>
            <p class="page-head__sub">32 teams, single elimination — from the Round of 32 (June 28) to the Final on July 19, 2026.</p>
        </div>

        <div class="bracket">
            @foreach ($stageOrder as $stage)
                <div class="bracket__round">
                    <div class="bracket__round-title">{{ $labels[$stage] ?? $stage }}</div>
                    @forelse ($rounds[$stage] ?? [] as $f)
                        @php
                            $t1win = $f->is_finished && $f->team1_score > $f->team2_score;
                            $t2win = $f->is_finished && $f->team2_score > $f->team1_score;
                        @endphp
                        <a class="bracket__match" href="{{ route('fixtures.show', $f) }}">
                            <div class="bracket__team {{ $f->team1 ? '' : 'bracket__team--tbd' }} {{ $t1win ? 'bracket__team--winner' : '' }}">
                                <span class="bracket__team-flag">{{ $f->team1?->flag_emoji ?? '🏳️' }}</span>
                                <span class="bracket__team-name">{{ $f->team1_label }}</span>
                                <span class="bracket__score">{{ $f->has_score ? $f->team1_score : '' }}</span>
                            </div>
                            <div class="bracket__team {{ $f->team2 ? '' : 'bracket__team--tbd' }} {{ $t2win ? 'bracket__team--winner' : '' }}">
                                <span class="bracket__team-flag">{{ $f->team2?->flag_emoji ?? '🏳️' }}</span>
                                <span class="bracket__team-name">{{ $f->team2_label }}</span>
                                <span class="bracket__score">{{ $f->has_score ? $f->team2_score : '' }}</span>
                            </div>
                            <div class="bracket__meta">
                                <span>{{ $f->match_date->format('j M') }}</span>
                                @if ($f->venue)<span>{{ $f->venue->city }}</span>@endif
                            </div>
                        </a>
                    @empty
                        <p class="muted">TBD</p>
                    @endforelse
                </div>
            @endforeach
        </div>

        @if ($thirdPlace)
            <div class="bracket-final">
                <div class="section__head"><h2 class="section__title">Third-place play-off</h2></div>
                <div class="grid grid--2">
                    <a class="match-card" href="{{ route('fixtures.show', $thirdPlace) }}">
                        <div class="match-card__top">
                            <span class="badge badge--stage">Third Place</span>
                            <span class="badge badge--scheduled">{{ $thirdPlace->match_date->format('j M') }}</span>
                        </div>
                        <div class="match-card__teams">
                            <div class="match-card__team match-card__team--home">
                                <span class="match-card__flag">{{ $thirdPlace->team1?->flag_emoji ?? '🏳️' }}</span>
                                <span class="match-card__name {{ $thirdPlace->team1 ? '' : 'match-card__name--tbd' }}">{{ $thirdPlace->team1_label }}</span>
                            </div>
                            <div class="match-card__mid">
                                @if ($thirdPlace->has_score)
                                    <span class="match-card__score">{{ $thirdPlace->team1_score }} - {{ $thirdPlace->team2_score }}</span>
                                @else
                                    <span class="match-card__time"><span data-localtime="{{ $thirdPlace->kickoff_at?->toIso8601String() }}">{{ $thirdPlace->kickoff_at?->format('H:i') }} UTC</span></span>
                                @endif
                            </div>
                            <div class="match-card__team match-card__team--away">
                                <span class="match-card__flag">{{ $thirdPlace->team2?->flag_emoji ?? '🏳️' }}</span>
                                <span class="match-card__name {{ $thirdPlace->team2 ? '' : 'match-card__name--tbd' }}">{{ $thirdPlace->team2_label }}</span>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        @endif
    </div>
</section>
@endsection
