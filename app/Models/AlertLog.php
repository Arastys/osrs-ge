<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlertLog extends Model
{
    protected $fillable = [
        'item_alert_id',
        'triggered_price',
    ];

    public function alert()
    {
        return $this->belongsTo(ItemAlert::class, 'item_alert_id');
    }
}
