<?php

namespace App\Models\data;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

use Illuminate\Database\Eloquent\Model;

class ParkingType extends Model
{
    use HasFactory, SoftDeletes, Auditable;
    protected $table = 'data_parking_type';
    protected $fillable = [
        'label',
        'slug',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
