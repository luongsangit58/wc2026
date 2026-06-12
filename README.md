# World Cup 2026 — Laravel

A faithful rebuild of the public World Cup 2026 information hub
(`dailyworldcup2026.live`) in **Laravel 13 + MySQL**: fixtures, live group
standings, all 48 teams and a live countdown to the next kick-off.

All tournament data comes from the public-domain
[openfootball](https://github.com/openfootball/worldcup.json) dataset
(16 host stadiums, 48 teams, 12 groups, 1,245 squad players, 104 matches).

## Requirements

- PHP 8.4, Composer
- MySQL 8 (a `wc2026` database; default credentials `root` / `root` on `127.0.0.1:3306`)

## Setup

```bash
cd /home/miichi/TMP/wc2026

# 1. create the database (already done in this environment)
mysql -uroot -proot -e "CREATE DATABASE IF NOT EXISTS wc2026 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 2. install deps (already installed)
composer install

# 3. build the schema + import the openfootball data
php artisan migrate:fresh --seed

# 4. run it
php artisan serve
# open http://127.0.0.1:8000
```

DB credentials live in `.env` (`DB_DATABASE=wc2026`, `DB_USERNAME=root`,
`DB_PASSWORD=root`). No front-end build step is required — CSS/JS are plain
files in `public/`.

## Pages

| Route | Page | Highlights |
|-------|------|-----------|
| `/` | Home | Next-match marquee + live JS countdown, "Live Now" block, stat leaderboards (empty-state while the tournament is in progress), 12-group standings preview, quick actions |
| `/fixtures` | Fixtures | All 104 matches grouped by date, filterable by stage / group / date / host city (auto-submitting selects) |
| `/standings` | Standings | 12 group tables (GP/W/D/L/GF/GA/GD/Pts) with qualifying highlight, computed live from results |
| `/teams` | Teams | All 48 nations grouped A–L |
| `/teams/{slug}` | Team profile | Squad by position, the team's fixtures, and its live group table |

## Architecture

```
app/
  Models/        Group, Venue, Team, Player, Fixture (+ relationships, accessors, scopes)
  Services/      StandingsService  — computes group tables from finished fixtures
  Http/Controllers/  Home, Fixtures, Standings, Teams
database/
  migrations/    groups, venues, teams, players, fixtures
  seeders/       WorldCupSeeder — imports storage/app/openfootball/*.json
resources/views/ layouts/app.blade.php + home / fixtures / standings / teams
public/
  css/app.css    single-file design system (dark "pitch" theme)
  js/app.js      countdown, filter auto-submit, local-time conversion, mobile nav
database/data/openfootball/ the source JSON datasets (public domain, git-tracked)
```

## Pages (continued)

| Route | Page |
|-------|------|
| `/fixtures/{id}` | Match detail — score/countdown, group table, both squads |
| `/bracket` | Knockout bracket, Round of 32 → Final, + third-place play-off |
| `/venues` · `/venues/{slug}` | 16 host stadiums + per-venue match list |

## Live results & realtime

Results, standings and stat leaderboards are **computed from match data**, so the
site is "alive" once matches have scores. Two artisan commands drive that:

```bash
php artisan wc:simulate                 # set scores as of the real clock
php artisan wc:simulate --as-of=2026-06-26T20:00:00   # ...as of a chosen moment
php artisan wc:simulate --live-demo      # accelerated demo clock (matches visibly progress)
php artisan wc:simulate --reset          # back to "tournament not started"
php artisan wc:sync [--reseed]           # re-pull the openfootball source data
```

- The browser **auto-refreshes every 30s while any match is live** (see `public/js/app.js`),
  paired with a server-side scheduler that ticks `wc:simulate` every minute
  (`routes/console.php`). Enable it in production with one cron line:
  `* * * * * cd /home/<user>/wc2026.sangtrang.com && php artisan schedule:run >> /dev/null 2>&1`

## Deployment (cPanel + GitHub webhook)

Auto-deploy mirrors the NoteDri setup: push to `main` → GitHub calls
`public/deploy.php` → the server pulls and updates itself. No SSH needed.

**First-time setup on the server (cPanel Terminal):**

```bash
cd /home/<user>/wc2026.sangtrang.com           # the domain's document-root parent
git clone https://github.com/luongsangit58/wc2026.git .
cp .env.example .env                            # then edit DB_* + set APP_KEY + DEPLOY_SECRET
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate:fresh --seed                # build schema + import openfootball data
php artisan wc:simulate                         # optional: populate live results now
```

Point the domain's document root at `…/wc2026.sangtrang.com/public`.

**Update later** — either run `bash deploy.sh` in the Terminal, or set up the webhook:

1. Put a long random `DEPLOY_SECRET=…` in the server `.env`.
2. GitHub repo → Settings → Webhooks → Add webhook:
   - Payload URL: `https://wc2026.sangtrang.com/deploy.php`
   - Content type: `application/json`
   - Secret: the same `DEPLOY_SECRET`
   - Events: just the `push` event.
3. Test in a browser: `https://wc2026.sangtrang.com/deploy.php?token=<DEPLOY_SECRET>`

### Notes

- **Kick-off times** are stored as UTC (`fixtures.kickoff_at`); the original
  wall-clock string (e.g. `13:00 UTC-6`) is kept in `time_label`, and the
  browser localises the display to the visitor's timezone.
- **Knockout placeholders** (`2A`, `W74`, `L101`, `3A/B/C/D/F`) are resolved to
  readable labels ("Runner-up Group A", "Winner of Match 74", …) until the
  bracket fills in.
- The data layer is split behind models + a service, so swapping the seeded
  data for a live results feed later only touches the seeder / a sync job.

### Notes

- **Kick-off times** are stored as UTC (`fixtures.kickoff_at`); the original
  wall-clock string (e.g. `13:00 UTC-6`) is kept in `time_label`, and the
  browser localises the display to the visitor's timezone.
- **Knockout placeholders** (`2A`, `W74`, `L101`, `3A/B/C/D/F`) are resolved to
  readable labels ("Runner-up Group A", "Winner of Match 74", …) until the
  bracket fills in.
- The data layer is split behind models + a service, so swapping the seeded
  data for a live results feed later only touches the seeder / a sync job.
