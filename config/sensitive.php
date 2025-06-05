<?php

/**
 * This file defines patterns for configuration keys that contain sensitive information
 * and should be encrypted when stored in the configuration cache.
 * 
 * Patterns support shell-style wildcards:
 * - * matches any number of characters
 * - ? matches a single character
 * - [abc] matches any character in the set
 * - [!abc] matches any character not in the set
 */
return [
    // Database credentials
    'database.*.password',
    'database.*.username',
    
    // Mail credentials
    'mail.password',
    'mail.username',
    'mail.*.password',
    'mail.*.username',
    
    // API keys and secrets
    '*.key',
    '*.secret',
    '*.token',
    '*.password',
    
    // OAuth credentials
    'services.*.client_secret',
    'services.*.private_key',
    
    // Any key explicitly marked as sensitive
    '*.sensitive.*'
];
