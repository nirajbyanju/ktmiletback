<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContactMessage extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'contact_messages';

    protected $fillable = [
        'user_id',
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
