<?php

namespace App\Http\Controllers;
use App\Http\Controllers\ImprovedController;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Subcategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AttributeValueController extends ImprovedController
{
    public function index(Attribute $attribute)
    {
        $values = $attribute->values;
        return $this->respondWithSuccess('Values fetched successfully', 200, $values);
    }

    public function show(Attribute $attribute, AttributeValue $value)
    {
        if ($value->attribute_id !== $attribute->id) {
            return $this->respondWithError('Value does not belong to this attribute', 404);
        }
        return $this->respondWithSuccess('Attribute value fetched successfully', 200, $value);
    }

    public function showBySlug($attributeId, $slug)
    {
        $attribute = Attribute::findOrFail($attributeId);
        $value = $attribute->values()->where('slug', $slug)->first();
        if (!$value) {
            return $this->respondWithError('Attribute value not found', 404);
        }
        return $this->respondWithSuccess('Attribute value fetched successfully', 200, $value);
    }

    public function store(Request $request, Attribute $attribute)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:255',
            'representation' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return $this->respondWithError($validator->errors(), 422);
        }

        $existingValue = $attribute->values()
            ->where('name', $request->name)
            ->first();

        if ($existingValue) {
            return $this->respondWithError('This value already exists for the given attribute', 422);
        }

        $attributeValue = $attribute->values()->create($validator->validated());
        return $this->respondWithSuccess('Attribute value created successfully', 201, $attributeValue);
    }

    public function update(Request $request, Attribute $attribute, AttributeValue $value)
    {
        if ($value->attribute_id !== $attribute->id) {
            return $this->respondWithError('Value does not belong to this attribute', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'max:255',
            'representation' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return $this->respondWithError($validator->errors(), 422);
        }

        $value->update($validator->validated());
        return $this->respondWithSuccess('Attribute value updated sucessfully', 200, $value);
    }

    public function destroy(Attribute $attribute, AttributeValue $value)
    {
        if ($value->attribute_id !== $attribute->id) {
            return $this->respondWithError('Value does not belong to this attribute', 404);
        }

        $value->delete();
        return $this->respondWithSuccess('Attribute value deleted successfully', 200);
    }

}
