<?php

namespace App\Http\Controllers;

use App\Models\Player;

class PlayersController extends Controller
{
    /** JSON for the click-to-open player card modal. */
    public function show(Player $player)
    {
        $player->loadMissing('team');

        return response()->json([
            'name' => $player->name,
            'number' => $player->number,
            'position' => $player->position,
            'position_label' => $player->position_label,
            'age' => $player->age,
            'dob' => $player->date_of_birth?->format('j M Y'),
            'goals' => $player->goals,
            'assists' => $player->assists,
            'rating' => $player->rating !== null ? (float) $player->rating : null,
            'photo' => $player->photo_url,
            'initials' => $player->initials,
            'team' => $player->team?->display_name,
            'team_flag' => $player->team?->flag_emoji,
            'team_slug' => $player->team?->slug,
        ]);
    }
}
