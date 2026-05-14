<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportChannel extends Model
{
    use HasFactory;

    protected $fillable = [
        'channel_type',
        'contact_value',
    ];
}
