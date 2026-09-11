<?php
// Copy to config.php and fill in. config.php is git-ignored and never committed.
// The installer (portal/install.php) writes this file for you.

return [
    'db' => [
        'host'     => 'localhost',
        'name'     => 'u000000000_example',
        'user'     => 'u000000000_example',
        'password' => '',
        'charset'  => 'utf8mb4',
    ],
    // Absolute URL base of the portal, no trailing slash.
    'base_url'      => '/portal',
    'campus_name'   => 'Kamala Science Campus',
    // Max upload size for learning materials, in bytes.
    'max_upload'    => 20 * 1024 * 1024,
];
