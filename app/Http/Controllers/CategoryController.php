<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ImprovedController;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;


class CategoryController extends ImprovedController
{
    public function index()
    {
        $categories = Category::all();
        return $this->respondWithSuccess('Categories fetched successfully', 200, $categories);

    }

    public function getSpecificNumberOfCategories($number)
    {
        $categories = Category::take($number)->get();
        return $this->respondWithSuccess('Categories fetched successfully', 200, $categories);
    }

    public function show($id)
    {
        try {
            $category = Category::with('subcategories')->findOrFail($id);
            return $this->respondWithSuccess('Category fetched successfully', 200, $category);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->respondWithError('Category not found', 404);
        }
    }

    public function store(Request $request)
    {
        \Log::info('Store method called', ['request' => $request->all()]);

        $validatedData = $request->validate([
            'name' => 'required|string|unique:categories|max:255',
            'image_path' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($request->hasFile('image_path')) {
            $image = $request->file('image_path');
            $imageName = time() . '_' . uniqid() . '_' . $image->getClientOriginalName();
            $path = $image->storeAs('categories', $imageName);

            if (!$path) {
                return $this->respondWithError('Image upload failed', 500);
            }

            $validatedData['image_path'] = '/storage/categories/' . $imageName;
        }
        $category = Category::create($validatedData);
        return $this->respondWithSuccess('Category created successfully', 201, $category);
    }

    public function update(Request $request, $id)
    {
        $category = Category::find($id);
        if (!$category) {
            return $this->respondWithError('Category not found', 404);
        }
        $validatedData = $request->validate([
            'name' => 'sometimes|string|unique:categories,name,'.$id.'|max:255',
            'image_path' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        // Handle image upload if a new image is provided
        if ($request->hasFile('image_path')) {
            // Delete the old image if it exists
            if ($category->image_path) {
                $oldImagePath = public_path('storage/categories/' . basename($category->image_path));
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }

            // Store the new image
            $image = $request->file('image_path');
            $imageName = time() . '_' . uniqid() . '_' . $image->getClientOriginalName();
            $path = $image->storeAs('categories', $imageName);
            $validatedData['image_path'] = '/storage/categories/' . $imageName;
        }

        $category->update($validatedData);
        return $this->respondWithSuccess('Category updated successfully', 200, $category);
    }

    public function destroy($id)
    {
        $category = Category::find($id);
        if (!$category) {
            return $this->respondWithError(['message' => "Category not found"], 404);
        }

        // Delete the associated image if it exists
        if ($category->image_path) {
            // Get the full path of the image
            $oldImagePath = public_path('storage/categories/' . basename($category->image_path));
            if (file_exists($oldImagePath)) {
                unlink($oldImagePath); // Delete the file from the public/categories folder
            }
        }

        $category->delete();
        return $this->respondWithSuccess('Category deleted successfully', 200);
    }
}
