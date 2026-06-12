@extends('layouts.app')

@section('title', 'Teams')
@section('meta_description', 'All 48 qualified nations of the FIFA World Cup 2026, organized across 12 groups.')

@section('content')
<section class="section">
    <div class="container">
        <div class="page-head">
            <div class="page-head__eyebrow">FIFA World Cup 2026</div>
            <h1 class="page-head__title">Teams</h1>
            <p class="page-head__sub">All {{ $teamCount }} qualified nations across 12 groups.</p>
        </div>

        <div class="grid grid--auto">
            @forelse ($groups as $group)
                <div class="group-block">
                    <h2 class="group-block__title">
                        <span class="pos">{{ $group->letter }}</span>
                        {{ $group->name }}
                    </h2>

                    @forelse ($group->teams as $team)
                        <a class="team-row" href="{{ route('teams.show', $team) }}">
                            <span class="team-row__flag">{{ $team->flag_emoji }}</span>
                            <span class="team-row__name">{{ $team->display_name }}</span>
                            <span class="team-row__code">{{ $team->fifa_code }}</span>
                        </a>
                    @empty
                        <p class="muted">No teams assigned yet.</p>
                    @endforelse
                </div>
            @empty
                <div class="empty-state">
                    <div class="empty-state__icon">🏆</div>
                    <p>No groups available yet. Check back soon.</p>
                </div>
            @endforelse
        </div>
    </div>
</section>
@endsection
