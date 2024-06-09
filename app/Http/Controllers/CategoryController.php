<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ImprovedController;
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

        return response()->json(['category' => $category,'subcategories' => $subcategories]);
    }

    public function getSpecificNumberOfCategories($number)
    {
        $categories = Category::take($number)->get();
        return response()->json($categories);
    }
}
