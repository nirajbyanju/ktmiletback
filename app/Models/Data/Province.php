<?php

namespace App\Models\Data;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;


class Province extends Model
{
    use HasFactory, SoftDeletes, Auditable;
    protected $table = 'data_provinces';

    protected $fillable = [
        'label',
        'slug',
    ];

    public function districts()
    {
        return $this->hasMany(District::class);
    }

    public function municipalities()
    {
        return $this->hasMany(Municipality::class);
    }
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
