<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'address', 'total_price', 'payment_method'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Assuming you have an OrderItem model and each order can have many items
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
