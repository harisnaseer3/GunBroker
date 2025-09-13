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
            <div id="category-selection">
                <select id="gunbroker_category" name="gunbroker_category">
                    <option value="">Loading categories...</option>
                </select>
                <div id="category-loading" style="display: none; color: #666; font-size: 12px; margin-top: 5px;">
                    Loading subcategories...
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
        
        // Progressive category loading
        let categoryStack = [];
        let currentCategoryId = null;
        
        // Load top-level categories on page load
        loadTopLevelCategories();
        
        function loadTopLevelCategories() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'gunbroker_get_top_categories',
                    nonce: '<?php echo wp_create_nonce("gunbroker_ajax_nonce"); ?>'
                },
                success: function(response) {
                    console.log('Top categories response:', response);
                    if (response.success) {
                        console.log('Categories data:', response.data);
                        populateCategorySelect(response.data, '');
                        // Set selected value if exists
                        const selectedValue = '<?php echo esc_js($category); ?>';
                        if (selectedValue) {
                            $('#gunbroker_category').val(selectedValue);
                        }
                    } else {
                        $('#gunbroker_category').html('<option value="">Error loading categories</option>');
                    }
                },
                error: function() {
                    $('#gunbroker_category').html('<option value="">Error loading categories</option>');
                }
            });
        }
        
        function loadSubcategories(parentId) {
            $('#category-loading').show();
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'gunbroker_get_subcategories',
                    parent_category_id: parentId,
                    nonce: '<?php echo wp_create_nonce("gunbroker_ajax_nonce"); ?>'
                },
                success: function(response) {
                    $('#category-loading').hide();
                    if (response.success && response.data.length > 0) {
                        // Add subcategories to the stack
                        categoryStack.push({
                            parentId: parentId,
                            categories: response.data
                        });
                        
                        // Update the select with current level
                        updateCategorySelect();
                    }
                },
                error: function() {
                    $('#category-loading').hide();
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