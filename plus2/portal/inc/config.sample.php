<?php
// Copy to config.php and fill in. config.php is git-ignored and never committed.
// The installer (plus2/portal/install.php) writes this file for you.
//
// This is the +2 Science portal of Shree Kamala Secondary School. It uses its
// OWN database — never the Kamala Science Campus portal's.

return [
    'db' => [
        'host'     => 'localhost',
        'name'     => 'u000000000_plus2',
        'user'     => 'u000000000_plus2',
        'password' => '',
        'charset'  => 'utf8mb4',
    ],
    // Absolute URL base of the portal, no trailing slash.
    'base_url'      => '/plus2/portal',
    'school_name'   => 'Shree Kamala Secondary School',
];
