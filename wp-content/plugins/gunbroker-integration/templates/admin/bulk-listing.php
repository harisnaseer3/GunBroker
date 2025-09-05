<div class="wrap">
    <h1>List Products to GunBroker</h1>

    <!-- Quick Actions Bar -->
    <div style="background: #fff; padding: 15px; border: 1px solid #ccd0d4; margin: 20px 0; display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
        <strong>Quick Actions:</strong>
        <button type="button" class="button" id="select-all-products">Select All</button>
        <button type="button" class="button" id="select-none-products">Select None</button>
        <button type="button" class="button button-primary" id="bulk-list-selected" disabled>
            List Selected to GunBroker (<span id="selected-count">0</span>)
        </button>

        <!-- View Toggle -->
        <div style="margin-left: auto; display: flex; gap: 10px; align-items: center;">
            <strong>View:</strong>
            <button type="button" class="button view-toggle active" data-view="grid" id="grid-view-btn">
                <span class="dashicons dashicons-grid-view" style="vertical-align: middle;"></span> Grid
            </button>
            <button type="button" class="button view-toggle" data-view="table" id="table-view-btn">
                <span class="dashicons dashicons-list-view" style="vertical-align: middle;"></span> Table
            </button>
        </div>

        <div id="bulk-action-status"></div>
    </div>

    <!-- Bulk Settings -->
    <div style="background: #f9f9f9; padding: 20px; border: 1px solid #ddd; margin: 20px 0; border-radius: 6px;">
        <h3 style="margin-top: 0; margin-bottom: 20px;">Listing Settings (Applied to Selected Products)</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 25px; align-items: start;">
            <div>
                <label for="bulk-markup" style="display: block; font-weight: 600; margin-bottom: 8px; color: #23282d;">Price Markup %</label>
                <input type="number" id="bulk-markup" value="<?php echo esc_attr(get_option('gunbroker_markup_percentage', 10)); ?>"
                       min="0" max="500" step="0.1" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" />
                <small style="display: block; color: #646970; margin-top: 4px;">Add % to WooCommerce price</small>
            </div>
            <div>
                <label for="bulk-duration" style="display: block; font-weight: 600; margin-bottom: 8px; color: #23282d;">Listing Duration</label>
                <select id="bulk-duration" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    <option value="1">1 Day</option>
                    <option value="3">3 Days</option>
                    <option value="5">5 Days</option>
                    <option value="7" selected>7 Days</option>
                    <option value="10">10 Days</option>
                </select>
                <small style="display: block; color: #646970; margin-top: 4px;">How long listing stays active</small>
            </div>
            <div>
                <label for="bulk-category" style="display: block; font-weight: 600; margin-bottom: 8px; color: #23282d;">GunBroker Category</label>
                <select id="bulk-category" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    <option value="3022">Firearms - General</option>
                    <option value="3023">Handguns</option>
                    <option value="3024">Rifles</option>
                    <option value="3025">Shotguns</option>
                    <option value="3026">Accessories</option>
                    <option value="3027">Ammunition</option>
                </select>
                <small style="display: block; color: #646970; margin-top: 4px;">Select appropriate category</small>
            </div>
            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #23282d;">Options</label>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <label style="display: flex; align-items: center; font-weight: normal; cursor: pointer;">
                        <input type="checkbox" id="auto-end-zero-stock" checked style="margin-right: 8px;">
                        <span>Auto-end when out of stock</span>
                    </label>
                    <label style="display: flex; align-items: center; font-weight: normal; cursor: pointer;">
                        <input type="checkbox" id="use-product-images" checked style="margin-right: 8px;">
                        <span>Use product images</span>
                    </label>
                </div>
                <small style="display: block; color: #646970; margin-top: 4px;">Listing preferences</small>
            </div>
        </div>
    </div>

    <!-- Grid View -->
    <div id="products-grid-view" class="products-view" style="background: #fff; border: 1px solid #ccd0d4; padding: 20px;">
        <div class="products-grid">
            <?php
            // Get all WooCommerce products for grid view
            $products = wc_get_products(array(
                'limit' => 50,
                'status' => 'publish',
                'type' => 'simple'
            ));

            $markup = get_option('gunbroker_markup_percentage', 10);

            foreach ($products as $product):
                $product_id = $product->get_id();
                $price = $product->get_price();
                $gb_price = $price * (1 + $markup / 100);
                $stock = $product->get_stock_quantity();
                $image_url = wp_get_attachment_image_url($product->get_image_id(), 'medium');

                // Check if already listed
                global $wpdb;
                $listing = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}gunbroker_listings WHERE product_id = %d",
                    $product_id
                ));

                $status = 'not-listed';
                $status_text = 'Not Listed';
                $listing_id = '';

                if ($listing) {
                    $status = $listing->status;
                    $status_text = ucfirst($listing->status);
                    $listing_id = $listing->gunbroker_id;
                }
                ?>
                <div class="product-card" data-product-id="<?php echo $product_id; ?>">
                    <!-- Product Image -->
                    <div class="product-image">
                        <?php if ($image_url): ?>
                            <img src="<?php echo esc_url($image_url); ?>"
                                 alt="<?php echo esc_attr($product->get_name()); ?>"
                                 loading="lazy"
                                 style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <div style="color: #999; font-size: 14px; text-align: center;">
                                <span class="dashicons dashicons-camera" style="font-size: 40px; display: block; margin-bottom: 10px;"></span>
                                No Image
                            </div>
                        <?php endif; ?>

                        <!-- Selection Checkbox -->
                        <div style="position: absolute; top: 10px; left: 10px;">
                            <input type="checkbox" class="product-checkbox" value="<?php echo $product_id; ?>"
                                   style="width: 20px; height: 20px; cursor: pointer;">
                        </div>

                        <!-- Status Badge -->
                        <div style="position: absolute; top: 10px; right: 10px;">
                        <span class="status-badge status-<?php echo $status; ?>">
                            <?php echo $status_text; ?>
                        </span>
                        </div>
                    </div>

                    <!-- Product Info -->
                    <div style="padding: 15px;">
                        <h4 style="margin: 0 0 8px 0; font-size: 14px; line-height: 1.3;">
                            <?php echo esc_html($product->get_name()); ?>
                        </h4>

                        <div style="font-size: 12px; color: #666; margin-bottom: 10px;">
                            SKU: <?php echo esc_html($product->get_sku() ?: 'N/A'); ?>
                        </div>

                        <!-- Pricing -->
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <div>
                                <strong>WC:</strong> $<?php echo number_format($price, 2); ?>
                            </div>
                            <div>
                                <strong>GB:</strong> <span class="gb-price">$<?php echo number_format($gb_price, 2); ?></span>
                            </div>
                        </div>

                        <!-- Stock -->
                        <div style="margin-bottom: 15px; font-size: 12px;">
                            <strong>Stock:</strong>
                            <span style="color: <?php echo $stock > 0 ? '#46b450' : '#d63638'; ?>">
                            <?php echo $stock ?: '∞'; ?>
                        </span>
                        </div>

                        <!-- Actions -->
                        <div class="product-actions">
                            <?php if ($listing && $status === 'active'): ?>
                                <button type="button" class="button-secondary button-small update-listing" data-product-id="<?php echo $product_id; ?>">
                                    Update
                                </button>
                                <button type="button" class="button-secondary button-small end-listing" data-product-id="<?php echo $product_id; ?>" style="color: #d63638;">
                                    End
                                </button>
                                <?php if ($listing_id): ?>
                                    <a href="https://www.gunbroker.com/item/<?php echo esc_attr($listing_id); ?>" target="_blank"
                                       class="button-secondary button-small" style="text-decoration: none;">
                                        View GB
                                    </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <button type="button" class="button-primary button-small list-single" data-product-id="<?php echo $product_id; ?>">
                                    List Now
                                </button>
                                <a href="<?php echo get_edit_post_link($product_id); ?>" class="button-secondary button-small" style="text-decoration: none;">
                                    Edit
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($products)): ?>
            <div style="padding: 60px; text-align: center; color: #666;">
                <span class="dashicons dashicons-store" style="font-size: 48px; color: #ddd; display: block; margin-bottom: 20px;"></span>
                <h3>No products found</h3>
                <p>Create some WooCommerce products first, then come back here to list them on GunBroker.</p>
                <a href="<?php echo admin_url('post-new.php?post_type=product'); ?>" class="button button-primary">Add New Product</a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Table View -->
    <div id="products-table-view" class="products-view" style="background: #fff; border: 1px solid #ccd0d4; display: none;">
        <table class="wp-list-table widefat fixed striped">
            <thead>
            <tr>
                <th style="width: 40px;">
                    <input type="checkbox" id="select-all-checkbox">
                </th>
                <th style="width: 60px;">Image</th>
                <th>Product Name</th>
                <th style="width: 100px;">WC Price</th>
                <th style="width: 100px;">GB Price</th>
                <th style="width: 80px;">Stock</th>
                <th style="width: 120px;">Status</th>
                <th style="width: 100px;">Actions</th>
            </tr>
            </thead>
            <tbody id="products-table-body">
            <?php foreach ($products as $product):
                $product_id = $product->get_id();
                $price = $product->get_price();
                $gb_price = $price * (1 + $markup / 100);
                $stock = $product->get_stock_quantity();
                $image = wp_get_attachment_image($product->get_image_id(), 'thumbnail', false, array('style' => 'width:50px;height:50px;object-fit:cover;'));

                $listing = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}gunbroker_listings WHERE product_id = %d",
                    $product_id
                ));

                $status = 'not-listed';
                $status_class = 'not-listed';

                if ($listing) {
                    $status = ucfirst($listing->status);
                    $status_class = $listing->status;
                }
                ?>
                <tr data-product-id="<?php echo $product_id; ?>">
                    <td>
                        <input type="checkbox" class="product-checkbox" value="<?php echo $product_id; ?>">
                    </td>
                    <td><?php echo $image ?: '<div style="width:50px;height:50px;background:#f0f0f0;border-radius:4px;"></div>'; ?></td>
                    <td>
                        <strong><?php echo esc_html($product->get_name()); ?></strong>
                        <div style="font-size: 12px; color: #666;">SKU: <?php echo esc_html($product->get_sku() ?: 'N/A'); ?></div>
                    </td>
                    <td>$<?php echo number_format($price, 2); ?></td>
                    <td class="gb-price">$<?php echo number_format($gb_price, 2); ?></td>
                    <td><?php echo $stock ?: '∞'; ?></td>
                    <td>
                        <span class="status-badge status-<?php echo $status_class; ?>">
                            <?php echo $status; ?>
                        </span>
                        <?php if ($listing && $listing->gunbroker_id): ?>
                            <br><small>ID: <?php echo esc_html($listing->gunbroker_id); ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($listing && $listing->status === 'active'): ?>
                            <button type="button" class="button-link update-listing" data-product-id="<?php echo $product_id; ?>">
                                Update
                            </button>
                            <button type="button" class="button-link end-listing" data-product-id="<?php echo $product_id; ?>" style="color: #d63638;">
                                End
                            </button>
                        <?php else: ?>
                            <button type="button" class="button-link list-single" data-product-id="<?php echo $product_id; ?>">
                                List Now
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php if (empty($products)): ?>
            <div style="padding: 40px; text-align: center; color: #666;">
                <h3>No products found</h3>
                <p>Create some WooCommerce products first, then come back here to list them on GunBroker.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Progress Modal -->
    <div id="listing-progress-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 100000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #fff; padding: 30px; border-radius: 8px; min-width: 400px; text-align: center;">
            <h2>Listing Products to GunBroker...</h2>
            <div style="margin: 20px 0;">
                <div id="progress-bar" style="width: 100%; height: 20px; background: #f0f0f0; border-radius: 10px; overflow: hidden;">
                    <div id="progress-fill" style="width: 0%; height: 100%; background: #0073aa; transition: width 0.3s ease;"></div>
                </div>
                <p id="progress-text" style="margin-top: 10px;">Starting...</p>
            </div>
            <div id="progress-details" style="max-height: 200px; overflow-y: auto; text-align: left; font-family: monospace; font-size: 12px; background: #f9f9f9; padding: 10px; border-radius: 4px;">
            </div>
        </div>
    </div>
