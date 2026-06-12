<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Player extends Model
{
    protected $fillable = [
        'team_id', 'number', 'position', 'name',
        'date_of_birth', 'goals', 'assists', 'rating',
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
}
