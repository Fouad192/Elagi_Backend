<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    // If your table name doesn't follow Laravel's naming convention,
    // you can specify it manually like this:
    // protected $table = 'your_table_name';

    // If your primary key is not 'id' or you want to customize it
    // protected $primaryKey = 'your_primary_key';

    // You can disable the timestamps if you don't have created_at and updated_at columns
    // public $timestamps = false;
    protected $table = 'medicines';
    // Mass assignable attributes
    protected $fillable = [
        'name',
        'name_ar',
        'description',
        'description_ar',
        'price',
        'stock',
        'image_url',
        'category',
        'category_ar',
        // ... any other column names that you wish to be mass assignable
    ];

    // Defining relationships, for example, if you have any
    public function alternativeMedicine() {
        return $this->belongsTo(Product::class, 'alternative_medicine_id');
    }

    // ... any other model methods
}
