<?php

return [
    'product_form' => [
        'total_steps' => 3,
        'expiration_hours' => 24,
        'steps' => [
            1 => [
                'label' => 'Basic Information',
                'description' => 'Enter the basic product details',
                'validation_rules' => [
                    'basic_info' => 'required|array',
                    'basic_info.name' => 'required|string|max:255',
                    'basic_info.description' => 'required|string',
                    'basic_info.price' => 'required|numeric|min:0',
                    'basic_info.subcategory_id' => 'required|exists:subcategories,id',
                    'session_id' => 'required|string',
                    'step' => 'required|integer|in:1'
                ]
            ],
            2 => [
                'label' => 'Product Attributes',
                'description' => 'Select product attributes and values',
                'validation_rules' => [
                    'attributes' => 'required|array',
                    'attributes.*.attribute_id' => 'required|integer|exists:attributes,id',
                    'attributes.*.value_id' => 'required|integer|exists:attribute_values,id',
                    'session_id' => 'required|string',
                    'step' => 'required|integer|in:2'
                ]
            ],
            3 => [
                'label' => 'Product Images',
                'description' => 'Upload product images',
                'validation_rules' => [
                    'images' => 'required|array|min:1',
                    'images.*' => [
                        'required',
                        'file',
                        'max:2048',
                        'mimes:jpeg,png,jpg'
                    ],
                    'session_id' => 'required|string',
                    'step' => 'required|integer|in:3'
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
            ]
          ],
        ]
    ]
];