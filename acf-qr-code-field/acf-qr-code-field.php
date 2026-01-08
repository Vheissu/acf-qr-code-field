<?php

declare(strict_types=1);

/*
Plugin Name: Advanced Custom Fields: QR Code Field
Plugin URI: https://github.com/Vheissu/acf-qr-code-field
Description: Adds a custom QR Code field type to Advanced Custom Fields.
Version: 1.0.0
Author: Dwayne Charrington
Author URI: https://ilikekillnerds.com
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: acf-qr-code-field
Domain Path: /languages
Requires at least: 6.0
Requires PHP: 8.0
Requires Plugins: advanced-custom-fields
*/

if (!defined('ABSPATH')) {
    exit;
}

define('ACF_QR_CODE_FIELD_VERSION', '1.0.0');
define('ACF_QR_CODE_FIELD_FILE', __FILE__);
define('ACF_QR_CODE_FIELD_PATH', plugin_dir_path(__FILE__));
define('ACF_QR_CODE_FIELD_URL', plugin_dir_url(__FILE__));

// Include the QR Code library
require_once plugin_dir_path(__FILE__) . 'lib/phpqrcode/qrlib.php';

// Include the field class
add_action('acf/include_field_types', function (): void {
    include_once ACF_QR_CODE_FIELD_PATH . 'fields/class-acf-field-qr-code.php';

    if (function_exists('acf_register_field_type')) {
        acf_register_field_type('acf_field_qrcode');
        return;
    }

    // Legacy fallback.
    new acf_field_qrcode();
});

// Add plugin text domain for translations
add_action('init', function (): void {
    load_plugin_textdomain('acf-qr-code-field', false, basename(dirname(__FILE__)) . '/languages');
});

/**
 * Display ACF QR Code field
 *
 * @param string $field_name The name of the ACF QR Code field
 * @param int|string $post_id Optional. The post ID. Defaults to current post.
 * @param array $attr Optional. Additional attributes for the img tag.
 * @return void
 */
function display_acf_qr_code(string $field_name, $post_id = null, array $attr = []): void {
    $post_id = $post_id ?: get_the_ID();
    $qr_code_url = get_field($field_name, $post_id);

    if ($qr_code_url) {
        $qr_field = acf_get_field_type('qrcode');
        $qr_code_image = $qr_field->format_value($qr_code_url, $post_id, acf_get_field($field_name), false);
        
        if (preg_match('/src="([^"]+)"/', $qr_code_image, $src_match) &&
            preg_match('/width="([^"]+)"/', $qr_code_image, $width_match) &&
            preg_match('/height="([^"]+)"/', $qr_code_image, $height_match)) {
            
            $default_attr = [
                'src' => $src_match[1],
                'width' => $width_match[1],
                'height' => $height_match[1],
                'alt' => 'QR Code',
                'class' => 'acf-qr-code',
            ];
            
            $img_attr = array_merge($default_attr, $attr);
            $attr_string = implode(' ', array_map(function($key, $value) {
                return sprintf('%s="%s"', esc_attr($key), esc_attr($value));
            }, array_keys($img_attr), $img_attr));
            
            echo sprintf('<img %s />', $attr_string);
        } else {
            echo $qr_code_image; // Fallback to original output if parsing fails
        }
    }
}
