<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Inquiry;
use App\Models\User;
use App\Models\Data\ContactMethod;
use App\Models\Data\FollowupStatus;
use App\Models\Inquery;

class InqueryFollowup extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'inqury_followups';

    protected $fillable = [
        'inquiry_id',
        'admin_id',
        'contact_method_id',
        'followup_status_id',
        'message',
        'next_followup_date',
    ];

    public function inquiry()
    {
        return $this->belongsTo(Inquery::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function contactMethod()
    {
        return $this->belongsTo(ContactMethod::class);
    }

    public function followupStatus()
    {
        return $this->belongsTo(FollowupStatus::class);
    }
}
