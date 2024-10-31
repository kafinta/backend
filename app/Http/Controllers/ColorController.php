<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ImprovedController;
use App\Models\Color;
use Illuminate\Http\Request;

class ColorController extends ImprovedController
{
  public function index() {
    $colors = Color::all();
    return response()->json($colors);
  }

  public function show($id)
  {
      $color = Color::findOrFail($id);
      return response()->json([$color]);
  }

  public function store(Request $request)
  {
      $validatedData = $request->validate([
          'name' => 'required|string|unique:colors|max:255',
          'hex_code' => 'nullable|string|max:6',
      ]);

      $color = Color::create($validatedData);
      return response()->json([
          'message' => "Color created successfully",
          'data' => $color
      ], 201);
  }

  public function update(Request $request, $id)
  {
      $color = Color::find($id);

      if (!$color) {
          return $this->respondWithError(['message' => "Color not found"], 404);
      }

      $validatedData = $request->validate([
          'name' => 'required|string|unique:colors,name,'.$id.'|max:255',
          'hex_code' => 'nullable|string|max:6',
      ]);

      $color->update($validatedData);
      return response()->json([
          'message'=> "Color updated successfully",
          'data' => $color,
      ], 200);
  }

  public function destroy($id)
  {
      $color = Color::find($id);
      if (!$color) {
          return $this->respondWithError(['message' => "Color not found"], 404);
      }
      $color->delete();
      return response()->json(['message' => 'Color deleted successfully'], 200);
  }
}
