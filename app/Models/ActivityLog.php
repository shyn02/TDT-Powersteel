<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActivityLog extends Model
{
    use SoftDeletes;
    public $timestamps = false;

    protected $fillable = ['actor_id', 'action', 'created_at'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public static function log(?User $actor, string $action): self
    {
        return self::create([
            'actor_id' => $actor?->id,
            'action' => $action,
            'created_at' => now(),
        ]);
    }
}
