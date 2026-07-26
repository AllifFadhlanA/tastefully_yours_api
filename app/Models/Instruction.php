<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Instruction extends Model
{
    protected $fillable = ['recipe_id', 'step_number', 'description'];

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }
}
