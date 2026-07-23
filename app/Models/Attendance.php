<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    public const STATUS_PRESENT = 'present';

    public const STATUS_ABSENT = 'absent';

    public const STATUSES = [self::STATUS_PRESENT, self::STATUS_ABSENT];

    protected $fillable = [
        'enrollment_id',
        'demo_request_id',
        'batch_id',
        'attended_on',
        'status',
        'marked_by',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'attended_on' => 'date',
            'enrollment_id' => 'integer',
            'demo_request_id' => 'integer',
            'batch_id' => 'integer',
            'marked_by' => 'integer',
        ];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function demoRequest(): BelongsTo
    {
        return $this->belongsTo(DemoRequest::class);
    }

    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by');
    }
}
