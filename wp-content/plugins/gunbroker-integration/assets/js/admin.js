jQuery(document).ready(function($) {

    // Handle GunBroker sync toggle
    $('#gunbroker_enabled').change(function() {
        if ($(this).is(':checked')) {
            $('.gunbroker-options').show();
        } else {
            $('.gunbroker-options').hide();
        }
    }).trigger('change');

    // Auto-save settings
    $('#gunbroker-settings input, #gunbroker-settings select').change(function() {
        // You can add auto-save functionality here if needed
    });

    // Product sync handler (works for both detailed and inline sync buttons)
    $(document).on('click', '.sync-product, .sync-product-inline', function(e) {
        e.preventDefault();

        var $button = $(this);
        var productId = $button.data('product-id');
        var $result = $button.hasClass('sync-product-inline') ?
            $('<div class="sync-inline-result"></div>').insertAfter($button) :
            $button.siblings('.sync-result');

        var originalText = $button.text();
        $button.prop('disabled', true).text('Syncing...');
        $result.removeClass('success error').html('');

        $.ajax({
            url: gunbroker_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'gunbroker_sync_product',
                product_id: productId,
                nonce: gunbroker_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $result.addClass('success').html('✓ Synced');
                    if ($button.hasClass('sync-product-inline')) {
                        setTimeout(function() {
                            $result.fadeOut();
                        }, 2000);
                    }
                } else {
                    $result.addClass('error').html('✗ ' + response.data);
                }
            },
            error: function() {
                $result.addClass('error').html('✗ Failed');
            },
            complete: function() {
                $button.prop('disabled', false).text(originalText);
            }
        });
    });

    // Connection test handler
    $(document).on('click', '#test-connection', function(e) {
        e.preventDefault();

        var $button = $(this);
        var $result = $('#connection-result');

        $button.prop('disabled', true).text('Testing...');
        $result.html('');

        $.ajax({
            url: gunbroker_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'gunbroker_test_connection',
                nonce: gunbroker_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $result.html('<div class="notice notice-success"><p>' + response.data + '</p></div>');
                } else {
                    $result.html('<div class="notice notice-error"><p>' + response.data + '</p></div>');
                }
            },
            error: function() {
                $result.html('<div class="notice notice-error"><p>Connection test failed</p></div>');
            },
            complete: function() {
                $button.prop('disabled', false).text('Test Connection');
            }
        });
    });
});