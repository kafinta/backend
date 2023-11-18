<?php

namespace App\Http\Controllers;
use App\Models\Subcategory;
use Illuminate\Http\Request;

class SubcategoryController extends Controller
{
    public function getAllSubcategories()
    {
        $subcategories = Subcategory::all();
        return response()->json($subcategories);
    }
}
