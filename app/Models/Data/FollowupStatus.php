<?php

namespace App\Models\Data;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class FollowupStatus extends Model
{
    use HasFactory, SoftDeletes, Auditable;
    protected $table = 'data_followup_status';

    protected $fillable = [
        'label',
        'slug',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
