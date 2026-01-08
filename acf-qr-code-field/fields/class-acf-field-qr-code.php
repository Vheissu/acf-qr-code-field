<?php

declare(strict_types=1);

/**
 * ACF QR Code Field Class.
 *
 * @package AcfQrCodeField
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * ACF QR Code field type.
 */
class acf_field_qrcode extends acf_field {

    /**
     * Valid error correction levels.
     */
    private const VALID_ERROR_LEVELS = ['L', 'M', 'Q', 'H'];

    /**
     * Minimum and maximum size constraints.
     */
    private const MIN_SIZE = 50;
    private const MAX_SIZE = 1000;
    private const MIN_MARGIN = 0;
    private const MAX_MARGIN = 10;

    /**
     * Field feature support flags.
     *
     * @var array<string, bool>
     */
    public $supports = [];

    /**
     * Constructor.
     */
    public function __construct() {
        $this->name = 'qrcode';
        $this->label = __('QR Code', 'acf-qr-code-field');
        $this->category = 'formatting';
        $this->supports = [
            'escaping_html' => true,
        ];
        $this->defaults = [
            'size' => 200,
            'error_correction' => 'L',
            'margin' => 4,
        ];

        parent::__construct();

        // Only register AJAX for logged-in users (admin only)
        add_action('wp_ajax_generate_qrcode', [$this, 'ajax_generate_qrcode']);
    }

    /**
     * Render the field settings in the admin.
     *
     * @param array $field The field settings.
     */
    public function render_field_settings(array $field): void {
        acf_render_field_setting($field, [
            'label' => __('Size (px)', 'acf-qr-code-field'),
            'instructions' => __('Set the size of the QR code in pixels.', 'acf-qr-code-field'),
            'type' => 'number',
            'name' => 'size',
            'min' => self::MIN_SIZE,
            'max' => self::MAX_SIZE,
            'step' => 10,
        ]);

        acf_render_field_setting($field, [
            'label' => __('Error Correction', 'acf-qr-code-field'),
            'instructions' => __('Select the error correction level.', 'acf-qr-code-field'),
            'type' => 'select',
            'name' => 'error_correction',
            'choices' => [
                'L' => __('Low (7%)', 'acf-qr-code-field'),
                'M' => __('Medium (15%)', 'acf-qr-code-field'),
                'Q' => __('Quartile (25%)', 'acf-qr-code-field'),
                'H' => __('High (30%)', 'acf-qr-code-field'),
            ],
        ]);

        acf_render_field_setting($field, [
            'label' => __('Margin', 'acf-qr-code-field'),
            'instructions' => __('Set the margin around the QR code.', 'acf-qr-code-field'),
            'type' => 'number',
            'name' => 'margin',
            'min' => self::MIN_MARGIN,
            'max' => self::MAX_MARGIN,
            'step' => 1,
        ]);
    }

