<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'company',
        'bio',
        'status',
        'profile_picture',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the profile picture URL
     */
    public function getProfilePictureAttribute($value)
    {
        if ($value) {
            return Storage::disk('public')->url($value);
        }
        return null;
    }

    /**
     * Check if user is an admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Get the assistants owned by the user.
     */
    public function assistants()
    {
        return $this->hasMany(Assistant::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(UserSubscription::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function activeSubscription()
    {
        return $this->hasOne(UserSubscription::class)->where('status', 'active')->orWhere('status', 'trial');
    }

    public function hasActiveSubscription()
    {
        return $this->activeSubscription()->exists();
    }

    public function getCurrentSubscriptionAttribute()
    {
        return $this->activeSubscription;
    }

    public function canCreateAssistant()
    {
        if (!$this->hasActiveSubscription()) {
            return false;
        }

        $subscription = $this->currentSubscription;
        $package = $subscription->package;
        
        // Check if unlimited or within limit
        if ($package->isUnlimited('voice_agents_limit')) {
            return true;
        }

        $currentCount = $this->assistants()->count();
        return $currentCount < $package->voice_agents_limit;
    }

    public function getRemainingAssistantsAttribute()
    {
        if (!$this->hasActiveSubscription()) {
            return 0;
        }

        $subscription = $this->currentSubscription;
        $package = $subscription->package;
        
        if ($package->isUnlimited('voice_agents_limit')) {
            return -1; // Unlimited
        }

        $currentCount = $this->assistants()->count();
        return max(0, $package->voice_agents_limit - $currentCount);
    }
}
