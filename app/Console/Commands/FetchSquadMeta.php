<?php

namespace App\Console\Commands;

use App\Models\Player;
use App\Models\Team;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Enriches players with club, club nationality, international caps and goals by
 * parsing the public, authoritative Wikipedia article "2026 FIFA World Cup
 * squads". That page lists every squad with {{nat fs ... player}} templates
 * carrying |no |pos |name |caps |goals |club |clubnat — the same dataset the
 * openfootball squads are derived from, so names/numbers line up cleanly.
 *
 * Idempotent: re-running just refreshes the four fields. Players that can't be
 * matched keep their existing values (club stays null and is simply hidden).
 *
 *   php artisan wc:fetch-squad-meta            # fill missing only
 *   php artisan wc:fetch-squad-meta --force    # overwrite everyone
 */
class FetchSquadMeta extends Command
{
    protected $signature = 'wc:fetch-squad-meta
        {--force : Overwrite club/caps even where already set}';

    protected $description = 'Fetch player club, caps and international goals from Wikipedia (2026 FIFA World Cup squads)';

    private const UA = 'WC2026FanProject/1.0 (Laravel; openfootball data)';
    private const API = 'https://en.wikipedia.org/w/api.php';
    private const PAGE = '2026 FIFA World Cup squads';

    public function handle(): int
    {
        $wikitext = $this->fetchWikitext();
        if ($wikitext === null) {
            $this->error('Could not download the squads page.');
            return self::FAILURE;
        }

        // Build a normalized-name => Team lookup (covers both `name` and `display_name`).
        $teamByName = [];
        foreach (Team::all() as $t) {
            $teamByName[$this->norm($t->name)] = $t;
            $teamByName[$this->norm($t->display_name)] = $t;
        }

        $currentTeam = null;
        $matched = 0;
        $missed = 0;
        $unknownTeams = [];

        // Walk the page line by line: section headers switch the "current team",
        // player templates are parsed and applied against it.
        foreach (preg_split('/\R/', $wikitext) as $line) {
            if (preg_match('/^==+\s*(.+?)\s*==+\s*$/', $line, $h)) {
                $key = $this->norm($h[1]);
                $currentTeam = $teamByName[$key] ?? null;
                if (! $currentTeam && $this->looksLikeTeam($h[1])) {
                    $unknownTeams[$key] = $h[1];
                }
                continue;
            }

            if (! $currentTeam || stripos($line, 'nat fs') === false) {
                continue;
            }

            foreach ($this->parsePlayers($line) as $row) {
                if ($this->apply($currentTeam, $row)) {
                    $matched++;
                } else {
                    $missed++;
                }
            }
        }

        $this->info("Matched {$matched} players, {$missed} unmatched.");
        if ($unknownTeams) {
            $this->warn('Unmatched team headers (skipped): ' . implode(', ', array_values($unknownTeams)));
        }
        $this->line('Coverage: ' . Player::whereNotNull('club')->count() . ' / ' . Player::count() . ' players now have a club.');

        return self::SUCCESS;
    }

    /** Download the raw wikitext of the squads article. */
    private function fetchWikitext(): ?string
    {
        $res = Http::withHeaders(['User-Agent' => self::UA])
            ->retry(2, 1500)
            ->get(self::API, [
                'action' => 'query',
                'prop' => 'revisions',
                'rvprop' => 'content',
                'rvslots' => 'main',
                'titles' => self::PAGE,
                'format' => 'json',
                'formatversion' => '2',
            ]);

        if (! $res->ok()) {
            return null;
        }
        $page = $res->json('query.pages.0', []);
        return $page['revisions'][0]['slots']['main']['content'] ?? null;
    }

    /**
     * Extract every {{nat fs ... player ...}} template on a line.
     * Returns rows of [no, name, caps, goals, club, clubnat].
     *
     * Note the template embeds a nested {{birth date and age2|...}} (its own
     * pipes and }}), so we split on the template marker and read named params
     * across the whole segment rather than trying to bound it on the first }}.
     */
    private function parsePlayers(string $line): array
    {
        $rows = [];
        $parts = preg_split('/\{\{nat fs (?:g |r )?player/', $line);
        // parts[0] is whatever preceded the first template — skip it.
        for ($i = 1; $i < count($parts); $i++) {
            $body = $parts[$i];
            $rows[] = [
                'no' => $this->param($body, 'no'),
                'name' => $this->linkParam($body, 'name'),
                'caps' => $this->param($body, 'caps'),
                'goals' => $this->param($body, 'goals'),
                'club' => $this->linkParam($body, 'club'),
                'clubnat' => $this->param($body, 'clubnat'),
            ];
        }
        return $rows;
    }

    /** Update one player matched within the given team by shirt number, then name. */
    private function apply(Team $team, array $row): bool
    {
        $player = null;
        if ($row['no'] !== null && $row['no'] !== '') {
            $player = $team->players()->where('number', (int) $row['no'])->first();
        }
        if (! $player && $row['name']) {
            $target = $this->norm($row['name']);
            $player = $team->players->first(fn ($p) => $this->norm($p->name) === $target);
        }
        if (! $player) {
            return false;
        }

        if (! $this->option('force') && $player->club) {
            return true; // already enriched
        }

        $player->forceFill([
            'club' => $row['club'] ?: null,
            'club_nat' => $row['clubnat'] ? strtoupper(trim($row['clubnat'])) : null,
            'caps' => is_numeric($row['caps']) ? (int) $row['caps'] : null,
            'intl_goals' => is_numeric($row['goals']) ? (int) $row['goals'] : null,
        ])->save();

        return true;
    }

    /** Plain scalar param (numbers, country codes): value up to the next | or brace. */
    private function param(string $body, string $key): ?string
    {
        if (preg_match('/\|\s*' . preg_quote($key, '/') . '\s*=\s*([^|{}\n]*)/', $body, $m)) {
            return trim($m[1]);
        }
        return null;
    }

    /**
     * Wikilink-valued param: "[[PSV Eindhoven]]" -> "PSV Eindhoven",
     * "[[Inter Milan|Inter]]" -> "Inter". Falls back to plain text.
     */
    private function linkParam(string $body, string $key): string
    {
        if (preg_match('/\|\s*' . preg_quote($key, '/') . '\s*=\s*\[\[([^\]]+)\]\]/', $body, $m)) {
            $parts = explode('|', $m[1]);          // [[Target|Display]] -> Display
            return trim(end($parts));
        }
        return (string) $this->param($body, $key);
    }

    private function norm(string $s): string
    {
        $s = Str::ascii($s);
        $s = str_replace('&', 'and', $s);
        $s = preg_replace('/[^a-z0-9]+/', ' ', strtolower($s));
        return trim($s);
    }

    /** Heuristic: a header is a team name (not "Group A", "References", etc.). */
    private function looksLikeTeam(string $h): bool
    {
        return ! preg_match('/^(group |references|notes|see also|external|statistics|coach|squads?$)/i', trim($h))
            && ! preg_match('/^[A-L]$/', trim($h));
    }
}
