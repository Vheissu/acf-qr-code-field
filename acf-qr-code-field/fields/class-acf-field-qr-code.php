<?php

if( !defined('ABSPATH') ) exit; // Exit if accessed directly

class acf_field_qrcode extends acf_field {

    /**
     * Constructor.
     */
    function __construct() {
        // Set up field type data
        $this->name = 'qrcode';
        $this->label = __('QR Code', 'acf-qrcode-field');
        $this->category = 'formatting'; // Basic, Content, Choice, etc.
        $this->defaults = array(
            'size' => 200,
            'error_correction' => 'L', // L, M, Q, H
            'margin' => 4,
        );

        parent::__construct();

        add_action('wp_ajax_generate_qrcode', array($this, 'ajax_generate_qrcode'));
        add_action('wp_ajax_nopriv_generate_qrcode', array($this, 'ajax_generate_qrcode'));
    }

    /**
     * Render the field settings in the admin.
     *
     * @param array $field The field settings.
     */
    function render_field_settings( $field ) {
        // Size setting
        acf_render_field_setting( $field, array(
            'label'         => __('Size (px)', 'acf-qrcode-field'),
            'instructions'  => __('Set the size of the QR code in pixels.', 'acf-qrcode-field'),
            'type'          => 'number',
            'name'          => 'size',
            'min'           => 50,
            'max'           => 1000,
            'step'          => 10,
        ));

        // Error Correction Level
        acf_render_field_setting( $field, array(
            'label'         => __('Error Correction', 'acf-qrcode-field'),
            'instructions'  => __('Select the error correction level.', 'acf-qrcode-field'),
            'type'          => 'select',
            'name'          => 'error_correction',
            'choices'       => array(
                'L' => __('Low (7%)', 'acf-qrcode-field'),
                'M' => __('Medium (15%)', 'acf-qrcode-field'),
                'Q' => __('Quartile (25%)', 'acf-qrcode-field'),
                'H' => __('High (30%)', 'acf-qrcode-field'),
            ),
        ));

        // Margin setting
        acf_render_field_setting( $field, array(
            'label'         => __('Margin', 'acf-qrcode-field'),
            'instructions'  => __('Set the margin around the QR code.', 'acf-qrcode-field'),
            'type'          => 'number',
            'name'          => 'margin',
            'min'           => 0,
            'max'           => 10,
            'step'          => 1,
        ));
    }

    /**
     * Render the field input in the post editor.
     *
     * @param array $field The field settings.
     */
    function render_field( $field ) {
        ?>
        <?php $nonce = wp_create_nonce('acf_qrcode_generate'); ?>
        <div class="acf-qrcode-field">
            <input type="text" class="widefat" name="<?php echo esc_attr($field['name']); ?>" value="<?php echo esc_attr($field['value']); ?>" placeholder="<?php esc_attr_e('Enter URL to generate QR Code', 'acf-qrcode-field'); ?>" />
            <div class="acf-qrcode-preview" style="margin-top:10px;">
                <?php if( !empty($field['value']) ): ?>
                    <?php echo $this->generate_qrcode_image($field['value'], $field); ?>
                <?php endif; ?>
            </div>
        </div>
        <script type="text/javascript">
        (function($){
            function refresh_qrcode_field($el) {
                var $input = $el.find('input');
                var $preview = $el.find('.acf-qrcode-preview');
                var url = $input.val();
                
                if (!url) {
                    $preview.html('');
                    return;
                }

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'generate_qrcode',
                        url: url,
                        field: <?php echo json_encode($field); ?>,
                        nonce: '<?php echo esc_js( $nonce ); ?>'
                    },
                    success: function(response) {
                        $preview.html(response);
                    }
                });
            }

            acf.add_action('ready_field/type=qrcode', function($el){
                var $input = $el.find('input');
                $input.on('input', function(){
                    refresh_qrcode_field($el);
                });
            });

            acf.add_action('append_field/type=qrcode', function($el){
                var $input = $el.find('input');
                $input.on('input', function(){
                    refresh_qrcode_field($el);
                });
            });

        })(jQuery);
        </script>
        <?php
    }

    /**
     * Generate QR Code Image HTML.
     *
     * @param string $url The URL to encode.
     * @param array $field The field settings.
     * @return string The image HTML.
     */
    private function generate_qrcode_image( $url, $field ) {
        // Define QR code parameters
        $size = isset($field['size']) ? intval($field['size']) : 200;
        $error_correction = isset($field['error_correction']) ? $field['error_correction'] : 'L';
        $margin = isset($field['margin']) ? intval($field['margin']) : 4;

        // Generate QR code and save to a temp file or use data URI
        try {
            if (!class_exists('QRcode')) {
                throw new Exception('QRcode class not found. Make sure phpqrcode library is properly included.');
            }

            // Generate QR code to a temporary file
            $temp_file = tempnam(sys_get_temp_dir(), 'qrcode');
            QRcode::png($url, $temp_file, $error_correction, $size / 25, $margin);

            // Read the file contents
            $image_data = file_get_contents($temp_file);
            
            if (empty($image_data)) {
                throw new Exception('Failed to generate QR code image.');
            }
            
            // Encode the image data
            $base64_image = base64_encode($image_data);
            $src = 'data:image/png;base64,' . $base64_image;

            // Remove the temporary file
            unlink($temp_file);

            return '<img src="' . esc_attr($src) . '" alt="QR Code" width="' . esc_attr($size) . '" height="' . esc_attr($size) . '" />';
        } catch (Exception $e) {
            error_log('QR Code Generation Error: ' . $e->getMessage());
            return '<p>Error generating QR code: ' . esc_html($e->getMessage()) . '</p>';
        }
    }

    /**
     * Save the field value.
     *
     * @param mixed $value The value.
     * @param int $post_id The post ID.
     * @param array $field The field.
     * @return mixed
     */
    function update_value( $value, $post_id, $field ) {
        // Optionally, sanitize or validate the URL
        return esc_url_raw($value);
    }

    /**
     * Load the field value.
     *
     * @param mixed $value The value.
     * @param int $post_id The post ID.
     * @param array $field The field.
     * @return mixed
     */
    function format_value( $value, $post_id, $field ) {
        if( empty($value) ) return '';

        // Generate the QR code image
        return $this->generate_qrcode_image($value, $field);
    }

    /**
     * AJAX handler for generating QR code.
     */
    function ajax_generate_qrcode() {
        if (!isset($_POST['url']) || !isset($_POST['field'])) {
            wp_send_json_error('Invalid request');
        }

        if ( ! check_ajax_referer( 'acf_qrcode_generate', 'nonce', false ) ) {
            wp_send_json_error('Invalid nonce');
        }

        $url = sanitize_text_field($_POST['url']);
        $field = $_POST['field'];

        echo $this->generate_qrcode_image($url, $field);
        wp_die();
    }

}

?>