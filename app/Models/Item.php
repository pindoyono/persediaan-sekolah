<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class Item extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = ['kode', 'name', 'category_id', 'satuan', 'min_stock'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['kode', 'name', 'category_id', 'satuan', 'min_stock'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "Item {$eventName}");
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function transactionDetails(): HasMany
    {
        return $this->hasMany(TransactionDetail::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Get current stock (calculated from stock_movements — source of truth).
     */
    public function currentStock(): int
    {
        $in  = $this->stockMovements()->where('type', 'IN')->sum('qty');
        $out = $this->stockMovements()->where('type', 'OUT')->sum('qty');

        return (int) ($in - $out);
    }

    public function isLowStock(): bool
    {
        return $this->currentStock() <= $this->min_stock;
    }
}
