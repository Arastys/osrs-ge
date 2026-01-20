<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'examine',
        'icon',
        'members',
        'limit',
        'high_alch',
        'last_high_price',
        'last_low_price',
        'last_synced_at',
    ];

    protected $casts = [
        'members' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    public function getIconAttribute($value)
    {
        return app(\App\Services\OsrsGeService::class)->getItemIconUrl($value);
    }

    public function alerts()
    {
        return $this->hasMany(ItemAlert::class);
    }
}
