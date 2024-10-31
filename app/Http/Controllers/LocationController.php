<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ImprovedController;
use App\Models\Location;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function index() {
        $locations = Location::all();
        return response()->json($locations);
    }

    public function show($id)
    {
        $location = Location::findOrFail($id);
        return response()->json([$location]);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|unique:locations|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $location = Location::create($validatedData);
        return response()->json([
            'message' => "Location created successfully",
            'data' => $location
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $location = Location::find($id);

        if (!$location) {
            return $this->respondWithError(['message' => "Location not found"], 404);
        }

        $validatedData = $request->validate([
            'name' => 'required|string|unique:locations,name,'.$id.'|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $location->update($validatedData);
        return response()->json([
            'message'=> "Location updated successfully",
            'data' => $location,
        ], 200);
    }

    public function destroy($id)
    {
        $location = Location::find($id);
        if (!$location) {
            return $this->respondWithError(['message' => "Location not found"], 404);
        }
        $location->delete();
        return response()->json(['message' => 'Location deleted successfully'], 200);
    }
}