<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function subcategories()
    {
        return $this->belongsToMany(Subcategory::class, 'location_subcategory')->withTimestamps();
    }

    protected $fillable = [
        'name',
        'image_path',
    ];
}
