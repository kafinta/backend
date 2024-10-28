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
use Illuminate\Support\Facades\Log;


class SubcategoryController extends ImprovedController
{
    public function index()
    {
        $subcategories = Subcategory::all();
        return response()->json($subcategories, 200);
    }

    public function show($id)
    {
        $subcategory = Subcategory::findOrFail($id);
        return response()->json([$subcategory, $this->formatSubcategory($subcategory)]);
    }

    public function getSubcategories(Request $request)
    {
        $query = Subcategory::query();

        if ($request->query('category_id')) {
            $categoryId = (int)$request->query('category_id');
            $query->where('category_id', $categoryId);
            $categoryName = Category::find($categoryId)?->name;        
        }

        if ($request->query('location_id')) {
            $locationId = (int)$request->query('location_id');
            $query->where('location_id', $locationId);
            $locationName = Location::find($locationId)?->name;       
        }

        $subcategories = $query->select('id', 'name')->get();


        return response()->json([
            'success' => true,
            'location' => $locationName,
            'category' => $categoryName,
            'data' => $subcategories,
        ]);
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