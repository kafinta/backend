<?php

namespace App\Http\Controllers;
use App\Http\Controllers\ImprovedController;

use App\Models\Attribute;
use App\Models\Subcategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AttributeController extends ImprovedController
{
    public function index(Request $request)
    {
        $query = Attribute::query();

        // Optional filtering
        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->has('is_variant_generator')) {
            $query->where('is_variant_generator', $request->boolean('is_variant_generator'));
        }

        $attributes = $query->paginate($request->input('per_page', 15));

        return $this->respondWithSuccess('Attributes fetched successfully', 200, $attributes);
    }

    public function show($id)
    {
        $attribute = Attribute::with('values')->findOrFail($id);
        return $this->respondWithSuccess('Attribute fetched successfully', 200, $attribute);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:attributes,name|max:255',
            'type' => 'in:select,color,radio',
            'is_variant_generator' => 'boolean',
            'is_required' => 'boolean',
            'display_order' => 'integer'
        ]);

        if ($validator->fails()) {
            return $this->respondWithError($validator->errors(), 422);
        }

        $attribute = Attribute::create($validator->validated());

        return $this->respondWithSuccess('Attributes created successfully', 201, $attribute);
    }

    public function update(Request $request, $id)
    {
        $attribute = Attribute::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => "unique:attributes,name,{$id}|max:255",
            'type' => 'in:select,color,radio',
            'is_variant_generator' => 'boolean',
            'is_required' => 'boolean',
            'display_order' => 'integer'
        ]);

        if ($validator->fails()) {
            return $this->respondWithError($validator->errors(), 422);
        }

        $attribute->update($validator->validated());

        return $this->respondWithSuccess('Attributes updated successfully', 200, $attribute);
    }

    public function destroy($id)
    {
        $attribute = Attribute::findOrFail($id);
        $attribute->delete();

        return $this->respondWithSuccess('Attribute deleted successfully', 200);
    }

    public function getAttributesForSubcategory($subcategoryId)
    {
        $subcategory = Subcategory::findOrFail($subcategoryId);
        
        $attributes = $subcategory->attributes()->with('values')->get();

        return $this->respondWithSuccess('Attributes fetched successfully', 200, $attributes);
    }
}
