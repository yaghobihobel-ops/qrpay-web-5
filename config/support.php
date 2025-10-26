<?php

return [
    'bot' => [
        'driver' => env('SUPPORT_BOT_DRIVER', 'dialogflow'),
        'confidence_threshold' => (float) env('SUPPORT_BOT_CONFIDENCE_THRESHOLD', 0.55),
        'dialogflow' => [
            'endpoint' => env('DIALOGFLOW_ENDPOINT'),
            'token' => env('DIALOGFLOW_TOKEN'),
            'language_code' => env('DIALOGFLOW_LANGUAGE_CODE', 'en'),
            'project_id' => env('DIALOGFLOW_PROJECT_ID'),
        ],
        'rasa' => [
            'endpoint' => env('RASA_REST_ENDPOINT', 'http://localhost:5005/webhooks/rest/webhook'),
        ],
        'fallback_responses' => [
            'greeting' => 'Hello! I\'m here to help. Could you tell me more about what you need?',
            'default' => 'I\'m sorry, I\'m not sure about that yet. I can connect you with an operator if you need further assistance.',
        ],
    ],
    'notifications' => [
        'email' => env('SUPPORT_TEAM_EMAIL'),
        'slack_webhook' => env('SUPPORT_SLACK_WEBHOOK'),
    ],
    'sla' => [
        'first_response_minutes' => (int) env('SUPPORT_SLA_FIRST_RESPONSE_MINUTES', 30),
        'resolution_minutes' => (int) env('SUPPORT_SLA_RESOLUTION_MINUTES', 1440),
    ],
];
