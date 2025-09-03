<div class="wrap">
    <h1>GunBroker Orders</h1>

    <!-- Order Stats -->
    <div class="order-stats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;">
        <div class="stat-card" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">
            <h3 style="margin: 0 0 10px 0; color: #23282d;">New Orders</h3>
            <p style="font-size: 24px; font-weight: bold; margin: 0; color: #d63638;" id="new-orders-count">0</p>
            <p style="margin: 5px 0 0 0; color: #646970; font-size: 13px;">Need attention</p>
        </div>
        <div class="stat-card" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">
            <h3 style="margin: 0 0 10px 0; color: #23282d;">Paid Orders</h3>
            <p style="font-size: 24px; font-weight: bold; margin: 0; color: #ffb900;" id="paid-orders-count">0</p>
            <p style="margin: 5px 0 0 0; color: #646970; font-size: 13px;">Ready to ship</p>
        </div>
        <div class="stat-card" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">
            <h3 style="margin: 0 0 10px 0; color: #23282d;">Shipped</h3>
            <p style="font-size: 24px; font-weight: bold; margin: 0; color: #46b450;" id="shipped-orders-count">0</p>
            <p style="margin: 5px 0 0 0; color: #646970; font-size: 13px;">In transit</p>
        </div>
        <div class="stat-card" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">
            <h3 style="margin: 0 0 10px 0; color: #23282d;">Total Value</h3>
            <p style="font-size: 24px; font-weight: bold; margin: 0; color: #0073aa;" id="total-value">$0</p>
            <p style="margin: 5px 0 0 0; color: #646970; font-size: 13px;">Last 30 days</p>
        </div>
    </div>

    <!-- Action Bar -->
    <div style="background: #fff; padding: 15px; border: 1px solid #ccd0d4; margin: 20px 0; display: flex; gap: 15px; align-items: center;">
        <button type="button" class="button button-primary" id="refresh-orders">
            <span class="dashicons dashicons-update" style="vertical-align: middle; margin-right: 5px;"></span>
            Refresh from GunBroker
        </button>
        <button type="button" class="button" id="export-orders">Export to CSV</button>
        <div style="margin-left: auto; display: flex; gap: 10px; align-items: center;">
            <label for="order-filter">Filter:</label>
            <select id="order-filter" class="regular-text">
                <option value="all">All Orders</option>
                <option value="new">New Orders</option>
                <option value="paid">Paid Orders</option>
                <option value="shipped">Shipped Orders</option>
                <option value="completed">Completed</option>
            </select>
            <input type="text" id="search-orders" placeholder="Search orders..." class="regular-text">
        </div>
    </div>

    <!-- Orders Table -->
    <div style="background: #fff; border: 1px solid #ccd0d4;">
        <table class="wp-list-table widefat fixed striped" id="orders-table">
            <thead>
            <tr>
                <th style="width: 40px;">
                    <input type="checkbox" id="select-all-orders">
                </th>
                <th style="width: 100px;">Order Date</th>
                <th style="width: 120px;">GB Order ID</th>
                <th>Product</th>
                <th style="width: 100px;">Buyer</th>
                <th style="width: 80px;">Amount</th>
                <th style="width: 100px;">Status</th>
                <th style="width: 120px;">Shipping</th>
                <th style="width: 150px;">Actions</th>
            </tr>
            </thead>
            <tbody id="orders-table-body">
            <!-- Orders will be loaded here via AJAX -->
            </tbody>
        </table>

        <div id="orders-loading" style="padding: 40px; text-align: center;">
            <div class="spinner is-active" style="float: none; margin: 0 auto 20px;"></div>
            <p>Loading orders from GunBroker...</p>
        </div>

        <div id="no-orders" style="display: none; padding: 40px; text-align: center; color: #666;">
            <h3>No orders found</h3>
            <p>Orders from GunBroker will appear here once customers start purchasing your listings.</p>
        </div>
    </div>

    <!-- Shipping Modal -->
    <div id="shipping-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 100000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #fff; padding: 30px; border-radius: 8px; min-width: 500px;">
            <h2>Add Shipping Information</h2>
            <form id="shipping-form">
                <input type="hidden" id="shipping-order-id" value="">
                <table class="form-table">
                    <tr>
                        <th><label for="tracking-carrier">Carrier</label></th>
                        <td>
                            <select id="tracking-carrier" class="regular-text">
                                <option value="ups">UPS</option>
                                <option value="fedex">FedEx</option>
                                <option value="usps">USPS</option>
                                <option value="dhl">DHL</option>
                                <option value="other">Other</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="tracking-number">Tracking Number</label></th>
                        <td>
                            <input type="text" id="tracking-number" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="shipping-notes">Notes</label></th>
                        <td>
                            <textarea id="shipping-notes" class="large-text" rows="3" placeholder="Optional shipping notes..."></textarea>
                        </td>
                    </tr>
                </table>
                <div style="margin-top: 20px; text-align: right;">
                    <button type="button" class="button" onclick="closeShippingModal()">Cancel</button>
                    <button type="submit" class="button button-primary" style="margin-left: 10px;">Mark as Shipped</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bulk Actions -->
    <div id="bulk-actions-bar" style="display: none; background: #f0f0f0; padding: 10px; border-top: 1px solid #ccd0d4;">
        <strong>Bulk Actions:</strong>
        <button type="button" class="button" id="bulk-mark-shipped">Mark as Shipped</button>
        <button type="button" class="button" id="bulk-export-labels">Export Shipping Labels</button>
        <span id="selected-orders-count" style="margin-left: 20px; font-weight: bold;">0 orders selected</span>
    </div>
