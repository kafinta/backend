<?php

namespace App\Http\Controllers;
use App\Models\Subcategory;
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
}
