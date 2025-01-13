<?php

return [
    'product_form' => [
        'total_steps' => 2,
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
    'service_form' => [
        'total_steps' => 3,
        'expiration_hours' => 48,
        'steps' => [
            1 => [
                'label' => 'Service Details',
                'description' => 'Enter basic service information',
                'validation_rules' => [
                    'title' => 'required|string|max:255',
                    'description' => 'required|string',
                ]
            ],
            2 => [
                'label' => 'Pricing',
                'description' => 'Set your service pricing',
                'validation_rules' => [
                    'price' => 'required|numeric|min:0',
                    'duration' => 'required|integer|min:1',
                ]
            ],
            3 => [
                'label' => 'Additional Information',
                'description' => 'Add any additional details',
                'validation_rules' => [
                    'terms' => 'required|string',
                    'availability' => 'required|array',
                ]
            ]
        ]
    ]
];