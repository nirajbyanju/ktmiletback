<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfferClaim extends Model
{
    use HasFactory;

    const SOURCE_COURSE    = 'course';
    const SOURCE_MOCK_TEST = 'mock_test';
    const SOURCE_BOOKING   = 'booking';

    protected $fillable = [
        'user_id',
        'offer_id',
        'offer_claim_date',
        'source_type',
        'source_id',
        'used_at',
    ];

    protected $casts = [
        'offer_claim_date' => 'date',
        'source_id'        => 'integer',
        'used_at'          => 'datetime',
    ];

    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }
}
