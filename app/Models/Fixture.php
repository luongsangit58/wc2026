<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Fixture extends Model
{
    protected $fillable = [
        'num', 'stage', 'round_label', 'matchday', 'group_id', 'venue_id',
        'match_date', 'time_label', 'kickoff_at',
        'team1_id', 'team2_id', 'team1_placeholder', 'team2_placeholder',
        'status', 'team1_score', 'team2_score',
    ];

    protected $casts = [
        'match_date' => 'date',
        'kickoff_at' => 'datetime',
        'matchday' => 'integer',
        'num' => 'integer',
        'team1_score' => 'integer',
        'team2_score' => 'integer',
    ];

    /** Stage keys in tournament order, with human labels. */
    public const STAGES = [
        'group' => 'Group Stage',
        'round_of_32' => 'Round of 32',
        'round_of_16' => 'Round of 16',
        'quarter_final' => 'Quarter-Finals',
        'semi_final' => 'Semi-Finals',
        'third_place' => 'Third Place',
        'final' => 'Final',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function team1(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team1_id');
    }

    public function team2(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team2_id');
    }

    /* ------------------------------------------------------------------ */
    /* Scopes                                                              */
    /* ------------------------------------------------------------------ */

    public function scopeGroupStage(Builder $q): Builder
    {
        return $q->where('stage', 'group');
    }

    public function scopeKnockout(Builder $q): Builder
    {
        return $q->where('stage', '!=', 'group');
    }

    public function scopeLive(Builder $q): Builder
    {
        return $q->where('status', 'live');
    }

    public function scopeFinished(Builder $q): Builder
    {
        return $q->where('status', 'finished');
    }

    public function scopeUpcoming(Builder $q): Builder
    {
        return $q->where('status', 'scheduled');
    }

    public function scopeChrono(Builder $q): Builder
    {
        return $q->orderBy('kickoff_at')->orderBy('num');
    }

    /* ------------------------------------------------------------------ */
    /* Accessors / helpers                                                 */
    /* ------------------------------------------------------------------ */

    public function getStageLabelAttribute(): string
    {
        return self::STAGES[$this->stage] ?? ucfirst(str_replace('_', ' ', $this->stage));
    }

    public function getIsKnockoutAttribute(): bool
    {
        return $this->stage !== 'group';
    }

    public function getIsFinishedAttribute(): bool
    {
        return $this->status === 'finished';
    }

    public function getIsLiveAttribute(): bool
    {
        return $this->status === 'live';
    }

    public function getHasScoreAttribute(): bool
    {
        return $this->team1_score !== null && $this->team2_score !== null;
    }

    /** Approximate clock minute while a match is live (1–90+). */
    public function getLiveMinuteAttribute(): ?int
    {
        if (! $this->is_live || ! $this->kickoff_at) {
            return null;
        }
        $elapsed = (int) $this->kickoff_at->diffInMinutes(static::effectiveNow());
        return max(1, min(90, $elapsed));
    }

    /**
     * "Now" from the app's point of view. During an accelerated live demo this
     * is the simulated clock (storage/app/sim-clock.json); otherwise real time.
     */
    protected static ?Carbon $effectiveNow = null;

    public static function effectiveNow(): Carbon
    {
        if (static::$effectiveNow) {
            return static::$effectiveNow;
        }
        $path = storage_path('app/sim-clock.json');
        if (is_file($path)) {
            $clock = json_decode(file_get_contents($path), true);
            if (! empty($clock['current_sim'])) {
                return static::$effectiveNow = Carbon::parse($clock['current_sim']);
            }
        }
        return static::$effectiveNow = now();
    }

    /** Display label for side 1: real team name, else bracket placeholder. */
    public function getTeam1LabelAttribute(): string
    {
        return $this->team1?->display_name ?? $this->placeholderLabel($this->team1_placeholder);
    }

    public function getTeam2LabelAttribute(): string
    {
        return $this->team2?->display_name ?? $this->placeholderLabel($this->team2_placeholder);
    }

    /** Turn raw bracket codes (2A, W74, L101) into readable text. */
    protected function placeholderLabel(?string $code): string
    {
        if (! $code) {
            return 'TBD';
        }
        if (preg_match('/^([12])([A-L])$/', $code, $m)) {
            return ($m[1] === '1' ? 'Winner Group ' : 'Runner-up Group ') . $m[2];
        }
        // Round-of-32 best-third-placed slots, e.g. "3A/B/C/D/F".
        if (preg_match('#^3([A-L](?:/[A-L])+)$#', $code, $m)) {
            return '3rd Place · Group ' . $m[1];
        }
        if (preg_match('/^W(\d+)$/', $code, $m)) {
            return 'Winner of Match ' . $m[1];
        }
        if (preg_match('/^L(\d+)$/', $code, $m)) {
            return 'Loser of Match ' . $m[1];
        }
        return $code;
    }
}
