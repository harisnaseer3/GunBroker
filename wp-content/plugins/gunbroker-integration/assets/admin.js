jQuery(document).ready(function($) {
    // Test connection functionality
    $('#test-connection').on('click', function() {
        var button = $(this);
        var resultDiv = $('#connection-result');
        
        button.prop('disabled', true).text('Testing...');
        resultDiv.html('');
        
        $.post(ajaxurl, {
            action: 'gunbroker_test_connection',
            nonce: gunbroker_ajax.nonce
        }, function(response) {
            if (response.success) {
                resultDiv.html('<div class="notice notice-success inline"><p>' + response.data + '</p></div>');
                $('#api-status').html('<span class="dashicons dashicons-yes-alt" style="color: green;"></span> API Connection: <strong>Connected</strong>');
            } else {
                resultDiv.html('<div class="notice notice-error inline"><p>Error: ' + response.data + '</p></div>');
                $('#api-status').html('<span class="dashicons dashicons-dismiss" style="color: red;"></span> API Connection: <strong>Failed</strong>');
            }
        }).fail(function() {
            resultDiv.html('<div class="notice notice-error inline"><p>Connection test failed</p></div>');
        }).always(function() {
            button.prop('disabled', false).text('Test Connection');
        });
    });

    // Product sync functionality
    $('.sync-product').on('click', function() {
        var button = $(this);
        var productId = button.data('product-id');
        
        button.prop('disabled', true).text('Syncing...');
        
        $.post(ajaxurl, {
            action: 'gunbroker_sync_product',
            product_id: productId,
            nonce: gunbroker_ajax.nonce
        }, function(response) {
            if (response.success) {
                button.text('Synced!').css('background', '#46b450');
                setTimeout(function() {
                    button.prop('disabled', false).text('Sync Now').css('background', '');
                    location.reload(); // Refresh to show updated status
                }, 2000);
            } else {
                alert('Sync failed: ' + response.data);
                button.prop('disabled', false).text('Sync Now');
            }
        }).fail(function() {
            alert('Sync request failed');
            button.prop('disabled', false).text('Sync Now');
        });
    });
});