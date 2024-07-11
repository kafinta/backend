<?php

namespace App\Http\Controllers;
use App\Models\Subcategory;
use App\Models\Category;
use App\Models\Location;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ImprovedController;
use Illuminate\Http\Request;

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
}