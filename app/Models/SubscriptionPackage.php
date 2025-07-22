<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'voice_agents_limit',
        'monthly_minutes_limit',
        'features',
        'support_level',
        'analytics_level',
        'is_popular',
        'is_active',
    ];

    protected $casts = [
        'features' => 'array',
        'is_popular' => 'boolean',
        'is_active' => 'boolean',
        'price' => 'decimal:2',
    ];

    public function subscriptions()
    {
        return $this->hasMany(UserSubscription::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePopular($query)
    {
        return $query->where('is_popular', true);
    }

    public function isUnlimited($field)
    {
        return $this->getAttribute($field) === -1;
    }

    public function getFormattedPriceAttribute()
    {
        return '$' . number_format($this->price, 0);
    }

    public function getFormattedVoiceAgentsLimitAttribute()
    {
        return $this->isUnlimited('voice_agents_limit') ? 'Unlimited' : $this->voice_agents_limit;
    }

    public function getFormattedMinutesLimitAttribute()
    {
        return $this->isUnlimited('monthly_minutes_limit') ? 'Unlimited' : number_format($this->monthly_minutes_limit);
    }
}
