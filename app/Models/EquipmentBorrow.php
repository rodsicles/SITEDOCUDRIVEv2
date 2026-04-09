<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EquipmentBorrow extends Model
{
    protected $primaryKey = 'equipment_borrow_id';

    protected $fillable = [
        'user_id',
        'equipment_item_id',
        'purpose',
        'borrow_date',
        'borrow_time',
        'return_date',
        'return_time',
        'actual_return_date',
        'status',
    ];

    protected $casts = [
        'borrow_date' => 'date',
        'return_date' => 'date',
        'actual_return_date' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function equipmentItem()
    {
        return $this->belongsTo(EquipmentItem::class, 'equipment_item_id', 'equipment_item_id');
    }

    public function isBorrowed(): bool
    {
        return $this->status === 'Borrowed';
    }

    public function isReturned(): bool
    {
        return $this->status === 'Returned';
    }

    public function isOverdue(): bool
    {
        return $this->isBorrowed() && $this->return_date->lt(now()->startOfDay());
    }
}
