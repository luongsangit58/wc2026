<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Pulls the latest public-domain openfootball World Cup 2026 datasets into
 * storage/. This is the seam where a real live-results feed would plug in:
 * swap the source URLs for an API and re-run the importer.
 *
 *   php artisan wc:sync               # download latest JSON
 *   php artisan wc:sync --reseed      # ...and rebuild the database from it
 */
class SyncOpenfootball extends Command
{
    protected $signature = 'wc:sync {--reseed : Rebuild the database from the freshly downloaded data}';

    protected $description = 'Refresh the openfootball World Cup 2026 source datasets';

    private const BASE = 'https://raw.githubusercontent.com/openfootball/worldcup.json/master/2026/';

    private const FILES = [
        'worldcup.json',
        'worldcup.groups.json',
        'worldcup.teams.json',
        'worldcup.stadiums.json',
        'worldcup.squads.json',
        'worldcup.quali_playoffs.json',
    ];

    public function handle(): int
    {
        $dir = database_path('data/openfootball');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $ok = 0;
        foreach (self::FILES as $file) {
            $this->line("Fetching {$file} …");
            try {
                $resp = Http::timeout(20)->get(self::BASE . $file);
            } catch (\Throwable $e) {
                $this->error("  network error: {$e->getMessage()}");
                continue;
            }
            if ($resp->failed()) {
                $this->error("  HTTP {$resp->status()} for {$file}");
                continue;
            }
            $body = $resp->body();
            try {
                json_decode($body, true, 512, JSON_THROW_ON_ERROR); // reject HTML error/redirect pages
            } catch (\JsonException $e) {
                $this->error('  response was not valid JSON — skipped (existing file kept)');
                continue;
            }
            file_put_contents("{$dir}/{$file}", $body);
            $this->info('  saved ' . number_format(strlen($body)) . ' bytes');
            $ok++;
        }

        $this->newLine();
        $this->info("Synced {$ok}/" . count(self::FILES) . ' files.');

        if ($this->option('reseed')) {
            $this->warn('Rebuilding database from fresh data…');
            $this->call('migrate:fresh', ['--seed' => true, '--force' => true]);
            $this->line('Tip: run `php artisan wc:simulate --live-demo` to restart the live demo.');
        }

        return self::SUCCESS;
    }
}
