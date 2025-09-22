<table class="form-table">
    <tr>
        <th scope="row">
            <label for="gunbroker_custom_title">Custom GunBroker Title</label>
        </th>
        <td>
            <?php if (empty($custom_title)) { $custom_title = 'Springfield Armory Echelon 9mm Optics Ready U-Notch Night Sights EC9459B-U'; } ?>
            <input type="text" id="gunbroker_custom_title" name="gunbroker_custom_title"
                   value="<?php echo esc_attr($custom_title); ?>" class="regular-text" />
            <p class="description">Leave blank to use product title</p>
        </td>
    </tr>

    <?php 
    $gb_condition = get_post_meta($post->ID, '_gunbroker_condition', true);
    $gb_use_default_taxes = get_post_meta($post->ID, '_gunbroker_use_default_taxes', true); if ($gb_use_default_taxes === '') { $gb_use_default_taxes = '1'; }
    ?>
    <tr>
        <th scope="row" style="vertical-align: top; padding-top: 15px;">
            <label>Item Details</label>
        </th>
        <td style="padding-top: 15px;">
            <div style="background: #f9f9f9; padding: 15px; border: 1px solid #ddd; border-radius: 4px;">
                <h4 style="margin: 0 0 12px 0; color: #333; font-size: 13px;">Product Condition & Policies</h4>
                <div style="display:grid; grid-template-columns: repeat(3, minmax(160px, 1fr)); gap:14px; align-items:center;">
                    <label style="margin:0;">Condition:
                        <?php $gb_condition = get_post_meta($post->ID, '_gunbroker_condition', true); if ($gb_condition === '') { $gb_condition = '1'; } ?>
                        <select name="gunbroker_condition" style="margin-left: 5px; max-width:160px;">
                            <option value="1" <?php selected($gb_condition, '1'); ?>>Factory New</option>
                            <option value="2" <?php selected($gb_condition, '2'); ?>>New Old Stock</option>
                            <option value="3" <?php selected($gb_condition, '3'); ?>>Used</option>
                        </select>
                    </label>
                    <label style="margin:0;">Use default taxes:
                        <?php $gb_use_default_taxes = get_post_meta($post->ID, '_gunbroker_use_default_taxes', true); if ($gb_use_default_taxes === '') { $gb_use_default_taxes = '1'; } ?>
                        <select name="gunbroker_use_default_taxes" style="margin-left:5px; max-width:120px;">
                            <option value="1" <?php selected($gb_use_default_taxes, '1'); ?>>Yes</option>
                            <option value="0" <?php selected($gb_use_default_taxes, '0'); ?>>No</option>
                        </select>
                    </label>
                    <label style="margin:0;">FFL Required?
                        <?php $ffl_required = get_post_meta($post->ID, '_gunbroker_ffl_required', true); if ($ffl_required === '') { $ffl_required = '1'; } ?>
                        <select name="gunbroker_ffl_required" style="margin-left:5px; max-width:120px;">
                            <option value="1" <?php selected($ffl_required, '1'); ?>>Yes</option>
                            <option value="0" <?php selected($ffl_required, '0'); ?>>No</option>
                        </select>
                    </label>
                </div>
            </div>
        </td>
    </tr>

    <tr>
        <th scope="row">
            <label for="gunbroker_category">Category ID</label>
        </th>
        <td>
            <div>
                <div id="category-selection" style="max-width: 360px;">
                    <select id="gunbroker_category" name="gunbroker_category">
                        <option value="">Loading categories...</option>
                    </select>
                    <div id="category-loading" style="display: none; color: #666; font-size: 12px; margin-top: 5px;">
                        <span class="spinner is-active" style="float: none; margin: 0 5px 0 0;"></span>Loading subcategories...
                    </div>
                </div>
                <p class="description">Select the appropriate category; IDs are shown in the list.</p>
            </div>
        </td>
    </tr>

    <tr>
        <th scope="row" style="vertical-align: top; padding-top: 15px;">
            <label>Identifiers</label>
        </th>
        <td style="padding-top: 15px;">
            <div style="display:grid; grid-template-columns: repeat(2, minmax(220px, 1fr)); gap: 14px; align-items:start;">
                <div>
                    <?php $sku_value = get_post_meta($post->ID, '_sku', true); if ($sku_value === '') { $sku_value = '706397970222'; } ?>
                    <label style="display:block; margin-bottom:6px;">SKU</label>
                    <input type="text" name="_sku" value="<?php echo esc_attr($sku_value); ?>" class="regular-text" />
                </div>
                <div>
                    <?php $serial_value = get_post_meta($post->ID, '_gunbroker_serial_number', true); ?>
                    <label style="display:block; margin-bottom:6px;">Serial Number</label>
                    <input type="text" name="gunbroker_serial_number" value="<?php echo esc_attr($serial_value); ?>" class="regular-text" />
                </div>
            </div>
        </td>
    </tr>

    <tr>
        <th scope="row" style="vertical-align: top; padding-top: 15px;">
            <label>Shipping</label>
        </th>
        <td style="padding-top: 15px;">
            <div style="display:grid; grid-template-columns: repeat(3, minmax(220px, 1fr)); gap: 14px; align-items:end;">
                <div>
                    <?php $gb_who_pays = get_post_meta($post->ID, '_gunbroker_who_pays_shipping', true); ?>
                    <label style="display:block; margin-bottom:6px;">Who pays for shipping?</label>
                    <select name="gunbroker_who_pays_shipping" class="regular-text">
                        <option value="" <?php selected($gb_who_pays, ''); ?>>Use shipping profile</option>
                        <option value="3" <?php selected($gb_who_pays, '3'); ?>>Seller pays for shipping</option>
                        <option value="2" <?php selected($gb_who_pays, '2'); ?>>Buyer pays actual shipping cost</option>
                        <option value="4" <?php selected($gb_who_pays, '4'); ?>>Buyer pays fixed amount</option>
                    </select>
                </div>
                <div>
                    <?php $gb_international = get_post_meta($post->ID, '_gunbroker_will_ship_international', true); if ($gb_international === '') { $gb_international = '0'; } ?>
                    <label style="display:block; margin-bottom:6px;">Will ship internationally?</label>
                    <select name="gunbroker_will_ship_international" class="regular-text">
                        <option value="0" <?php selected($gb_international, '0'); ?>>No</option>
                        <option value="1" <?php selected($gb_international, '1'); ?>>Yes</option>
                    </select>
                </div>
                <div>
                    <?php $gb_ship_profile = get_post_meta($post->ID, '_gunbroker_shipping_profile_id', true); if ($gb_ship_profile === '') { $gb_ship_profile = 'everything'; } ?>
                    <label style="display:block; margin-bottom:6px;">Shipping Profile ID</label>
                    <select name="gunbroker_shipping_profile_id" class="regular-text">
                        <option value="accessories" <?php selected($gb_ship_profile, 'accessories'); ?>>Accessories $15 +7.5</option>
                        <option value="free_ground" <?php selected($gb_ship_profile, 'free_ground'); ?>>FREE SHIPPING - Ground</option>
                        <option value="everything" <?php selected($gb_ship_profile, 'everything'); ?>>everything</option>
                    </select>
                </div>
            </div>
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

    <tr>
        <th scope="row" style="vertical-align: top; padding-top: 15px;">
            <label>Inspection Period</label>
        </th>
        <td style="padding-top: 15px;">
            <?php $gb_return_policy = get_post_meta($post->ID, '_gunbroker_return_policy', true); if ($gb_return_policy === '') { $gb_return_policy = '14'; } ?>
            <select name="gunbroker_return_policy" class="regular-text" style="max-width:420px;">
                <option value="1"  <?php selected($gb_return_policy, '1');  ?>>AS IS - No refund or exchange</option>
                <option value="2"  <?php selected($gb_return_policy, '2');  ?>>No refund but item can be returned for exchange or store credit within fourteen days</option>
                <option value="3"  <?php selected($gb_return_policy, '3');  ?>>No refund but item can be returned for exchange or store credit within thirty days</option>
                <option value="4"  <?php selected($gb_return_policy, '4');  ?>>Three Days from the date the item is received</option>
                <option value="5"  <?php selected($gb_return_policy, '5');  ?>>Three Days from the date the item is received, including the cost of shipping</option>
                <option value="6"  <?php selected($gb_return_policy, '6');  ?>>Five Days from the date the item is received</option>
                <option value="7"  <?php selected($gb_return_policy, '7');  ?>>Five Days from the date the item is received, including the cost of shipping</option>
                <option value="8"  <?php selected($gb_return_policy, '8');  ?>>Seven Days from the date the item is received</option>
                <option value="9"  <?php selected($gb_return_policy, '9');  ?>>Seven Days from the date the item is received, including the cost of shipping</option>
                <option value="10" <?php selected($gb_return_policy, '10'); ?>>Fourteen Days from the date the item is received</option>
                <option value="11" <?php selected($gb_return_policy, '11'); ?>>Fourteen Days from the date the item is received, including the cost of shipping</option>
                <option value="12" <?php selected($gb_return_policy, '12'); ?>>30 day money back guarantee</option>
                <option value="13" <?php selected($gb_return_policy, '13'); ?>>30 day money back guarantee including the cost of shipping</option>
                <option value="14" <?php selected($gb_return_policy, '14'); ?>>Factory Warranty</option>
            </select>
        </td>
    </tr>

    <tr>
        <th scope="row" style="vertical-align: top; padding-top: 15px;">
            <label>Scheduled Start Time <span style="font-weight: normal; color:#666;">(premium feature)</span></label>
        </th>
        <td style="padding-top: 15px;">
            <?php 
            $schedule_date = get_post_meta($post->ID, '_gunbroker_schedule_date', true);
            $schedule_time = get_post_meta($post->ID, '_gunbroker_schedule_time', true);
            // Build next 14 days list
            $days = array();
            $now = current_time('timestamp');
            for ($i = 0; $i < 14; $i++) {
                $ts = strtotime('+' . $i . ' day', $now);
                $days[] = array(
                    'value' => date('Y-m-d', $ts),
                    'label' => date('l m/d/y', $ts)
                );
            }
            // Build time options every 30 minutes
            $times = array();
            for ($h = 0; $h < 24; $h++) {
                foreach (array('00','30') as $m) {
                    $times[] = sprintf('%02d:%s', $h, $m);
                }
            }
            ?>
            <div style="display:flex; gap:10px; align-items:center;">
                <select name="gunbroker_schedule_date" class="regular-text" style="max-width:260px;">
                    <option value="">Day</option>
                    <?php foreach ($days as $d): ?>
                        <option value="<?php echo esc_attr($d['value']); ?>" <?php selected($schedule_date, $d['value']); ?>><?php echo esc_html($d['label']); ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="gunbroker_schedule_time" class="regular-text" style="max-width:180px;">
                    <option value="">Time</option>
                    <?php foreach ($times as $t): ?>
                        <option value="<?php echo esc_attr($t); ?>" <?php selected($schedule_time, $t); ?>><?php echo esc_html($t); ?></option>
                    <?php endforeach; ?>
                </select>
                <span style="color:#666;">$0.10 charge if used</span>
            </div>
        </td>
    </tr>

    <tr>
        <th scope="row" style="vertical-align: top; padding-top: 15px;">
            <label>Listing Profile</label>
        </th>
        <td style="padding-top: 15px;">
            <?php $gb_listing_type_profile = get_post_meta($post->ID, '_gunbroker_listing_type', true); ?>
            <select id="gb_listing_type" name="gunbroker_listing_type" class="regular-text" style="max-width: 240px;">
                <option value="" <?php selected($gb_listing_type_profile, ''); ?>>Default</option>
                <option value="FixedPrice" <?php selected($gb_listing_type_profile, 'FixedPrice'); ?>>Fixed Price</option>
                <option value="StartingBid" <?php selected($gb_listing_type_profile, 'StartingBid'); ?>>Auction</option>
            </select>
        </td>
    </tr>

    <tr>
        <th scope="row" style="vertical-align: top; padding-top: 15px;">
            <label>Listing Details</label>
        </th>
        <td style="padding-top: 15px;">
            <div style="display:flex; gap:20px; flex-wrap:wrap; align-items:flex-end;">
                <div>
                    <label style="display:block; margin-bottom:6px;">Listing Duration</label>
                    <?php $gb_duration = get_post_meta($post->ID, '_gunbroker_listing_duration', true); if ($gb_duration === '') { $gb_duration = '90'; } ?>
                    <select id="gb_listing_duration" name="gunbroker_listing_duration" class="regular-text" style="max-width:200px;">
                        <option value="90" <?php selected($gb_duration, '90'); ?>>90 days</option>
                        <option value="60" <?php selected($gb_duration, '60'); ?>>60 days</option>
                        <option value="30" <?php selected($gb_duration, '30'); ?>>30 days</option>
                    </select>
                </div>
                <div>
                    <label style="display:block; margin-bottom:6px;">Listing Type</label>
                    <?php $gb_inner_type = get_post_meta($post->ID, '_gunbroker_inner_listing_type', true); if ($gb_inner_type === '') { $gb_inner_type = 'FixedPrice'; } ?>
                    <select id="gb_inner_listing_type" name="gunbroker_inner_listing_type" class="regular-text" style="max-width:200px;">
                        <option value="StartingBid" <?php selected($gb_inner_type, 'StartingBid'); ?>>Starting Bid</option>
                        <option value="FixedPrice" <?php selected($gb_inner_type, 'FixedPrice'); ?>>Fixed Price</option>
                    </select>
                </div>
                <div>
                    <label style="display:block; margin-bottom:6px;">Auto Relist</label>
                    <?php $gb_auto = get_post_meta($post->ID, '_gunbroker_auto_relist', true); if ($gb_auto === '') { $gb_auto = '4'; } ?>
                    <select id="gb_auto_relist" name="gunbroker_auto_relist" class="regular-text" style="max-width:220px;">
                        <option value="4" <?php selected($gb_auto, '4'); ?>>Relist Fixed Price</option>
                        <option value="1" <?php selected($gb_auto, '1'); ?>>Do Not Relist</option>
                    </select>
                </div>
            </div>

            <div id="gb_fixed_fields" style="margin-top:12px; display:flex; gap:20px; flex-wrap:wrap;">
                <?php $gb_fixed = get_post_meta($post->ID, '_gunbroker_fixed_price', true); if ($gb_fixed === '') { $gb_fixed = '549.0'; } ?>
                <?php $gb_qty = get_post_meta($post->ID, '_gunbroker_quantity', true); if ($gb_qty === '') { $gb_qty = '4'; } ?>
                <div style="margin-right:20px;">
                    <label style="display:block; margin-bottom:6px;">Fixed Price</label>
                    <input type="number" step="0.01" min="0" name="gunbroker_fixed_price" value="<?php echo esc_attr($gb_fixed); ?>" class="regular-text" style="max-width:200px;" />
                </div>
                <div>
                    <label style="display:block; margin-bottom:6px;">Quantity</label>
                    <input type="number" min="1" name="gunbroker_quantity" value="<?php echo esc_attr($gb_qty); ?>" class="regular-text" style="max-width:120px;" />
                </div>
            </div>

            <div id="gb_auction_fields" style="margin-top:12px; display:none; gap:20px; flex-wrap:wrap;">
                <?php $gb_start = get_post_meta($post->ID, '_gunbroker_starting_bid', true); if ($gb_start === '') { $gb_start = '549.0'; } ?>
                <?php $gb_buy = get_post_meta($post->ID, '_gunbroker_buy_now_price', true); ?>
                <?php $gb_reserve = get_post_meta($post->ID, '_gunbroker_reserve_price', true); ?>
                <div style="margin-right:20px;">
                    <label style="display:block; margin-bottom:6px;">Starting Bid</label>
                    <input type="number" step="0.01" min="0" name="gunbroker_starting_bid" value="<?php echo esc_attr($gb_start); ?>" class="regular-text" style="max-width:200px;" />
                </div>
                <div style="margin-right:20px;">
                    <label style="display:block; margin-bottom:6px;">Buy Now Price</label>
                    <input type="number" step="0.01" min="0" name="gunbroker_buy_now_price" value="<?php echo esc_attr($gb_buy); ?>" class="regular-text" style="max-width:200px;" />
                </div>
                <div>
                    <label style="display:block; margin-bottom:6px;">Reserve Price</label>
                    <input type="number" step="0.01" min="0" name="gunbroker_reserve_price" value="<?php echo esc_attr($gb_reserve); ?>" class="regular-text" style="max-width:200px;" />
                </div>
            </div>
        </td>
    </tr>

    <script>
    jQuery(function($){
        function toggleListingFields(){
            var type = $('#gb_listing_type').val();
            if(type === 'StartingBid'){
                $('#gb_auction_fields').css('display','flex');
                $('#gb_fixed_fields').hide();
                var auctionOpts = '<option value="7">7 days</option>';
                if($('#gb_listing_duration option').length !== 1){
                    $('#gb_listing_duration').data('orig', $('#gb_listing_duration').html());
                    $('#gb_listing_duration').html(auctionOpts).val('7');
                } else {
                    $('#gb_listing_duration').val('7');
                }
                // AutoRelist: Auction -> Relist Until Sold (2) only
                $('#gb_auto_relist').html('<option value="2">Relist Until Sold</option>').val('2');
                $('#gb_inner_listing_type').val('StartingBid');
            } else { // Default or FixedPrice
                $('#gb_auction_fields').hide();
                $('#gb_fixed_fields').css('display','flex');
                var orig = $('#gb_listing_duration').data('orig');
                if(orig){ $('#gb_listing_duration').html(orig); }
                // AutoRelist: show Do Not Relist (1) and Relist Fixed Price (4), default 4
                $('#gb_auto_relist').html('<option value="4">Relist Fixed Price</option><option value="1">Do Not Relist</option>').val('4');
                $('#gb_inner_listing_type').val('FixedPrice');
            }
        }
        toggleListingFields();
        $('#gb_listing_type').on('change', toggleListingFields);
    });
    </script>
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
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
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
        
        // Progressive category loading
        let categoryStack = [];
        let currentCategoryId = null;
        
        // Load top-level categories on page load
        loadTopLevelCategories();
        
        function loadTopLevelCategories() {
            console.log('Loading top level categories...');
            // Show loading spinner
            $('#gunbroker_category').html('<option value="">Loading categories...</option>');
            $('#category-loading').show().html('<span class="spinner is-active" style="float: none; margin: 0 5px 0 0;"></span>Loading categories...');
            
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'gunbroker_get_top_categories',
                    nonce: '<?php echo wp_create_nonce("gunbroker_ajax_nonce"); ?>'
                },
                success: function(response) {
                    console.log('Top categories response:', response);
                    $('#category-loading').hide(); // Hide loading spinner
                    
                    if (response.success) {
                        console.log('Categories data:', response.data);
                        populateCategorySelect(response.data, '');
                        // Set selected value if exists, otherwise default to Guns & Firearms
                        const selectedValue = '<?php echo esc_js($category); ?>';
                        if (selectedValue) {
                            $('#gunbroker_category').val(selectedValue);
                        } else {
                            // Default to Guns & Firearms category (851) for new products
                            $('#gunbroker_category').val('851');
                            // Auto-load subcategories for Guns & Firearms to find Rifles, then Semi Auto Rifles
                            loadSubcategories('851');
                        }
                    } else {
                        console.error('Error loading categories:', response.data);
                        $('#gunbroker_category').html('<option value="">Error loading categories: ' + (response.data || 'Unknown error') + '</option>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error loading categories:', status, error, xhr.responseText);
                    $('#category-loading').hide(); // Hide loading spinner
                    
                    let errorMessage = 'Error loading categories: ' + error;
                    if (xhr.status === 403) {
                        errorMessage = 'Authentication error (403). Please check your GunBroker credentials in Settings and test the connection.';
                    } else if (xhr.status === 401) {
                        errorMessage = 'Unauthorized (401). Please check your GunBroker credentials in Settings and test the connection.';
                    } else if (xhr.responseText) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.data) {
                                errorMessage = 'Error: ' + response.data;
                            }
                        } catch (e) {
                            // Use default error message
                        }
                    }
                    $('#gunbroker_category').html('<option value="">' + errorMessage + '</option>');
                }
            });
        }
        
        function loadSubcategories(parentId) {
            console.log('Loading subcategories for parent ID:', parentId);
            $('#category-loading').show();
            
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'gunbroker_get_subcategories',
                    parent_category_id: parentId,
                    nonce: '<?php echo wp_create_nonce("gunbroker_ajax_nonce"); ?>'
                },
                success: function(response) {
                    $('#category-loading').hide();
                    console.log('Subcategories response for parent', parentId, ':', response);
                    if (response.success && response.data.length > 0) {
                        // Add subcategories to the stack
                        categoryStack.push({
                            parentId: parentId,
                            categories: response.data
                        });
                        
                        // Update the select with current level
                        updateCategorySelect();
                        
                        // Handle multi-level category loading for Guns & Firearms → Rifles → Semi Auto Rifles
                        if (parentId === '851') {
                            console.log('Loading Guns & Firearms subcategories:', response.data);
                            // First level: Find "Rifles" in Guns & Firearms subcategories
                            const riflesCategory = response.data.find(cat => 
                                (cat.name && cat.name.toLowerCase().includes('rifle')) ||
                                (cat.categoryName && cat.categoryName.toLowerCase().includes('rifle'))
                            );
                            console.log('Found Rifles category:', riflesCategory);
                            if (riflesCategory) {
                                // Load subcategories of Rifles to find Semi Auto Rifles
                                loadSubcategories(riflesCategory.id || riflesCategory.categoryID);
                            }
                        } else if (response.data.length > 0 && response.data[0].name && response.data[0].name.toLowerCase().includes('rifle')) {
                            console.log('Loading Rifles subcategories:', response.data);
                            // Second level: Find "Semi Auto Rifles" in Rifles subcategories
                            const semiAutoRifles = response.data.find(cat => {
                                const name = (cat.name || cat.categoryName || '').toLowerCase();
                                const categoryPath = (cat.categoryPath || cat.CategoryPath || '').toLowerCase();
                                
                                // Check both name and category path for "semi auto rifle"
                                return (name.includes('semi') && name.includes('auto') && name.includes('rifle')) ||
                                       categoryPath.includes('semi auto rifle');
                            });
                            console.log('Found Semi Auto Rifles category:', semiAutoRifles);
                            if (semiAutoRifles) {
                                const categoryId = semiAutoRifles.id || semiAutoRifles.categoryID;
                                $('#gunbroker_category').val(categoryId);
                                console.log('Selected Semi Auto Rifles with ID:', categoryId);
                            } else {
                                console.log('Semi Auto Rifles not found in subcategories. Available categories:', response.data.map(cat => ({
                                    id: cat.id || cat.categoryID,
                                    name: cat.name || cat.categoryName,
                                    path: cat.categoryPath || cat.CategoryPath
                                })));
                                
                                // Fallback: Look for any category containing "semi" and "rifle"
                                const fallbackSemiRifle = response.data.find(cat => {
                                    const name = (cat.name || cat.categoryName || '').toLowerCase();
                                    return name.includes('semi') && name.includes('rifle');
                                });
                                
                                if (fallbackSemiRifle) {
                                    const categoryId = fallbackSemiRifle.id || fallbackSemiRifle.categoryID;
                                    $('#gunbroker_category').val(categoryId);
                                    console.log('Selected fallback semi-rifle category with ID:', categoryId, 'Name:', fallbackSemiRifle.name || fallbackSemiRifle.categoryName);
                                }
                            }
                        }
                    } else {
                        console.error('Error loading subcategories:', response.data);
                        $('#category-loading').hide();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error loading subcategories:', status, error, xhr.responseText);
                    $('#category-loading').hide();
                    let errorMessage = 'Error loading subcategories: ' + error;
                    if (xhr.status === 403) {
                        errorMessage = 'Authentication error (403). Please check your GunBroker credentials in Settings and test the connection.';
                    } else if (xhr.status === 401) {
                        errorMessage = 'Unauthorized (401). Please check your GunBroker credentials in Settings and test the connection.';
                    }
                    console.error(errorMessage);
                }
            });
        }
        
        function updateCategorySelect() {
            const $select = $('#gunbroker_category');
            const currentValue = $select.val();
            
            // Clear current options
            $select.empty();
            
            // Add back option
            $select.append('<option value="">Select a category...</option>');
            
            // Add all categories from stack
            categoryStack.forEach(function(level) {
                level.categories.forEach(function(category) {
                    const indent = '— '.repeat(categoryStack.indexOf(level));
                    const id = category.id || category.categoryID || category.CategoryID;
                    const name = category.name || category.categoryName || category.CategoryName;
                    $select.append('<option value="' + id + '">' + indent + name + '</option>');
                });
            });
            
            // Restore selection if it still exists
            if (currentValue && $select.find('option[value="' + currentValue + '"]').length) {
                $select.val(currentValue);
            }
        }
        
        function populateCategorySelect(categories, prefix = '') {
            console.log('populateCategorySelect called with:', categories);
            const $select = $('#gunbroker_category');
            $select.empty();
            $select.append('<option value="">Select a category...</option>');
            
            if (Array.isArray(categories)) {
                categories.forEach(function(category, index) {
                    console.log('Category ' + index + ':', category);
                    const categoryId = category.id || category.ID || category.CategoryID;
                    const categoryName = category.name || category.Name || category.CategoryName || 'Unknown';
                    console.log('Processed - ID:', categoryId, 'Name:', categoryName);
                    $select.append('<option value="' + categoryId + '">' + prefix + categoryName + '</option>');
                });
            } else {
                console.error('Categories is not an array:', categories);
            }
        }
        
        // Handle category selection change
        $('#gunbroker_category').on('change', function() {
            const selectedId = $(this).val();
            if (selectedId && selectedId !== currentCategoryId) {
                currentCategoryId = selectedId;
                loadSubcategories(selectedId);
            }
        });
    });
</script>