    /**
     * Render the field input in the post editor.
     *
     * @param array $field The field settings.
     */
    public function render_field(array $field): void {
        $nonce = wp_create_nonce('acf_qrcode_generate');
        $field_name = esc_attr($field['name']);
        $field_value = esc_attr($field['value'] ?? '');
        $field_key = esc_attr($field['key'] ?? '');
        $size = $this->sanitize_size($field['size'] ?? $this->defaults['size']);
        $error_correction = $this->sanitize_error_correction($field['error_correction'] ?? $this->defaults['error_correction']);
        $margin = $this->sanitize_margin($field['margin'] ?? $this->defaults['margin']);
        $ajax_url = admin_url('admin-ajax.php');
        ?>
        <div
            class="acf-qr-code-field"
            data-field-key="<?php echo $field_key; ?>"
            data-size="<?php echo esc_attr((string) $size); ?>"
            data-error-correction="<?php echo esc_attr($error_correction); ?>"
            data-margin="<?php echo esc_attr((string) $margin); ?>"
            data-nonce="<?php echo esc_attr($nonce); ?>"
            data-ajax-url="<?php echo esc_url($ajax_url); ?>"
        >
            <input
                type="url"
                class="widefat acf-qrcode-input"
                name="<?php echo $field_name; ?>"
                value="<?php echo $field_value; ?>"
                placeholder="<?php esc_attr_e('Enter URL to generate QR Code', 'acf-qr-code-field'); ?>"
            />
            <div class="acf-qrcode-preview">
                <?php if (!empty($field['value'])): ?>
                    <?php echo $this->generate_qrcode_image($field['value'], $field); ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Enqueue scripts and styles for the input UI.
     */
    public function input_admin_enqueue_scripts(): void {
        $version = defined('ACF_QR_CODE_FIELD_VERSION') ? ACF_QR_CODE_FIELD_VERSION : '1.0.0';
        $url = plugin_dir_url(dirname(__DIR__) . '/acf-qr-code-field.php');

        wp_register_script(
            'acf-qr-code-field-input',
            $url . 'assets/js/input.js',
            ['acf-input', 'jquery'],
            $version,
            true
        );

        wp_register_style(
            'acf-qr-code-field-input',
            $url . 'assets/css/input.css',
            ['acf-input'],
            $version
        );

        wp_localize_script('acf-qr-code-field-input', 'acfQrCodeField', [
            'i18n' => [
                'invalidUrl' => __('Please enter a valid URL starting with http:// or https://', 'acf-qr-code-field'),
                'generating' => __('Generating QR code...', 'acf-qr-code-field'),
                'failed' => __('Failed to generate QR code', 'acf-qr-code-field'),
                'error' => __('Error generating QR code', 'acf-qr-code-field'),
            ],
        ]);

        wp_enqueue_script('acf-qr-code-field-input');
        wp_enqueue_style('acf-qr-code-field-input');
    }

    /**
     * Generate QR Code Image HTML.
     *
     * @param string $url The URL to encode.
     * @param array $field The field settings.
     * @return string The image HTML or error message.
     */
    public function generate_qrcode_image(string $url, array $field): string {
        $size = $this->sanitize_size($field['size'] ?? 200);
        $error_correction = $this->sanitize_error_correction($field['error_correction'] ?? 'L');
        $margin = $this->sanitize_margin($field['margin'] ?? 4);

        $temp_file = null;

        try {
            if (!class_exists('QRcode')) {
                throw new Exception(__('QR code library not loaded.', 'acf-qr-code-field'));
            }

            // Validate URL
            $url = esc_url_raw($url);
            if (empty($url)) {
                throw new Exception(__('Invalid URL provided.', 'acf-qr-code-field'));
            }

            // Generate QR code to a temporary file
            $temp_file = tempnam(sys_get_temp_dir(), 'qrcode_');
            if ($temp_file === false) {
                throw new Exception(__('Could not create temporary file.', 'acf-qr-code-field'));
            }

            // phpqrcode size is actually a multiplier (1-10), not pixel size
            // Convert pixel size to multiplier (200px / 25 = 8)
            $size_multiplier = max(1, min(10, (int) round($size / 25)));

            QRcode::png($url, $temp_file, $error_correction, $size_multiplier, $margin);

            $image_data = file_get_contents($temp_file);

            if ($image_data === false || empty($image_data)) {
                throw new Exception(__('Failed to read QR code image.', 'acf-qr-code-field'));
            }

            $base64_image = base64_encode($image_data);
            $src = 'data:image/png;base64,' . $base64_image;

            return sprintf(
                '<img src="%s" alt="%s" width="%d" height="%d" style="max-width:100%%;height:auto;" />',
                esc_attr($src),
                esc_attr__('QR Code', 'acf-qr-code-field'),
                $size,
                $size
            );
        } catch (Exception $e) {
            error_log('ACF QR Code Field Error: ' . $e->getMessage());
            return sprintf(
                '<p style="color:#d63638;">%s</p>',
                esc_html__('Error generating QR code.', 'acf-qr-code-field')
            );
        } finally {
            // Always clean up temporary file
            if ($temp_file !== null && file_exists($temp_file)) {
                @unlink($temp_file);
            }
        }
    }

    /**
     * Sanitize the size parameter.
     *
     * @param mixed $size The size value.
     * @return int The sanitized size.
     */
    private function sanitize_size($size): int {
        $size = (int) $size;
        return max(self::MIN_SIZE, min(self::MAX_SIZE, $size));
    }

    /**
     * Sanitize the error correction level.
     *
     * @param mixed $level The error correction level.
     * @return string The sanitized level.
     */
    private function sanitize_error_correction($level): string {
        $level = strtoupper((string) $level);
        return in_array($level, self::VALID_ERROR_LEVELS, true) ? $level : 'L';
    }

    /**
     * Sanitize the margin parameter.
     *
     * @param mixed $margin The margin value.
     * @return int The sanitized margin.
     */
    private function sanitize_margin($margin): int {
        $margin = (int) $margin;
        return max(self::MIN_MARGIN, min(self::MAX_MARGIN, $margin));
    }

    /**
     * Save the field value.
     *
     * @param mixed $value The value.
     * @param int|string $post_id The post ID.
     * @param array $field The field.
     * @return string The sanitized URL.
     */
    public function update_value($value, $post_id, array $field): string {
        // Only allow http and https URLs
        if ($value === null || $value === '') {
            return '';
        }
        $url = esc_url_raw((string) $value, ['http', 'https']);
        return $url ?: '';
    }

    /**
     * Format the field value for output.
     *
     * @param mixed $value The value.
     * @param int|string $post_id The post ID.
     * @param array $field The field.
     * @return string The QR code image HTML.
     */
    public function format_value($value, $post_id, array $field, $escape_html = false): string {
        if (empty($value)) {
            return '';
        }

        $html = $this->generate_qrcode_image((string) $value, $field);

        if ($escape_html) {
            return $this->sanitize_output_html($html);
        }

        return $html;
    }

    /**
     * Sanitize HTML output when escape_html is requested by ACF.
     */
    private function sanitize_output_html(string $html): string {
        return wp_kses($html, [
            'img' => [
                'src' => true,
                'alt' => true,
                'width' => true,
                'height' => true,
                'class' => true,
                'style' => true,
            ],
            'p' => [
                'style' => true,
                'class' => true,
            ],
        ]);
    }

    /**
     * AJAX handler for generating QR code.
     */
    public function ajax_generate_qrcode(): void {
        // Verify nonce
        if (!check_ajax_referer('acf_qrcode_generate', 'nonce', false)) {
            wp_send_json_error(__('Security check failed.', 'acf-qr-code-field'), 403);
        }

        // Check user capability
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('You do not have permission to perform this action.', 'acf-qr-code-field'), 403);
        }

        // Validate required parameter
        if (!isset($_POST['url'])) {
            wp_send_json_error(__('URL is required.', 'acf-qr-code-field'), 400);
        }

        $url = esc_url_raw(sanitize_text_field(wp_unslash($_POST['url'])), ['http', 'https']);

        if (empty($url)) {
            wp_send_json_error(__('Invalid URL. Please enter a valid http or https URL.', 'acf-qr-code-field'), 400);
        }

        // Build field config from sanitized POST data (not trusting client-sent field array)
        $field = [
            'size' => isset($_POST['size']) ? (int) $_POST['size'] : 200,
            'error_correction' => isset($_POST['error_correction']) ? sanitize_text_field($_POST['error_correction']) : 'L',
            'margin' => isset($_POST['margin']) ? (int) $_POST['margin'] : 4,
        ];

        $image_html = $this->generate_qrcode_image($url, $field);
        wp_send_json_success($image_html);
    }
}
