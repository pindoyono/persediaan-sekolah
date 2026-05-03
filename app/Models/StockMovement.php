<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = ['item_id', 'type', 'qty', 'reference_type', 'reference_id'];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo('reference');
    }
}
