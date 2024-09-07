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
     * Display a listing of the attributes with their values.
     */
    public function index()
    {
        $subcategories = Subcategory::with('attributes.values')->get();
        return response()->json($subcategories);
    }

    /**
     * Display the specified attribute with its values.
     */
    public function show($id)
    {
        $subcategory = Subcategory::with('attributes.values')->findOrFail($id);
        
        $formattedSubcategory = [
            'id' => $subcategory->id,
            'name' => $subcategory->name,
            'attributes' => $subcategory->attributes->map(function ($attribute) {
                return [
                    'id' => $attribute->id,
                    'name' => $attribute->name,
                    'values' => $attribute->values->pluck('value')
                ];
            })
        ];

        return response()->json($formattedSubcategory);
        // if (!$attribute) {
        //     return response()->json(['message' => 'Attribute not found'], 404);
        // }

        // $attributeData = [
        //     'id' => $attribute->id,
        //     'name' => $attribute->name,
        //     'values' => $attribute->subcategories->pluck('pivot.value')->unique()->sort()->values()->all()
        // ];

        // return response()->json(['attribute' => $attributeData], 200);
    }

    /**
     * Update the specified attribute and its values in storage.
     */
    public function update(Request $request, $id)
    {
        $attribute = Attribute::find($id);
        
        if (!$attribute) {
            return response()->json(['message' => 'Attribute not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:attributes,name,' . $id,
            'values' => 'required|array',
            'values.*' => 'required|string|distinct',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();

        try {
            $attribute->update(['name' => $request->name]);

            // Update values across all subcategories
            $newValues = collect($request->values);
            $currentValues = $attribute->subcategories->pluck('pivot.value')->unique();

            $valuesToAdd = $newValues->diff($currentValues);
            $valuesToRemove = $currentValues->diff($newValues);

            foreach ($attribute->subcategories as $subcategory) {
                if ($valuesToRemove->contains($subcategory->pivot->value)) {
                    $subcategory->attributes()->detach($attribute->id);
                }
            }

            foreach (Subcategory::all() as $subcategory) {
                foreach ($valuesToAdd as $value) {
                    $subcategory->attributes()->attach($attribute->id, ['value' => $value]);
                }
            }

            DB::commit();

            return response()->json([
                'attribute' => [
                    'id' => $attribute->id,
                    'name' => $attribute->name,
                    'values' => $newValues->sort()->values()->all()
                ],
                'message' => 'Attribute updated successfully'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'An error occurred while updating the attribute'], 500);
        }
    }

    /**
     * Remove the specified attribute from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
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
     * Get attributes with their values for a specific subcategory.
     *
     * @param  int  $subcategoryId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAttributesBySubcategory($subcategoryId)
    {
        $subcategory = Subcategory::find($subcategoryId);

        if (!$subcategory) {
            return response()->json(['message' => 'Subcategory not found'], 404);
        }

        $attributes = $subcategory->attributes->map(function ($attribute) {
            return [
                'id' => $attribute->id,
                'name' => $attribute->name,
                'values' => [$attribute->pivot->value]
            ];
        });

        return response()->json(['attributes' => $attributes], 200);
    }
}
