<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ImprovedController;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::all();
        return response()->json($categories);
    }

    public function getSpecificNumberOfCategories($number)
    {
        $categories = Category::take($number)->get();
        return response()->json($categories);
    }

    public function show($id)
    {
        $category = Category::findOrFail($id);
        return response()->json([$category]);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|unique:attributes|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $category = Category::create($validatedData);
        return response()->json([
            'message' => "Category created successfully",
            'data' => $category
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $category = Category::find($id);
        $name = $request->name;

        if (!$category) {
            return $this->respondWithError(['message' => "Category not found"], 404);
        }

        $validatedData = $request->validate([
            'name' => 'required|string|unique:categories,name,'.$id.'|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $category->update($validatedData);
        return response()->json([
            'message'=> "Category updated successfully",
            'data' => $category,
        ], 200);
    }

    public function destroy($id)
    {
        $category = Category::find($id);
        if (!$category) {
            return $this->respondWithError(['message' => "Category not found"], 404);
        }
        $category->delete();
        return response()->json(['message' => 'Category deleted successfully'], 200);
    }
}