</div>

<style>
    .order-status {
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: bold;
        text-transform: uppercase;
    }

    .status-new { background: #f8d7da; color: #721c24; }
    .status-paid { background: #fff3cd; color: #856404; }
    .status-shipped { background: #d4edda; color: #155724; }
    .status-completed { background: #d1ecf1; color: #0c5460; }

    .order-actions button {
        margin-right: 5px;
        font-size: 11px;
    }

    .shipping-info {
        font-size: 12px;
        color: #666;
    }

    .shipping-info a {
        color: #0073aa;
        text-decoration: none;
    }

    .shipping-info a:hover {
        text-decoration: underline;
    }

    #orders-table tbody tr:hover {
        background-color: #f9f9f9;
    }
</style>

<script>
    jQuery(document).ready(function($) {
        let allOrders = [];
        let selectedOrders = [];

        // Load orders on page load
        loadOrders();

        function loadOrders() {
            $('#orders-loading').show();
            $('#orders-table-body').empty();
            $('#no-orders').hide();

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'gunbroker_load_orders',
                    nonce: '<?php echo wp_create_nonce("gunbroker_ajax_nonce"); ?>'
                },
                success: function(response) {
                    $('#orders-loading').hide();

                    if (response.success && response.data.length > 0) {
                        allOrders = response.data;
                        displayOrders(allOrders);
                        updateStats(allOrders);
                    } else {
                        $('#no-orders').show();
                    }
                },
                error: function() {
                    $('#orders-loading').hide();
                    $('#orders-table-body').html('<tr><td colspan="9" style="text-align: center; color: #d63638;">Failed to load orders. Please check your API connection.</td></tr>');
                }
            });
        }

        function displayOrders(orders) {
            const tbody = $('#orders-table-body');
            tbody.empty();

            orders.forEach(function(order) {
                const row = `
                <tr data-order-id="${order.id}">
                    <td><input type="checkbox" class="order-checkbox" value="${order.id}"></td>
                    <td>${formatDate(order.date)}</td>
                    <td><a href="${order.url}" target="_blank">${order.id}</a></td>
                    <td>
                        <strong>${order.product_name}</strong>
                        <div style="font-size: 12px; color: #666;">SKU: ${order.sku || 'N/A'}</div>
                    </td>
                    <td>
                        <strong>${order.buyer_name}</strong>
                        <div style="font-size: 12px; color: #666;">${order.buyer_location}</div>
                    </td>
                    <td>$${parseFloat(order.amount).toFixed(2)}</td>
                    <td><span class="order-status status-${order.status}">${order.status.toUpperCase()}</span></td>
                    <td class="shipping-info">
                        ${order.tracking_number ?
                    `<a href="${getTrackingUrl(order.carrier, order.tracking_number)}" target="_blank">${order.tracking_number}</a>` :
                    '<span style="color: #666;">Not shipped</span>'
                }
                    </td>
                    <td class="order-actions">
                        ${order.status === 'paid' ?
                    `<button type="button" class="button-link ship-order" data-order-id="${order.id}">Ship</button>` : ''
                }
                        <button type="button" class="button-link view-order" data-order-id="${order.id}">View</button>
                        ${order.tracking_number ?
                    `<button type="button" class="button-link print-label" data-order-id="${order.id}">Label</button>` : ''
                }
                    </td>
                </tr>
            `;
                tbody.append(row);
            });

            // Reattach event handlers
            attachOrderEventHandlers();
        }

        function attachOrderEventHandlers() {
            // Ship order
            $('.ship-order').click(function() {
                const orderId = $(this).data('order-id');
                openShippingModal(orderId);
            });

            // View order
            $('.view-order').click(function() {
                const orderId = $(this).data('order-id');
                const order = allOrders.find(o => o.id == orderId);
                if (order && order.url) {
                    window.open(order.url, '_blank');
                }
            });

            // Order selection
            $('.order-checkbox').change(function() {
                updateSelectedOrders();
            });
        }

        function updateStats(orders) {
            let newCount = 0, paidCount = 0, shippedCount = 0, totalValue = 0;

            orders.forEach(function(order) {
                switch(order.status) {
                    case 'new': newCount++; break;
                    case 'paid': paidCount++; break;
                    case 'shipped': shippedCount++; break;
                }
                totalValue += parseFloat(order.amount);
            });

            $('#new-orders-count').text(newCount);
            $('#paid-orders-count').text(paidCount);
            $('#shipped-orders-count').text(shippedCount);
            $('#total-value').text(' + totalValue.toFixed(2));
        }

        function updateSelectedOrders() {
            selectedOrders = $('.order-checkbox:checked').map(function() {
                return $(this).val();
            }).get();

            const count = selectedOrders.length;
            $('#selected-orders-count').text(count + ' orders selected');

            if (count > 0) {
                $('#bulk-actions-bar').show();
            } else {
                $('#bulk-actions-bar').hide();
            }
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric'
            });
        }

        function getTrackingUrl(carrier, trackingNumber) {
            const urls = {
                'ups': `https://www.ups.com/track?loc=null&tracknum=${trackingNumber}`,
                'fedex': `https://www.fedex.com/fedextrack/?tracknumbers=${trackingNumber}`,
                'usps': `https://tools.usps.com/go/TrackConfirmAction?qtc_tLabels1=${trackingNumber}`,
                'dhl': `https://www.dhl.com/us-en/home/tracking.html?tracking-id=${trackingNumber}`
            };
            return urls[carrier] || `#`;
        }

        // Refresh orders
        $('#refresh-orders').click(function() {
            $(this).prop('disabled', true).html('<span class="spinner is-active" style="float: none; margin-right: 5px;"></span>Refreshing...');
            loadOrders();
            setTimeout(() => {
                $(this).prop('disabled', false).html('<span class="dashicons dashicons-update" style="vertical-align: middle; margin-right: 5px;"></span>Refresh from GunBroker');
            }, 2000);
        });

        // Filter orders
        $('#order-filter').change(function() {
            const filter = $(this).val();
            let filteredOrders = allOrders;

            if (filter !== 'all') {
                filteredOrders = allOrders.filter(order => order.status === filter);
            }

            displayOrders(filteredOrders);
        });

        // Search orders
        $('#search-orders').on('input', function() {
            const search = $(this).val().toLowerCase();
            let filteredOrders = allOrders;

            if (search) {
                filteredOrders = allOrders.filter(order =>
                    order.product_name.toLowerCase().includes(search) ||
                    order.buyer_name.toLowerCase().includes(search) ||
                    order.id.toString().includes(search)
                );
            }

            displayOrders(filteredOrders);
        });

        // Select all orders
        $('#select-all-orders').change(function() {
            $('.order-checkbox').prop('checked', $(this).is(':checked'));
            updateSelectedOrders();
        });

        // Shipping form
        $('#shipping-form').submit(function(e) {
            e.preventDefault();

            const orderId = $('#shipping-order-id').val();
            const carrier = $('#tracking-carrier').val();
            const trackingNumber = $('#tracking-number').val();
            const notes = $('#shipping-notes').val();

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'gunbroker_ship_order',
                    order_id: orderId,
                    carrier: carrier,
                    tracking_number: trackingNumber,
                    notes: notes,
                    nonce: '<?php echo wp_create_nonce("gunbroker_ajax_nonce"); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        closeShippingModal();
                        loadOrders(); // Refresh the orders
                        alert('Order marked as shipped successfully!');
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                error: function() {
                    alert('Network error occurred');
                }
            });
        });

        // Export orders
        $('#export-orders').click(function() {
            const csvContent = generateCSV(allOrders);
            downloadCSV(csvContent, 'gunbroker-orders.csv');
        });

        function generateCSV(orders) {
            const headers = ['Order Date', 'GB Order ID', 'Product Name', 'Buyer Name', 'Buyer Location', 'Amount', 'Status', 'Tracking Number'];
            let csv = headers.join(',') + '\\n';

            orders.forEach(order => {
                const row = [
                    order.date,
                    order.id,
                    `"${order.product_name}"`,
                    `"${order.buyer_name}"`,
                    `"${order.buyer_location}"`,
                    order.amount,
                    order.status,
                    order.tracking_number || ''
                ];
                csv += row.join(',') + '\\n';
            });

            return csv;
        }

        function downloadCSV(content, filename) {
            const blob = new Blob([content], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            a.click();
            window.URL.revokeObjectURL(url);
        }
    });

    function openShippingModal(orderId) {
        document.getElementById('shipping-order-id').value = orderId;
        document.getElementById('shipping-modal').style.display = 'block';
        document.getElementById('tracking-number').focus();
    }

    function closeShippingModal() {
        document.getElementById('shipping-modal').style.display = 'none';
        document.getElementById('shipping-form').reset();
    }
</script>