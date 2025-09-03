<table class="form-table">
    <tr>
        <th scope="row">
            <label for="gunbroker_enabled">Enable GunBroker Sync</label>
        </th>
        <td>
            <input type="checkbox" id="gunbroker_enabled" name="gunbroker_enabled"
                   value="yes" <?php checked($enabled, 'yes'); ?> />
            <label for="gunbroker_enabled">Sync this product to GunBroker</label>
        </td>
    </tr>

    <tr>
        <th scope="row">
            <label for="gunbroker_custom_title">Custom GunBroker Title</label>
        </th>
        <td>
            <input type="text" id="gunbroker_custom_title" name="gunbroker_custom_title"
                   value="<?php echo esc_attr($custom_title); ?>" class="regular-text" />
            <p class="description">Leave blank to use product title</p>
        </td>
    </tr>

    <tr>
        <th scope="row">
            <label for="gunbroker_category">GunBroker Category</label>
        </th>
        <td>
            <select id="gunbroker_category" name="gunbroker_category">
                <option value="3022" <?php selected($category, '3022'); ?>>Firearms</option>
                <option value="3023" <?php selected($category, '3023'); ?>>Handguns</option>
                <option value="3024" <?php selected($category, '3024'); ?>>Rifles</option>
                <option value="3025" <?php selected($category, '3025'); ?>>Shotguns</option>
                <option value="3026" <?php selected($category, '3026'); ?>>Accessories</option>
            </select>
            <p class="description">Select the appropriate GunBroker category</p>
        </td>
    </tr>

    <?php if ($listing_id && $listing_status): ?>
        <tr>
            <th scope="row">GunBroker Status</th>
            <td>
                <strong>Status:</strong> <?php echo ucfirst($listing_status); ?>
                <br>
                <strong>Listing ID:</strong> #<?php echo esc_html($listing_id); ?>
                <br>
                <a href="https://www.gunbroker.com/item/<?php echo esc_attr($listing_id); ?>" target="_blank">
                    View on GunBroker
                </a>
                <br><br>
                <button type="button" class="button sync-product" data-product-id="<?php echo $post->ID; ?>">
                    Sync Now
                </button>
                <div class="sync-result" style="margin-top: 10px;"></div>
            </td>
        </tr>
    <?php endif; ?>
</table>

<script>
    jQuery(document).ready(function($) {
        $('.sync-product').click(function() {
            var button = $(this);
            var productId = button.data('product-id');
            var result = button.siblings('.sync-result');

            button.prop('disabled', true).text('Syncing...');
            result.html('');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'gunbroker_sync_product',
                    product_id: productId,
                    nonce: '<?php echo wp_create_nonce("gunbroker_ajax_nonce"); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        result.html('<div style="color: green;">' + response.data + '</div>');
                    } else {
                        result.html('<div style="color: red;">' + response.data + '</div>');
                    }
                },
                error: function() {
                    result.html('<div style="color: red;">Sync failed</div>');
                },
                complete: function() {
                    button.prop('disabled', false).text('Sync Now');
                }
            });
        });
    });
</script>