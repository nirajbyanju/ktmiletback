<?php

namespace App\Models\Data;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class Ward extends Model
{
     use HasFactory, SoftDeletes, Auditable;
    protected $table = 'data_wards';

    protected $fillable = [
        'label',
        'slug',
        'data_municipality_id',
    ];

    public function municipalities()
    {
        return $this->belongsTo(Municipality::class);
    }
    public function wards()
    {
        return $this->hasMany(Ward::class);
    }
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
