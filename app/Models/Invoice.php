<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Invoice extends Model
{
    use HasFactory;

    public const STATUS_UNPAID = 'unpaid';
    public const STATUS_PAID = 'paid';

    protected $fillable = [
        'invoice_number',
        'user_id',
        'batch_id',
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
    ];

    protected $casts = [
        'user_id' => 'integer',
        'batch_id' => 'integer',
        'subtotal_npr' => 'decimal:2',
        'discount_npr' => 'decimal:2',
        'tax_npr' => 'decimal:2',
        'total_npr' => 'decimal:2',
        'invoice_date' => 'date:Y-m-d',
        'due_date' => 'date:Y-m-d',
        'verified_at' => 'datetime',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function enrollment(): HasOne
    {
        return $this->hasOne(Enrollment::class);
    }
}
