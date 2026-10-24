<?php
// START SESSION
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// BASE URL (important)
define('BASE_URL', 'https://library.morg.in/console/');

// TIMEZONE
date_default_timezone_set('Asia/Kolkata');
