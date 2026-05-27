<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_UNPAID = 'unpaid';
    public const STATUS_PAID   = 'paid';

    // Which business entity this invoice covers
    public const TYPE_COURSE    = 'course';
    public const TYPE_MOCK_TEST = 'mock_test';
    public const TYPE_EXAM      = 'exam';

    protected $fillable = [
        'invoice_number',
        'user_id',
        'batch_id',
        'mock_test_subscription_id',
        'exam_booking_enrollment_id',
        'subtotal_npr',
        'discount_npr',
        'tax_npr',
        'total_npr',
        'status',
        'payment_method',
        'invoice_date',
        'due_date',
        'verified_at',
        'verified_by',
        'notes',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'user_id'                    => 'integer',
        'batch_id'                   => 'integer',
        'mock_test_subscription_id'    => 'integer',
        'exam_booking_enrollment_id'   => 'integer',
        'subtotal_npr'               => 'decimal:2',
        'discount_npr'               => 'decimal:2',
        'tax_npr'                    => 'decimal:2',
        'total_npr'                  => 'decimal:2',
        'invoice_date'               => 'date:Y-m-d',
        'due_date'                   => 'date:Y-m-d',
        'verified_at' => 'datetime',
        'created_by'  => 'integer',
        'updated_by'  => 'integer',
        'deleted_by'  => 'integer',
    ];

    // Derived type based on which FK is set
    public function getTypeAttribute(): string
    {
        if ($this->batch_id) return self::TYPE_COURSE;
        if ($this->mock_test_subscription_id) return self::TYPE_MOCK_TEST;
        if ($this->exam_booking_enrollment_id) return self::TYPE_EXAM;
        return 'unknown';
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function mockTestSubscription(): BelongsTo
    {
        return $this->belongsTo(MockTestSubscription::class);
    }

    public function examBookingEnrollment(): BelongsTo
    {
        return $this->belongsTo(ExamBookingEnrollment::class, 'exam_booking_enrollment_id');
    }

    // Course enrollment activated on payment
    public function enrollment(): HasOne
    {
        return $this->hasOne(Enrollment::class);
    }

    // Mock test enrollment activated on payment
    public function mockTestEnrollment(): HasOne
    {
        return $this->hasOne(MockTestEnrollment::class);
    }
}
