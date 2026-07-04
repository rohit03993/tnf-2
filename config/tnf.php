<?php

return [
    'allow_public_registration' => env('ALLOW_PUBLIC_REGISTRATION', true),

    // Public contact details (Contact Us page + Play Console)
    'contact_email' => env('TNF_CONTACT_EMAIL', 'contact@tnftoday.com'),
    'contact_phone' => env('TNF_CONTACT_PHONE', '+19412359817'),
    'contact_company' => env('TNF_CONTACT_COMPANY', 'TNF Today Media Network Pvt Ltd'),
    'contact_address' => env('TNF_CONTACT_ADDRESS', ''),

    // Android App Links — Play Console → App integrity → App signing (SHA-256)
    'android_package_name' => env('ANDROID_PACKAGE_NAME', 'com.tnftoday.news'),
    'android_sha256_fingerprints' => array_values(array_filter(array_map(
        static fn (string $fp) => trim($fp),
        explode(',', (string) env('ANDROID_SHA256_FINGERPRINTS', '')),
    ))),

    'pdf_service_url' => env('PDF_SERVICE_URL'),
    'pdf_service_secret' => env('PDF_SERVICE_SECRET'),
    'pdf_callback_secret' => env('PDF_CALLBACK_SECRET'),

    // Set true only on production with Redis + Supervisor/Horizon running queue workers.
    'pdf_use_queue' => env('PDF_USE_QUEUE', false),

    'onesignal_app_id' => env('ONESIGNAL_APP_ID'),
    'onesignal_rest_key' => env('ONESIGNAL_REST_KEY'),
    'frontend_url' => env('FRONTEND_URL', env('APP_URL')),

    'developer_credit' => [
        'name' => 'Pal Digital',
        'url' => 'https://paldigital.in/',
    ],

    'epaper_clip_og_secret' => env('EPAPER_CLIP_OG_SECRET'),

    // Local dev: any logged-in user can open restricted ePaper (off in production)
    'epaper_local_auth_access' => env('TNF_EPAPER_LOCAL_AUTH_ACCESS', env('APP_ENV') === 'local'),

    // Phase M — performance
    'page_cache_enabled' => env('TNF_PAGE_CACHE', env('APP_ENV') !== 'local'),
    'page_cache_ttl' => (int) env('TNF_PAGE_CACHE_TTL', 300),
    'homepage_cache_ttl' => (int) env('TNF_HOMEPAGE_CACHE_TTL', 300),
    'chrome_cache_ttl' => (int) env('TNF_CHROME_CACHE_TTL', 300),
    'browser_cache_max_age' => (int) env('TNF_BROWSER_CACHE_MAX_AGE', 60),
    'clip_url_ttl' => (int) env('TNF_CLIP_URL_TTL', 86400),

    // All public-site image uploads (news, videos, banner, submissions, etc.)
    'max_image_kb' => (int) env('TNF_MAX_IMAGE_KB', 150),

    // Phase M — security
    'security_headers_enabled' => env('TNF_SECURITY_HEADERS', true),
    'hsts_enabled' => env('TNF_HSTS', false),
];
