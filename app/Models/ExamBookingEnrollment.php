<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExamBookingEnrollment extends Model
{
    use SoftDeletes;

    protected $table = 'exam_bookings_enrollments';

    protected $fillable = [
        'user_id',
        'exam_booking_id',
        'preferred_date',
        'preferred_time',
        'test_location',
        'preferred_test_centre',
        'passport_name',
        'passport_number',
        'date_of_birth',
        'contact_number',
        'phone',
        'email',
        'passport_copy_path',
        'passport_copy_original_name',
        'special_message',
        'status',
        'available_slot_checked',
        'admin_notes',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'preferred_date'         => 'date:Y-m-d',
        'date_of_birth'          => 'date:Y-m-d',
        'available_slot_checked' => 'boolean',
        'created_by'             => 'integer',
        'updated_by'             => 'integer',
        'deleted_by'             => 'integer',
    ];

    public const STATUSES = [
        'new_request',
        'document_pending',
        'payment_pending',
        'booking_in_process',
        'booked',
        'cancelled',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function examBooking(): BelongsTo
    {
        return $this->belongsTo(ExamBooking::class, 'exam_booking_id');
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class, 'exam_booking_enrollment_id');
    }
}
