<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Enrollment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'lead_id',
        'student_name',
        'phone',
        'email',
        'user_id',
        'batch_id',
        'invoice_id',
        'enrollment_date',
        'amount_paid',
        'status',
        'payment_status',
        'crm_status',
        'teacher',
        'attendance_percentage',
        'certificate_eligible',
        'notes',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'lead_id'               => 'integer',
        'user_id'               => 'integer',
        'batch_id'              => 'integer',
        'invoice_id'            => 'integer',
        'enrollment_date'       => 'date:Y-m-d',
        'amount_paid'           => 'decimal:2',
        'attendance_percentage' => 'decimal:2',
        'certificate_eligible'  => 'boolean',
        'created_by'            => 'integer',
        'updated_by'            => 'integer',
        'deleted_by'            => 'integer',
    ];

    public const CRM_STATUSES   = ['lead', 'prospect', 'active', 'inactive', 'completed', 'dropped'];

    // Keys must match payment_statuses.key in the database
    public const PAYMENT_STATUSES = [
        'action_required',
        'under_review',
        'not_verified',
        'fee_waived',
        'confirmed',
        'refund_under_review',
        'refund_not_approved',
        'refund_completed',
    ];

    protected $attributes = [
        'enrollment_date' => null,
    ];

    protected static function booted(): void
    {
        static::creating(function (Enrollment $enrollment) {
            if (empty($enrollment->enrollment_date)) {
                $enrollment->enrollment_date = now()->toDateString();
            }
        });
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
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
