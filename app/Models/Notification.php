<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'data'    => 'array',
        ];
    }

    /**
     * The user this notification belongs to (via polymorphic notifiable).
     * notifiable_type = App\Models\User, notifiable_id = user uuid
     */
    public function notifiable()
    {
        return $this->morphTo();
    }
}
