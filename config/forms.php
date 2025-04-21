<?php

return [
    // Global configuration options
    'bypass_expiration' => env('FORM_BYPASS_EXPIRATION', false),
    'product_form' => [
        'total_steps' => 3,
        'expiration_hours' => 24,
        'steps' => [
            1 => [
                'label' => 'Basic Information',
                'description' => 'Enter the basic product details',
                'validation_rules' => [
                    'data' => 'required|array',
                    'data.name' => 'required|string|max:255',
                    'data.description' => 'required|string',
                    'data.price' => 'required|numeric|min:0',
                    'data.subcategory_id' => 'required|exists:subcategories,id',
                    'data.status' => 'sometimes|in:draft,active,inactive',
                    'data.location_id' => 'sometimes|exists:locations,id',
                    'session_id' => 'required|string',
                    'step' => 'required|integer|in:1'
                ]
            ],
            2 => [
                'label' => 'Product Attributes',
                'description' => 'Select product attributes and values',
                'validation_rules' => [
                    'data' => 'sometimes|array',
                    'data.*.id' => 'sometimes|integer|exists:attributes,id',
                    'data.*.value.id' => 'sometimes|integer|exists:attribute_values,id',
                    'data.*.attribute_id' => 'sometimes|integer|exists:attributes,id',
                    'data.*.value_id' => 'sometimes|integer|exists:attribute_values,id',
                    'session_id' => 'required|string',
                    'step' => 'required|integer|in:2'
                ]
            ],
            3 => [
                'label' => 'Product Images',
                'description' => 'Upload product images',
                'validation_rules' => [
                    'session_id' => 'required|string',
                    'step' => 'required|integer|in:3',
                    'images' => 'sometimes|array|min:1',
                    'images.*' => 'sometimes|file|image|mimes:jpeg,png,jpg,gif|max:2048',
                ]
            ]
        ]
    ],
    // You can add more form types here
    'seller_form' => [
        'total_steps' => 2,
        'expiration_hours' => 48,
        'steps' => [
          1 => [
            'label' => 'Business Details',
            'description' => 'Enter your business information',
            'validation_rules' => [
              'business_name' => 'required|string|max:255',
              'business_description' => 'nullable|string',
              'business_address' => 'required|string',
              'phone_number' => 'required|string',
              'session_id' => 'required|string',
              'step' => 'required|integer|in:1'
            ]
          ],
          2 => [
            'label' => 'Identification',
            'description' => 'Upload your identification document',
            'validation_rules' => [
              'id_type' => 'required|in:passport,national_id,nin',
              'id_number' => 'required|string',
              'id_document' => [
                'required',
                'file',
                'max:2048',
                'mimetypes:application/pdf,image/jpeg,image/png,image/jpg'
              ],
              'session_id' => 'required|string',
              'step' => 'required|integer|in:2'
            ]
          ],
        ]
    ]
];