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
        'is_popular' => 'boolean',
        'is_active' => 'boolean',
        'price' => 'decimal:2',
    ];

    /**
     * Get features as array (split from comma-separated string)
     */
    public function getFeaturesArrayAttribute()
    {
        if (empty($this->features)) {
            return [];
        }
        return array_map('trim', explode(',', $this->features));
    }

    /**
     * Set features from array (join as comma-separated string)
     */
    public function setFeaturesArrayAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['features'] = implode(',', $value);
        } else {
            $this->attributes['features'] = $value;
        }
    }

    /**
     * Get features as array for backward compatibility
     */
    public function getFeaturesAttribute($value)
    {
        if (empty($value)) {
            return [];
        }
        return array_map('trim', explode(',', $value));
    }

    /**
     * Set features as comma-separated string
     */
    public function setFeaturesAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['features'] = implode(',', $value);
        } else {
            $this->attributes['features'] = $value;
        }
    }

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
