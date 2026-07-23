<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Offer extends Model
{
    use HasFactory, SoftDeletes;

    const STATUS_ACTIVE = 'active';

    const STATUS_INACTIVE = 'inactive';

    const APPLICABLE_ALL = 'all';

    const APPLICABLE_COURSE = 'course';

    const APPLICABLE_MOCK_TEST = 'mock_test';

    const APPLICABLE_BOOKING = 'booking';

    const APPLICABLE_TYPES = [
        self::APPLICABLE_ALL,
        self::APPLICABLE_COURSE,
        self::APPLICABLE_MOCK_TEST,
        self::APPLICABLE_BOOKING,
    ];

    protected $fillable = [
        'title',
        'description',
        'start_date',
        'valid_date',
        'claim_discount_amount',
        'status',
        'badge',
        'cta_text',
        'cta_url',
        'sort_order',
        'applicable_type',
        'applicable_id',
        'is_referral',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'valid_date' => 'date',
        'claim_discount_amount' => 'decimal:2',
        'is_referral' => 'boolean',
        'sort_order' => 'integer',
        'applicable_id' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'deleted_by' => 'integer',
    ];

    public function isApplicableTo(string $type, ?int $subjectId = null): bool
    {
        if ($this->applicable_type === self::APPLICABLE_ALL) {
            return true;
        }

        if ($this->applicable_type !== $type) {
            return false;
        }

        // Type matches — check specific item if set
        return $this->applicable_id === null || $this->applicable_id === $subjectId;
    }

    public function claims(): HasMany
    {
        return $this->hasMany(OfferClaim::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isExpired(): bool
    {
        return $this->valid_date && $this->valid_date->isPast();
    }

    public function isClaimable(): bool
    {
        return $this->isActive() && ! $this->isExpired();
    }
}
