<?php

use App\Http\Controllers\BracketController;
use App\Http\Controllers\FixturesController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\StandingsController;
use App\Http\Controllers\TeamsController;
use App\Http\Controllers\VenuesController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/fixtures', [FixturesController::class, 'index'])->name('fixtures.index');
Route::get('/fixtures/{fixture}', [FixturesController::class, 'show'])->name('fixtures.show');

Route::get('/bracket', [BracketController::class, 'index'])->name('bracket.index');
Route::get('/standings', [StandingsController::class, 'index'])->name('standings.index');

Route::get('/venues', [VenuesController::class, 'index'])->name('venues.index');
Route::get('/venues/{venue}', [VenuesController::class, 'show'])->name('venues.show');

Route::get('/teams', [TeamsController::class, 'index'])->name('teams.index');
Route::get('/teams/{team}', [TeamsController::class, 'show'])->name('teams.show');
