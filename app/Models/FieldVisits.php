<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Auditable;

class FieldVisits extends Model
{
    use HasFactory, SoftDeletes, Auditable;

     protected $table = 'field_visits';

     protected $fillable = [
        'property_id',
        'date',
        'time',
        'name',
        'phone',
        'email',
        'message',
        'remarks',
        'accept_term',
        'status'
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }
}
