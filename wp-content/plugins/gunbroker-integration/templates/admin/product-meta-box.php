<table class="form-table">
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

    <?php
    $gb_returns = get_post_meta($post->ID, '_gunbroker_returns_accepted', true);
    $gb_international = get_post_meta($post->ID, '_gunbroker_will_ship_international', true);
    $gb_who_pays = get_post_meta($post->ID, '_gunbroker_who_pays_shipping', true);
    $gb_auto_relist = get_post_meta($post->ID, '_gunbroker_auto_relist', true);
    $gb_country = get_post_meta($post->ID, '_gunbroker_country', true);
    $gb_state = get_post_meta($post->ID, '_gunbroker_seller_state', true);
    $gb_city = get_post_meta($post->ID, '_gunbroker_seller_city', true);
    $gb_postal = get_post_meta($post->ID, '_gunbroker_seller_postal', true);
    $gb_phone = get_post_meta($post->ID, '_gunbroker_contact_phone', true);
    $gb_pm = (array) get_post_meta($post->ID, '_gunbroker_payment_methods', true);
    $gb_sm = (array) get_post_meta($post->ID, '_gunbroker_shipping_methods', true);
    ?>

    <tr>
        <th scope="row" style="vertical-align: top; padding-top: 15px;">
            <label>Listing Options</label>
        </th>
        <td style="padding-top: 15px;">
            <div style="background: #f9f9f9; padding: 15px; border: 1px solid #ddd; border-radius: 4px;">
                <h4 style="margin: 0 0 12px 0; color: #333; font-size: 13px;">Returns & Shipping</h4>
                <p style="margin: 0 0 10px 0;">
                    <label style="margin-right: 20px;"><input type="checkbox" name="gunbroker_returns_accepted" value="1" <?php checked($gb_returns, '1'); ?> /> Accept returns</label>
                    <label><input type="checkbox" name="gunbroker_will_ship_international" value="1" <?php checked($gb_international, '1'); ?> /> Will ship internationally</label>
                </p>
                <p style="margin: 0 0 10px 0;">
                    <label style="margin-right: 20px;">Who pays shipping: 
                        <select name="gunbroker_who_pays_shipping" style="margin-left: 5px;">
                            <option value="">Use default</option>
                            <option value="1" <?php selected($gb_who_pays, '1'); ?>>Buyer pays</option>
                            <option value="2" <?php selected($gb_who_pays, '2'); ?>>Seller pays</option>
                        </select>
                    </label>
                    <label>Auto relist: 
                        <select name="gunbroker_auto_relist" style="margin-left: 5px;">
                            <option value="">Use default</option>
                            <option value="1" <?php selected($gb_auto_relist, '1'); ?>>Do not relist</option>
                            <option value="2" <?php selected($gb_auto_relist, '2'); ?>>Relist</option>
                        </select>
                    </label>
                </p>
            </div>
        </td>
    </tr>

    <?php 
    $gb_condition = get_post_meta($post->ID, '_gunbroker_condition', true);
    $gb_inspection_period = get_post_meta($post->ID, '_gunbroker_inspection_period', true);
    $gb_use_default_taxes = get_post_meta($post->ID, '_gunbroker_use_default_taxes', true);
    ?>
    <tr>
        <th scope="row" style="vertical-align: top; padding-top: 15px;">
            <label>Item Details</label>
        </th>
        <td style="padding-top: 15px;">
            <div style="background: #f9f9f9; padding: 15px; border: 1px solid #ddd; border-radius: 4px;">
                <h4 style="margin: 0 0 12px 0; color: #333; font-size: 13px;">Product Condition & Policies</h4>
                <p style="margin: 0 0 10px 0;">
                            <label style="margin-right: 20px;">Condition: 
                                <select name="gunbroker_condition" style="margin-left: 5px;">
                                    <option value="">Use default</option>
                                    <option value="1" <?php selected($gb_condition, '1'); ?>>Factory New</option>
                                    <option value="2" <?php selected($gb_condition, '2'); ?>>New Old Stock</option>
                                    <option value="3" <?php selected($gb_condition, '3'); ?>>Used</option>
                                </select>
                            </label>
                    <label>Inspection Period: 
                        <select name="gunbroker_inspection_period" style="margin-left: 5px;">
                            <option value="">Use default</option>
                            <option value="1" <?php selected($gb_inspection_period, '1'); ?>>1 Day</option>
                            <option value="3" <?php selected($gb_inspection_period, '3'); ?>>3 Days</option>
                            <option value="7" <?php selected($gb_inspection_period, '7'); ?>>7 Days</option>
                            <option value="14" <?php selected($gb_inspection_period, '14'); ?>>14 Days</option>
                            <option value="30" <?php selected($gb_inspection_period, '30'); ?>>30 Days</option>
                        </select>
                    </label>
                </p>
                <p style="margin: 0;">
                    <label><input type="checkbox" name="gunbroker_use_default_taxes" value="1" <?php checked($gb_use_default_taxes, '1'); ?> /> Use default tax settings</label>
                    <span class="description" style="margin-left: 10px;">Sales tax collected based on ship to address</span>
                </p>
            </div>
        </td>
    </tr>

    <tr>
        <th scope="row" style="vertical-align: top; padding-top: 15px;">
            <label>Seller Address</label>
        </th>
        <td style="padding-top: 15px;">
            <div style="background: #f9f9f9; padding: 15px; border: 1px solid #ddd; border-radius: 4px;">
                <h4 style="margin: 0 0 12px 0; color: #333; font-size: 13px;">Location & Contact</h4>
                <p style="margin: 0 0 10px 0;">
                    <label style="margin-right: 15px;">Country (2 letters): 
                        <input type="text" name="gunbroker_country" value="<?php echo esc_attr($gb_country); ?>" style="width:60px; text-transform:uppercase;" maxlength="2" />
                    </label>
                    <label style="margin-right: 15px;">City: 
                        <input type="text" name="gunbroker_seller_city" value="<?php echo esc_attr($gb_city); ?>" style="width:120px;" />
                    </label>
                    <label style="margin-right: 15px;">State: 
                        <input type="text" name="gunbroker_seller_state" value="<?php echo esc_attr($gb_state); ?>" style="width:80px;" />
                    </label>
                    <label style="margin-right: 15px;">Postal: 
                        <input type="text" name="gunbroker_seller_postal" value="<?php echo esc_attr($gb_postal); ?>" style="width:100px;" />
                    </label>
                </p>
                <p style="margin: 0;">
                    <label>Contact phone: 
                        <input type="text" name="gunbroker_contact_phone" value="<?php echo esc_attr($gb_phone); ?>" style="width:150px;" />
                    </label>
                </p>
            </div>
        </td>
    </tr>

    <tr>
        <th scope="row" style="vertical-align: top; padding-top: 15px;">
            <label>Payment Methods</label>
        </th>
        <td style="padding-top: 15px;">
            <div style="background: #f9f9f9; padding: 15px; border: 1px solid #ddd; border-radius: 4px;">
                <h4 style="margin: 0 0 12px 0; color: #333; font-size: 13px;">Accepted Payment Methods</h4>
                <p style="margin: 0 0 8px 0;">
                    <label style="margin-right: 15px;"><input type="checkbox" name="gunbroker_payment_methods[]" value="Check" <?php checked(in_array('Check', $gb_pm, true)); ?> /> Check</label>
                    <label style="margin-right: 15px;"><input type="checkbox" name="gunbroker_payment_methods[]" value="MoneyOrder" <?php checked(in_array('MoneyOrder', $gb_pm, true)); ?> /> Money Order</label>
                    <label style="margin-right: 15px;"><input type="checkbox" name="gunbroker_payment_methods[]" value="CreditCard" <?php checked(in_array('CreditCard', $gb_pm, true)); ?> /> Credit Card</label>
                </p>
                <p style="margin: 0;">
                    <label style="margin-right: 15px;"><input type="checkbox" name="gunbroker_payment_methods[]" value="CertifiedCheck" <?php checked(in_array('CertifiedCheck', $gb_pm, true)); ?> /> Certified Check</label>
                    <label><input type="checkbox" name="gunbroker_payment_methods[]" value="USPSMoneyOrder" <?php checked(in_array('USPSMoneyOrder', $gb_pm, true)); ?> /> USPS Money Order</label>
                </p>
            </div>
        </td>
    </tr>

    <tr>
        <th scope="row" style="vertical-align: top; padding-top: 15px;">
            <label>Shipping Methods</label>
        </th>
        <td style="padding-top: 15px;">
            <div style="background: #f9f9f9; padding: 15px; border: 1px solid #ddd; border-radius: 4px;">
                <h4 style="margin: 0 0 12px 0; color: #333; font-size: 13px;">Available Shipping Options</h4>
                <p style="margin: 0;">
                    <label style="margin-right: 20px;"><input type="checkbox" name="gunbroker_shipping_methods[]" value="StandardShipping" <?php checked(in_array('StandardShipping', $gb_sm, true)); ?> /> Standard Shipping</label>
                    <label><input type="checkbox" name="gunbroker_shipping_methods[]" value="UPSGround" <?php checked(in_array('UPSGround', $gb_sm, true)); ?> /> UPS Ground</label>
                </p>
            </div>
        </td>
    </tr>

    <tr>
        <th scope="row">
            <label for="gunbroker_category">GunBroker Category</label>
        </th>
        <td>
            <div id="category-selection">
                <select id="gunbroker_category" name="gunbroker_category">
                    <option value="">Loading categories...</option>
                </select>
                <div id="category-loading" style="display: none; color: #666; font-size: 12px; margin-top: 5px;">
                    <span class="spinner is-active" style="float: none; margin: 0 5px 0 0;"></span>Loading subcategories...
                </div>
            </div>
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
                    $select.append('<option value="' + category.id + '">' + indent + category.name + '</option>');
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