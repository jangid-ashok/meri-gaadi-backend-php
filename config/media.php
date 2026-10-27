<?php

return [
    'disk' => 'media',
    'max_image_kb' => (int) env('MEDIA_MAX_IMAGE_KB', 5120),
    'max_video_kb' => (int) env('MEDIA_MAX_VIDEO_KB', 51200),
    'max_dimension' => (int) env('MEDIA_MAX_DIMENSION', 20000),
    'max_pixels' => (int) env('MEDIA_MAX_IMAGE_PIXELS', 120000000),
];
