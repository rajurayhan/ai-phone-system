<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Assistant extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'user_id',
        'vapi_assistant_id',
        'created_by',
        'type',
        'phone_number',
        'webhook_url',
    ];

    /**
     * Get the user that owns the assistant.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user that created the assistant.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope to get assistants for a specific user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get assistants created by a specific user
     */
    public function scopeCreatedBy($query, $userId)
    {
        return $query->where('created_by', $userId);
    }

    /**
     * Scope to get demo assistants
     */
    public function scopeDemo($query)
    {
        return $query->where('type', 'demo');
    }

    /**
     * Scope to get production assistants
     */
    public function scopeProduction($query)
    {
        return $query->where('type', 'production');
    }

    /**
     * Check if assistant is a demo
     */
    public function isDemo(): bool
    {
        return $this->type === 'demo';
    }

    /**
     * Check if assistant is production
     */
    public function isProduction(): bool
    {
        return $this->type === 'production';
    }
    
    /**
     * Get the call logs for this assistant.
     */
    public function callLogs()
    {
        return $this->hasMany(CallLog::class);
    }
}
