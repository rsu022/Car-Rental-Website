<?php
require_once 'config/session.php';

// Debug current session before logout
error_log('Session before logout: ' . print_r($_SESSION, true));

// Unset all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Destroy the session
session_destroy();

// Debug after session destruction
error_log('Session destroyed');

// Redirect to login page
header("Location: login.php");
exit();
?>