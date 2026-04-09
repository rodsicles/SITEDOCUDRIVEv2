<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EquipmentItem extends Model
{
    protected $primaryKey = 'equipment_item_id';

    protected $fillable = [
        'item_name',
        'description',
        'quantity',
        'status',
        'created_by',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function borrows()
    {
        return $this->hasMany(EquipmentBorrow::class, 'equipment_item_id', 'equipment_item_id');
    }

    public function isAvailable(): bool
    {
        return $this->status === 'Available';
    }
}
