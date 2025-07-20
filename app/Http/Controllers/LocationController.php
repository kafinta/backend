<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ImprovedController;
use App\Http\Resources\LocationResource;
use App\Models\Location;
use Illuminate\Http\Request;

class LocationController extends ImprovedController
{
    public function index() {
        $locations = Location::all();
        return $this->respondWithSuccess("Locations Fetched Successfully", 200, LocationResource::collection($locations));
    }

    public function show($id)
    {
        $location = Location::findOrFail($id);
        return $this->respondWithSuccess("Location Fetched Successfully", 200, new LocationResource($location));
    }

    public function showBySlug($slug)
    {
        $location = Location::where('slug', $slug)->first();
        if (!$location) {
            return $this->respondWithError('Location not found', 404);
        }
        return $this->respondWithSuccess('Location fetched successfully', 200, new LocationResource($location));
    }

    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|unique:locations|max:255',
                'image_path' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);
        } catch (\Illuminate\Validation\ValidationException $validator) {
            return $this->validationFailedResponse($validator);
        }

        if ($request->hasFile('image_path')) {
            $image = $request->file('image_path');
            $imageName = time() . '_' . uniqid() . '_' . $image->getClientOriginalExtension();
            $path = $image->storeAs('locations', $imageName);

            if (!$path) {
                return $this->respondWithError('Image upload failed', 500);
            }

            $validatedData['image_path'] = '/storage/locations/' . $imageName;
        }
        $location = Location::create($validatedData);
        return $this->respondWithSuccess('Location created successfully', 201, new LocationResource($location));
    }

    public function update(Request $request, $id)
    {
        $location = Location::find($id);
        if (!$location) {
            return $this->respondWithError('Location not found', 404);
        }

        try {
            $validatedData = $request->validate([
                'name' => 'sometimes|string|unique:locations,name,'.$id.'|max:255',
                'image_path' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);
        } catch (\Illuminate\Validation\ValidationException $validator) {
            return $this->validationFailedResponse($validator);
        }

        // Handle image upload if a new image is provided
        if ($request->hasFile('image_path')) {
            // Delete the old image if it exists
            if ($location->image_path) {
                $oldImagePath = public_path('storage/locations/' . basename($location->image_path));
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }

            // Store the new image
            $image = $request->file('image_path');
            $imageName = time() . '_' . uniqid() . '_' . $image->getClientOriginalExtension();
            $path = $image->storeAs('locations', $imageName);
            $validatedData['image_path'] = '/storage/locations/' . $imageName;
        }

        $location->update($validatedData);
        return $this->respondWithSuccess('Location updated successfully', 200, new LocationResource($location));
    }

    public function destroy($id)
    {
        $location = Location::find($id);
        if (!$location) {
            return $this->respondWithError('Location not found', 404);
        }

        // Delete the associated image if it exists
        if ($location->image_path) {
            // Get the full path of the image
            $oldImagePath = public_path('storage/locations/' . basename($location->image_path));
            if (file_exists($oldImagePath)) {
                unlink($oldImagePath);
            }
        }

        $location->delete();
        return $this->respondWithSuccess('Location deleted successfully', 200);
    }



    protected function validationFailedResponse($validator)
    {
        return $this->respondWithError($validator->errors(), 422, );
    }
}