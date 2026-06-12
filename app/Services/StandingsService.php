<?php

namespace App\Services;

use App\Models\Group;
use App\Models\Team;
use Illuminate\Support\Collection;

class StandingsService
{
    /**
     * Build the live standings table for a single group.
     *
     * Stats are computed from finished fixtures only, so before any match is
     * played every column is 0 — matching the source site's "tournament in
     * progress" state. Returns a sorted collection of plain row objects.
     *
     * @return Collection<int, object>
     */
    public function forGroup(Group $group): Collection
    {
        $group->loadMissing('teams', 'fixtures');

        $rows = $group->teams->mapWithKeys(function (Team $team) {
            return [$team->id => (object) [
                'team' => $team,
                'played' => 0, 'won' => 0, 'drawn' => 0, 'lost' => 0,
                'gf' => 0, 'ga' => 0, 'gd' => 0, 'points' => 0,
            ]];
        });

        foreach ($group->fixtures as $fixture) {
            if ($fixture->status !== 'finished' || $fixture->team1_id === null || $fixture->team2_id === null) {
                continue;
            }
            if (! $rows->has($fixture->team1_id) || ! $rows->has($fixture->team2_id)) {
                continue;
            }

            $home = $rows[$fixture->team1_id];
            $away = $rows[$fixture->team2_id];
            $hs = (int) $fixture->team1_score;
            $as = (int) $fixture->team2_score;

            $home->played++; $away->played++;
            $home->gf += $hs; $home->ga += $as;
            $away->gf += $as; $away->ga += $hs;

            if ($hs > $as) {
                $home->won++; $away->lost++; $home->points += 3;
            } elseif ($hs < $as) {
                $away->won++; $home->lost++; $away->points += 3;
            } else {
                $home->drawn++; $away->drawn++; $home->points++; $away->points++;
            }
        }

        return $rows->values()
            ->each(fn ($r) => $r->gd = $r->gf - $r->ga)
            ->sortBy([
                ['points', 'desc'],
                ['gd', 'desc'],
                ['gf', 'desc'],
            ])
            ->values()
            ->each(function ($r, $i) {
                $r->rank = $i + 1;
                // Top two qualify directly; rank 3 may qualify as a best third-placed side.
                $r->qualifying = $r->rank <= 2;
            });
    }

    /** Standings for every group, keyed by group letter. */
    public function all(): Collection
    {
        return Group::with('teams', 'fixtures')
            ->orderBy('letter')
            ->get()
            ->mapWithKeys(fn (Group $g) => [$g->letter => [
                'group' => $g,
                'rows' => $this->forGroup($g),
            ]]);
    }
}
