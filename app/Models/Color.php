<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Color extends Model
{
    use HasFactory;
    protected $fillable = [
        'subcategory_id',
        'product_id', 
    ];

    public function subcategory()
    {
        return $this->belongsToMany(Subcategory::class);
    }
}
