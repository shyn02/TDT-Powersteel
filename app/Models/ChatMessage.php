<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChatMessage extends Model
{
    use SoftDeletes;
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
