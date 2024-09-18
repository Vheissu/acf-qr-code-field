# Advanced Custom Fields: QR Code Field

## Description

This WordPress plugin adds a custom QR Code field type to Advanced Custom Fields (ACF). It allows users to easily generate and display QR codes within their WordPress content.

## Features

- Seamless integration with Advanced Custom Fields
- Customisable QR code size and error correction level
- Easy to use in templates and theme files

## Installation

1. Upload the `acf-qr-code-field` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Create a new field using the ACF field creator and select 'QR Code' as the field type

## Usage

After adding a QR Code field to your ACF field group, you can use it in your templates like this:

```php
<div class="qr-code-container">
    <?php display_acf_qr_code('your_qr_code_field_name'); ?>
</div>
```

You can also easily use it with custom post IDs or add additional attributes:

```php
// With a specific post ID
display_acf_qr_code('your_qr_code_field_name', 123);

// With custom attributes
display_acf_qr_code('your_qr_code_field_name', null, [
    'class' => 'my-custom-class',
    'data-custom' => 'some-value'
]);
```