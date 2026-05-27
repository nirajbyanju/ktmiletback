<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class MockTestSubscription extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'subscriptions_name',
        'subscriptions_type',
        'subscriptions_category',
        'company_name',
        'country',
        'price',
        'discount',
        'duration',
        'duration_type',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'price'    => 'decimal:2',
        'discount' => 'decimal:2',
        'duration' => 'integer',
    ];

    public function enrollments(): HasMany
    {
        return $this->hasMany(MockTestEnrollment::class, 'subscription_id');
    }

    public function latestEnrollment(): HasOne
    {
        return $this->hasOne(MockTestEnrollment::class, 'subscription_id')->latestOfMany();
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'mock_test_subscription_id');
    }
}
