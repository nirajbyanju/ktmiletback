<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExamBooking extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'exam_bookings';

    protected $fillable = [
        'lead_id',
        'user_id',
        'student_name',
        'test_type',
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
        'payment_status',
        'available_slot_checked',
        'pte_login_details_received',
        'pte_username_email',
        'pte_password_notes',
        'admin_notes',
    ];

    protected $casts = [
        'preferred_date'             => 'date:Y-m-d',
        'date_of_birth'              => 'date:Y-m-d',
        'available_slot_checked'     => 'boolean',
        'pte_login_details_received' => 'boolean',
        'pte_username_email'         => 'encrypted',
        'pte_password_notes'         => 'encrypted',
    ];

    public const STATUSES = [
        'new_request',
        'document_pending',
        'payment_pending',
        'booking_in_process',
        'booked',
        'cancelled',
    ];

    public const PAYMENT_STATUSES = [
        'pending',
        'paid',
        'waived',
        'refunded',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
