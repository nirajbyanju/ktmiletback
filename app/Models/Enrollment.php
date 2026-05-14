<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_name',
        'user_id',
        'batch_id',
        'invoice_id',
        'enrollment_date',
        'amount_paid',
        'status',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'batch_id' => 'integer',
        'invoice_id' => 'integer',
        'enrollment_date' => 'date:Y-m-d',
        'amount_paid' => 'decimal:2',
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
