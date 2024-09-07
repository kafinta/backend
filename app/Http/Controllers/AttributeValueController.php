<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AttributeValueController extends Controller
{
    public function index($attributeId)
    {
        $attribute = Attribute::findOrFail($attributeId);
        $values = $attribute->values;
        return response()->json($values);
    }

    public function show($attributeId, $id)
    {
        $attribute = Attribute::findOrFail($attributeId);
        $value = $attribute->values()->findOrFail($id);
        return response()->json($value);
    }

    public function store(Request $request, $attributeId)
    {
        $attribute = Attribute::findOrFail($attributeId);
        
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
        return response()->json($value, 201);
    }

    public function update(Request $request, $attributeId, $id)
    {
        $attribute = Attribute::findOrFail($attributeId);
        $value = $attribute->values()->findOrFail($id);
        
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
        return response()->json($value);
    }

    public function destroy($attributeId, $id)
    {
        $attribute = Attribute::findOrFail($attributeId);
        $value = $attribute->values()->findOrFail($id);
        $value->delete();
        return response()->json(null, 204);
    }
}
