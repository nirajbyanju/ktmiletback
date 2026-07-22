<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Referral extends Model
{
    const STATUS_PENDING = 'pending';   // friend signed up, not yet paid

    const STATUS_QUALIFIED = 'qualified'; // friend's first course payment verified → referrer rewarded

    protected $fillable = [
        'referrer_id',
        'referred_user_id',
        'referred_email',
        'status',
        'friend_claim_id',
        'referrer_claim_id',
        'qualifying_invoice_id',
        'qualified_at',
    ];

    protected $casts = [
        'referrer_id' => 'integer',
        'referred_user_id' => 'integer',
        'qualified_at' => 'datetime',
    ];

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referredUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }
}
