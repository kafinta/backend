<?php

namespace App\Http\Controllers;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function getAllCategories()
    {
        $categories = Category::all();
        return response()->json($categories);
    }

    public function getSubcategories($categoryId)
    {
        $category = Category::find($categoryId);

        if (!$category) {
            return response()->json(['error' => 'Category not found'], 404);
        }

        $subcategories = $category->subcategories;

        return response()->json(['subcategories' => $subcategories]);
    }

    public function getSubcategorieswithQuery(Request $request, $categoryId)
    {
        $category = Category::find($categoryId);

        if (!$category) {
            return response()->json(['error' => 'Category not found'], 404);
        }

        $query = $request->query('query'); // Access the 'query' parameter from the URL

        // Use the $query parameter to customize your query if needed
        $subcategories = Category->subcategories()->where('name', 'like', "%$query%")->get();
    
        return response()->json(['subcategories' => $subcategories]);
    }

    public function getSpecificNumberOfCategories($number)
    {
        $categories = Category::take($number)->get();
        return response()->json($categories);
    }
}
