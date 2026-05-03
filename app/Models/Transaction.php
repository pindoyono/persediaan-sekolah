<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class Transaction extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = ['kode', 'type', 'tanggal', 'created_by', 'keterangan'];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['kode', 'type', 'tanggal', 'keterangan'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "Transaction {$eventName}");
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function details(): HasMany
    {
        return $this->hasMany(TransactionDetail::class);
    }
}
