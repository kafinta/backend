<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ImprovedController;
use Illuminate\Http\Request;
use App\Models\Attribute;
use App\Models\Subcategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;


class AttributeController extends ImprovedController
{
    public function index()
    {
        $attributes = Attribute::with('values')->get();
        return response()->json($attributes);
    }

    public function show($id)
    {
        $attribute = Attribute::with('values')->findOrFail($id);
        return response()->json($attribute);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|unique:attributes|max:255',
        ]);

        $attribute = Attribute::create($validatedData);
        return response()->json($attribute, 201);
    }

    public function update(Request $request, $id)
    {
        Log::info('Update request received:', $request->all());

        $attribute = Attribute::findOrFail($id);
        
        $validatedData = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('attributes')->ignore($attribute->id),
            ],
        ]);

        Log::info('Validated data:', $validatedData);

        $attribute->update($validatedData);
        return response()->json($attribute);
    }

    public function destroy($id)
    {
        $attribute = Attribute::findOrFail($id);
        $attribute->delete();
        return response()->noContent();
    }
}
