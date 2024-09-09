<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ImprovedController;
use Illuminate\Http\Request;
use App\Models\Attribute;
use App\Models\Subcategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;


class AttributeController extends ImprovedController
{
    public function index()
    {
        $attributes = Attribute::with('values')->get();
        return response()->json($attributes);
    }

    public function show($id)
    {
        $attribute = Attribute::with('values')->find($id);
        if (!$attribute) {
            return $this->respondWithError(['message' => "Attribute not found"], 404);
        }
        return response()->json($attribute);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|unique:attributes|max:255',
        ]);

        $attribute = Attribute::create($validatedData);
        return response()->json(['message' => "Attribute created successfully", $attribute], 201);
    }

    public function update(Request $request, $id)
    {
        $attribute = Attribute::find($id);
        $name = $request->name;

        if (!$attribute) {
            return $this->respondWithError(['message' => "Attribute not found"], 404);
        }

        $validatedData = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('attributes')->ignore($attribute->id),
            ],
        ]);
        $attribute->update($validatedData);
        return response()->json([
            'message'=> "Attribute upddated successfully",
            'data' => $attribute
        ], 201);
    }

    public function destroy($id)
    {
        $attribute = Attribute::find($id);
        if (!$attribute) {
            return $this->respondWithError(['message' => "Attribute not found"], 404);
        }
        $attribute->delete();
        return response()->json(['message' => 'Attribute deleted successfully'], 200);
    }
}
