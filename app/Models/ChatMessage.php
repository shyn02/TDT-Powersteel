<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'session_id', 'sender', 'staff_user_id', 'message', 'is_read', 'created_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function session()
    {
        return $this->belongsTo(ChatSession::class, 'session_id');
    }

    public function staffUser()
    {
        return $this->belongsTo(User::class, 'staff_user_id');
    }
}
