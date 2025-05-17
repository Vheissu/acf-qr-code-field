<?php
use PHPUnit\Framework\TestCase;

class GenerateQRCodeTest extends TestCase
{
    public function test_generate_qrcode_image_returns_base64_img()
    {
        $field = new acf_field_qrcode();
        $method = new ReflectionMethod(acf_field_qrcode::class, 'generate_qrcode_image');
        $method->setAccessible(true);

        $html = $method->invoke($field, 'https://example.com', [
            'size' => 200,
            'error_correction' => 'L',
            'margin' => 4,
        ]);

        $this->assertStringContainsString('src="data:image/png;base64,', $html);
    }
}
