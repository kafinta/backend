<?php

namespace App\Http\Controllers;
use App\Models\Subcategory;
use App\Models\Category;
use App\Models\Location;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Http\Resources\AttributeResource;
use App\Http\Resources\SubcategoryResource;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ImprovedController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class SubcategoryController extends ImprovedController
{
    public function index(Request $request)
    {
        $query = Subcategory::with(['locations', 'category']); // Eager load locations and category

        $categoryName = null;
        $locationName = null;

        // Filter by category if provided
        if ($request->has('category_id')) {
            $query->where('category_id', $request->input('category_id'));
            $category = Category::find($request->input('category_id'));
            $categoryName = $category ? $category->name : null; // Get category name
        }

        // Filter by locations if provided
        if ($request->has('location_id')) {
            $query->whereHas('locations', function ($q) use ($request) {
                $q->where('location_id', $request->input('location_id'));
            });
            $location = Location::find($request->input('location_id'));
            $locationName = $location ? $location->name : null; // Get location name
        }

        $subcategories = $query->get();

        // Format the response to include the filtered category and location names
        $data = [
            'category' => $categoryName,
            'location' => $locationName,
            'subcategories' => SubcategoryResource::collection($subcategories)
        ];

        return $this->respondWithSuccess('Subcategories fetched successfully', 200, $data);
    }

    public function show($id)
    {
        try {
            $subcategory = Subcategory::with(['attributes.values', 'category', 'locations'])->findOrFail($id);
            return $this->respondWithSuccess('Subcategory fetched successfully', 200, new SubcategoryResource($subcategory));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->respondWithError('Subcategory not found', 404);
        }
    }

    public function showBySlug($slug)
    {
        $subcategory = Subcategory::where('slug', $slug)->with(['attributes.values', 'category', 'locations'])->first();
        if (!$subcategory) {
            return $this->respondWithError('Subcategory not found', 404);
        }
        return $this->respondWithSuccess('Subcategory fetched successfully', 200, new SubcategoryResource($subcategory));
    }

    public function filterByCategoryAndLocation(Request $request)
    {
        $categorySlug = $request->query('category');
        $locationSlug = $request->query('location');
        $query = Subcategory::query()->with(['locations', 'category']);
        if ($categorySlug) {
            $query->whereHas('category', function ($q) use ($categorySlug) {
                $q->where('slug', $categorySlug);
            });
        }
        if ($locationSlug) {
            $query->whereHas('locations', function ($q) use ($locationSlug) {
                $q->where('slug', $locationSlug);
            });
        }
        $subcategories = $query->get();
        return $this->respondWithSuccess('Subcategories fetched successfully', 200, SubcategoryResource::collection($subcategories));
    }

    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|unique:subcategories|max:255',
                'image_path' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'category_id' => 'required|exists:categories,id'
            ]);
        } catch (\Illuminate\Validation\ValidationException $validator) {
            return $this->validationFailedResponse($validator);
        }

        if ($request->hasFile('image_path')) {
            $image = $request->file('image_path');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $path = $image->storeAs('subcategories', $imageName);

            if (!$path) {
                return $this->respondWithError('Image upload failed', 500);
            }

            $validatedData['image_path'] = '/storage/subcategories/' . $imageName;
        }
        $subcategory = Subcategory::create($validatedData);

        // Attach locations if provided
        if ($request->has('locations')) {
            $subcategory->locations()->attach($request->input('locations'));
        }

        return $this->respondWithSuccess('Subcategory created successfully', 201, new SubcategoryResource($subcategory->load(['category', 'locations'])));
    }

    public function update(Request $request, $id)
    {
        $subcategory = Subcategory::findOrFail($id);

        try {
            $validatedData = $request->validate([
                'name' => 'sometimes|string|unique:subcategories,name,'.$subcategory->id.'|max:255',
                'image_path' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'category_id' => 'sometimes|exists:categories,id',
                'locations' => 'sometimes|array',
                'locations.*' => 'integer|exists:locations,id'
            ]);
        } catch (\Illuminate\Validation\ValidationException $validator) {
            return $this->validationFailedResponse($validator);
        }

        // Handle image upload if a new image is provided
        if ($request->hasFile('image_path')) {
            // Delete the old image if it exists
            if ($subcategory->image_path) {
                $oldImagePath = public_path($subcategory->image_path);
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }

            // Store the new image
            $image = $request->file('image_path');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $validatedData['image_path'] = '/storage/subcategories/' . $imageName; // Set the new image path

            // Save the new image to the storage
            $image->storeAs('subcategories', $imageName);
        }

        // Update the subcategory with the validated data
        $subcategory->update($validatedData);

        // Update locations if provided
        if ($request->has('locations')) {
            $subcategory->locations()->sync($request->input('locations')); // Sync locations
        }

        return $this->respondWithSuccess('Subcategory updated successfully', 200, new SubcategoryResource($subcategory->load(['category', 'locations'])));
    }

    public function destroy($id)
    {
        $subcategory = Subcategory::findOrFail($id);

        // Delete the associated image if it exists
        if ($subcategory->image_path) {
            $oldImagePath = public_path($subcategory->image_path);
            if (file_exists($oldImagePath)) {
                unlink($oldImagePath); // Delete the file from the public/categories folder
            }
        }

        $subcategory->delete();
        return $this->respondWithSuccess('Subcategory deleted successfully', 200);
    }




    protected function validationFailedResponse($validator)
    {
        return $this->respondWithError($validator->errors(), 422, );
    }
}