<?php

return [
    /*
    |--------------------------------------------------------------------------
    | LiveKit Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for LiveKit server integration
    |
    */

    'api_key' => env('LIVEKIT_API_KEY'),
    'api_secret' => env('LIVEKIT_API_SECRET'),
    'url' => env('LIVEKIT_URL', 'http://localhost:7880'),
    
    // Webhook secret for validating LiveKit webhooks (optional)
    'webhook_secret' => env('LIVEKIT_WEBHOOK_SECRET'),

    // Default token TTL in seconds (1 hour)
    'default_token_ttl' => 3600,

    // Default room options
    'default_room_options' => [
        'max_participants' => 50,
        'empty_timeout' => 300, // 5 minutes
        'max_duration' => 7200, // 2 hours
    ],

    // Default permissions for different roles
    'permissions' => [
        'host' => [
            'canPublish' => true,
            'canSubscribe' => true,
            'canPublishData' => true,
            'canUpdateOwnMetadata' => true,
            'hidden' => false,
            'recorder' => false,
        ],
        'cohost' => [
            'canPublish' => true,
            'canSubscribe' => true,
            'canPublishData' => true,
            'canUpdateOwnMetadata' => true,
            'hidden' => false,
            'recorder' => false,
        ],
        'participant' => [
            'canPublish' => true,
            'canSubscribe' => true,
            'canPublishData' => true,
            'canUpdateOwnMetadata' => true,
            'hidden' => false,
            'recorder' => false,
        ],
    ],
];
