<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RareMedicineRequest extends Model
{
    use HasFactory;

    protected $table = 'rare_medicines_requests';
    protected $fillable = ['name', 'phone', 'address', 'medicine_name', 'quantity'];
}
