<?php

namespace App\Console\Commands;

use App\Models\Fixture;
use App\Models\Player;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Simulates "the tournament in progress": every group match whose kick-off has
 * passed gets a deterministic score (finished, or live + partial if it is
 * happening right now), and goals/assists/ratings are attributed to real squad
 * players so the standings and stat leaderboards come alive.
 *
 *   php artisan wc:simulate                 # as of now
 *   php artisan wc:simulate --as-of=2026-06-20T12:00:00
 *   php artisan wc:simulate --reset         # wipe results, back to "not started"
 */
class SimulateResults extends Command
{
    protected $signature = 'wc:simulate
        {--as-of= : Pretend "now" is this datetime (ISO 8601). Defaults to the real current time.}
        {--live-demo : Use an accelerated virtual clock (persisted on disk) so matches visibly progress over real minutes.}
        {--speed=10 : Sim-minutes elapsed per real-minute when using --live-demo.}
        {--seed=2026 : RNG seed for reproducible scores.}
        {--reset : Clear all results (and the demo clock) and leave every match scheduled.}';

    protected $description = 'Simulate live + finished World Cup results and player stats';

    /** @var array<int,array{g:int,a:int,r:float|null}> player_id => tallies */
    private array $stats = [];

    public function handle(): int
    {
        DB::transaction(function () {
            $this->clearResults();

            if ($this->option('reset')) {
                @unlink(storage_path('app/sim-clock.json'));
                $this->info('All results + demo clock cleared — tournament reset to "not started".');
                return;
            }

            $baseSeed = (int) $this->option('seed');
            $asOf = $this->resolveAsOf();

            $players = Player::all()->groupBy('team_id');
            $playedTeams = [];
            $finished = 0;
            $live = 0;

            $fixtures = Fixture::query()->groupStage()
                ->whereNotNull('team1_id')->whereNotNull('team2_id')
                ->orderBy('num')->orderBy('kickoff_at')->get();

            foreach ($fixtures as $f) {
                if (! $f->kickoff_at || $asOf->lt($f->kickoff_at)) {
                    continue; // not started yet
                }

                // Seed per match so each result is stable as the demo clock advances
                // (finished scores never change; live scores only grow toward full-time).
                mt_srand($baseSeed * 100000 + (int) $f->id);

                $end = $f->kickoff_at->copy()->addMinutes(95); // aligns with the /95 live-score ramp below
                $isLive = $asOf->lt($end);

                $homeFull = $this->generateGoals();
                $awayFull = $this->generateGoals();

                if ($isLive) {
                    $frac = min(1.0, max(0.0, $f->kickoff_at->diffInMinutes($asOf) / 95));
                    $home = (int) floor($homeFull * $frac);
                    $away = (int) floor($awayFull * $frac);
                    $status = 'live';
                    $live++;
                } else {
                    $home = $homeFull;
                    $away = $awayFull;
                    $status = 'finished';
                    $finished++;
                }

                $f->update(['status' => $status, 'team1_score' => $home, 'team2_score' => $away]);

                $this->attributeGoals($players->get($f->team1_id), $home);
                $this->attributeGoals($players->get($f->team2_id), $away);
                $playedTeams[$f->team1_id] = true;
                $playedTeams[$f->team2_id] = true;
            }

            $this->assignRatings($players, array_keys($playedTeams));
            $this->persist();

            $this->info("Simulated as of {$asOf->toDayDateTimeString()}: {$finished} finished, {$live} live.");
            $this->line('Standings + stat leaderboards now reflect these results.');
        });

        return self::SUCCESS;
    }

    private function resolveAsOf(): Carbon
    {
        if ($this->option('live-demo')) {
            return $this->virtualClock((float) $this->option('speed'));
        }
        // Real-time / fixed-moment runs are authoritative. Drop any demo clock so
        // Fixture::effectiveNow() reads the same timeline as the board we just wrote
        // (otherwise live_minute would desync from the scores).
        @unlink(storage_path('app/sim-clock.json'));
        if ($this->option('as-of')) {
            return Carbon::parse($this->option('as-of'));
        }
        return now();
    }

