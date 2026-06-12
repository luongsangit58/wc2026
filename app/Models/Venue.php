<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Venue extends Model
{
    protected $fillable = ['name', 'city', 'slug', 'country_code', 'capacity', 'timezone', 'coords'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function fixtures(): HasMany
    {
        return $this->hasMany(Fixture::class);
    }

    /** Map ISO country code to a flag emoji for display. */
    public function getCountryFlagAttribute(): string
    {
        return match (strtolower($this->country_code)) {
            'us' => '🇺🇸',
            'ca' => '🇨🇦',
            'mx' => '🇲🇽',
            default => '🏟️',
        };
    }
}
