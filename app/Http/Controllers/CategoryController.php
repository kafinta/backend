<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ImprovedController;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends ImprovedController
{
    public function getAllCategories()
    {
        $categories = Category::all();
        return response()->json($categories);
    }

    public function getSpecificNumberOfCategories($number)
    {
        $categories = Category::take($number)->get();
        return response()->json($categories);
    }
}
