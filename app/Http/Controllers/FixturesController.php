<?php

namespace App\Http\Controllers;

use App\Models\Fixture;
use App\Models\Group;
use App\Models\Venue;
use App\Services\StandingsService;
use Illuminate\Http\Request;

class FixturesController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            'stage' => $request->query('stage'),
            'group' => $request->query('group'),
            'date' => $request->query('date'),
            'city' => $request->query('city'),
        ];

        $query = Fixture::with(['team1', 'team2', 'venue', 'group'])->chrono();

        if ($filters['stage'] && array_key_exists($filters['stage'], Fixture::STAGES)) {
            $query->where('stage', $filters['stage']);
        }
        if ($filters['group']) {
            $query->whereHas('group', fn ($q) => $q->where('letter', $filters['group']));
        }
        if ($filters['date']) {
            $query->whereDate('match_date', $filters['date']);
        }
        if ($filters['city']) {
            $query->whereHas('venue', fn ($q) => $q->where('slug', $filters['city']));
        }

        $fixturesByDate = $query->get()->groupBy(fn (Fixture $f) => $f->match_date->toDateString());

        return view('fixtures', [
            'fixturesByDate' => $fixturesByDate,
            'filters' => $filters,
            'stages' => Fixture::STAGES,
            'groups' => Group::orderBy('letter')->get(),
            'venues' => Venue::orderBy('city')->get(),
            'dates' => Fixture::query()->orderBy('match_date')->distinct()->pluck('match_date')
                ->map(fn ($d) => $d->toDateString())->unique()->values(),
            'totalMatches' => Fixture::count(),
        ]);
    }

    public function show(Fixture $fixture, StandingsService $standings)
    {
        $fixture->load([
            'team1.players', 'team2.players', 'venue', 'group',
        ]);

        $groupRows = $fixture->group
            ? $standings->forGroup($fixture->group)
            : collect();

        $sideForm = fn (?\App\Models\Team $team) => $team
            ? $team->fixtures()->with(['team1', 'team2', 'venue'])->where('fixtures.id', '!=', $fixture->id)->get()
            : collect();

        return view('fixtures.show', [
            'fixture' => $fixture,
            'groupRows' => $groupRows,
            'squad1' => $fixture->team1 ? $fixture->team1->players->groupBy('position') : collect(),
            'squad2' => $fixture->team2 ? $fixture->team2->players->groupBy('position') : collect(),
            'team1Form' => $sideForm($fixture->team1),
            'team2Form' => $sideForm($fixture->team2),
        ]);
    }
}
