<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'parent_enrollment_id',
        'enrollment_date',
        'amount_paid',
        'status',
        'payment_status',
        'crm_status',
        'teacher',
        'attendance_percentage',
        'start_date',
        'end_date',
        'preferred_schedule',
        'certificate_eligible',
        'certificate_sent_at',
        'notes',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'lead_id' => 'integer',
        'user_id' => 'integer',
        'batch_id' => 'integer',
        'invoice_id' => 'integer',
        'enrollment_date' => 'date:Y-m-d',
        'amount_paid' => 'decimal:2',
        'attendance_percentage' => 'decimal:2',
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
        'certificate_eligible' => 'boolean',
        'certificate_sent_at' => 'datetime',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'deleted_by' => 'integer',
    ];

    public const CRM_STATUSES = ['lead', 'prospect', 'active', 'inactive', 'completed', 'dropped'];

    /** A student's course is complete once their own end_date has passed. */
    public function isExpired(): bool
    {
        return $this->end_date !== null && $this->end_date->lt(now()->startOfDay());
    }

    /** Minimum attendance required to earn the completion certificate. */
    public const CERTIFICATE_MIN_ATTENDANCE = 80.0;

    /**
     * A student earns the completion certificate once their course has ended and
     * they kept at least 80% attendance. An admin can also grant it manually by
     * ticking `certificate_eligible`.
     */
    public function isCertificateEligible(): bool
    {
        if (! $this->isExpired()) {
            return false;
        }

        if ($this->certificate_eligible) {
            return true;
        }

        return $this->attendance_percentage !== null
            && (float) $this->attendance_percentage >= self::CERTIFICATE_MIN_ATTENDANCE;
    }

    /** Exposed to the frontend so the dashboard can show a download button. */
    protected function certificateAvailable(): Attribute
    {
        return Attribute::get(fn (): bool => $this->isCertificateEligible());
    }

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

    /** @var list<string> */
    protected $appends = ['certificate_available'];

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

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Group booking: the paying leader this member belongs to. */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class, 'parent_enrollment_id');
    }

    /** Group booking: the members riding on this leader enrollment. */
    public function members(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'parent_enrollment_id');
    }
}
