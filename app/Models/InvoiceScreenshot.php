<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceScreenshot extends Model
{
    protected $fillable = [
        'invoice_id',
        'file_path',
        'uploaded_by',
        'note',
    ];

    protected $appends = ['url'];

    public function getUrlAttribute(): string
    {
        return url('/storage/' . $this->file_path);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
