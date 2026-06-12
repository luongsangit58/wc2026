<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends Model
{
    protected $fillable = ['letter', 'name'];

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    public function fixtures(): HasMany
    {
        return $this->hasMany(Fixture::class);
    }
}
