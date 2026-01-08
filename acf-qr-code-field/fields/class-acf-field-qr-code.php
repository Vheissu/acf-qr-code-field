<?php
/**
 * ACF QR Code Field Class
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
     * Constructor.
     */
    public function __construct() {
        $this->name = 'qrcode';
        $this->label = __('QR Code', 'acf-qrcode-field');
        $this->category = 'formatting';
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
            'label' => __('Size (px)', 'acf-qrcode-field'),
            'instructions' => __('Set the size of the QR code in pixels.', 'acf-qrcode-field'),
            'type' => 'number',
            'name' => 'size',
            'min' => self::MIN_SIZE,
            'max' => self::MAX_SIZE,
            'step' => 10,
        ]);

        acf_render_field_setting($field, [
            'label' => __('Error Correction', 'acf-qrcode-field'),
            'instructions' => __('Select the error correction level.', 'acf-qrcode-field'),
            'type' => 'select',
            'name' => 'error_correction',
            'choices' => [
                'L' => __('Low (7%)', 'acf-qrcode-field'),
                'M' => __('Medium (15%)', 'acf-qrcode-field'),
                'Q' => __('Quartile (25%)', 'acf-qrcode-field'),
                'H' => __('High (30%)', 'acf-qrcode-field'),
            ],
        ]);

        acf_render_field_setting($field, [
            'label' => __('Margin', 'acf-qrcode-field'),
            'instructions' => __('Set the margin around the QR code.', 'acf-qrcode-field'),
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
        ?>
        <div class="acf-qrcode-field" data-field-key="<?php echo $field_key; ?>">
            <input
                type="url"
                class="widefat acf-qrcode-input"
                name="<?php echo $field_name; ?>"
                value="<?php echo $field_value; ?>"
                placeholder="<?php esc_attr_e('Enter URL to generate QR Code', 'acf-qrcode-field'); ?>"
            />
            <div class="acf-qrcode-preview" style="margin-top:10px;">
                <?php if (!empty($field['value'])): ?>
                    <?php echo $this->generate_qrcode_image($field['value'], $field); ?>
                <?php endif; ?>
            </div>
        </div>
        <script type="text/javascript">
        (function($){
            var debounceTimer;
            var fieldConfig = {
                size: <?php echo (int) ($field['size'] ?? 200); ?>,
                error_correction: '<?php echo esc_js($field['error_correction'] ?? 'L'); ?>',
                margin: <?php echo (int) ($field['margin'] ?? 4); ?>
            };

            function refreshQrcodeField($el) {
                var $input = $el.find('.acf-qrcode-input');
                var $preview = $el.find('.acf-qrcode-preview');
                var url = $input.val().trim();

                if (!url) {
                    $preview.html('');
                    return;
                }

                // Basic URL validation
                if (!url.match(/^https?:\/\//i)) {
                    $preview.html('<p style="color:#d63638;"><?php echo esc_js(__('Please enter a valid URL starting with http:// or https://', 'acf-qrcode-field')); ?></p>');
                    return;
                }

                $preview.html('<p><?php echo esc_js(__('Generating QR code...', 'acf-qrcode-field')); ?></p>');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'generate_qrcode',
                        url: url,
                        size: fieldConfig.size,
                        error_correction: fieldConfig.error_correction,
                        margin: fieldConfig.margin,
                        nonce: '<?php echo esc_js($nonce); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $preview.html(response.data);
                        } else {
                            $preview.html('<p style="color:#d63638;">' + (response.data || 'Error generating QR code') + '</p>');
                        }
                    },
                    error: function() {
                        $preview.html('<p style="color:#d63638;"><?php echo esc_js(__('Failed to generate QR code', 'acf-qrcode-field')); ?></p>');
                    }
                });
            }

            function setupField($el) {
                var $input = $el.find('.acf-qrcode-input');
                $input.on('input', function() {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(function() {
                        refreshQrcodeField($el);
                    }, 500);
                });
            }

            if (typeof acf !== 'undefined') {
                acf.add_action('ready_field/type=qrcode', setupField);
                acf.add_action('append_field/type=qrcode', setupField);
            }
        })(jQuery);
        </script>
        <?php
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
                throw new Exception(__('QR code library not loaded.', 'acf-qrcode-field'));
            }

            // Validate URL
            $url = esc_url_raw($url);
            if (empty($url)) {
                throw new Exception(__('Invalid URL provided.', 'acf-qrcode-field'));
            }

            // Generate QR code to a temporary file
            $temp_file = tempnam(sys_get_temp_dir(), 'qrcode_');
            if ($temp_file === false) {
                throw new Exception(__('Could not create temporary file.', 'acf-qrcode-field'));
            }

            // phpqrcode size is actually a multiplier (1-10), not pixel size
            // Convert pixel size to multiplier (200px / 25 = 8)
            $size_multiplier = max(1, min(10, (int) round($size / 25)));

            QRcode::png($url, $temp_file, $error_correction, $size_multiplier, $margin);

            $image_data = file_get_contents($temp_file);

            if ($image_data === false || empty($image_data)) {
                throw new Exception(__('Failed to read QR code image.', 'acf-qrcode-field'));
            }

            $base64_image = base64_encode($image_data);
            $src = 'data:image/png;base64,' . $base64_image;

            return sprintf(
                '<img src="%s" alt="%s" width="%d" height="%d" style="max-width:100%%;height:auto;" />',
                esc_attr($src),
                esc_attr__('QR Code', 'acf-qrcode-field'),
                $size,
                $size
            );
        } catch (Exception $e) {
            error_log('ACF QR Code Field Error: ' . $e->getMessage());
            return sprintf(
                '<p style="color:#d63638;">%s</p>',
                esc_html__('Error generating QR code.', 'acf-qrcode-field')
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
        $url = esc_url_raw($value, ['http', 'https']);
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
    public function format_value($value, $post_id, array $field): string {
        if (empty($value)) {
            return '';
        }

        return $this->generate_qrcode_image((string) $value, $field);
    }

    /**
     * AJAX handler for generating QR code.
     */
    public function ajax_generate_qrcode(): void {
        // Verify nonce
        if (!check_ajax_referer('acf_qrcode_generate', 'nonce', false)) {
            wp_send_json_error(__('Security check failed.', 'acf-qrcode-field'), 403);
        }

        // Check user capability
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('You do not have permission to perform this action.', 'acf-qrcode-field'), 403);
        }

        // Validate required parameter
        if (!isset($_POST['url'])) {
            wp_send_json_error(__('URL is required.', 'acf-qrcode-field'), 400);
        }

        $url = esc_url_raw(sanitize_text_field(wp_unslash($_POST['url'])), ['http', 'https']);

        if (empty($url)) {
            wp_send_json_error(__('Invalid URL. Please enter a valid http or https URL.', 'acf-qrcode-field'), 400);
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
