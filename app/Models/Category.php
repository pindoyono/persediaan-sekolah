<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class Category extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = ['kode', 'name', 'slug'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['kode', 'name', 'slug'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "Category {$eventName}");
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }
}
