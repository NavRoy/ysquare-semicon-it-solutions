<?php
/* ============================================================
   config.php — YSQUARE Internship Application Configuration
   Edit this file before going live. Never expose it publicly.
   ============================================================ */

/* ── HR / Recipient ─────────────────────────────────────── */
define('HR_EMAIL',        'naveenroy1940@gmail.com');      // Where applications go
define('HR_NAME',         'YSQUARE HR Team');
define('SENDER_EMAIL',    'no-reply@ysquaresemicon.com'); // From address
define('SENDER_NAME',     'YSQUARE Semicon IT Solutions');

/* ── Upload Directory ───────────────────────────────────── */
// Relative to submit.php. Must exist and be writable (chmod 755).
define('UPLOAD_DIR',      __DIR__ . '/uploads/');

/* ── File Upload Constraints ────────────────────────────── */
define('MAX_FILE_SIZE',   5 * 1024 * 1024);  // 5 MB in bytes
define('ALLOWED_TYPES',   ['application/pdf',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document']);
define('ALLOWED_EXTS',    ['pdf', 'doc', 'docx']);

/* ── Reference ID Prefix ────────────────────────────────── */
define('REF_PREFIX',      'YSQ-' . date('Y') . '-');

/* ── Valid Domains (must match <option> values in apply.php) */
define('VALID_DOMAINS', [
    'Software Engineering',
    'Embedded Systems',
    'Semiconductor Engineering',
    'Digital Engineering',
    'Artificial Intelligence',
    'IoT',
    'Cloud',
    'Cyber Security',
]);

/* ── Valid Years ─────────────────────────────────────────── */
define('VALID_YEARS', ['2024', '2025', '2026', '2027', '2028', '2029']);