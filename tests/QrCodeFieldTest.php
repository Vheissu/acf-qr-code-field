<?php

declare(strict_types=1);

beforeEach(function (): void {
    $this->field = new acf_field_qrcode();
});

describe('acf_field_qrcode', function (): void {

    describe('constructor', function (): void {

        it('sets correct field name', function (): void {
            expect($this->field->name)->toBe('qrcode');
        });

        it('sets correct label', function (): void {
            expect($this->field->label)->toBe('QR Code');
        });

        it('sets correct category', function (): void {
            expect($this->field->category)->toBe('formatting');
        });

        it('has correct default values', function (): void {
            expect($this->field->defaults)->toHaveKeys(['size', 'error_correction', 'margin']);
            expect($this->field->defaults['size'])->toBe(200);
            expect($this->field->defaults['error_correction'])->toBe('L');
            expect($this->field->defaults['margin'])->toBe(4);
        });

    });

    describe('generate_qrcode_image', function (): void {

        it('returns base64 encoded image for valid URL', function (): void {
            $html = $this->field->generate_qrcode_image('https://example.com', [
                'size' => 200,
                'error_correction' => 'L',
                'margin' => 4,
            ]);

            expect($html)->toContain('src="data:image/png;base64,');
            expect($html)->toContain('<img');
            expect($html)->toContain('alt="QR Code"');
        });

        it('returns error for empty URL', function (): void {
            $html = $this->field->generate_qrcode_image('', [
                'size' => 200,
                'error_correction' => 'L',
                'margin' => 4,
            ]);

            expect($html)->toContain('Error generating QR code');
        });

        it('returns error for invalid URL', function (): void {
            $html = $this->field->generate_qrcode_image('not-a-url', [
                'size' => 200,
                'error_correction' => 'L',
                'margin' => 4,
            ]);

            expect($html)->toContain('Error generating QR code');
        });

        it('returns error for javascript: URL', function (): void {
            $html = $this->field->generate_qrcode_image('javascript:alert(1)', [
                'size' => 200,
                'error_correction' => 'L',
                'margin' => 4,
            ]);

            expect($html)->toContain('Error generating QR code');
        });

        it('uses default size when not provided', function (): void {
            $html = $this->field->generate_qrcode_image('https://example.com', []);

            expect($html)->toContain('width="200"');
            expect($html)->toContain('height="200"');
        });

        it('clamps size to minimum', function (): void {
            $html = $this->field->generate_qrcode_image('https://example.com', [
                'size' => 10, // Below minimum
            ]);

            expect($html)->toContain('width="50"'); // MIN_SIZE
        });

        it('clamps size to maximum', function (): void {
            $html = $this->field->generate_qrcode_image('https://example.com', [
                'size' => 5000, // Above maximum
            ]);

            expect($html)->toContain('width="1000"'); // MAX_SIZE
        });

        it('sanitizes invalid error correction level', function (): void {
            $html = $this->field->generate_qrcode_image('https://example.com', [
                'size' => 200,
                'error_correction' => 'INVALID',
                'margin' => 4,
            ]);

            // Should still generate (defaults to L)
            expect($html)->toContain('src="data:image/png;base64,');
        });

        it('clamps margin to valid range', function (): void {
            $html = $this->field->generate_qrcode_image('https://example.com', [
                'size' => 200,
                'error_correction' => 'L',
                'margin' => 50, // Above max
            ]);

            // Should still generate with clamped margin
            expect($html)->toContain('src="data:image/png;base64,');
        });

        it('works with all error correction levels', function (): void {
            foreach (['L', 'M', 'Q', 'H'] as $level) {
                $html = $this->field->generate_qrcode_image('https://example.com', [
                    'size' => 200,
                    'error_correction' => $level,
                    'margin' => 4,
                ]);

                expect($html)->toContain('src="data:image/png;base64,');
            }
        });

    });

    describe('update_value', function (): void {

        it('allows valid http URL', function (): void {
            $result = $this->field->update_value('http://example.com', 1, []);
            expect($result)->toBe('http://example.com');
        });

        it('allows valid https URL', function (): void {
            $result = $this->field->update_value('https://example.com', 1, []);
            expect($result)->toBe('https://example.com');
        });

        it('rejects javascript: URLs', function (): void {
            $result = $this->field->update_value('javascript:alert(1)', 1, []);
            expect($result)->toBe('');
        });

        it('rejects data: URLs', function (): void {
            $result = $this->field->update_value('data:text/html,<script>alert(1)</script>', 1, []);
            expect($result)->toBe('');
        });

        it('handles empty input', function (): void {
            $result = $this->field->update_value('', 1, []);
            expect($result)->toBe('');
        });

        it('handles null input', function (): void {
            $result = $this->field->update_value(null, 1, []);
            expect($result)->toBe('');
        });

    });

    describe('format_value', function (): void {

        it('returns empty string for empty value', function (): void {
            $result = $this->field->format_value('', 1, []);
            expect($result)->toBe('');
        });

        it('returns empty string for null value', function (): void {
            $result = $this->field->format_value(null, 1, []);
            expect($result)->toBe('');
        });

        it('generates QR code image for valid URL', function (): void {
            $result = $this->field->format_value('https://example.com', 1, [
                'size' => 200,
                'error_correction' => 'L',
                'margin' => 4,
            ]);

            expect($result)->toContain('src="data:image/png;base64,');
            expect($result)->toContain('<img');
        });

    });

    describe('ajax_generate_qrcode', function (): void {

        it('generates QR code for valid request', function (): void {
            $_POST['url'] = 'https://example.com';
            $_POST['size'] = 200;
            $_POST['error_correction'] = 'L';
            $_POST['margin'] = 4;
            $_POST['nonce'] = 'test_nonce';

            try {
                $this->field->ajax_generate_qrcode();
            } catch (Exception $e) {
                $message = $e->getMessage();
                expect($message)->toContain('wp_send_json_success');
                // JSON encoding escapes forward slashes
                expect($message)->toContain('data:image\\/png;base64');
            }
        });

        it('returns error for missing URL', function (): void {
            unset($_POST['url']);
            $_POST['nonce'] = 'test_nonce';

            try {
                $this->field->ajax_generate_qrcode();
            } catch (Exception $e) {
                expect($e->getMessage())->toContain('wp_send_json_error');
                expect($e->getMessage())->toContain('URL is required');
            }
        });

        it('returns error for invalid URL', function (): void {
            $_POST['url'] = 'not-a-valid-url';
            $_POST['nonce'] = 'test_nonce';

            try {
                $this->field->ajax_generate_qrcode();
            } catch (Exception $e) {
                expect($e->getMessage())->toContain('wp_send_json_error');
                expect($e->getMessage())->toContain('Invalid URL');
            }
        });

        it('sanitizes size parameter', function (): void {
            $_POST['url'] = 'https://example.com';
            $_POST['size'] = '200abc'; // Invalid - should be cast to int
            $_POST['error_correction'] = 'L';
            $_POST['margin'] = 4;
            $_POST['nonce'] = 'test_nonce';

            try {
                $this->field->ajax_generate_qrcode();
            } catch (Exception $e) {
                // Should still succeed with sanitized size
                expect($e->getMessage())->toContain('wp_send_json_success');
            }
        });

    });

});

afterEach(function (): void {
    unset($_POST['url'], $_POST['size'], $_POST['error_correction'], $_POST['margin'], $_POST['nonce']);
});
