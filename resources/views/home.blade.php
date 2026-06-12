@extends('layouts.app')

@section('title', 'Home')
@section('meta_description', 'Live scores, fixtures, group standings and all 48 teams for the FIFA World Cup 2026 across the USA, Canada and Mexico.')

@section('content')

    {{-- 1) HERO --}}
    <section class="hero">
        <div class="container">
            <div class="hero__card">
                <div class="hero__label">⚽ NEXT KICK-OFF · {{ $featured->stage_label }}</div>

                <div class="hero__match">
                    <div class="hero__team hero__team--home">
                        <span class="hero__flag">{{ $featured->team1?->flag_emoji ?? '🏳️' }}</span>
                        <span class="hero__name">{{ $featured->team1_label }}</span>
                        @if($featured->team1)
                            <span class="hero__code">{{ $featured->team1->fifa_code }}</span>
                        @endif
                    </div>

                    <div class="hero__vs">VS</div>

                    <div class="hero__team hero__team--away">
                        <span class="hero__flag">{{ $featured->team2?->flag_emoji ?? '🏳️' }}</span>
                        <span class="hero__name">{{ $featured->team2_label }}</span>
                        @if($featured->team2)
                            <span class="hero__code">{{ $featured->team2->fifa_code }}</span>
                        @endif
                    </div>
                </div>

                <div class="hero__meta">
                    @if($featured->venue)
                        <span>📍 <b>{{ $featured->venue->name }}</b>, {{ $featured->venue->city }}</span>
                    @endif
                    <span>📅 <b>{{ $featured->match_date->format('D, j M Y') }}</b></span>
                    <span>🕑
                        <b><span data-localtime="{{ $featured->kickoff_at?->toIso8601String() }}">{{ $featured->kickoff_at?->format('H:i') }} UTC</span></b>
                        ({{ $featured->time_label }})
                    </span>
                </div>

                @if($featured->kickoff_at)
                    <div class="countdown" data-countdown="{{ $featured->kickoff_at->toIso8601String() }}">
                        <div class="countdown__unit">
                            <span class="countdown__num" data-cd="days">00</span>
                            <span class="countdown__label">Days</span>
                        </div>
                        <div class="countdown__unit">
                            <span class="countdown__num" data-cd="hours">00</span>
                            <span class="countdown__label">Hours</span>
                        </div>
                        <div class="countdown__unit">
                            <span class="countdown__num" data-cd="minutes">00</span>
                            <span class="countdown__label">Minutes</span>
                        </div>
                        <div class="countdown__unit">
                            <span class="countdown__num" data-cd="seconds">00</span>
                            <span class="countdown__label">Seconds</span>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- 2) STAT STRIP --}}
    <section class="section section--tight">
        <div class="container">
            <div class="stat-strip">
                <div class="stat">
                    <span class="stat__num">48</span>
                    <span class="stat__label">Teams</span>
                </div>
                <div class="stat">
                    <span class="stat__num">{{ $totalCount }}</span>
                    <span class="stat__label">Matches</span>
                </div>
                <div class="stat">
                    <span class="stat__num">{{ $playedCount }}/{{ $totalCount }}</span>
                    <span class="stat__label">Matches Played</span>
                </div>
                <div class="stat">
                    <span class="stat__num">12</span>
                    <span class="stat__label">Groups</span>
                </div>
                <div class="stat">
                    <span class="stat__num">16</span>
                    <span class="stat__label">Host Cities</span>
                </div>
                <div class="stat">
                    <span class="stat__num">3</span>
                    <span class="stat__label">Host Nations</span>
                </div>
            </div>
        </div>
    </section>

    {{-- 3) LIVE NOW --}}
    <section class="section">
        <div class="container">
            <div class="section__head">
                <h2 class="section__title">Live Now</h2>
                <a class="section__link" href="{{ route('fixtures.index') }}">View all scores →</a>
            </div>

            @forelse($liveMatches as $match)
                @if($loop->first)<div class="grid grid--2">@endif
                <a class="match-card" href="{{ route('fixtures.show', $match) }}">
                    <div class="match-card__top">
                        <span class="match-card__stage">{{ $match->stage_label }}</span>
                        <span class="match-card__status">
                            <span class="badge badge--live">● LIVE</span>
                        </span>
                    </div>
                    <div class="match-card__teams">
                        <div class="match-card__team match-card__team--home">
                            <span class="match-card__flag">{{ $match->team1?->flag_emoji ?? '🏳️' }}</span>
                            <span class="match-card__name {{ $match->team1 ? '' : 'match-card__name--tbd' }}">{{ $match->team1_label }}</span>
                        </div>
                        <div class="match-card__mid">
                            <span class="match-card__score">{{ $match->team1_score ?? 0 }} – {{ $match->team2_score ?? 0 }}</span>
                        </div>
                        <div class="match-card__team match-card__team--away">
                            <span class="match-card__flag">{{ $match->team2?->flag_emoji ?? '🏳️' }}</span>
                            <span class="match-card__name {{ $match->team2 ? '' : 'match-card__name--tbd' }}">{{ $match->team2_label }}</span>
                        </div>
                    </div>
                    <div class="match-card__meta">
                        @if($match->venue)<span>{{ $match->venue->name }}, {{ $match->venue->city }}</span>@endif
                        @if($match->group)<span>{{ $match->group->name }}</span>@endif
                    </div>
                </a>
                @if($loop->last)</div>@endif
            @empty
                <div class="empty-state">
                    <div class="empty-state__icon">⚽</div>
                    <p>No matches live right now</p>
                    <p class="muted">Check back during kick-off for live scores and updates.</p>
                </div>
            @endforelse
        </div>
    </section>

    {{-- 4) UPCOMING + LEADERBOARDS --}}
    <section class="section">
        <div class="container">
            <div class="grid grid--sidebar">

                {{-- LEFT: Upcoming Matches --}}
                <div>
                    <div class="section__head">
                        <h2 class="section__title">Upcoming Matches</h2>
                        <a class="section__link" href="{{ route('fixtures.index') }}">All fixtures →</a>
                    </div>

                    @forelse($upcoming as $f)
                        <a class="match-card" href="{{ route('fixtures.show', $f) }}">
                            <div class="match-card__top">
                                <span class="match-card__stage">{{ $f->stage_label }}</span>
                                <span class="match-card__status">
                                    @if($f->group)
                                        <span class="badge badge--group">{{ $f->group->name }}</span>
                                    @else
                                        <span class="badge badge--stage">{{ $f->stage_label }}</span>
                                    @endif
                                </span>
                            </div>
                            <div class="match-card__teams">
                                <div class="match-card__team match-card__team--home">
                                    <span class="match-card__flag">{{ $f->team1?->flag_emoji ?? '🏳️' }}</span>
                                    <span class="match-card__name {{ $f->team1 ? '' : 'match-card__name--tbd' }}">{{ $f->team1_label }}</span>
                                </div>
                                <div class="match-card__mid">
                                    <span class="match-card__time">
                                        <span data-localtime="{{ $f->kickoff_at?->toIso8601String() }}">{{ $f->kickoff_at?->format('H:i') }} UTC</span>
                                        <small>{{ $f->match_date->format('D, j M') }}</small>
                                    </span>
                                </div>
                                <div class="match-card__team match-card__team--away">
                                    <span class="match-card__flag">{{ $f->team2?->flag_emoji ?? '🏳️' }}</span>
                                    <span class="match-card__name {{ $f->team2 ? '' : 'match-card__name--tbd' }}">{{ $f->team2_label }}</span>
                                </div>
                            </div>
                            <div class="match-card__meta">
                                @if($f->venue)<span>{{ $f->venue->name }}, {{ $f->venue->city }}</span>@endif
                            </div>
                        </a>
                    @empty
                        <div class="empty-state">
                            <div class="empty-state__icon">📅</div>
                            <p>No upcoming matches scheduled</p>
                        </div>
                    @endforelse
                </div>

                {{-- RIGHT: Leaderboards --}}
                <aside>
                    {{-- Top Scorers --}}
                    <div class="standings-card">
                        <div class="standings-card__head">
                            <h3 class="standings-card__title">Top Scorers</h3>
                        </div>
                        @forelse($topScorers as $i => $player)
                            @if($loop->first)<div class="leaderboard">@endif
                            <div class="leaderboard__item">
                                <span class="leaderboard__rank {{ $loop->iteration === 1 ? 'leaderboard__rank--1' : '' }}">{{ $loop->iteration }}</span>
                                <span class="leaderboard__flag">{{ $player->team?->flag_emoji ?? '🏳️' }}</span>
                                <span class="leaderboard__who">
                                    <span class="leaderboard__player">{{ $player->name }}</span>
                                    <span class="leaderboard__team">{{ $player->team?->display_name }}</span>
                                </span>
                                <span class="leaderboard__value">{{ $player->goals }}</span>
                            </div>
                            @if($loop->last)</div>@endif
                        @empty
                            <div class="empty-state">
                                <div class="empty-state__icon">⚽</div>
                                <p class="muted">No data yet — tournament in progress</p>
                            </div>
                        @endforelse
                    </div>

                    {{-- Top Assists --}}
                    <div class="standings-card">
                        <div class="standings-card__head">
                            <h3 class="standings-card__title">Top Assists</h3>
                        </div>
                        @forelse($topAssists as $player)
                            @if($loop->first)<div class="leaderboard">@endif
                            <div class="leaderboard__item">
                                <span class="leaderboard__rank {{ $loop->iteration === 1 ? 'leaderboard__rank--1' : '' }}">{{ $loop->iteration }}</span>
                                <span class="leaderboard__flag">{{ $player->team?->flag_emoji ?? '🏳️' }}</span>
                                <span class="leaderboard__who">
                                    <span class="leaderboard__player">{{ $player->name }}</span>
                                    <span class="leaderboard__team">{{ $player->team?->display_name }}</span>
                                </span>
                                <span class="leaderboard__value">{{ $player->assists }}</span>
                            </div>
                            @if($loop->last)</div>@endif
                        @empty
                            <div class="empty-state">
                                <div class="empty-state__icon">🅰️</div>
                                <p class="muted">No data yet — tournament in progress</p>
                            </div>
                        @endforelse
                    </div>

                    {{-- Player Ratings --}}
                    <div class="standings-card">
                        <div class="standings-card__head">
                            <h3 class="standings-card__title">Player Ratings</h3>
                        </div>
                        @forelse($topRated as $player)
                            @if($loop->first)<div class="leaderboard">@endif
                            <div class="leaderboard__item">
                                <span class="leaderboard__rank {{ $loop->iteration === 1 ? 'leaderboard__rank--1' : '' }}">{{ $loop->iteration }}</span>
                                <span class="leaderboard__flag">{{ $player->team?->flag_emoji ?? '🏳️' }}</span>
                                <span class="leaderboard__who">
                                    <span class="leaderboard__player">{{ $player->name }}</span>
                                    <span class="leaderboard__team">{{ $player->team?->display_name }}</span>
                                </span>
                                <span class="leaderboard__value">{{ $player->rating }}</span>
                            </div>
                            @if($loop->last)</div>@endif
                        @empty
                            <div class="empty-state">
                                <div class="empty-state__icon">⭐</div>
                                <p class="muted">No data yet — tournament in progress</p>
                            </div>
                        @endforelse
                    </div>
                </aside>

            </div>
        </div>
    </section>

    {{-- 5) GROUP STANDINGS PREVIEW --}}
    <section class="section">
        <div class="container">
            <div class="section__head">
                <h2 class="section__title">Group Standings</h2>
                <a class="section__link" href="{{ route('standings.index') }}">Full standings →</a>
            </div>

            @forelse($standings as $entry)
                @if($loop->first)<div class="grid grid--auto">@endif
                <div class="standings-card">
                    <div class="standings-card__head">
                        <h3 class="standings-card__title">{{ $entry['group']->name }}</h3>
                    </div>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Team</th>
                                <th>P</th>
                                <th>GD</th>
                                <th>Pts</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($entry['rows'] as $row)
                                <tr class="{{ $row->qualifying ? 'is-qualifying' : '' }}">
                                    <td><span class="pos {{ $row->rank <= 2 ? 'pos--qual' : '' }}">{{ $row->rank }}</span></td>
                                    <td>
                                        <span class="team-cell">
                                            <span class="team-cell__flag">{{ $row->team?->flag_emoji ?? '🏳️' }}</span>
                                            <span class="team-cell__name">{{ $row->team?->fifa_code ?? $row->team?->display_name }}</span>
                                        </span>
                                    </td>
                                    <td class="num">{{ $row->played }}</td>
                                    <td class="num">{{ $row->gd > 0 ? '+'.$row->gd : $row->gd }}</td>
                                    <td class="pts">{{ $row->points }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($loop->last)</div>@endif
            @empty
                <div class="empty-state">
                    <div class="empty-state__icon">🏆</div>
                    <p>Standings will appear once groups are drawn.</p>
                </div>
            @endforelse
        </div>
    </section>

    {{-- 6) QUICK ACTIONS --}}
    <section class="section">
        <div class="container">
            <div class="section__head">
                <h2 class="section__title">Explore</h2>
            </div>

            <div class="quick-actions">
                @foreach($quickActions as $action)
                    <a class="quick-action" href="{{ $action['url'] }}">
                        <span class="quick-action__icon">{{ $action['icon'] }}</span>
                        <span class="quick-action__label">{{ $action['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

@endsection
