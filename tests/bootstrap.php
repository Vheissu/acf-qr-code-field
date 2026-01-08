<?php

declare(strict_types=1);

/**
 * Test bootstrap file.
 */

// WordPress function stubs
if (!function_exists('esc_attr')) {
    function esc_attr(string $text): string {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_html')) {
    function esc_html(string $text): string {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_url_raw')) {
    function esc_url_raw(string $url, array $protocols = ['http', 'https', 'ftp']): string {
        if (empty($url)) {
            return '';
        }
        $parsed = parse_url($url);
        if (!$parsed || !isset($parsed['scheme'])) {
            return '';
        }
        if (!in_array(strtolower($parsed['scheme']), $protocols, true)) {
            return '';
        }
        return $url;
    }
}

if (!function_exists('esc_attr__')) {
    function esc_attr__(string $text, string $domain = 'default'): string {
        return esc_attr($text);
    }
}

if (!function_exists('esc_url')) {
    function esc_url(string $url): string {
        return $url;
    }
}

if (!function_exists('esc_html__')) {
    function esc_html__(string $text, string $domain = 'default'): string {
        return esc_html($text);
    }
}

if (!function_exists('__')) {
    function __(string $text, string $domain = 'default'): string {
        return $text;
    }
}

if (!function_exists('esc_js')) {
    function esc_js(string $text): string {
        return addslashes($text);
    }
}

if (!function_exists('esc_attr_e')) {
    function esc_attr_e(string $text, string $domain = 'default'): void {
        echo esc_attr($text);
    }
}

if (!function_exists('wp_create_nonce')) {
    function wp_create_nonce(string $action): string {
        return 'test_nonce_' . $action;
    }
}

if (!function_exists('check_ajax_referer')) {
    function check_ajax_referer(string $action, string|false $query_arg = false, bool $die = true): bool {
        return true;
    }
}

if (!function_exists('current_user_can')) {
    function current_user_can(string $capability): bool {
        return true;
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field(string $text): string {
        return trim(strip_tags($text));
    }
}

if (!function_exists('wp_unslash')) {
    function wp_unslash($value) {
        return is_string($value) ? stripslashes($value) : $value;
    }
}

if (!function_exists('wp_kses')) {
    function wp_kses(string $string, array $allowed_html = []): string {
        return $string;
    }
}

if (!function_exists('wp_send_json_success')) {
    function wp_send_json_success($data = null): void {
        throw new Exception('wp_send_json_success: ' . json_encode(['success' => true, 'data' => $data]));
    }
}

if (!function_exists('wp_send_json_error')) {
    function wp_send_json_error($data = null, ?int $code = null): void {
        throw new Exception('wp_send_json_error: ' . json_encode(['success' => false, 'data' => $data]));
    }
}

if (!function_exists('add_action')) {
    function add_action(string $tag, $callback, int $priority = 10, int $accepted_args = 1): bool {
        return true;
    }
}

if (!function_exists('acf_render_field_setting')) {
    function acf_render_field_setting(array $field, array $setting): void {
        // Stub
    }
}

// Define ABSPATH if not defined
if (!defined('ABSPATH')) {
    define('ABSPATH', '/var/www/html/');
}

// Mock acf_field base class
if (!class_exists('acf_field')) {
    class acf_field {
        public $name = '';
        public $label = '';
        public $category = '';
        public $defaults = [];

        public function __construct() {}
    }
}

// Load the phpqrcode library
require_once dirname(__DIR__) . '/acf-qr-code-field/lib/phpqrcode/qrlib.php';

// Load the field class
require_once dirname(__DIR__) . '/acf-qr-code-field/fields/class-acf-field-qr-code.php';
