<?php
/**
 * Security Handler - Provides security utilities for the SEO Optimizer plugin
 *
 * Implements data sanitization, encryption, capability checks, and audit logging
 * following OWASP security best practices.
 */
class SEO_Security {
    /**
     * Sanitize input data recursively
     *
     * @param mixed $input Input data to sanitize
     * @return mixed Sanitized data
     */
    public static function sanitize_input($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitize_input'], $input);
        }
        return sanitize_text_field($input);
    }

    /**
     * Encrypt data using Libsodium
     *
     * @param string $data Data to encrypt
     * @return string Encrypted data (base64 encoded)
     */
    public static function encrypt_data($data) {
        if (!function_exists('sodium_crypto_secretbox')) {
            return $data; // Fallback to plaintext if Libsodium not available
        }

        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $key = self::get_encryption_key();
        
        $encrypted = sodium_crypto_secretbox($data, $nonce, $key);
        return base64_encode($nonce . $encrypted);
    }

    /**
     * Decrypt data encrypted with encrypt_data()
     *
     * @param string $encrypted Encrypted data (base64 encoded)
     * @return string Decrypted data
     */
    public static function decrypt_data($encrypted) {
        if (!function_exists('sodium_crypto_secretbox_open')) {
            return $encrypted; // Fallback to plaintext if Libsodium not available
        }

        $decoded = base64_decode($encrypted);
        $nonce = substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $key = self::get_encryption_key();

        return sodium_crypto_secretbox_open($ciphertext, $nonce, $key);
    }

    /**
     * Get encryption key (generate if not defined)
     *
     * @return string Encryption key
     */
    private static function get_encryption_key() {
        $key = defined('SEO_ENCRYPTION_KEY') ? SEO_ENCRYPTION_KEY : '';
        if (empty($key) || strlen($key) < SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
            // Generate a key if not defined or too short
            $key = sodium_crypto_secretbox_keygen();
        }
        return $key;
    }

    /**
     * Check user capability
     *
     * @param string $capability Capability to check
     * @return bool True if user has capability
     */
    public static function check_capability($capability = 'manage_options') {
        return current_user_can($capability);
    }

    /**
     * Log security audit trail
     *
     * @param string $action Action being logged
     * @param array $details Action details
     */
    public static function log_audit_trail($action, $details) {
        // In production, this would log to a dedicated audit system
        error_log("SEO Audit: $action - " . print_r($details, true));
    }
}