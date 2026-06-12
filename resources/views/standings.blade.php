@extends('layouts.app')

@section('title', 'Standings')
@section('meta_description', 'FIFA World Cup 2026 group stage standings. Top two of each of the 12 groups advance, plus the eight best third-placed teams.')

@section('content')
<section class="section">
    <div class="container">
        <div class="page-head">
            <div class="page-head__eyebrow">Group Stage</div>
            <h1 class="page-head__title">Standings</h1>
            <p class="page-head__sub">12 groups · top two of each group advance, plus the eight best third-placed teams.</p>
        </div>

        <div class="progress-strip section--tight">
            <span class="pill">{{ $playedCount }}/{{ $groupTotal }} group matches played</span>
            <div class="progress">
                <div class="progress__bar" style="width: {{ $groupTotal ? round($playedCount / $groupTotal * 100) : 0 }}%"></div>
            </div>
        </div>

        <div class="grid grid--2">
            @forelse($standings as $letter => $data)
                <div class="standings-card">
                    <div class="standings-card__head">
                        <h2 class="standings-card__title">{{ $data['group']->name }}</h2>
                    </div>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Team</th>
                                <th>P</th>
                                <th>W</th>
                                <th>D</th>
                                <th>L</th>
                                <th>GF</th>
                                <th>GA</th>
                                <th>GD</th>
                                <th>Pts</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($data['rows'] as $row)
                                <tr @class(['is-qualifying' => $row->qualifying])>
                                    <td>
                                        <span class="pos @if($row->qualifying) pos--qual @endif">{{ $row->rank }}</span>
                                    </td>
                                    <td>
                                        <span class="team-cell">
                                            <span class="team-cell__flag flag">{{ $row->team->flag_emoji }}</span>
                                            <a href="{{ route('teams.show', $row->team) }}" class="team-cell__name">{{ $row->team->display_name }}</a>
                                        </span>
                                    </td>
                                    <td class="num">{{ $row->played }}</td>
                                    <td class="num">{{ $row->won }}</td>
                                    <td class="num">{{ $row->drawn }}</td>
                                    <td class="num">{{ $row->lost }}</td>
                                    <td class="num">{{ $row->gf }}</td>
                                    <td class="num">{{ $row->ga }}</td>
                                    <td class="num">{{ $row->gd > 0 ? '+' . $row->gd : $row->gd }}</td>
                                    <td class="num pts">{{ $row->points }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="muted">No teams in this group yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @empty
                <div class="empty-state">
                    <div class="empty-state__icon">🏆</div>
                    <p>Standings are not available yet.</p>
                </div>
            @endforelse
        </div>

        <div class="legend">
            <span><i class="legend__swatch" style="background: var(--primary)"></i> Advance to knockout stage</span>
        </div>
    </div>
</section>
@endsection
