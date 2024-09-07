<?php

namespace App\Http\Controllers;
use App\Models\Subcategory;
use App\Models\Category;
use App\Models\Location;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ImprovedController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubcategoryController extends ImprovedController
{
    public function getAllSubcategories()
    {
        $subcategories = Subcategory::all();
        return response()->json($subcategories);
    }

    public function getSubcategories(Request $request)
    {
        $category = $request->query('category');
        $location = $request->query('location');

        $requested_category = Category::where('name', $category)->first();
        $requested_location = Location::where('name', $location)->first();

        if (!$requested_category) {
            return $this->respondWithError("Category not Found", 404);
        }

        // If I'm fetching for just the category
        if (!$requested_location) {
            $subcategories = $requested_category->subcategories()->get();
            return response()->json(['message' => 'Location not found','category' => $category, 'subcategories' => $subcategories]);
        }

        $subcategories = $requested_category->subcategories()->where('location_id', $requested_location->id)->get();

        // If the location doesn't have subcategories
        if (count($subcategories) === 0) {
            // return ('The location does not return');
            $subcategories = $requested_category->subcategories()->get();
            return response()->json(['message' => 'Location does not have subcategories','category' => $category, 'subcategories' => $subcategories]);
        } else {
            return response()->json(['category' => $category, 'location' => $location, 'subcategories' => $subcategories]);
        }
    }

    public function getFormattedSubcategory($id)
    {
        $subcategory = Subcategory::findOrFail($id);
        
        $attributes = DB::table('attributes')
            ->join('attribute_subcategory', 'attributes.id', '=', 'attribute_subcategory.attribute_id')
            ->where('attribute_subcategory.subcategory_id', $subcategory->id)
            ->select('attributes.id', 'attributes.name')
            ->get();

        $formattedSubcategory = [
            'id' => $subcategory->id,
            'name' => $subcategory->name,
            'attributes' => $attributes->map(function ($attribute) {
                $values = AttributeValue::where('attribute_id', $attribute->id)
                    ->pluck('value')
                    ->toArray();
                
                return [
                    'id' => $attribute->id,
                    'name' => $attribute->name,
                    'values' => $values
                ];
            })
        ];

        return response()->json($formattedSubcategory);
    }

    public function index()
    {
        $subcategories = Subcategory::all()->map(function ($subcategory) {
            return $this->formatSubcategory($subcategory);
        });

        return response()->json($subcategories);
    }

    public function show($id)
    {
        $subcategory = Subcategory::findOrFail($id);
        return response()->json($this->formatSubcategory($subcategory));
    }

    private function formatSubcategory($subcategory)
    {
        $attributes = DB::table('attributes')
            ->join('attribute_subcategory', 'attributes.id', '=', 'attribute_subcategory.attribute_id')
            ->where('attribute_subcategory.subcategory_id', $subcategory->id)
            ->select('attributes.id', 'attributes.name')
            ->get();

        return [
            'id' => $subcategory->id,
            'name' => $subcategory->name,
            'attributes' => $attributes->map(function ($attribute) {
                $values = AttributeValue::where('attribute_id', $attribute->id)
                    ->pluck('value')
                    ->toArray();
                
                return [
                    'id' => $attribute->id,
                    'name' => $attribute->name,
                    'values' => $values
                ];
            })
        ];
    }
}