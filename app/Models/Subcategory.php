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
      ->withTimestamps();
  }

  public function attributeValues()
  {
    return $this->belongsToMany(AttributeValue::class, 'subcategory_attribute_values')
      ->withPivot('attribute_id')
      ->withTimestamps();
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

  // Get available attributes with their values
  public function getAvailableAttributes()
  {
    return $this->attributes()->with('values')->get();
  }
}