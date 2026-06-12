<?php

use App\Http\Controllers\BracketController;
use App\Http\Controllers\FixturesController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PlayersController;
use App\Http\Controllers\StandingsController;
use App\Http\Controllers\TeamsController;
use App\Http\Controllers\VenuesController;
use App\Http\Middleware\SetLocale;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

// Switch UI language (persisted in the session) and return to the previous page.
Route::get('/lang/{locale}', function (string $locale) {
    if (array_key_exists($locale, SetLocale::LOCALES)) {
        session(['locale' => $locale]);
    }
    return redirect()->back();
})->name('lang.switch');

// Static legal pages.
Route::get('/privacy', [PageController::class, 'privacy'])->name('privacy');
Route::get('/terms', [PageController::class, 'terms'])->name('terms');

Route::get('/fixtures', [FixturesController::class, 'index'])->name('fixtures.index');
Route::get('/fixtures/{fixture}', [FixturesController::class, 'show'])->name('fixtures.show');

Route::get('/bracket', [BracketController::class, 'index'])->name('bracket.index');
Route::get('/standings', [StandingsController::class, 'index'])->name('standings.index');

Route::get('/venues', [VenuesController::class, 'index'])->name('venues.index');
Route::get('/venues/{venue}', [VenuesController::class, 'show'])->name('venues.show');

Route::get('/teams', [TeamsController::class, 'index'])->name('teams.index');
Route::get('/teams/{team}', [TeamsController::class, 'show'])->name('teams.show');

Route::get('/players/{player}', [PlayersController::class, 'show'])->name('players.show');
