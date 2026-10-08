<?php

return [
    'session_lifetime' => (int)(getenv('SESSION_LIFETIME') ?: 7200),
    'session_absolute_lifetime' => (int)(getenv('SESSION_ABSOLUTE_LIFETIME') ?: 28800),
    'max_login_attempts' => (int)(getenv('AUTH_MAX_LOGIN_ATTEMPTS') ?: 5),
    'ip_max_attempts' => (int)(getenv('AUTH_IP_MAX_ATTEMPTS') ?: 20),
    'account_max_attempts' => (int)(getenv('AUTH_ACCOUNT_MAX_ATTEMPTS') ?: 5),
    'lockout_minutes' => (int)(getenv('AUTH_LOCKOUT_MINUTES') ?: 15),
    'mfa' => [
        'encryption_key' => getenv('MFA_ENCRYPTION_KEY') ?: '',
        'issuer' => getenv('MFA_ISSUER') ?: 'Gestão de Academia',
        'challenge_timeout_minutes' => (int)(getenv('MFA_CHALLENGE_TIMEOUT_MINUTES') ?: 5),
        'max_attempts' => (int)(getenv('MFA_MAX_ATTEMPTS') ?: 5),
        'enforce_roles' => ['admin', 'profissional'],
    ],
    'password_reset' => [
        'timeout_minutes' => (int)(getenv('PASSWORD_RESET_TIMEOUT_MINUTES') ?: 30),
        'max_attempts' => (int)(getenv('PASSWORD_RESET_MAX_ATTEMPTS') ?: 3),
    ],
    'mail' => [
        'driver' => getenv('MAIL_DRIVER') ?: 'outbox',
        'host' => getenv('MAIL_HOST') ?: '127.0.0.1',
        'port' => (int)(getenv('MAIL_PORT') ?: 1025),
        'username' => getenv('MAIL_USERNAME') ?: '',
        'password' => getenv('MAIL_PASSWORD') ?: '',
        'encryption' => getenv('MAIL_ENCRYPTION') ?: null,
        'from_address' => getenv('MAIL_FROM_ADDRESS') ?: 'noreply@gestaoacademia.local',
        'from_name' => getenv('MAIL_FROM_NAME') ?: 'Gestão de Academia',
    ],
];
