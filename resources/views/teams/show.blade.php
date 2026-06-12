@extends('layouts.app')

@section('title', $team->display_name)
@section('meta_description', $team->display_name . ' at the FIFA World Cup 2026 — squad, fixtures, group standings, confederation and FIFA code.')

@php
    $positionLabels = ['GK' => 'Goalkeepers', 'DF' => 'Defenders', 'MF' => 'Midfielders', 'FW' => 'Forwards'];
@endphp

@section('content')
<section class="section">
    <div class="container">
        <nav class="breadcrumb">
            <a href="{{ route('teams.index') }}">{{ __('Teams') }}</a>
            <span aria-hidden="true">/</span>
            <span>{{ $team->display_name }}</span>
        </nav>

        <div class="team-hero">
            <span class="team-hero__flag">{{ $team->flag_emoji }}</span>
            <div>
                <h1 class="team-hero__name">{{ $team->display_name }}</h1>
                <div class="team-hero__tags">
                    @if($team->group)<span class="pill">{{ $team->group->name }}</span>@endif
                    @if($team->confederation)<span class="pill">{{ $team->confederation }}</span>@endif
                    @if($team->continent)<span class="pill">{{ $team->continent }}</span>@endif
                    <span class="pill">FIFA: {{ $team->fifa_code }}</span>
                    <span class="pill">{{ __(':count players', ['count' => $team->players->count()]) }}</span>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Matches + group table --}}
<section class="section section--tight">
    <div class="container">
        <div class="grid grid--sidebar">
            <div>
                <div class="section__head"><h2 class="section__title">{{ __('Matches') }}</h2></div>

                @forelse($fixtures as $f)
                    <a class="match-card" href="{{ route('fixtures.show', $f) }}">
                        <div class="match-card__top">
                            @if($f->group)
                                <span class="badge badge--group">{{ $f->group->name }}</span>
                            @else
                                <span class="badge badge--stage">{{ __($f->stage_label) }}</span>
                            @endif
                            @if($f->is_live)
                                <span class="badge badge--live">{{ __('Live') }}</span>
                            @elseif($f->is_finished)
                                <span class="badge badge--finished">{{ __('FT') }}</span>
                            @else
                                <span class="badge badge--scheduled">{{ __('Scheduled') }}</span>
                            @endif
                        </div>

                        <div class="match-card__teams">
                            <div class="match-card__team match-card__team--home">
                                <span class="match-card__flag">{{ $f->team1?->flag_emoji ?? '🏳️' }}</span>
                                <span class="match-card__name @if($f->team1 && $f->team1->id === $team->id) accent @endif @unless($f->team1) match-card__name--tbd @endunless">{{ $f->team1_label }}</span>
                            </div>
                            <div class="match-card__mid">
                                @if($f->has_score)
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
                            @if($f->venue)<span>{{ $f->venue->name }}, {{ $f->venue->city }}</span>@endif
                        </div>
                    </a>
                @empty
                    <div class="empty-state"><span class="empty-state__icon">⚽</span><p>{{ __('No fixtures scheduled.') }}</p></div>
                @endforelse
            </div>

            <aside>
                @if($team->group)
                    <div class="standings-card">
                        <div class="standings-card__head"><h2 class="standings-card__title">{{ __(':group table', ['group' => $team->group->name]) }}</h2></div>
                        <table class="table">
                            <thead><tr><th>#</th><th>{{ __('Team') }}</th><th>{{ __('P') }}</th><th>{{ __('GD') }}</th><th>{{ __('Pts') }}</th></tr></thead>
                            <tbody>
                                @forelse($groupRows as $row)
                                    <tr @class(['is-qualifying' => $row->team->id === $team->id])>
                                        <td><span class="pos {{ $row->qualifying ? 'pos--qual' : '' }}">{{ $row->rank }}</span></td>
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
                                    <tr><td colspan="5" class="muted">{{ __('No standings available.') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                        <div class="legend"><span><i class="pos--qual"></i> {{ __('Top two qualify for the knockout stage') }}</span></div>
                    </div>
                @endif
            </aside>
        </div>
    </div>
</section>

{{-- Squad: master (left) + detail panel (right), click a player to load it --}}
<section class="section">
    <div class="container">
        <div class="section__head"><h2 class="section__title">{{ __('Squad') }}</h2></div>

        <div class="grid grid--sidebar">
            <div>
                @forelse($squadByPosition as $pos => $players)
                    <div class="squad-group">
                        <h3 class="squad-group__label">{{ __($positionLabels[$pos] ?? $pos) }}</h3>
                        <div class="squad-grid">
                            @foreach($players as $p)
                                <div class="sqd js-player-detail" data-player-id="{{ $p->id }}" role="button" tabindex="0" aria-label="{{ $p->name }}">
                                    @include('partials.avatar', ['player' => $p])
                                    <div class="sqd__info">
                                        <div class="sqd__name">{{ $p->name }}</div>
                                        <div class="sqd__meta"><b>#{{ $p->number ?? '—' }}</b> · {{ $p->position_label }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="empty-state"><span class="empty-state__icon">👥</span><p>{{ __('Squad not yet announced.') }}</p></div>
                @endforelse
            </div>

            <aside>
                <div class="player-detail" data-player-detail>
                    <p class="player-detail__hint">{{ __('👈 Select a player to see their photo & stats') }}</p>
                </div>
            </aside>
        </div>
    </div>
</section>
@endsection
