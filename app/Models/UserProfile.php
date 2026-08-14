<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    protected $fillable = ['user_id', 'position', 'contact_number'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
