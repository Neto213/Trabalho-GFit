<?php

return [
    'notice_version' => getenv('PRIVACY_NOTICE_VERSION') ?: '2026-10-01',
    'controller_name' => getenv('PRIVACY_CONTROLLER_NAME') ?: 'GFit / academia responsável pela conta',
    'contact_email' => getenv('PRIVACY_CONTACT_EMAIL') ?: 'privacidade@gfit.local',
    'encryption_key' => getenv('DATA_ENCRYPTION_KEY') ?: '',
    'fingerprint_key' => getenv('PRIVACY_FINGERPRINT_KEY') ?: '',
    'retention' => [
        'security_logs_days' => (int)(getenv('PRIVACY_SECURITY_LOG_DAYS') ?: 180),
        'application_logs_days' => (int)(getenv('PRIVACY_APP_LOG_DAYS') ?: 90),
        'mail_outbox_days' => (int)(getenv('PRIVACY_MAIL_OUTBOX_DAYS') ?: 30),
        'incident_records_days' => 1825,
    ],
];
