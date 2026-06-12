<?php

namespace App\Http\Controllers;

use App\Models\Fixture;
use App\Models\Player;
use App\Services\StandingsService;

class HomeController extends Controller
{
    public function index(StandingsService $standings)
    {
        // The next match to kick off (nearest in the future); fall back to the
        // most recent one once the tournament is over.
        $now = Fixture::effectiveNow();
        $featured = Fixture::with(['team1', 'team2', 'venue', 'group'])
            ->where('kickoff_at', '>=', $now)
            ->orderBy('kickoff_at')
            ->first()
            ?? Fixture::with(['team1', 'team2', 'venue', 'group'])
                ->orderByDesc('kickoff_at')
                ->first();

        $liveMatches = Fixture::with(['team1', 'team2', 'venue', 'group'])
            ->live()
            ->chrono()
            ->get();

        $upcoming = Fixture::with(['team1', 'team2', 'venue', 'group'])
            ->upcoming()
            ->chrono()
            ->limit(6)
            ->get();

        $topScorers = Player::with('team')
            ->where('goals', '>', 0)
            ->orderByDesc('goals')->orderByDesc('assists')
            ->limit(5)->get();

        $topAssists = Player::with('team')
            ->where('assists', '>', 0)
            ->orderByDesc('assists')->orderByDesc('goals')
            ->limit(5)->get();

        $topRated = Player::with('team')
            ->whereNotNull('rating')
            ->orderByDesc('rating')
            ->limit(5)->get();

        return view('home', [
            'featured' => $featured,
            'liveMatches' => $liveMatches,
            'upcoming' => $upcoming,
            'topScorers' => $topScorers,
            'topAssists' => $topAssists,
            'topRated' => $topRated,
            'standings' => $standings->all(),
            'playedCount' => Fixture::finished()->count(),
            'totalCount' => Fixture::count(),
            'quickActions' => $this->quickActions(),
        ]);
    }

    /** @return array<int,array{label:string,icon:string,url:string}> */
    private function quickActions(): array
    {
        $final = Fixture::where('stage', 'final')->first();

        return [
            ['label' => 'My National Team', 'icon' => '🏳️', 'url' => route('teams.index')],
            ['label' => 'Venue Guide', 'icon' => '🏟️', 'url' => route('venues.index')],
            ['label' => 'Knockout Bracket', 'icon' => '🏆', 'url' => route('bracket.index')],
            ['label' => 'Fixtures', 'icon' => '📅', 'url' => route('fixtures.index')],
            ['label' => 'Standings', 'icon' => '📊', 'url' => route('standings.index')],
            ['label' => 'The Final', 'icon' => '🥇', 'url' => $final ? route('fixtures.show', $final) : route('bracket.index')],
        ];
    }
}
