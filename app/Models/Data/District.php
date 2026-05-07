<?php

namespace App\Models\Data;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class District extends Model
{
     use HasFactory, SoftDeletes, Auditable;
    protected $table = 'data_districts';

    protected $fillable = [
        'label',
        'slug',
        'data_province_id',
    ];

    public function provinces()
    {
        return $this->hasMany(Province::class);
    }
    public function municipalities()
    {
        return $this->hasMany(Municipality::class);
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
