<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChatSession extends Model
{
    use SoftDeletes;
    public $timestamps = false;

    protected $fillable = [
        'session_token', 'client_name', 'page', 'is_active', 'created_at',
        'last_message_at', 'assigned_to', 'status', 'is_priority',
        'token_version', 'revoked_at', 'expires_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_priority' => 'boolean',
        'created_at' => 'datetime',
        'last_message_at' => 'datetime',
        'revoked_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function assignedRep()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function messages()
    {
        return $this->hasMany(ChatMessage::class, 'session_id');
    }

    public function getUnreadCountAttribute(): int
    {
        return $this->messages()->where('sender', 'client')->where('is_read', false)->count();
    }
}
