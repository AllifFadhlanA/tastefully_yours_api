<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ingredient extends Model
{
    protected $fillable = ['recipe_id', 'name', 'amount'];

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }
}
