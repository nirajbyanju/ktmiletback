<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MockTestEnrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscription_id',
        'invoice_id',
        'user_id',
        'enrollment_date',
        'subscription_start',
        'subscription_end',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'subscription_id'    => 'integer',
        'invoice_id'         => 'integer',
        'user_id'            => 'integer',
        'enrollment_date'    => 'date:Y-m-d',
        'subscription_start' => 'date:Y-m-d',
        'subscription_end'   => 'date:Y-m-d',
        'created_by'         => 'integer',
        'updated_by'         => 'integer',
        'deleted_by'         => 'integer',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(MockTestSubscription::class, 'subscription_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