</div>

<style>
    .status-badge {
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: bold;
        text-transform: uppercase;
    }

    .status-active { background: #d4edda; color: #155724; }
    .status-pending { background: #fff3cd; color: #856404; }
    .status-error { background: #f8d7da; color: #721c24; }
    .status-inactive { background: #f8f9fa; color: #6c757d; }
    .status-not-listed { background: #e2e3e5; color: #383d41; }

    .button-link {
        color: #0073aa !important;
        text-decoration: none !important;
        font-size: 12px;
        border: none;
        background: none;
        cursor: pointer;
        padding: 0;
        margin-right: 8px;
    }

    .button-link:hover {
        text-decoration: underline !important;
    }

    #selected-count {
        font-weight: bold;
        color: #0073aa;
    }

    /* Grid View Styles */
    .products-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 20px;
    }

    .product-card {
        border: 1px solid #ddd;
        border-radius: 8px;
        overflow: hidden;
        background: #fff;
        transition: all 0.2s ease;
        position: relative;
    }

    .product-card:hover {
        border-color: #0073aa;
        box-shadow: 0 4px 12px rgba(0, 115, 170, 0.15);
        transform: translateY(-2px);
    }

    .product-card.selected {
        border-color: #0073aa;
        box-shadow: 0 0 0 2px rgba(0, 115, 170, 0.2);
    }

    .product-image {
        position: relative;
        height: 200px;
        background: #f5f5f5;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }

    .product-image img {
        max-width: 100%;
        max-height: 100%;
        object-fit: cover;
        transition: transform 0.2s ease;
    }

    .product-card:hover .product-image img {
        transform: scale(1.05);
    }

    .product-actions {
        display: flex;
        gap: 8px;
        justify-content: center;
        flex-wrap: wrap;
    }

    .button-small {
        padding: 4px 12px !important;
        font-size: 11px !important;
        height: auto !important;
        line-height: 1.4 !important;
    }

    /* View Toggle */
    .view-toggle {
        border: 1px solid #ddd !important;
        background: #f7f7f7 !important;
        color: #666 !important;
    }

    .view-toggle.active {
        background: #0073aa !important;
        color: #fff !important;
        border-color: #0073aa !important;
    }

    .view-toggle .dashicons {
        font-size: 16px;
        margin-right: 5px;
    }

    /* Responsive Grid */
    @media (max-width: 1400px) {
        .products-grid {
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        }
    }

    @media (max-width: 1024px) {
        .products-grid {
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        }
    }

    @media (max-width: 768px) {
        .products-grid {
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }

        .product-card {
            font-size: 13px;
        }

        .product-image {
            height: 150px;
        }
    }
</style>

<script>
    jQuery(document).ready(function($) {
        let selectedProducts = [];

        // View Toggle
        $('.view-toggle').click(function() {
            const view = $(this).data('view');

            $('.view-toggle').removeClass('active');
            $(this).addClass('active');

            $('.products-view').hide();
            if (view === 'grid') {
                $('#products-grid-view').show();
            } else {
                $('#products-table-view').show();
            }

            // Save preference
            localStorage.setItem('gunbroker_view_preference', view);

            updateSelectedCount();
            updateBulkButton();
        });

        // Restore view preference
        const savedView = localStorage.getItem('gunbroker_view_preference') || 'grid';
        $('[data-view="' + savedView + '"]').click();

        // Product card selection (grid view)
        $(document).on('change', '.product-checkbox', function() {
            const $card = $(this).closest('.product-card');
            if ($(this).is(':checked')) {
                $card.addClass('selected');
            } else {
                $card.removeClass('selected');
            }
            updateSelectedCount();
            updateBulkButton();
        });

        // Update GB price when markup changes
        $('#bulk-markup').on('input', function() {
            const markup = parseFloat($(this).val()) || 0;

            // Update grid view prices
            $('.product-card').each(function() {
                const productId = $(this).data('product-id');
                const wcPriceText = $(this).find('strong:contains("WC:")').parent().text();
                const wcPrice = parseFloat(wcPriceText.replace(/[^0-9.]/g, ''));
                const gbPrice = wcPrice * (1 + markup / 100);
                $(this).find('.gb-price').text('$' + gbPrice.toFixed(2));
            });

            // Update table view prices
            $('#products-table-body tr').each(function() {
                const wcPrice = parseFloat($(this).find('td:nth-child(4)').text().replace('$', ''));
                const gbPrice = wcPrice * (1 + markup / 100);
                $(this).find('.gb-price').text('$' + gbPrice.toFixed(2));
            });
        });

        $('#select-all-checkbox').change(function() {
            $('.product-checkbox').prop('checked', $(this).is(':checked'));
            $('.product-card').toggleClass('selected', $(this).is(':checked'));
            updateSelectedCount();
            updateBulkButton();
        });

        $('#select-all-products').click(function() {
            $('.product-checkbox').prop('checked', true);
            $('.product-card').addClass('selected');
            $('#select-all-checkbox').prop('checked', true);
            updateSelectedCount();
            updateBulkButton();
        });

        $('#select-none-products').click(function() {
            $('.product-checkbox').prop('checked', false);
            $('.product-card').removeClass('selected');
            $('#select-all-checkbox').prop('checked', false);
            updateSelectedCount();
            updateBulkButton();
        });

        function updateSelectedCount() {
            const count = $('.product-checkbox:checked').length;
            $('#selected-count').text(count);
            selectedProducts = $('.product-checkbox:checked').map(function() {
                return $(this).val();
            }).get();
        }

        function updateBulkButton() {
            const hasSelected = $('.product-checkbox:checked').length > 0;
            $('#bulk-list-selected').prop('disabled', !hasSelected);
        }

        // Bulk listing
        $('#bulk-list-selected').click(function() {
            if (selectedProducts.length === 0) {
                alert('Please select at least one product');
                return;
            }

            const settings = {
                markup: parseFloat($('#bulk-markup').val()) || 0,
                duration: parseInt($('#bulk-duration').val()) || 7,
                category: $('#bulk-category').val(),
                autoEndZeroStock: $('#auto-end-zero-stock').is(':checked'),
                useProductImages: $('#use-product-images').is(':checked')
            };

            bulkListProducts(selectedProducts, settings);
        });

        // Single product actions
        $(document).on('click', '.list-single', function() {
            const productId = $(this).data('product-id');
            const settings = {
                markup: parseFloat($('#bulk-markup').val()) || 0,
                duration: parseInt($('#bulk-duration').val()) || 7,
                category: $('#bulk-category').val(),
                autoEndZeroStock: $('#auto-end-zero-stock').is(':checked'),
                useProductImages: $('#use-product-images').is(':checked')
            };

            bulkListProducts([productId], settings);
        });

        function bulkListProducts(productIds, settings) {
            $('#listing-progress-modal').show();
            $('#progress-fill').css('width', '0%');
            $('#progress-text').text('Starting bulk listing...');
            $('#progress-details').html('');

            let completed = 0;
            const total = productIds.length;

            function processNext() {
                if (completed >= total) {
                    $('#progress-text').text('Completed! Refreshing page...');
                    setTimeout(() => {
                        $('#listing-progress-modal').hide();
                        location.reload();
                    }, 2000);
                    return;
                }

                const productId = productIds[completed];
                const progress = ((completed / total) * 100);

                $('#progress-fill').css('width', progress + '%');
                $('#progress-text').text(`Processing product ${completed + 1} of ${total}...`);

                // Get product name for display
                let productName = 'Product ' + productId;
                const $card = $('.product-card[data-product-id="' + productId + '"]');
                const $row = $('tr[data-product-id="' + productId + '"]');

                if ($card.length) {
                    productName = $card.find('h4').text();
                } else if ($row.length) {
                    productName = $row.find('td:nth-child(3) strong').text();
                }

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'gunbroker_bulk_list_products',
                        product_id: productId,
                        settings: settings,
                        nonce: '<?php echo wp_create_nonce("gunbroker_ajax_nonce"); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#progress-details').append('<div style="color: green; margin: 2px 0;">✓ ' + productName + ': Listed successfully</div>');
                        } else {
                            $('#progress-details').append('<div style="color: red; margin: 2px 0;">✗ ' + productName + ': ' + response.data + '</div>');
                        }

                        completed++;
                        $('#progress-details').scrollTop($('#progress-details')[0].scrollHeight);
                        processNext();
                    },
                    error: function() {
                        $('#progress-details').append('<div style="color: red; margin: 2px 0;">✗ ' + productName + ': Network error</div>');
                        completed++;
                        $('#progress-details').scrollTop($('#progress-details')[0].scrollHeight);
                        processNext();
                    }
                });
            }

            processNext();
        }

        // Update and end listing actions
        $(document).on('click', '.update-listing', function() {
            const productId = $(this).data('product-id');
            const $button = $(this);
            const originalText = $button.text();

            $button.prop('disabled', true).text('Updating...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'gunbroker_update_listing',
                    product_id: productId,
                    nonce: '<?php echo wp_create_nonce("gunbroker_ajax_nonce"); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        alert('Listing updated successfully!');
                    } else {
                        alert('Update failed: ' + response.data);
                    }
                },
                error: function() {
                    alert('Network error occurred');
                },
                complete: function() {
                    $button.prop('disabled', false).text(originalText);
                }
            });
        });

        $(document).on('click', '.end-listing', function() {
            const productId = $(this).data('product-id');
            const $button = $(this);

            if (!confirm('Are you sure you want to end this GunBroker listing?')) {
                return;
            }

            const originalText = $button.text();
            $button.prop('disabled', true).text('Ending...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'gunbroker_end_listing',
                    product_id: productId,
                    nonce: '<?php echo wp_create_nonce("gunbroker_ajax_nonce"); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        alert('Listing ended successfully!');
                        location.reload();
                    } else {
                        alert('Failed to end listing: ' + response.data);
                    }
                },
                error: function() {
                    alert('Network error occurred');
                },
                complete: function() {
                    $button.prop('disabled', false).text(originalText);
                }
            });
        });
    });
</script>