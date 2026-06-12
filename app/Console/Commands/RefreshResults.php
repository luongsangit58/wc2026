<?php

namespace App\Console\Commands;

use App\Models\Fixture;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Pulls the latest REAL results from the public-domain openfootball feed and
 * updates finished scores, statuses and goal scorers — without wiping the DB
 * (squad photos and everything else are preserved). Idempotent: safe to run on
 * a schedule. This is the real-data counterpart to the wc:simulate demo engine.
 *
 *   php artisan wc:refresh
 */
class RefreshResults extends Command
{
    protected $signature = 'wc:refresh';
    protected $description = 'Update fixtures with the latest real openfootball results + scorers';

    private const UA = 'WC2026FanProject/1.0 (Laravel; openfootball data)';
    private const URL = 'https://raw.githubusercontent.com/openfootball/worldcup.json/master/2026/worldcup.json';

    public function handle(): int
    {
        try {
            $resp = Http::withHeaders(['User-Agent' => self::UA])->timeout(30)->retry(3, 800, throw: false)->get(self::URL);
        } catch (\Throwable $e) {
            $this->error('Network error: ' . $e->getMessage());
            return self::FAILURE;
        }
        if (! $resp->ok()) {
            $this->error("HTTP {$resp->status()} fetching results.");
            return self::FAILURE;
        }

        try {
            $data = json_decode($resp->body(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            $this->error('Feed did not return valid JSON.');
            return self::FAILURE;
        }

        // normalised team-name -> team, and per-team normalised player-name -> id
        $teamByNorm = Team::all()->keyBy(fn (Team $t) => $this->norm($t->name));
        $playerIndex = [];
        foreach (Player::select('id', 'team_id', 'name')->get() as $pl) {
            $playerIndex[$pl->team_id][$this->norm($pl->name)] = $pl->id;
        }

        $finished = 0;
        $goalTally = [];

        foreach ($data['matches'] as $m) {
            $ft = $m['score']['ft'] ?? null;
            $isFinished = is_array($ft) && count($ft) === 2;
            if (! $isFinished) {
                continue; // only act on played matches
            }

            $t1 = $teamByNorm[$this->norm($m['team1'])] ?? null;
            $t2 = $teamByNorm[$this->norm($m['team2'])] ?? null;

            $fixture = $this->locate($m, $t1?->id, $t2?->id);
            if (! $fixture) {
                continue;
            }

            $update = [
                'status' => 'finished',
                'team1_score' => $ft[0],
                'team2_score' => $ft[1],
            ];
            // fill knockout teams once they are known
            if ($t1 && ! $fixture->team1_id) {
                $update['team1_id'] = $t1->id;
                $update['team1_placeholder'] = null;
            }
            if ($t2 && ! $fixture->team2_id) {
                $update['team2_id'] = $t2->id;
                $update['team2_placeholder'] = null;
            }
            $fixture->update($update);
            $finished++;

            $homeId = $fixture->team1_id ?? $t1?->id;
            $awayId = $fixture->team2_id ?? $t2?->id;
            $this->tally($goalTally, $playerIndex[$homeId] ?? [], $m['goals1'] ?? []);
            $this->tally($goalTally, $playerIndex[$awayId] ?? [], $m['goals2'] ?? []);
        }

        // recompute goals from scratch (idempotent)
        Player::where('goals', '>', 0)->update(['goals' => 0]);
        foreach ($goalTally as $playerId => $goals) {
            Player::whereKey($playerId)->update(['goals' => $goals]);
        }

        $this->info("Refreshed: {$finished} finished matches, " . array_sum($goalTally) . ' goals attributed.');
        return self::SUCCESS;
    }

    private function locate(array $m, ?int $t1, ?int $t2): ?Fixture
    {
        if (! empty($m['num'])) {
            $f = Fixture::where('num', $m['num'])->first();
            if ($f) {
                return $f;
            }
        }
        if ($t1 && $t2) {
            return Fixture::whereDate('match_date', $m['date'])
                ->where('team1_id', $t1)->where('team2_id', $t2)
                ->first();
        }
        return null;
    }

    private function tally(array &$tally, array $nameToId, array $goals): void
    {
        foreach ($goals as $g) {
            if (empty($g['name'])) {
                continue;
            }
            $id = $nameToId[$this->norm($g['name'])] ?? null;
            if ($id) {
                $tally[$id] = ($tally[$id] ?? 0) + 1;
            }
        }
    }

    private function norm(string $s): string
    {
        return strtolower(preg_replace('/\s+/', ' ', trim(Str::ascii($s))));
    }
}
