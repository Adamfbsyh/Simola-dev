<?php

return [
    'enabled' => env('SIMOLA_HELP_ENABLED', true),

    'ai' => [
        'enabled' => env('SIMOLA_HELP_AI_ENABLED', true),
        'base_url' => rtrim((string) env(
            'SIMOLA_HELP_AI_BASE_URL',
            'https://api.openai.com/v1'
        ), '/'),
        'endpoint' => env('SIMOLA_HELP_AI_ENDPOINT', 'auto'),
        'api_key' => env(
            'SIMOLA_HELP_AI_API_KEY',
            env('OPENAI_API_KEY')
        ),
        'model' => env('SIMOLA_HELP_AI_MODEL', 'gpt-5-mini'),
        'timeout' => (int) env('SIMOLA_HELP_AI_TIMEOUT', 35),
        'max_output_tokens' => (int) env(
            'SIMOLA_HELP_MAX_OUTPUT_TOKENS',
            650
        ),
    ],

    'local_direct_score' => (int) env(
        'SIMOLA_HELP_LOCAL_DIRECT_SCORE',
        12
    ),

    'max_context_articles' => 4,
];
