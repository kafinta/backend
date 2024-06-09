<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ImprovedController;
use App\Models\Location;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function getAllLocations() {
        $locations = Location::all();
        return response()->json($locations);
    }
}