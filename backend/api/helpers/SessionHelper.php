<?php
/**
 * St. Lawrence Junior School - Centralized Session Security Helper
 * 
 * Features:
 * - Strict session mode (session.use_strict_mode = 1)
 * - Secure cookie parameters (HttpOnly, SameSite=Lax, Secure over HTTPS)
 * - Protection against Session Fixation (session_regenerate_id)
 * - Complete session invalidation on logout
 * - Inactivity / idle session timeout enforcement
 */

class SessionHelper {

    const IDLE_TIMEOUT_SECONDS = 7200; // 2 hours idle timeout

    /**
     * Initializes a hardened PHP session.
     */
    public static function start(): void {
        if (session_status() === PHP_SESSION_ACTIVE) {
            self::checkIdleTimeout();
            return;
        }

        // Enable strict session mode to prevent uninitialized session adoption
        ini_set('session.use_strict_mode', '1');

        // Prevent session ID from being passed in URLs
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');

        // Determine if request is over HTTPS
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

        // Configure session cookie parameters
        session_set_cookie_params([
            'lifetime' => 0, // Session cookie expires when browser closes
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        session_start();

        self::checkIdleTimeout();
    }

    /**
     * Regenerates the session ID to prevent session fixation upon authentication.
     */
    public static function regenerate(): void {
        if (session_status() === PHP_SESSION_NONE) {
            self::start();
        }
        session_regenerate_id(true);
        $_SESSION['last_activity'] = time();
    }

    /**
     * Completely destroys the session and invalidates the client-side session cookie.
     */
    public static function destroy(): void {
        if (session_status() === PHP_SESSION_NONE) {
            self::start();
        }

        // Unset all session variables
        $_SESSION = [];

        // Expire session cookie on browser
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
                || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'] ?? '/',
                $params['domain'] ?? '',
                $isHttps,
                true
            );
        }

        session_unset();
        session_destroy();
    }

    /**
     * Checks whether the current session has exceeded the idle timeout.
     */
    private static function checkIdleTimeout(): void {
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
            if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > self::IDLE_TIMEOUT_SECONDS)) {
                // Session expired due to inactivity
                self::destroy();
                return;
            }
            $_SESSION['last_activity'] = time();
        }
    }
}
