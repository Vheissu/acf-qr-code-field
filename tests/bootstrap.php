<?php
// Basic stubs for WordPress functions used by the plugin
if (!function_exists('esc_attr')) {
    function esc_attr($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}
if (!function_exists('esc_html')) {
    function esc_html($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

// Load the plugin classes
require_once dirname(__DIR__) . '/acf-qr-code-field/lib/phpqrcode/qrlib.php';
require_once dirname(__DIR__) . '/acf-qr-code-field/fields/class-acf-field-qr-code.php';
