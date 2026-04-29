<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class UserDevice extends Model
{
    use HasUuids;

    protected $guarded = [];

    /**
     * The user who owns this device.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
