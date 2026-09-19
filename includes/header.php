<?php
// Set HTTP security headers before HTML output
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

// Ensure sessions and CSRF helpers are available to any pages loading the header
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - College Club Management System</title>
    <!-- Use base relative path to access assets properly regardless of nested directory -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
