<?php

use Portfolion\Session\Session;

if (!function_exists('session')) {
    /**
     * Get or set session data
     * 
     * @param string|array|null $key The key to get or an array of values to set
     * @param mixed $default The default value if the key doesn't exist
     * @return mixed The session value or instance
     */
    function session($key = null, $default = null)
    {
        $session = Session::getInstance();
        
        // Return the session instance if no arguments
        if ($key === null) {
            return $session;
        }
        
        // Set multiple values if key is an array
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $session->set($k, $v);
            }
            return null;
        }
        
        // Get a value
        return $session->get($key, $default);
    }
}

if (!function_exists('session_has')) {
    /**
     * Check if a session key exists
     * 
     * @param string $key The key to check
     * @return bool Whether the key exists
     */
    function session_has(string $key): bool
    {
        return Session::getInstance()->has($key);
    }
}

if (!function_exists('session_remove')) {
    /**
     * Remove a value from the session
     * 
     * @param string $key The key to remove
     * @return void
     */
    function session_remove(string $key): void
    {
        Session::getInstance()->remove($key);
    }
}

if (!function_exists('session_flash')) {
    /**
     * Flash a value to the session
     * 
     * @param string $key The key to flash
     * @param mixed $value The value to flash
     * @return void
     */
    function session_flash(string $key, $value): void
    {
        Session::getInstance()->flash($key, $value);
    }
}

if (!function_exists('session_get_flash')) {
    /**
     * Get a flashed value from the session
     * 
     * @param string $key The key to get
     * @param mixed $default The default value if the key doesn't exist
     * @return mixed The value or default
     */
    function session_get_flash(string $key, $default = null)
    {
        return Session::getInstance()->getFlash($key, $default);
    }
}

if (!function_exists('session_has_flash')) {
    /**
     * Check if a flashed value exists in the session
     * 
     * @param string $key The key to check
     * @return bool Whether the key exists
     */
    function session_has_flash(string $key): bool
    {
        return Session::getInstance()->hasFlash($key);
    }
}

if (!function_exists('session_regenerate')) {
    /**
     * Regenerate the session ID
     * 
     * @param bool $deleteOldSession Whether to delete the old session
     * @return bool Whether the ID was regenerated
     */
    function session_regenerate(bool $deleteOldSession = true): bool
    {
        return Session::getInstance()->regenerateId($deleteOldSession);
    }
}

if (!function_exists('session_destroy')) {
    /**
     * Destroy the session
     * 
     * @return bool Whether the session was destroyed
     */
    function session_destroy(): bool
    {
        return Session::getInstance()->destroy();
    }
}

if (!function_exists('csrf_token')) {
    /**
     * Get or generate a CSRF token
     * 
     * @return string The CSRF token
     */
    function csrf_token(): string
    {
        $session = Session::getInstance();
        
        if (!$session->has('_csrf_token')) {
            $session->set('_csrf_token', bin2hex(random_bytes(32)));
        }
        
        return $session->get('_csrf_token');
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Generate a CSRF token field
     * 
     * @return string The CSRF token field HTML
     */
    function csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . csrf_token() . '">';
    }
} 