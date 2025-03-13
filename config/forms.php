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
                    'name' => 'required|string|max:255',
                    'description' => 'required|string',
                    'price' => 'required|numeric|min:0',
                    'subcategory_id' => 'required|exists:subcategories,id',
                ]
            ],
            2 => [
                'label' => 'Product Attributes',
                'description' => 'Select product attributes and values',
                'validation_rules' => [
                    'attribute_values' => 'required|array',
                    'attribute_values.*' => 'required|exists:attribute_values,id'
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
                    ]
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