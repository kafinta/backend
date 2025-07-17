<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'image_path',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($location) {
            $baseSlug = \Illuminate\Support\Str::slug($location->name);
            $slug = $baseSlug;
            $i = 2;
            while (self::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $i;
                $i++;
            }
            $location->slug = $slug;
        });
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function subcategories()
    {
        return $this->belongsToMany(Subcategory::class, 'location_subcategory')->withTimestamps();
    }
}
