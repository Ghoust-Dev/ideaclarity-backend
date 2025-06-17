<?php

return [
    /*
    |--------------------------------------------------------------------------
    | DeepSeek API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for DeepSeek API integration
    |
    */

    'deepseek' => [
        'api_key' => env('DEEPSEEK_API_KEY'),
        'base_url' => env('DEEPSEEK_BASE_URL', 'https://api.deepseek.com/v1'),
        'model' => env('DEEPSEEK_MODEL', 'deepseek-chat'),
        'timeout' => env('DEEPSEEK_TIMEOUT', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Legacy OpenAI Configuration (Deprecated)
    |--------------------------------------------------------------------------
    |
    | These settings are kept for backward compatibility but should not be used
    | in new implementations. Use DeepSeek configuration above instead.
    |
    */

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'organization' => env('OPENAI_ORGANIZATION'),
    ],
];
