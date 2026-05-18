<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Transaction extends Model
{
    protected $fillable = [
        'reference', 'user_id', 'film_id', 'amount', 'currency',
        'payment_method', 'provider_transaction_id', 'status',
        'provider_response', 'retry_count', 'country', 'phone', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'provider_response' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Transaction $transaction) {
            $transaction->reference = (string) Str::uuid();
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function film()
    {
        return $this->belongsTo(Film::class);
    }

    public function access()
    {
        return $this->hasOne(UserAccess::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function canRetry(): bool
    {
        return $this->status === 'failed' && $this->retry_count < 2;
    }
}
