(function ($) {
    'use strict';

    var FIELD_TYPE = 'qrcode';
    var settings = window.acfQrCodeField || {};
    var i18n = settings.i18n || {};

    function getConfig($field) {
        return {
            size: parseInt($field.data('size'), 10) || 200,
            error_correction: $field.data('error-correction') || 'L',
            margin: parseInt($field.data('margin'), 10) || 4,
            nonce: $field.data('nonce') || '',
            ajaxUrl: $field.data('ajax-url') || (typeof ajaxurl !== 'undefined' ? ajaxurl : '')
        };
    }

    function refreshQrcodeField($field) {
        var $input = $field.find('.acf-qrcode-input');
        var $preview = $field.find('.acf-qrcode-preview');
        var url = ($input.val() || '').trim();
        var config = getConfig($field);

        if (!url) {
            $preview.html('');
            return;
        }

        if (!/^https?:\/\//i.test(url)) {
            $preview.html('<p style="color:#d63638;">' + (i18n.invalidUrl || 'Please enter a valid URL starting with http:// or https://') + '</p>');
            return;
        }

        $preview.html('<p>' + (i18n.generating || 'Generating QR code...') + '</p>');

        $.ajax({
            url: config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'generate_qrcode',
                url: url,
                size: config.size,
                error_correction: config.error_correction,
                margin: config.margin,
                nonce: config.nonce
            },
            success: function (response) {
                if (response && response.success) {
                    $preview.html(response.data);
                } else {
                    $preview.html('<p style="color:#d63638;">' + (response && response.data ? response.data : (i18n.error || 'Error generating QR code')) + '</p>');
                }
            },
            error: function () {
                $preview.html('<p style="color:#d63638;">' + (i18n.failed || 'Failed to generate QR code') + '</p>');
            }
        });
    }

    function setupField($field) {
        var $input = $field.find('.acf-qrcode-input');

        $input.on('input', function () {
            var timer = $field.data('qrcodeTimer');
            if (timer) {
                clearTimeout(timer);
            }

            timer = setTimeout(function () {
                refreshQrcodeField($field);
            }, 500);

            $field.data('qrcodeTimer', timer);
        });
    }

    if (typeof acf !== 'undefined') {
        acf.add_action('ready_field/type=' + FIELD_TYPE, setupField);
        acf.add_action('append_field/type=' + FIELD_TYPE, setupField);
    }
})(jQuery);
