<?php

return [
    'SITE_TITLE' => 'Meri Gaadi',
    'ASSETS_PATH' => 'assets',
    'JS_PATH' => 'assets/js',
    'BLOG_CATEGORY_IMAGE_PATH' => 'assets/images/blog-categories',
    // Public path segment for blog gallery files; DB stores filename only (see BlogMedia).
    'BLOG_GALLERY_MEDIA_PATH' => 'assets/media/blogs',
    'CATEGORY_TYPES' => [
        'automotive' => 'Automotive',
        'content' => 'Content',
    ],
    'CAR_USE_CASES' => [
        'first-car' => 'My first car',
        'family' => 'Family life',
        'city' => 'City driving',
        'road-trips' => 'Long road trips',
        'electric' => 'Going electric',
        'driving-fun' => 'Driving for fun',
    ],
    // Gallery file limits are configured in config/media.php.
];
