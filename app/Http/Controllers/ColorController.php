<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ImprovedController;
use App\Models\Color;
use Illuminate\Http\Request;

class ColorController extends ImprovedController
{
  public function getAllColors() {
      $colors = Color::all();
      return response()->json($colors);
  }
}