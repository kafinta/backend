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
    ->withPivot('value')
    ->withTimestamps();
  }
}