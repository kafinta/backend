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


class SubcategoryController extends Controller
{
    public function index()
    {
        $subcategories = Subcategory::all();
        return response()->json(['message' => 'Subcategories fetched successfully', 'data' => $subcategories]);
    }

    public function show($id)
    {
        $subcategory = Subcategory::findOrFail($id);
        return response()->json(['message' => 'Subcategory fetched successfully', 'data' => $this->formatSubcategory($subcategory)]);
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
            'message' => 'Subcategories fetched successfully',
            'location' => $locationName,
            'category' => $categoryName,
            'data' => $subcategories,
        ]);
    }
    
    public function store(Request $request, $categoryId, $locationId)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'image_path' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'has_colors' => 'nullable|boolean'
        ]);

        $validatedData['category_id'] = $categoryId;
        $validatedData['location_id'] = $locationId;

        $subcategory = Subcategory::create($validatedData);

        return response()->json([
            'message' => 'Subcategory created successfully',
            'data' => $subcategory
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $subcategory = Subcategory::find($id);
        $name = $request->name;

        if (!$subcategory) {
            return $this->respondWithError(['message' => "Subcategory not found"], 404);
        }

        $validatedData = $request->validate([
            'name' => 'required|string|unique:subcategories,name,'.$id.'|max:255',
            'image_path' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'has_colors' => 'nullable|boolean',
        ]);

        $subcategory->update($validatedData);
        return response()->json([
            'message'=> "Subcategory updated successfully",
            'data' => $subcategory,
        ], 200);
    }

    public function destroy($id)
    {
        $subcategory = Subcategory::find($id);
        if (!$subcategory) {
            return $this->respondWithError(['message' => "Subcategory not found"], 404);
        }
        $subcategory->delete();
        return response()->json(['message' => 'Subcategory deleted successfully'], 200);
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
            'has_colors' => $subcategory->has_colors,
            'image_path' => $subcategory->image_path,
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