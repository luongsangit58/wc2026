@extends('layouts.app')

@section('title', $team->display_name)
@section('meta_description', $team->display_name . ' at the FIFA World Cup 2026 — squad, fixtures, group standings, confederation and FIFA code.')

@section('content')
<section class="section">
    <div class="container">
        <nav class="breadcrumb">
            <a href="{{ route('teams.index') }}">Teams</a>
            <span aria-hidden="true">/</span>
            <span>{{ $team->display_name }}</span>
        </nav>

        <div class="team-hero">
            <span class="team-hero__flag">{{ $team->flag_emoji }}</span>
            <div>
                <h1 class="team-hero__name">{{ $team->display_name }}</h1>
                <div class="team-hero__tags">
                    @if($team->group)
                        <span class="pill">{{ $team->group->name }}</span>
                    @endif
                    @if($team->confederation)
                        <span class="pill">{{ $team->confederation }}</span>
                    @endif
                    @if($team->continent)
                        <span class="pill">{{ $team->continent }}</span>
                    @endif
                    <span class="pill">FIFA: {{ $team->fifa_code }}</span>
                    <span class="pill">{{ $team->players->count() }} players</span>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section section--tight">
    <div class="container">
        <div class="grid grid--sidebar">
            <div>
                <div class="section__head">
                    <h2 class="section__title">Matches</h2>
                </div>

                @forelse($fixtures as $f)
                    @php
                        $href = $f->venue ? null : null;
                    @endphp
                    <article class="match-card">
                        <div class="match-card__top">
                            @if($f->group)
                                <span class="match-card__stage badge badge--group">{{ $f->group->name }}</span>
                            @else
                                <span class="match-card__stage badge badge--stage">{{ $f->stage_label }}</span>
                            @endif
                            <span class="match-card__status">
                                @if($f->is_live)
                                    <span class="badge badge--live">Live</span>
                                @elseif($f->is_finished)
                                    <span class="badge badge--finished">Finished</span>
                                @else
                                    <span class="badge badge--scheduled">Scheduled</span>
                                @endif
                            </span>
                        </div>

                        <div class="match-card__teams">
                            <div class="match-card__team match-card__team--home">
                                <span class="match-card__flag">{{ $f->team1?->flag_emoji ?? '🏳️' }}</span>
                                <span class="match-card__name @if($f->team1 && $f->team1->id === $team->id) accent @endif @unless($f->team1) match-card__name--tbd @endunless">{{ $f->team1_label }}</span>
                            </div>

                            <div class="match-card__mid">
                                @if($f->is_finished && $f->team1_score !== null && $f->team2_score !== null)
                                    <span class="match-card__score">{{ $f->team1_score }} – {{ $f->team2_score }}</span>
                                @else
                                    <span class="match-card__time">
                                        <span data-localtime="{{ $f->kickoff_at?->toIso8601String() }}">{{ $f->kickoff_at?->format('H:i') ?? '—' }}</span>
                                        <small>{{ $f->time_label }}</small>
                                    </span>
                                @endif
                            </div>

                            <div class="match-card__team match-card__team--away">
                                <span class="match-card__flag">{{ $f->team2?->flag_emoji ?? '🏳️' }}</span>
                                <span class="match-card__name @if($f->team2 && $f->team2->id === $team->id) accent @endif @unless($f->team2) match-card__name--tbd @endunless">{{ $f->team2_label }}</span>
                            </div>
                        </div>

                        <div class="match-card__meta">
                            <span>{{ $f->match_date->format('D, j M') }}</span>
                            @if($f->venue)
                                <span>{{ $f->venue->name }}</span>
                                <span>{{ $f->venue->city }}</span>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="empty-state">
                        <span class="empty-state__icon">⚽</span>
                        <p>No fixtures scheduled.</p>
                    </div>
                @endforelse

                <div class="section__head">
                    <h2 class="section__title">Squad</h2>
                </div>

                @php
                    $positionLabels = ['GK' => 'Goalkeepers', 'DF' => 'Defenders', 'MF' => 'Midfielders', 'FW' => 'Forwards'];
                @endphp

                @forelse($squadByPosition as $pos => $players)
                    <div class="squad-group">
                        <h3 class="squad-group__label">{{ $positionLabels[$pos] ?? $pos }}</h3>
                        <div class="squad-list">
                            @foreach($players as $p)
                                <div class="player js-player" data-player-id="{{ $p->id }}" role="button" tabindex="0">
                                    @include('partials.avatar', ['player' => $p, 'size' => 'avatar--sm'])
                                    <span class="player__num">{{ $p->number ?? '—' }}</span>
                                    <span class="player__name">{{ $p->name }}</span>
                                    <span class="player__pos">{{ $p->position }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="empty-state">
                        <span class="empty-state__icon">👥</span>
                        <p>Squad not yet announced.</p>
                    </div>
                @endforelse
            </div>

            <aside>
                @if($team->group)
                    <div class="standings-card">
                        <div class="standings-card__head">
                            <h2 class="standings-card__title">{{ $team->group->name }} table</h2>
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
                                @forelse($groupRows as $row)
                                    <tr @class(['is-qualifying' => $row->team->id === $team->id])>
                                        <td>
                                            <span class="pos {{ $row->qualifying ? 'pos--qual' : '' }}">{{ $row->rank }}</span>
                                        </td>
                                        <td>
                                            <a class="team-cell" href="{{ route('teams.show', $row->team) }}">
                                                <span class="team-cell__flag">{{ $row->team->flag_emoji }}</span>
                                                <span class="team-cell__name">{{ $row->team->display_name }}</span>
                                            </a>
                                        </td>
                                        <td class="num">{{ $row->played }}</td>
                                        <td class="num">{{ $row->gd > 0 ? '+' . $row->gd : $row->gd }}</td>
                                        <td class="pts">{{ $row->points }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="muted">No standings available.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                        <div class="legend">
                            <span><i class="pos--qual"></i> Top two qualify for the knockout stage</span>
                        </div>
                    </div>
                @endif
            </aside>
        </div>
    </div>
</section>
@endsection
