<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    protected $fillable = [
        'name', 'slug', 'name_normalised', 'fifa_code',
        'confederation', 'continent', 'flag_emoji', 'group_id',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }

    /** Preferred English display name. */
    public function getDisplayNameAttribute(): string
    {
        return $this->name_normalised ?: $this->name;
    }

    /** All fixtures this team appears in (home or away), ordered by kickoff. */
    public function fixtures()
    {
        return Fixture::query()
            ->where('team1_id', $this->id)
            ->orWhere('team2_id', $this->id)
            ->orderBy('kickoff_at');
    }
}
