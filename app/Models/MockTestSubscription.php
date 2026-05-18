<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MockTestSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscription_id',
        'lead_id',
        'student_name',
        'phone',
        'course',
        'package',
        'price',
        'payment_status',
        'payment_screenshot_link',
        'login_email',
        'login_sent',
        'subscription_start',
        'subscription_end',
        'platform',
        'admin_notes',
        'user_id',
    ];

    protected $casts = [
        'price'              => 'decimal:2',
        'login_sent'         => 'boolean',
        'subscription_start' => 'date:Y-m-d',
        'subscription_end'   => 'date:Y-m-d',
        'user_id'            => 'integer',
    ];

    public const PAYMENT_STATUSES = ['not_paid', 'partial', 'paid', 'waived', 'refunded'];

    protected static function booted(): void
    {
        static::creating(function (MockTestSubscription $sub) {
            if (empty($sub->subscription_id)) {
                $sub->subscription_id = static::generateSubscriptionId();
            }
        });
    }

    private static function generateSubscriptionId(): string
    {
        $date   = now()->format('Ymd');
        $prefix = "MOCK-{$date}-";

        $last = static::where('subscription_id', 'like', $prefix . '%')
            ->orderByDesc('subscription_id')
            ->value('subscription_id');

        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
