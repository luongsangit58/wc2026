<?php

namespace App\Http\Controllers;

use App\Models\Fixture;
use App\Services\StandingsService;

class StandingsController extends Controller
{
    public function index(StandingsService $standings)
    {
        return view('standings', [
            'standings' => $standings->all(),
            'playedCount' => Fixture::groupStage()->finished()->count(),
            'groupTotal' => Fixture::groupStage()->count(),
        ]);
    }
}
