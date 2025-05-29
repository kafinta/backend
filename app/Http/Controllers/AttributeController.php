<?php

namespace App\Http\Controllers;
use App\Http\Controllers\ImprovedController;

use App\Models\Attribute;
use App\Models\Subcategory;
use App\Http\Resources\AttributeResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AttributeController extends ImprovedController
{
    public function index(Request $request)
    {
        $query = Attribute::query();

        // Optional filtering
        if ($request->has('is_variant_generator')) {
            $query->where('is_variant_generator', $request->boolean('is_variant_generator'));
        }

        // Order by sort_order and name
        $query->ordered();

        $attributes = $query->paginate($request->input('per_page', 15));

        return $this->respondWithSuccess('Attributes fetched successfully', 200, [
            'data' => AttributeResource::collection($attributes->items()),
            'pagination' => [
                'current_page' => $attributes->currentPage(),
                'last_page' => $attributes->lastPage(),
                'per_page' => $attributes->perPage(),
                'total' => $attributes->total(),
            ]
        ]);
    }

    public function show($id)
    {
        $attribute = Attribute::with('values')->findOrFail($id);
        return $this->respondWithSuccess('Attribute fetched successfully', 200, new AttributeResource($attribute));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:attributes,name|max:255',
            'is_variant_generator' => 'boolean',
            'help_text' => 'nullable|string',
            'sort_order' => 'integer|min:0'
        ]);

        if ($validator->fails()) {
            return $this->respondWithError($validator->errors(), 422);
        }

        $attribute = Attribute::create($validator->validated());

        return $this->respondWithSuccess('Attribute created successfully', 201, new AttributeResource($attribute));
    }

    public function update(Request $request, $id)
    {
        $attribute = Attribute::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => "unique:attributes,name,{$id}|max:255",
            'is_variant_generator' => 'boolean',
            'help_text' => 'nullable|string',
            'sort_order' => 'integer|min:0'
        ]);

        if ($validator->fails()) {
            return $this->respondWithError($validator->errors(), 422);
        }

        $attribute->update($validator->validated());

        return $this->respondWithSuccess('Attribute updated successfully', 200, new AttributeResource($attribute));
    }

    public function destroy($id)
    {
        $attribute = Attribute::findOrFail($id);
        $attribute->delete();

        return $this->respondWithSuccess('Attribute deleted successfully', 200);
    }

    public function getAttributesForSubcategory($subcategoryId)
    {
        $subcategory = Subcategory::findOrFail($subcategoryId);

        // Get attributes with all their values (clean response using resources)
        $attributes = $subcategory->attributes()
            ->with(['values' => function($query) use ($subcategory) {
                $query->whereHas('subcategories', function($q) use ($subcategory) {
                    $q->where('subcategory_id', $subcategory->id);
                });
            }])
            ->orderBy('attributes.sort_order')
            ->orderBy('attributes.name')
            ->get();

        return $this->respondWithSuccess('Attributes fetched successfully', 200, AttributeResource::collection($attributes));
    }



    /**
     * Validate attribute combinations for a subcategory
     */
    public function validateAttributeCombination(Request $request, $subcategoryId)
    {
        $subcategory = Subcategory::findOrFail($subcategoryId);

        $validator = Validator::make($request->all(), [
            'attributes' => 'required|array',
            'attributes.*.attribute_id' => 'required|integer|exists:attributes,id',
            'attributes.*.value_ids' => 'required|array',
            'attributes.*.value_ids.*' => 'integer|exists:attribute_values,id'
        ]);

        if ($validator->fails()) {
            return $this->respondWithError($validator->errors(), 422);
        }

        $errors = [];
        foreach ($request->attributes as $attributeData) {
            try {
                $attribute = Attribute::findOrFail($attributeData['attribute_id']);
                $attribute->validateValuesForSubcategory($subcategory, $attributeData['value_ids']);
            } catch (\InvalidArgumentException $e) {
                $errors[] = [
                    'attribute_id' => $attributeData['attribute_id'],
                    'error' => $e->getMessage()
                ];
            }
        }

        if (!empty($errors)) {
            return $this->respondWithError('Validation failed', 422, ['validation_errors' => $errors]);
        }

        return $this->respondWithSuccess('Attribute combination is valid', 200);
    }
}
