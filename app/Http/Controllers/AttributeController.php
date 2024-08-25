<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ImprovedController;
use Illuminate\Http\Request;
use App\Models\Attribute;
use App\Models\Subcategory;

class AttributeController extends ImprovedController
{
    /**
     * Display a listing of the attributes.
     */
    public function index()
    {
        $attributes = Attribute::all();
        return response()->json(['attributes' => $attributes], 200);
    }

    /**
     * Display the specified attribute.
     */
    public function show($id)
    {
        $attribute = Attribute::find($id);
        
        if (!$attribute) {
            return response()->json(['message' => 'Attribute not found'], 404);
        }

        return response()->json(['attribute' => $attribute], 200);
    }

    /**
     * Update the specified attribute in storage.
     */
    public function update(Request $request, $id)
    {
        $attribute = Attribute::find($id);
        
        if (!$attribute) {
            return response()->json(['message' => 'Attribute not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:attributes,name,' . $id,
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $attribute->update($request->all());

        return response()->json(['attribute' => $attribute, 'message' => 'Attribute updated successfully'], 200);
    }

    /**
     * Remove the specified attribute from storage.
     */
    public function destroy($id)
    {
        $attribute = Attribute::find($id);
        
        if (!$attribute) {
            return response()->json(['message' => 'Attribute not found'], 404);
        }

        $attribute->delete();

        return response()->json(['message' => 'Attribute deleted successfully'], 200);
    }

    /**
     * Get attributes for a specific subcategory.
     */
    public function getAttributesBySubcategory($subcategoryId)
    {
        $attributes = Attribute::whereHas('subcategories', function ($query) use ($subcategoryId) {
            $query->where('subcategory_id', $subcategoryId);
        })->get();

        return response()->json(['attributes' => $attributes], 200);
    }}
