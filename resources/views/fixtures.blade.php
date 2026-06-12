@extends('layouts.app')

@section('title', 'Fixtures')
@section('meta_description', 'All FIFA World Cup 2026 fixtures and match schedule — group stage and knockout rounds across the USA, Canada and Mexico, June 11 to July 19, 2026.')

@section('content')
<section class="section">
    <div class="container">
        <div class="page-head">
            <div class="page-head__eyebrow">FIFA World Cup 2026</div>
            <h1 class="page-head__title">Fixtures</h1>
            <p class="page-head__sub">All {{ $totalMatches }} matches · June 11 – July 19, 2026</p>
        </div>

        <div data-auto-filter>
            <form method="GET" action="{{ route('fixtures.index') }}" class="filters">
                <div class="filters__group">
                    <label class="filters__label" for="filter-stage">Stage</label>
                    <select class="select" name="stage" id="filter-stage">
                        <option value="">All stages</option>
                        @foreach ($stages as $key => $label)
                            <option value="{{ $key }}" @selected(($filters['stage'] ?? null) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="filters__group">
                    <label class="filters__label" for="filter-group">Group</label>
                    <select class="select" name="group" id="filter-group">
                        <option value="">All groups</option>
                        @foreach ($groups as $g)
                            <option value="{{ $g->letter }}" @selected(($filters['group'] ?? null) === $g->letter)>{{ $g->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="filters__group">
                    <label class="filters__label" for="filter-date">Date</label>
                    <select class="select" name="date" id="filter-date">
                        <option value="">All dates</option>
                        @foreach ($dates as $d)
                            <option value="{{ $d }}" @selected(($filters['date'] ?? null) === $d)>{{ \Illuminate\Support\Carbon::parse($d)->format('D, j M') }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="filters__group">
                    <label class="filters__label" for="filter-city">City</label>
                    <select class="select" name="city" id="filter-city">
                        <option value="">All cities</option>
                        @foreach ($venues as $v)
                            <option value="{{ $v->slug }}" @selected(($filters['city'] ?? null) === $v->slug)>{{ $v->city }}</option>
                        @endforeach
                    </select>
                </div>

                <a class="btn btn--ghost btn--sm filters__reset" href="{{ route('fixtures.index') }}">Reset</a>

                <noscript>
                    <button type="submit" class="btn btn--primary btn--sm">Apply</button>
                </noscript>
            </form>
        </div>

        @forelse ($fixturesByDate as $date => $dayFixtures)
            <div class="date-head">
                <span class="date-head__day">{{ \Illuminate\Support\Carbon::parse($date)->format('l, j F Y') }}</span>
                <span class="date-head__count">{{ $dayFixtures->count() }} {{ \Illuminate\Support\Str::plural('match', $dayFixtures->count()) }}</span>
            </div>

            <div class="grid grid--2">
                @foreach ($dayFixtures as $f)
                    <a class="match-card" href="{{ route('fixtures.show', $f) }}">
                        <div class="match-card__top">
                            <span class="badge badge--stage">
                                {{ $f->stage_label }}@if ($f->group) · {{ $f->group->name }}@endif
                            </span>

                            @if ($f->is_live)
                                <span class="badge badge--live">LIVE</span>
                            @elseif ($f->is_finished)
                                <span class="badge badge--finished">FT</span>
                            @else
                                <span class="badge badge--scheduled">{{ $f->round_label ?? 'Upcoming' }}</span>
                            @endif
                        </div>

                        <div class="match-card__teams">
                            <div class="match-card__team match-card__team--home">
                                <span class="match-card__flag">{{ $f->team1?->flag_emoji ?? '🏳️' }}</span>
                                <span class="match-card__name {{ $f->team1 ? '' : 'match-card__name--tbd' }}">{{ $f->team1_label }}</span>
                                @if ($f->team1?->fifa_code)
                                    <span class="team-row__code">{{ $f->team1->fifa_code }}</span>
                                @endif
                            </div>

                            <div class="match-card__mid">
                                @if ($f->is_finished)
                                    <span class="match-card__score">{{ $f->team1_score }} - {{ $f->team2_score }}</span>
                                @else
                                    <span class="match-card__time">
                                        <span data-localtime="{{ $f->kickoff_at?->toIso8601String() }}">{{ $f->kickoff_at?->format('H:i') }} UTC</span>
                                        <small>{{ $f->time_label }}</small>
                                    </span>
                                @endif
                            </div>

                            <div class="match-card__team match-card__team--away">
                                <span class="match-card__flag">{{ $f->team2?->flag_emoji ?? '🏳️' }}</span>
                                <span class="match-card__name {{ $f->team2 ? '' : 'match-card__name--tbd' }}">{{ $f->team2_label }}</span>
                                @if ($f->team2?->fifa_code)
                                    <span class="team-row__code">{{ $f->team2->fifa_code }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="match-card__meta">
                            @if ($f->venue)
                                <span>{{ $f->venue->country_flag }} {{ $f->venue->name }}, {{ $f->venue->city }}</span>
                            @endif
                            @if ($f->num)
                                <span>#{{ $f->num }}</span>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        @empty
            <div class="empty-state">
                <div class="empty-state__icon">⚽</div>
                <p>No matches match these filters.</p>
                <a class="btn btn--primary btn--sm" href="{{ route('fixtures.index') }}">Reset filters</a>
            </div>
        @endforelse
    </div>
</section>
@endsection
