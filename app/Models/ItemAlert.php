<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemAlert extends Model
{
    protected $fillable = [
        'user_id',
        'item_id',
        'type',
        'target_value',
        'threshold_price',
        'direction',
        'webhook_url',
        'cooldown_minutes',
        'is_active',
        'baseline_price',
        'last_triggered_at',
        'last_notified_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_triggered_at' => 'datetime',
        'last_notified_at' => 'datetime',
        'threshold_price' => 'integer',
        'baseline_price' => 'integer',
        'target_value' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function logs()
    {
        return $this->hasMany(AlertLog::class);
    }
}
