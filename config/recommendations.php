<?php

return [
    'loyalty_threshold' => (float) env('RECOMMENDATION_LOYALTY_THRESHOLD', 0.35),

    'cache_ttl' => (int) env('RECOMMENDATION_CACHE_TTL', 600),

    'rule_weight' => (float) env('RECOMMENDATION_RULE_WEIGHT', 0.55),

    'ml_weight' => (float) env('RECOMMENDATION_ML_WEIGHT', 0.45),

    'decay_period_days' => (int) env('RECOMMENDATION_DECAY_PERIOD_DAYS', 180),

    'min_transactions' => (int) env('RECOMMENDATION_MIN_TRANSACTIONS', 5),

    'supported_routes' => [
        'payment' => [
            'label' => 'Payment Route',
            'event_type' => 'transactions.payment.completed',
        ],
        'exchange' => [
            'label' => 'Exchange Route',
            'event_type' => 'transactions.exchange.completed',
        ],
        'withdrawal' => [
            'label' => 'Withdrawal Route',
            'event_type' => 'transactions.withdrawal.completed',
        ],
    ],
];
