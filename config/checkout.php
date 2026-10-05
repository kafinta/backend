<?php

return [
    'default_shipping_method' => env('CHECKOUT_DEFAULT_SHIPPING_METHOD', 'standard'),

    'shipping_methods' => [
        'standard' => [
            'id' => 'standard',
            'name' => 'Standard delivery',
            'description' => 'Local delivery in 3-5 business days',
            'price' => 1500.00,
        ],
        'express' => [
            'id' => 'express',
            'name' => 'Express delivery',
            'description' => 'Priority delivery in 1-2 business days',
            'price' => 3500.00,
        ],
        'free' => [
            'id' => 'free',
            'name' => 'Free delivery',
            'description' => 'Delivery in 5-7 business days',
            'price' => 0.00,
        ],
    ],

    'payment_methods' => [
        [
            'id' => 'paystack',
            'name' => 'Paystack',
            'description' => 'Pay securely with card, bank transfer, or USSD',
        ],
    ],
];
