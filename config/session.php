<?php
// Default session cookie lifetime (24h)
$cookieLifetime = 86400;
// Session configuration
if (session_status() === PHP_SESSION_NONE) {
    // Secure if over HTTPS
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    // Determine session name based on area
    if (strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false) {
        session_name('ADMINSESSID');
    } else {
        session_name('PHPSESSID');
    }
    // Use project root as cookie path (e.g. '/Rental')
    $cookiePath = dirname($_SERVER['SCRIPT_NAME'], 2);
    // Set session cookie params
    session_set_cookie_params([
        'lifetime' => $cookieLifetime,
        'path'     => $cookiePath,
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    // Set garbage collection max lifetime
    ini_set('session.gc_maxlifetime', $cookieLifetime);
    // Start the session
    session_start();
}
// Regenerate session ID periodically (24h)
if (isset($_SESSION['last_regeneration'])) {
    if (time() - $_SESSION['last_regeneration'] > $cookieLifetime) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
} else {
    $_SESSION['last_regeneration'] = time();
}
?>