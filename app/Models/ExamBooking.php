<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamBooking extends Model
{
    protected $table = 'exam_bookings';

    protected $fillable = [
        'exam_name',
        'exam_type',
        'price',
        'discount',
    ];

    protected $casts = [
        'price'    => 'decimal:2',
        'discount' => 'decimal:2',
    ];

    public const EXAM_TYPES = ['IELTS', 'PTE', 'TOEFL', 'Duolingo'];

    public function enrollments(): HasMany
    {
        return $this->hasMany(ExamBookingEnrollment::class, 'exam_booking_id');
    }
}
