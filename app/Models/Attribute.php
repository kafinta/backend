<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attribute extends Model
{
  use HasFactory;
  public function subcategories()
  {
    return $this->belongsToMany(Subcategory::class, 'subcategory_attributes')
      ->withPivot('value')
      ->withTimestamps();
  }

  public function products()
  {
    return $this->belongsToMany(Product::class, 'product_attributes')
      ->withPivot('value')
      ->withTimestamps();
  }
}
