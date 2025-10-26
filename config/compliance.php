<?php

return [
    'regional_rules' => [
        'GLOBAL' => [
            [
                'name' => 'SanctionsScreening',
                'description' => 'Flag users whose provided country is on the sanctions watch list.',
                'risk' => 40,
                'conditions' => [
                    ['field' => 'kyc.country', 'operator' => 'in', 'value' => ['IR', 'KP', 'SY']]
                ],
                'resolution' => 'enhanced_due_diligence',
            ],
            [
                'name' => 'HighRiskDocumentMismatch',
                'description' => 'Fail submissions where document country and declared residency differ.',
                'risk' => 25,
                'conditions' => [
                    ['field' => 'kyc.country', 'operator' => 'neq', 'value' => 'kyc.document_country'],
                ],
                'resolution' => 'manual_review',
            ],
        ],
        'CN' => [
            [
                'name' => 'ChinaTransactionVolume',
                'description' => 'Escalate customers in China whose expected transaction volume is above local thresholds.',
                'risk' => 35,
                'conditions' => [
                    ['field' => 'kyc.expected_monthly_volume', 'operator' => 'gte', 'value' => 50000],
                ],
                'resolution' => 'manual_review',
            ],
            [
                'name' => 'ChinaCrossBorderFlag',
                'description' => 'Flag cross-border customers operating in restricted industries.',
                'risk' => 50,
                'conditions' => [
                    ['field' => 'kyc.is_cross_border', 'operator' => 'equals', 'value' => true],
                    ['field' => 'kyc.industry', 'operator' => 'in', 'value' => ['GAMBLING', 'CRYPTO', 'FOREX']],
                ],
                'resolution' => 'enhanced_due_diligence',
            ],
        ],
        'IR' => [
            [
                'name' => 'IranSourceOfFunds',
                'description' => 'Require documentary proof of source of funds in Iran.',
                'risk' => 45,
                'conditions' => [
                    ['field' => 'kyc.source_of_funds', 'operator' => 'missing'],
                ],
                'resolution' => 'request_additional_documentation',
            ],
        ],
        'RU' => [
            [
                'name' => 'RussiaPEPFlag',
                'description' => 'Escalate Russian PEP or state-owned enterprise associations.',
                'risk' => 55,
                'conditions' => [
                    ['field' => 'kyc.is_pep', 'operator' => 'equals', 'value' => true],
                ],
                'resolution' => 'enhanced_due_diligence',
            ],
        ],
        'TR' => [
            [
                'name' => 'TurkeyCashIntensiveBusiness',
                'description' => 'Require inspection for high-cash industries in Turkey.',
                'risk' => 30,
                'conditions' => [
                    ['field' => 'kyc.industry', 'operator' => 'in', 'value' => ['JEWELRY', 'GAMBLING', 'REAL_ESTATE']],
                ],
                'resolution' => 'manual_review',
            ],
        ],
    ],

    'thresholds' => [
        'review' => 40,
        'escalate' => 70,
    ],

    'audit_log_retention' => [
        'GLOBAL' => 365,
        'CN' => 730,
        'IR' => 365,
        'RU' => 548,
        'TR' => 730,
    ],
];
