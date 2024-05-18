<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Medicine extends Model
{
    public function alternativeMedicine()
    {
        return $this->belongsTo(Medicine::class, 'alternative_medicine_id');
    }
    use HasFactory;
}
