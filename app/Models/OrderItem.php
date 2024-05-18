<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = ['order_id', 'medicine_name', 'medicine_name_ar' , 'quantity', 'price', 'payment_method'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
