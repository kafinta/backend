<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subcategory extends Model
{
  use HasFactory;

  public function category()
  {
    return $this->belongsTo(Category::class);
  }

  public function location()
  {
    return $this->belongsTo(Location::class);
  }

  public function products()
  {
    return $this->hasMany(Product::class);
  }

  public function attributes()
  {
    return $this->belongsToMany(Attribute::class, 'subcategory_attributes')
      ->select('attributes.*');
  }

  public function attributeValues()
  {
    return $this->belongsToMany(AttributeValue::class, 'subcategory_attribute_values')
      ->withPivot('attribute_id')
      ->withTimestamps()
      ->select('attribute_values.*');
  }

  public function locations()
  {
    return $this->belongsToMany(Location::class, 'location_subcategory')
      ->withTimestamps();
  }



  protected $fillable = [
    'name',
    'image_path',
    'category_id',
  ];

  protected static function boot()
  {
    parent::boot();
    static::creating(function ($subcategory) {
      $baseSlug = \Illuminate\Support\Str::slug($subcategory->name);
      $slug = $baseSlug;
      $i = 2;
      while (self::where('slug', $slug)->exists()) {
        $slug = $baseSlug . '-' . $i;
        $i++;
      }
      $subcategory->slug = $slug;
    });
  }

  // Get available attributes with their values
  public function getAvailableAttributes()
  {
    return $this->attributes()
      ->select('attributes.*')
      ->with(['values' => function($query) {
        $query->select('attribute_values.*');
      }])
      ->get();
  }

  public function getAttributeValues(Attribute $attribute)
  {
    return $this->attributeValues()
      ->select('attribute_values.*')
      ->where('attribute_id', $attribute->id)
      ->get();
  }
}