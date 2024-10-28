<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ImprovedController;
use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class AttributeValueController extends ImprovedController
{
    public function index($attributeId)
    {
        $attribute = Attribute::find($attributeId);
        if (!$attribute) {
            return $this->respondWithError(['message' => "Attribute not found", 404]);
        }

        $values = $attribute->values;
        if (!$values) {
            return $this->respondWithError(['message' => "Attribute doesn't have any values"], 404);
        }
        return response()->json([$values], 200);
    }

    public function show($attributeId, $id)
    {
        $attribute = Attribute::find($attributeId);
        if (!$attribute) {
            return $this->respondWithError(['message' => "Attribute not found"], 404);
        }

        $value = $attribute->values()->find($id);
        if (!$value) {
            return $this->respondWithError(['message' => "Value not found"], 404);
        }

        return response()->json([$value], 200);
    }

    public function store(Request $request, $attributeId)
    {
        $attribute = Attribute::find($attributeId);
        if (!$attribute) {
            return $this->respondWithError(['message' => "Attribute not found"], 404);
        }
        $validatedData = $request->validate([
            'value' => [
                'required',
                'string',
                'max:255',
                Rule::unique('attribute_values')->where(function ($query) use ($attributeId) {
                    return $query->where('attribute_id', $attributeId);
                }),
            ],
        ]);

        $value = $attribute->values()->create($validatedData);
        return response()->json([
            'message'=> "Value created successfully",
            'data' => $value
        ], 201);
    }

    public function update(Request $request, $attributeId, $id)
    {
        $attribute = Attribute::find($attributeId);
        if (!$attribute) {
            return $this->respondWithError(['message' => "Attribute not found"], 404);
        }

        $value = $attribute->values()->find($id);
        if (!$value) {
            return $this->respondWithError(['message' => "Value not found"], 404);
        }

        $validatedData = $request->validate([
            'value' => [
                'required',
                'string',
                'max:255',
                Rule::unique('attribute_values')->where(function ($query) use ($attributeId) {
                    return $query->where('attribute_id', $attributeId);
                })->ignore($id),
            ],
        ]);

        $value->update($validatedData);
        return response()->json([
            'message'=> "Value upddated successfully",
            'data' => $value
        ], 201);
    }

    public function destroy($attributeId, $id)
    {
        $attribute = Attribute::find($attributeId);
        if (!$attribute) {
            return $this->respondWithError(['message' => "Attribute not found"], 404);
        }

        $value = $attribute->values()->find($id);
        if (!$value) {
            return $this->respondWithError(['message' => "Value not found"], 404);
        }

        $value->delete();
        
        return response()->json(['message' => 'Value deleted successfully'], 200);
    }
}
