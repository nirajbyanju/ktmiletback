<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ContactMessage extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'contact_messages';

    protected $fillable = [
        'full_name',
        'email',
        'whatsapp_number',
        'subject',
        'message',
        'status',
        'admin_notes',
        'ip_address',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public const STATUSES = [
        'new',
        'read',
        'in_progress',
        'replied',
        'resolved',
        'spam',
    ];
}