    /**
     * An accelerated clock persisted on disk: every real minute advances the
     * simulated tournament time by {speed} minutes. First call pins the start.
     */
    private function virtualClock(float $speed): Carbon
    {
        $path = storage_path('app/sim-clock.json');
        $realNow = now();

        if (is_file($path)) {
            $c = json_decode(file_get_contents($path), true);
        } else {
            $c = [
                'base_real' => $realNow->toIso8601String(),
                'base_sim' => '2026-06-26T19:30:00+00:00', // final group matchday — board full, matches live
            ];
            file_put_contents($path, json_encode($c, JSON_PRETTY_PRINT));
        }

        $elapsedRealMinutes = Carbon::parse($c['base_real'])->diffInSeconds($realNow) / 60;
        $asOf = Carbon::parse($c['base_sim'])->addMinutes($elapsedRealMinutes * $speed);

        // expose the current simulated time so the UI (live minute) can read it
        $c['current_sim'] = $asOf->toIso8601String();
        file_put_contents($path, json_encode($c, JSON_PRETTY_PRINT));

        return $asOf;
    }

    private function clearResults(): void
    {
        Fixture::query()->update(['status' => 'scheduled', 'team1_score' => null, 'team2_score' => null]);
        Player::query()->update(['goals' => 0, 'assists' => 0, 'rating' => null]);
        $this->stats = [];
    }

    /** Weighted, low-scoring-realistic goal count for one side. */
    private function generateGoals(): int
    {
        $r = mt_rand(1, 100);
        return match (true) {
            $r <= 24 => 0,
            $r <= 54 => 1,
            $r <= 78 => 2,
            $r <= 92 => 3,
            $r <= 98 => 4,
            default => 5,
        };
    }

    private function attributeGoals(?iterable $squad, int $goals): void
    {
        if (! $squad || $goals < 1) {
            return;
        }
        $squad = collect($squad);

        for ($i = 0; $i < $goals; $i++) {
            $scorer = $this->weightedPick($squad, ['FW' => 50, 'MF' => 30, 'DF' => 12, 'GK' => 1]);
            if (! $scorer) {
                continue;
            }
            $this->bump($scorer->id, 'g');

            if (mt_rand(1, 100) <= 65) {
                $assist = $this->weightedPick(
                    $squad->where('id', '!=', $scorer->id),
                    ['MF' => 42, 'FW' => 33, 'DF' => 24, 'GK' => 1]
                );
                if ($assist) {
                    $this->bump($assist->id, 'a');
                }
            }
        }
    }

    private function weightedPick(iterable $squad, array $weights): ?Player
    {
        $pool = [];
        foreach ($squad as $p) {
            $w = $weights[$p->position] ?? 5;
            for ($i = 0; $i < $w; $i++) {
                $pool[] = $p;
            }
        }
        return $pool ? $pool[mt_rand(0, count($pool) - 1)] : null;
    }

    private function bump(int $playerId, string $key): void
    {
        $this->stats[$playerId] ??= ['g' => 0, 'a' => 0, 'r' => null];
        $this->stats[$playerId][$key]++;
    }

    /** Give the likely starting XI of every team that played a match rating. */
    private function assignRatings($players, array $teamIds): void
    {
        $baseSeed = (int) $this->option('seed');
        foreach ($teamIds as $teamId) {
            $squad = $players->get($teamId);
            if (! $squad) {
                continue;
            }
            mt_srand($baseSeed * 7919 + (int) $teamId); // stable base ratings per team
            $starters = collect($squad)->sortBy('number')->take(14);
            foreach ($starters as $p) {
                $base = mt_rand(62, 75) / 10;            // 6.2 – 7.5
                $g = $this->stats[$p->id]['g'] ?? 0;
                $a = $this->stats[$p->id]['a'] ?? 0;
                $rating = min(9.9, round($base + 0.5 * $g + 0.3 * $a, 1));
                $this->stats[$p->id] ??= ['g' => 0, 'a' => 0, 'r' => null];
                $this->stats[$p->id]['r'] = $rating;
            }
        }
    }

    private function persist(): void
    {
        foreach ($this->stats as $playerId => $s) {
            Player::whereKey($playerId)->update([
                'goals' => $s['g'],
                'assists' => $s['a'],
                'rating' => $s['r'],
            ]);
        }
    }
}
