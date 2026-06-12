<?php

namespace App\Http\Controllers;

use App\Models\Fixture;

class BracketController extends Controller
{
    public function index()
    {
        $stages = ['round_of_32', 'round_of_16', 'quarter_final', 'semi_final', 'final'];

        $rounds = Fixture::with(['team1', 'team2', 'venue'])
            ->whereIn('stage', $stages)
            ->orderBy('num')
            ->get()
            ->groupBy('stage');

        $thirdPlace = Fixture::with(['team1', 'team2', 'venue'])
            ->where('stage', 'third_place')
            ->first();

        return view('bracket', [
            'rounds' => $rounds,
            'stageOrder' => $stages,
            'thirdPlace' => $thirdPlace,
        ]);
    }
}
