<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DemoRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'company_name',
        'industry',
        'country',
        'services',
        'status',
        'notes',
        'contacted_at',
        'completed_at',
    ];

    protected $casts = [
        'contacted_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected $appends = [
        'status_badge_class',
        'status_display_name',
    ];

    /**
     * Get status badge class
     */
    public function getStatusBadgeClassAttribute()
    {
        return match($this->status) {
            'pending' => 'bg-yellow-100 text-yellow-800',
            'contacted' => 'bg-blue-100 text-blue-800',
            'completed' => 'bg-green-100 text-green-800',
            'cancelled' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Get status display name
     */
    public function getStatusDisplayNameAttribute()
    {
        return match($this->status) {
            'pending' => 'Pending',
            'contacted' => 'Contacted',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            default => 'Unknown',
        };
    }

    /**
     * Scope for pending requests
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for contacted requests
     */
    public function scopeContacted($query)
    {
        return $query->where('status', 'contacted');
    }

    /**
     * Scope for completed requests
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for cancelled requests
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }
} 