<?php

namespace App\Console\Commands;

use App\Models\Player;
use Illuminate\Console\Command;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;

/**
 * Fetches free-licensed player headshots from Wikipedia (images live on
 * Wikimedia Commons). Stores the thumbnail URL on players.photo_url; players
 * without a match keep photo_url null and fall back to an initials avatar.
 *
 *   php artisan wc:fetch-photos                 # only players still missing a photo
 *   php artisan wc:fetch-photos --team=france   # one squad
 *   php artisan wc:fetch-photos --force         # refetch everyone
 */
class FetchPlayerPhotos extends Command
{
    protected $signature = 'wc:fetch-photos
        {--force : Refetch even players that already have a photo}
        {--team= : Limit to a single team slug}
        {--chunk=20 : Concurrent requests per batch}';

    protected $description = 'Fetch free-licensed player photos from Wikipedia / Wikimedia Commons';

    private const UA = 'WC2026FanProject/1.0 (Laravel; openfootball data)';
    private const SUMMARY = 'https://en.wikipedia.org/api/rest_v1/page/summary/';

    public function handle(): int
    {
        $query = Player::query()->with('team');
        if (! $this->option('force')) {
            $query->whereNull('photo_url');
        }
        if ($slug = $this->option('team')) {
            $query->whereHas('team', fn ($q) => $q->where('slug', $slug));
        }
        $players = $query->get();

        if ($players->isEmpty()) {
            $this->info('Nothing to fetch — every targeted player already has a photo.');
            return self::SUCCESS;
        }

        $this->info("Fetching photos for {$players->count()} players from Wikipedia…");
        $bar = $this->output->createProgressBar($players->count());
        $bar->start();

        $size = max(5, (int) $this->option('chunk'));

        // Pass 1: exact player name.
        $missing = $this->fetchPass($players, fn (Player $p) => $p->name, $size, $bar);
        // Pass 2: disambiguated "Name (footballer)" for whoever is still missing.
        if ($missing->isNotEmpty()) {
            $this->fetchPass($missing, fn (Player $p) => $p->name . ' (footballer)', $size, $bar, true);
        }

        $bar->finish();
        $this->newLine(2);

        $found = Player::whereNotNull('photo_url')->count();
        $this->info("Done. {$found}/" . Player::count() . ' players now have a photo.');
        return self::SUCCESS;
    }

    /**
     * Resolve a title for each player and store the first thumbnail found.
     *
     * @return \Illuminate\Support\Collection<int,Player> players still without a photo
     */
    private function fetchPass($players, callable $title, int $size, $bar, bool $secondPass = false)
    {
        $stillMissing = collect();

        foreach ($players->chunk($size) as $chunk) {
            $responses = Http::pool(fn (Pool $pool) => $chunk->map(
                fn (Player $p) => $pool->as((string) $p->id)
                    ->withHeaders(['User-Agent' => self::UA, 'accept' => 'application/json'])
                    ->timeout(15)
                    ->retry(3, 800, throw: false)   // ride out transient 429/5xx
                    ->get(self::SUMMARY . rawurlencode($title($p)))
            )->all());

            foreach ($chunk as $p) {
                $url = $this->extractThumb($responses[(string) $p->id] ?? null);
                if ($url) {
                    $p->update(['photo_url' => $url]);
                } else {
                    $stillMissing->push($p);
                }
                if (! $secondPass) {
                    $bar->advance();
                }
            }

            usleep(500_000); // stay polite to the API
        }

        return $stillMissing;
    }

    private function extractThumb($resp): ?string
    {
        if (! $resp || ! $resp->ok()) {
            return null;
        }
        $d = $resp->json();
        if (($d['type'] ?? '') === 'standard' && ! empty($d['thumbnail']['source'])) {
            return $d['thumbnail']['source'];
        }
        return null;
    }
}
