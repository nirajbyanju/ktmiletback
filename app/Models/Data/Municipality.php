<?php

namespace App\Models\Data;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class Municipality extends Model
{
     use HasFactory, SoftDeletes, Auditable;
    protected $table = 'data_municipalities';

    protected $fillable = [
        'label',
        'slug',
        'data_district_id',
    ];

    public function districts()
    {
        return $this->belongsTo(District::class);
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
