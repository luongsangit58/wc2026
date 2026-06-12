<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Team;
use App\Services\StandingsService;

class TeamsController extends Controller
{
    public function index()
    {
        $groups = Group::with(['teams' => fn ($q) => $q->orderBy('name')])
            ->orderBy('letter')
            ->get();

        return view('teams.index', [
            'groups' => $groups,
            'teamCount' => Team::count(),
        ]);
    }

    public function show(Team $team, StandingsService $standings)
    {
        $team->load(['group', 'players' => fn ($q) => $q->orderBy('number')]);

        $fixtures = $team->fixtures()
            ->with(['team1', 'team2', 'venue', 'group'])
            ->get();

        $squadByPosition = $team->players
            ->groupBy('position')
            ->sortBy(fn ($players, $pos) => array_search($pos, ['GK', 'DF', 'MF', 'FW']));

        $groupRows = $team->group
            ? $standings->forGroup($team->group)
            : collect();

        return view('teams.show', [
            'team' => $team,
            'fixtures' => $fixtures,
            'squadByPosition' => $squadByPosition,
            'groupRows' => $groupRows,
        ]);
    }
}
