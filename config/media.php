<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Disk
    |--------------------------------------------------------------------------
    |
    | The default disk to store media files.
    | Options: 'public', 's3', 'spaces', etc.
    |
    */
    'default_disk' => env('MEDIA_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Maximum File Sizes (in KB)
    |--------------------------------------------------------------------------
    */
    'max_file_size' => [
        'image' => 10240, // 10MB
        'video' => 512000, // 500MB (for local upload)
        'document' => 51200, // 50MB
        'audio' => 51200, // 50MB
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed MIME Types
    |--------------------------------------------------------------------------
    */
    'allowed_mime_types' => [
        'image' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'],
        'video' => ['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime'],
        'document' => [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        ],
        'audio' => ['audio/mpeg', 'audio/wav', 'audio/ogg'],
        'archive' => ['application/zip', 'application/x-rar-compressed', 'application/x-7z-compressed'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Processing
    |--------------------------------------------------------------------------
    */
    'image_conversions' => [
        'thumb' => ['width' => 150, 'height' => 150],
        'small' => ['width' => 300, 'height' => 300],
        'medium' => ['width' => 600, 'height' => 600],
        'large' => ['width' => 1200, 'height' => 1200],
    ],

    /*
    |--------------------------------------------------------------------------
    | Video Processing
    |--------------------------------------------------------------------------
    */
    'ffmpeg_path' => env('FFMPEG_PATH', '/usr/bin/ffmpeg'),
    'ffprobe_path' => env('FFPROBE_PATH', '/usr/bin/ffprobe'),

    /*
    |--------------------------------------------------------------------------
    | CDN Configuration
    |--------------------------------------------------------------------------
    */
    'cdn_url' => env('CDN_URL', null),

    /*
    |--------------------------------------------------------------------------
    | Watermark
    |--------------------------------------------------------------------------
    */
    'watermark' => [
        'enabled' => env('WATERMARK_ENABLED', false),
        'image' => env('WATERMARK_IMAGE', null),
        'position' => 'bottom-right', // top-left, top-right, bottom-left, bottom-right, center
        'opacity' => 50, // 0-100
    ],
];
