<?php

namespace App\Console\Commands;

use App\Models\Player;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Fetches free-licensed player headshots from Wikipedia (images live on
 * Wikimedia Commons) and stores the thumbnail on players.photo_url.
 *
 * Uses the batched MediaWiki action API (up to 50 titles per request) so the
 * whole squad list is covered in ~25 requests — fast and well under rate limits.
 * Players without a match keep photo_url null and fall back to an initials avatar.
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
        {--chunk=50 : Titles per API request (max 50)}';

    protected $description = 'Fetch free-licensed player photos from Wikipedia / Wikimedia Commons';

    private const UA = 'WC2026FanProject/1.0 (Laravel; openfootball data)';
    private const API = 'https://en.wikipedia.org/w/api.php';

    public function handle(): int
    {
        $query = Player::query();
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

        $size = min(50, max(1, (int) $this->option('chunk')));

        // Pass 1: exact player name. Pass 2: "Name (footballer)" for the rest.
        $missing = $this->fetchPass($players, fn (Player $p) => $p->name, $size, $bar);
        if ($missing->isNotEmpty()) {
            $this->fetchPass($missing, fn (Player $p) => $p->name . ' (footballer)', $size, null);
        }

        $bar->finish();
        $this->newLine(2);

        $found = Player::whereNotNull('photo_url')->count();
        $this->info("Done. {$found}/" . Player::count() . ' players now have a photo.');
        return self::SUCCESS;
    }

    /**
     * Resolve a Wikipedia title per player and store the first thumbnail found.
     *
     * @return \Illuminate\Support\Collection<int,Player> players still without a photo
     */
    private function fetchPass($players, callable $title, int $size, $bar)
    {
        $stillMissing = collect();

        foreach ($players->chunk($size) as $chunk) {
            // input title (exact string we send) keyed by player id
            $titles = $chunk->mapWithKeys(fn (Player $p) => [$p->id => $title($p)]);

            $resp = Http::withHeaders(['User-Agent' => self::UA])
                ->timeout(30)
                ->retry(3, 1500, throw: false)
                ->get(self::API, [
                    'action' => 'query',
                    'format' => 'json',
                    'prop' => 'pageimages',
                    'piprop' => 'thumbnail',
                    'pithumbsize' => 320,
                    'redirects' => 1,
                    'titles' => $titles->values()->implode('|'),
                ]);

            $thumbFor = $this->parseThumbnails($resp);

            foreach ($chunk as $p) {
                $url = $thumbFor($titles[$p->id]);
                if ($url) {
                    $p->update(['photo_url' => $url]);
                } else {
                    $stillMissing->push($p);
                }
                if ($bar) {
                    $bar->advance();
                }
            }

            usleep(150_000); // polite gap between batches
        }

        return $stillMissing;
    }

    /**
     * Build a resolver: input title -> thumbnail URL (following normalisation
     * and redirects the API reports).
     */
    private function parseThumbnails($resp): callable
    {
        if (! $resp || ! $resp->ok()) {
            return fn () => null;
        }

        $q = $resp->json('query') ?? [];

        $normalized = [];
        foreach ($q['normalized'] ?? [] as $n) {
            $normalized[$n['from']] = $n['to'];
        }
        $redirects = [];
        foreach ($q['redirects'] ?? [] as $r) {
            $redirects[$r['from']] = $r['to'];
        }
        $byTitle = [];
        foreach ($q['pages'] ?? [] as $page) {
            if (! empty($page['title'])) {
                $byTitle[$page['title']] = $page['thumbnail']['source'] ?? null;
            }
        }

        return function (string $input) use ($normalized, $redirects, $byTitle): ?string {
            $t = $normalized[$input] ?? $input;
            $t = $redirects[$t] ?? $t;
            return $byTitle[$t] ?? null;
        };
    }
}
