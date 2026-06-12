<?php

namespace App\Http\Controllers;

use App\Models\Venue;

class VenuesController extends Controller
{
    public function index()
    {
        $venues = Venue::withCount('fixtures')
            ->orderByDesc('capacity')
            ->get();

        return view('venues.index', [
            'venues' => $venues,
            'totalCapacity' => $venues->sum('capacity'),
        ]);
    }

    public function show(Venue $venue)
    {
        $fixtures = $venue->fixtures()
            ->with(['team1', 'team2', 'group', 'venue'])
            ->orderBy('kickoff_at')
            ->get();

        return view('venues.show', [
            'venue' => $venue,
            'fixtures' => $fixtures,
        ]);
    }
}
