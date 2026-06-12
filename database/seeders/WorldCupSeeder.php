<?php

namespace Database\Seeders;

use App\Models\Fixture;
use App\Models\Group;
use App\Models\Player;
use App\Models\Team;
use App\Models\Venue;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Imports the public-domain openfootball World Cup 2026 dataset
 * (stored under storage/app/openfootball) into the relational schema.
 */
class WorldCupSeeder extends Seeder
{
    private string $dir;

    public function run(): void
    {
        $this->dir = database_path('data/openfootball');

        $venues = $this->seedVenues();
        $groups = $this->seedGroups();
        $teams = $this->seedTeams($groups);
        $this->seedPlayers($teams);
        $this->seedFixtures($teams, $groups, $venues);
    }

    private function json(string $file): array
    {
        return json_decode(file_get_contents("{$this->dir}/{$file}"), true, 512, JSON_THROW_ON_ERROR);
    }

    /** @return array<string,Venue> keyed by city */
    private function seedVenues(): array
    {
        $data = $this->json('worldcup.stadiums.json');
        $byCity = [];

        foreach ($data['stadiums'] as $s) {
            $venue = Venue::create([
                'name' => $s['name'],
                'city' => $s['city'],
                'slug' => Str::slug($s['city']),
                'country_code' => $s['cc'],
                'capacity' => $s['capacity'] ?? null,
                'timezone' => $s['timezone'] ?? null,
                'coords' => $s['coords'] ?? null,
            ]);
            $byCity[$s['city']] = $venue;
        }

        return $byCity;
    }

    /** @return array<string,Group> keyed by single-letter */
    private function seedGroups(): array
    {
        $byLetter = [];

        foreach (range('A', 'L') as $letter) {
            $byLetter[$letter] = Group::create([
                'letter' => $letter,
                'name' => "Group {$letter}",
            ]);
        }

        return $byLetter;
    }

    /**
     * @param  array<string,Group>  $groups
     * @return array<string,Team> keyed by team name (raw, as used in fixtures)
     */
    private function seedTeams(array $groups): array
    {
        $data = $this->json('worldcup.teams.json');
        $byName = [];

        foreach ($data as $t) {
            $team = Team::create([
                'name' => $t['name'],
                'slug' => Str::slug($t['name']),
                'name_normalised' => $t['name_normalised'] ?? null,
                'fifa_code' => $t['fifa_code'],
                'confederation' => $t['confed'] ?? null,
                'continent' => $t['continent'] ?? null,
                'flag_emoji' => $t['flag_icon'] ?? null,
                'group_id' => isset($t['group']) ? $groups[$t['group']]->id : null,
            ]);
            $byName[$t['name']] = $team;
        }

        return $byName;
    }

    /** @param  array<string,Team>  $teams keyed by name */
    private function seedPlayers(array $teams): void
    {
        $data = $this->json('worldcup.squads.json');
        $byCode = [];
        foreach ($teams as $team) {
            $byCode[$team->fifa_code] = $team;
        }

        $rows = [];
        $now = now();

        foreach ($data as $squad) {
            $team = $byCode[$squad['fifa_code']] ?? null;
            if (! $team) {
                continue;
            }
            foreach ($squad['players'] ?? [] as $p) {
                $rows[] = [
                    'team_id' => $team->id,
                    'number' => $p['number'] ?? null,
                    'position' => $p['pos'] ?? null,
                    'name' => $p['name'],
                    'date_of_birth' => $p['date_of_birth'] ?? null,
                    'goals' => 0,
                    'assists' => 0,
                    'rating' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            Player::insert($chunk);
        }
    }

    /**
     * @param  array<string,Team>  $teams keyed by name
     * @param  array<string,Group>  $groups keyed by letter
     * @param  array<string,Venue>  $venues keyed by city
     */
    private function seedFixtures(array $teams, array $groups, array $venues): void
    {
        $data = $this->json('worldcup.json');

        foreach ($data['matches'] as $m) {
            [$stage, $matchday] = $this->resolveStage($m['round']);

            $groupLetter = isset($m['group']) ? Str::after($m['group'], 'Group ') : null;

            Fixture::create([
                'num' => $m['num'] ?? null,
                'stage' => $stage,
                'round_label' => $m['round'],
                'matchday' => $matchday,
                'group_id' => $groupLetter ? ($groups[$groupLetter]->id ?? null) : null,
                'venue_id' => isset($m['ground']) ? ($venues[$m['ground']]->id ?? null) : null,
                'match_date' => $m['date'],
                'time_label' => $m['time'] ?? null,
                'kickoff_at' => $this->toUtc($m['date'], $m['time'] ?? null),
                'team1_id' => $teams[$m['team1']]->id ?? null,
                'team2_id' => $teams[$m['team2']]->id ?? null,
                'team1_placeholder' => isset($teams[$m['team1']]) ? null : $m['team1'],
                'team2_placeholder' => isset($teams[$m['team2']]) ? null : $m['team2'],
                'status' => 'scheduled',
            ]);
        }
    }

    /** @return array{0:string,1:?int} [stage key, matchday|null] */
    private function resolveStage(string $round): array
    {
        if (Str::startsWith($round, 'Matchday')) {
            return ['group', (int) Str::after($round, 'Matchday ')];
        }

        return [match ($round) {
            'Round of 32' => 'round_of_32',
            'Round of 16' => 'round_of_16',
            'Quarter-final' => 'quarter_final',
            'Semi-final' => 'semi_final',
            'Match for third place' => 'third_place',
            'Final' => 'final',
            default => Str::slug($round, '_'),
        }, null];
    }

    /** Convert a wall-clock "13:00 UTC-6" plus date into a UTC datetime. */
    private function toUtc(string $date, ?string $time): ?Carbon
    {
        if (! $time) {
            return null;
        }

        if (! preg_match('/^(\d{1,2}):(\d{2})\s*UTC([+-]\d+)/', $time, $m)) {
            return null;
        }

        $offsetHours = (int) $m[3];

        return Carbon::createFromFormat('Y-m-d H:i', sprintf('%s %02d:%02d', $date, $m[1], $m[2]), 'UTC')
            ->subHours($offsetHours);
    }
}
