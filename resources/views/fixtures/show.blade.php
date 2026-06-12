@extends('layouts.app')

@section('title', $fixture->team1_label . ' vs ' . $fixture->team2_label)

@php
    $positions = ['GK' => 'Goalkeepers', 'DF' => 'Defenders', 'MF' => 'Midfielders', 'FW' => 'Forwards'];
@endphp

@section('content')
<section class="match-hero">
    <div class="container">
        <div class="breadcrumb">
            <a href="{{ route('fixtures.index') }}">Fixtures</a> / {{ $fixture->round_label }}
        </div>

        <div class="match-hero__card">
            <div class="match-hero__top">
                <span class="badge badge--stage">{{ $fixture->stage_label }}@if ($fixture->group) · {{ $fixture->group->name }}@endif</span>
                @if ($fixture->is_live)
                    <span class="badge badge--live">LIVE @if ($fixture->live_minute)· {{ $fixture->live_minute }}'@endif</span>
                @elseif ($fixture->is_finished)
                    <span class="badge badge--finished">Full time</span>
                @else
                    <span class="badge badge--scheduled">Upcoming</span>
                @endif
            </div>

            <div class="match-hero__grid">
                <div class="match-hero__team">
                    <span class="match-hero__flag">{{ $fixture->team1?->flag_emoji ?? '🏳️' }}</span>
                    @if ($fixture->team1)
                        <a class="match-hero__name" href="{{ route('teams.show', $fixture->team1) }}">{{ $fixture->team1->display_name }}</a>
                    @else
                        <span class="match-hero__name match-hero__name--tbd">{{ $fixture->team1_label }}</span>
                    @endif
                </div>

                <div class="match-hero__center">
                    @if ($fixture->has_score)
                        <div class="match-hero__score">{{ $fixture->team1_score }}<span class="match-hero__sep">–</span>{{ $fixture->team2_score }}</div>
                    @else
                        <div class="match-hero__vs">VS</div>
                    @endif
                    <span class="match-hero__kickoff">
                        <span data-localtime="{{ $fixture->kickoff_at?->toIso8601String() }}">{{ $fixture->kickoff_at?->format('H:i') }} UTC</span>
                    </span>
                </div>

                <div class="match-hero__team">
                    <span class="match-hero__flag">{{ $fixture->team2?->flag_emoji ?? '🏳️' }}</span>
                    @if ($fixture->team2)
                        <a class="match-hero__name" href="{{ route('teams.show', $fixture->team2) }}">{{ $fixture->team2->display_name }}</a>
                    @else
                        <span class="match-hero__name match-hero__name--tbd">{{ $fixture->team2_label }}</span>
                    @endif
                </div>
            </div>

            <div class="match-hero__meta">
                <span>📅 <b>{{ $fixture->match_date->format('l, j F Y') }}</b></span>
                <span>🕘 <b>{{ $fixture->time_label }}</b></span>
                @if ($fixture->venue)
                    <span>📍 <a href="{{ route('venues.show', $fixture->venue) }}"><b>{{ $fixture->venue->name }}</b>, {{ $fixture->venue->city }}</a></span>
                @endif
                @if ($fixture->num)<span>Match <b>#{{ $fixture->num }}</b></span>@endif
            </div>
        </div>
    </div>
</section>

@if ($groupRows->isNotEmpty())
    <section class="section section--tight">
        <div class="container">
            <div class="standings-card">
                <div class="standings-card__head">
                    <span class="standings-card__title">{{ $fixture->group->name }} table</span>
                </div>
                <table class="table">
                    <thead>
                        <tr><th>#</th><th>Team</th><th>P</th><th>W</th><th>D</th><th>L</th><th>GF</th><th>GA</th><th>GD</th><th>Pts</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($groupRows as $row)
                            @php $isThis = in_array($row->team->id, [$fixture->team1_id, $fixture->team2_id], true); @endphp
                            <tr @class(['is-qualifying' => $row->qualifying])>
                                <td><span class="pos {{ $row->qualifying ? 'pos--qual' : '' }}">{{ $row->rank }}</span></td>
                                <td>
                                    <a class="team-cell" href="{{ route('teams.show', $row->team) }}">
                                        <span class="team-cell__flag">{{ $row->team->flag_emoji }}</span>
                                        <span class="team-cell__name">{{ $row->team->display_name }}@if ($isThis) <span class="muted">●</span>@endif</span>
                                    </a>
                                </td>
                                <td class="num">{{ $row->played }}</td>
                                <td class="num">{{ $row->won }}</td>
                                <td class="num">{{ $row->drawn }}</td>
                                <td class="num">{{ $row->lost }}</td>
                                <td class="num">{{ $row->gf }}</td>
                                <td class="num">{{ $row->ga }}</td>
                                <td class="num">{{ $row->gd > 0 ? '+' . $row->gd : $row->gd }}</td>
                                <td class="pts">{{ $row->points }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endif

@if ($squad1->isNotEmpty() || $squad2->isNotEmpty())
    <section class="section">
        <div class="container">
            <div class="section__head"><h2 class="section__title">Squads</h2></div>
            <div class="lineups">
                @foreach ([[$fixture->team1, $squad1], [$fixture->team2, $squad2]] as [$team, $squad])
                    <div>
                        @if ($team)
                            <div class="lineup__head"><span class="flag">{{ $team->flag_emoji }}</span> {{ $team->display_name }}</div>
                            @foreach ($positions as $code => $label)
                                @if (($squad[$code] ?? collect())->isNotEmpty())
                                    <div class="squad-group">
                                        <div class="squad-group__label">{{ $label }}</div>
                                        <div class="squad-list">
                                            @foreach ($squad[$code] as $p)
                                                <div class="player">
                                                    <span class="player__num">{{ $p->number }}</span>
                                                    <span>
                                                        <span class="player__name">{{ $p->name }}</span>
                                                        @if ($p->goals)<span class="player__pos">· {{ $p->goals }}⚽</span>@endif
                                                    </span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        @else
                            <div class="empty-state"><div class="empty-state__icon">⏳</div><p>Team to be decided</p></div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif
@endsection
