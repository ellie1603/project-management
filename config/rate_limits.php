<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Request Rate Limits
    |--------------------------------------------------------------------------
    |
    | Maximum requests per minute for each limiter registered in
    | AppServiceProvider. Signed-in users are counted per account, because
    | the whole office usually shares one public IP address; guests are
    | counted per IP address.
    |
    */

    // Every web request.
    'global_user' => (int) env('RATE_LIMIT_GLOBAL_USER', 300),
    'global_guest' => (int) env('RATE_LIMIT_GLOBAL_GUEST', 120),

    // Anything that changes data (POST, PUT, PATCH, DELETE).
    'writes' => (int) env('RATE_LIMIT_WRITES', 60),

    // Wrong passwords allowed for one email + IP address before sign-in is
    // locked for a minute. This is a count of attempts, not a per-minute rate.
    'login_attempts' => (int) env('RATE_LIMIT_LOGIN_ATTEMPTS', 5),

    // Sign-in attempts from one IP address, across all email addresses.
    'login' => (int) env('RATE_LIMIT_LOGIN', 20),

    // Password reset, confirmation, and change attempts.
    'password' => (int) env('RATE_LIMIT_PASSWORD', 6),

    // Global search runs several LIKE queries per request.
    'search' => (int) env('RATE_LIMIT_SEARCH', 30),

    // Reports load every visible project and may render a PDF or spreadsheet.
    'reports' => (int) env('RATE_LIMIT_REPORTS', 30),

    // File uploads: documents, progress attachments, budget request files.
    'uploads' => (int) env('RATE_LIMIT_UPLOADS', 20),

];
