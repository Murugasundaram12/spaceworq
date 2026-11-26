<?php

return [
    'api_prefix' => 'api/v1',

    'payment' => [
        'default_gateway' => env('SPACEWORQ_PAYMENT_GATEWAY', 'razorpay'),
        'razorpay' => [
            'key_id' => env('RAZORPAY_KEY_ID'),
            'key_secret' => env('RAZORPAY_KEY_SECRET'),
            'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET'),
        ],
    ],

    'booking' => [
        // Fail-closed / configurable business policy rules (require explicit business approval before non-null defaults)
        'pay_at_venue_enabled' => filter_var(env('SPACEWORQ_PAY_AT_VENUE_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
        'hold_minutes' => env('SPACEWORQ_BOOKING_HOLD_MINUTES') !== null ? (int) env('SPACEWORQ_BOOKING_HOLD_MINUTES') : null,
        'default_tax_rate' => env('SPACEWORQ_DEFAULT_TAX_RATE') !== null ? (float) env('SPACEWORQ_DEFAULT_TAX_RATE') : null,
        'half_day_multiplier' => env('SPACEWORQ_HALF_DAY_MULTIPLIER') !== null ? (float) env('SPACEWORQ_HALF_DAY_MULTIPLIER') : null,
        'max_instant_booking_seats' => env('SPACEWORQ_MAX_INSTANT_SEATS') !== null ? (int) env('SPACEWORQ_MAX_INSTANT_SEATS') : null,
        'cancellation_cutoff_hours' => env('SPACEWORQ_CANCELLATION_CUTOFF_HOURS') !== null ? (int) env('SPACEWORQ_CANCELLATION_CUTOFF_HOURS') : null,
        'allow_non_day_pass_instant_book' => filter_var(env('SPACEWORQ_ALLOW_NON_DAY_PASS_INSTANT', false), FILTER_VALIDATE_BOOLEAN),
    ],

    'uploads' => [
        'workspace_images_disk' => env('SPACEWORQ_WORKSPACE_IMAGES_DISK', 'local'),
        'provider_documents_disk' => env('SPACEWORQ_PROVIDER_DOCS_DISK', 'local'),
        'max_workspace_images' => 5,
        'max_image_kb' => 5120,
        'max_document_kb' => 10240,
    ],

    'otp' => [
        'ttl_minutes' => 10,
        'max_attempts' => 5,
    ],

    'super_admin' => [
        'name' => env('SPACEWORQ_SUPER_ADMIN_NAME'),
        'email' => env('SPACEWORQ_SUPER_ADMIN_EMAIL'),
        'password' => env('SPACEWORQ_SUPER_ADMIN_PASSWORD'),
        'mobile' => env('SPACEWORQ_SUPER_ADMIN_MOBILE'),
        'username' => env('SPACEWORQ_SUPER_ADMIN_USERNAME'),
        'seed_on_db_seed' => filter_var(env('SPACEWORQ_SEED_SUPER_ADMIN', false), FILTER_VALIDATE_BOOLEAN),
    ],
];
