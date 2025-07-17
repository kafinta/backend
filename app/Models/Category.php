<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'image_path',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($category) {
            $baseSlug = \Illuminate\Support\Str::slug($category->name);
            $slug = $baseSlug;
            $i = 2;
            while (self::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $i;
                $i++;
            }
            $category->slug = $slug;
        });
    }

    public function subcategories()
    {
        return $this->hasMany(Subcategory::class);
    }
}
