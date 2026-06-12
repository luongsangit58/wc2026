<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Player extends Model
{
    protected $fillable = [
        'team_id', 'number', 'position', 'name',
        'date_of_birth', 'goals', 'assists', 'rating', 'photo_url',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'goals' => 'integer',
        'assists' => 'integer',
        'rating' => 'decimal:1',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public const POSITIONS = ['GK' => 'Goalkeeper', 'DF' => 'Defender', 'MF' => 'Midfielder', 'FW' => 'Forward'];

    public function getHasPhotoAttribute(): bool
    {
        return ! empty($this->photo_url);
    }

    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth?->age;
    }

    public function getPositionLabelAttribute(): string
    {
        return self::POSITIONS[$this->position] ?? ($this->position ?? '—');
    }

    /** Up to two initials for the avatar fallback. */
    public function getInitialsAttribute(): string
    {
        $parts = preg_split('/\s+/', trim($this->name)) ?: [];
        $parts = array_values(array_filter($parts));
        if (count($parts) === 0) {
            return '?';
        }
        $first = mb_substr($parts[0], 0, 1);
        $last = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';
        return mb_strtoupper($first . $last);
    }
}
