<?php
// Handle form submission
if (isset($_POST['gunbroker_settings_nonce']) && wp_verify_nonce($_POST['gunbroker_settings_nonce'], 'gunbroker_settings')) {
    // Save all settings
    update_option('gunbroker_dev_key', sanitize_text_field($_POST['gunbroker_dev_key']));
    update_option('gunbroker_username', sanitize_text_field($_POST['gunbroker_username']));
    update_option('gunbroker_password', sanitize_text_field($_POST['gunbroker_password']));
    // Save global markup (used as default)
    if (isset($_POST['gunbroker_markup_percentage'])) {
    update_option('gunbroker_markup_percentage', floatval($_POST['gunbroker_markup_percentage']));
    }
    update_option('gunbroker_auto_end_zero_stock', isset($_POST['gunbroker_auto_end_zero_stock']));
    update_option('gunbroker_public_domain', sanitize_text_field($_POST['gunbroker_public_domain']));
    update_option('gunbroker_sandbox_mode', isset($_POST['gunbroker_sandbox_mode']));
    update_option('gunbroker_enable_buy_now', isset($_POST['gunbroker_enable_buy_now']));
    update_option('gunbroker_default_category', intval($_POST['gunbroker_default_category']));
    update_option('gunbroker_default_country', sanitize_text_field($_POST['gunbroker_default_country']));
    
    // New global settings
    update_option('gunbroker_default_postal', sanitize_text_field($_POST['gunbroker_default_postal']));
    update_option('gunbroker_default_country_code', sanitize_text_field($_POST['gunbroker_default_country_code']));
    update_option('gunbroker_default_use_sales_tax', isset($_POST['gunbroker_default_use_sales_tax']));
    update_option('gunbroker_default_listing_type', sanitize_text_field($_POST['gunbroker_default_listing_type']));
    update_option('gunbroker_default_listing_duration', intval($_POST['gunbroker_default_listing_duration']));
    
    // Additional global settings
    update_option('gunbroker_default_auto_relist', sanitize_text_field($_POST['gunbroker_default_auto_relist']));
    update_option('gunbroker_default_who_pays_shipping', sanitize_text_field($_POST['gunbroker_default_who_pays_shipping']));
    update_option('gunbroker_default_will_ship_international', $_POST['gunbroker_default_will_ship_international'] === '1');
    update_option('gunbroker_default_inspection_period', intval($_POST['gunbroker_default_inspection_period']));
    update_option('gunbroker_default_return_policy', sanitize_text_field($_POST['gunbroker_default_return_policy']));
    
    // Payment Methods and Shipping Profiles
    update_option('gunbroker_payment_methods', isset($_POST['gunbroker_payment_methods']) ? (array) $_POST['gunbroker_payment_methods'] : array());
    update_option('gunbroker_shipping_profile_ids', sanitize_text_field($_POST['gunbroker_shipping_profile_ids']));
    update_option('gunbroker_shipping_methods', isset($_POST['gunbroker_shipping_methods']) ? (array) $_POST['gunbroker_shipping_methods'] : array());
    update_option('gunbroker_standard_text_id', sanitize_text_field($_POST['gunbroker_standard_text_id']));
    update_option('gunbroker_accessories_shipping', sanitize_text_field($_POST['gunbroker_accessories_shipping']));
    update_option('gunbroker_free_shipping_ground', sanitize_text_field($_POST['gunbroker_free_shipping_ground']));
    update_option('gunbroker_everything', sanitize_text_field($_POST['gunbroker_everything']));
    update_option('gunbroker_header_content', wp_kses_post($_POST['gunbroker_header_content']));
    update_option('gunbroker_footer_content', wp_kses_post($_POST['gunbroker_footer_content']));
    
    echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
}
?>

<div class="wrap">
    <h1>GunBroker Settings</h1>

    <?php settings_errors('gunbroker_settings'); ?>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
        <!-- Main Settings -->
        <div>
            <form method="post" action="">
                <?php wp_nonce_field('gunbroker_settings', 'gunbroker_settings_nonce'); ?>

                <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; margin-bottom: 20px;">
                    <h2 style="margin-top: 0;">API Credentials</h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="gunbroker_dev_key">Developer Key</label>
                            </th>
                            <td>
                                <input type="text" id="gunbroker_dev_key" name="gunbroker_dev_key"
                                       value="<?php echo esc_attr(get_option('gunbroker_dev_key')); ?>"
                                       class="regular-text" />
                                <p class="description">
                                    Get your key from <a href="https://api.gunbroker.com/User/DevKey/Create" target="_blank">GunBroker API</a>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="gunbroker_username">GunBroker Username</label>
                            </th>
                            <td>
                                <input type="text" id="gunbroker_username" name="gunbroker_username"
                                       value="<?php echo esc_attr(get_option('gunbroker_username')); ?>"
                                       class="regular-text" />
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="gunbroker_password">GunBroker Password</label>
                            </th>
                            <td>
                                <input type="password" id="gunbroker_password" name="gunbroker_password"
                                       value="<?php echo esc_attr(get_option('gunbroker_password')); ?>"
                                       class="regular-text" />
                            </td>
                        </tr>
                    </table>
                </div>

                <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; margin-bottom: 20px;">
                    <h2 style="margin-top: 0;">Percentage Markup</h2>
                    <p style="margin-bottom: 20px;">ie. If you want to add 3% to the price of each product on GunBroker, enter 0.03 into the field.</p>
                    <div style="margin-bottom: 16px;">
                        <label for="gunbroker_markup_percentage" style="display: block; margin-bottom: 6px; font-weight: 600;">MARKUP % AS DECIMAL</label>
                        <input type="number" id="gunbroker_markup_percentage" name="gunbroker_markup_percentage"
                               value="<?php echo esc_attr(get_option('gunbroker_markup_percentage', 0)); ?>"
                               min="0" max="500" step="0.01" class="regular-text" style="max-width: 220px;" />
                    </div>
                </div>

                <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; margin-bottom: 20px;">
                    <h2 style="margin-top: 0;">Listing Configuration</h2>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <label for="gunbroker_default_postal" style="display: block; margin-bottom: 6px; font-weight: 600;">POSTAL CODE</label>
                            <input type="text" id="gunbroker_default_postal" name="gunbroker_default_postal"
                                   value="<?php echo esc_attr(get_option('gunbroker_default_postal', '35137')); ?>"
                                   class="regular-text" maxlength="10" />
                        </div>
                        <div>
                            <label for="gunbroker_default_country_code" style="display: block; margin-bottom: 6px; font-weight: 600;">COUNTRY CODE</label>
                            <select id="gunbroker_default_country_code" name="gunbroker_default_country_code" class="regular-text">
                                <option value="US" <?php selected(get_option('gunbroker_default_country_code', 'US'), 'US'); ?>>US</option>
                                <option value="CA" <?php selected(get_option('gunbroker_default_country_code', 'US'), 'CA'); ?>>CA</option>
                            </select>
                        </div>

                        <div>
                            <label for="gunbroker_default_listing_type" style="display: block; margin-bottom: 6px; font-weight: 600;">LISTING TYPE</label>
                            <select id="gunbroker_default_listing_type" name="gunbroker_default_listing_type" class="regular-text">
                                <option value="FixedPrice" <?php selected(get_option('gunbroker_default_listing_type', 'FixedPrice'), 'FixedPrice'); ?>>Fixed Price</option>
                                <option value="StartingBid" <?php selected(get_option('gunbroker_default_listing_type', 'FixedPrice'), 'StartingBid'); ?>>Starting Bid</option>
                            </select>
                        </div>
                        <div>
                            <label for="gunbroker_default_listing_duration" style="display: block; margin-bottom: 6px; font-weight: 600;">LISTING DURATION</label>
                            <select id="gunbroker_default_listing_duration" name="gunbroker_default_listing_duration" class="regular-text">
                                <option value="30" <?php selected(get_option('gunbroker_default_listing_duration', 90), 30); ?>>30 days</option>
                                <option value="60" <?php selected(get_option('gunbroker_default_listing_duration', 90), 60); ?>>60 days</option>
                                <option value="90" <?php selected(get_option('gunbroker_default_listing_duration', 90), 90); ?>>90 days</option>
                            </select>
                        </div>

                        <div>
                            <label for="gunbroker_default_who_pays_shipping" style="display: block; margin-bottom: 6px; font-weight: 600;">WHO PAYS FOR SHIPPING</label>
                            <select id="gunbroker_default_who_pays_shipping" name="gunbroker_default_who_pays_shipping" class="regular-text">
                                <option value="3" <?php selected(get_option('gunbroker_default_who_pays_shipping', '4'), '3'); ?>>Seller pays for shipping</option>
                                <option value="2" <?php selected(get_option('gunbroker_default_who_pays_shipping', '4'), '2'); ?>>Buyer pays actual shipping cost</option>
                                <option value="4" <?php selected(get_option('gunbroker_default_who_pays_shipping', '4'), '4'); ?>>Use shipping profile</option>
                            </select>
                        </div>
                        <div>
                            <label for="gunbroker_default_will_ship_international" style="display: block; margin-bottom: 6px; font-weight: 600;">WILL SHIP INTERNATIONAL?</label>
                            <select id="gunbroker_default_will_ship_international" name="gunbroker_default_will_ship_international" class="regular-text">
                                <option value="0" <?php selected(get_option('gunbroker_default_will_ship_international', false), false); ?>>No</option>
                                <option value="1" <?php selected(get_option('gunbroker_default_will_ship_international', false), true); ?>>Yes</option>
                            </select>
                        </div>

                        <div>
                            <label for="gunbroker_default_use_sales_tax" style="display: block; margin-bottom: 8px; font-weight: 600;">USE DEFAULT SALES TAX</label>
                            <select id="gunbroker_default_use_sales_tax" name="gunbroker_default_use_sales_tax" class="regular-text">
                                <option value="1" <?php selected(get_option('gunbroker_default_use_sales_tax', false), true); ?>>Yes</option>
                                <option value="0" <?php selected(get_option('gunbroker_default_use_sales_tax', false), false); ?>>No</option>
                            </select>
                        </div>
                        <div>
                            <label for="gunbroker_default_auto_relist" style="display: block; margin-bottom: 6px; font-weight: 600;">AUTO RELIST</label>
                            <select id="gunbroker_default_auto_relist" name="gunbroker_default_auto_relist" class="regular-text">
                                <option value="1" <?php selected(get_option('gunbroker_default_auto_relist', '2'), '1'); ?>>Do not relist</option>
                                <option value="2" <?php selected(get_option('gunbroker_default_auto_relist', '2'), '2'); ?>>Relist Fixed Price</option>
                            </select>
                        </div>
                        <div>
                            <label for="gunbroker_default_return_policy" style="display: block; margin-bottom: 8px; font-weight: 600;">INSPECTION PERIOD</label>
                            <select id="gunbroker_default_return_policy" name="gunbroker_default_return_policy" class="regular-text">
                                <option value="1" <?php selected(get_option('gunbroker_default_return_policy', '14'), '1'); ?>>AS IS - No refund or exchange</option>
                                <option value="2" <?php selected(get_option('gunbroker_default_return_policy', '14'), '2'); ?>>No refund but item can be returned for exchange or store credit within fourteen days</option>
                                <option value="3" <?php selected(get_option('gunbroker_default_return_policy', '14'), '3'); ?>>No refund but item can be returned for exchange or store credit within thirty days</option>
                                <option value="4" <?php selected(get_option('gunbroker_default_return_policy', '14'), '4'); ?>>Three Days from the date the item is received</option>
                                <option value="5" <?php selected(get_option('gunbroker_default_return_policy', '14'), '5'); ?>>Three Days from the date the item is received, including the cost of shipping</option>
                                <option value="6" <?php selected(get_option('gunbroker_default_return_policy', '14'), '6'); ?>>Five Days from the date the item is received</option>
                                <option value="7" <?php selected(get_option('gunbroker_default_return_policy', '14'), '7'); ?>>Five Days from the date the item is received, including the cost of shipping</option>
                                <option value="8" <?php selected(get_option('gunbroker_default_return_policy', '14'), '8'); ?>>Seven Days from the date the item is received</option>
                                <option value="9" <?php selected(get_option('gunbroker_default_return_policy', '14'), '9'); ?>>Seven Days from the date the item is received, including the cost of shipping</option>
                                <option value="10" <?php selected(get_option('gunbroker_default_return_policy', '14'), '10'); ?>>Fourteen Days from the date the item is received</option>
                                <option value="11" <?php selected(get_option('gunbroker_default_return_policy', '14'), '11'); ?>>Fourteen Days from the date the item is received, including the cost of shipping</option>
                                <option value="12" <?php selected(get_option('gunbroker_default_return_policy', '14'), '12'); ?>>30 day money back guarantee</option>
                                <option value="13" <?php selected(get_option('gunbroker_default_return_policy', '14'), '13'); ?>>30 day money back guarantee including the cost of shipping</option>
                                <option value="14" <?php selected(get_option('gunbroker_default_return_policy', '14'), '14'); ?>>Factory Warranty</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Payment Methods and Shipping Profiles -->
                <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; margin-bottom: 20px;">
                    <h2 style="margin-top: 0;">Payment Methods & Shipping</h2>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; align-items: start;">
                        <!-- Left column: Payment Methods + Standard Text ID -->
                        <div>
                            <h3 style="margin-top: 0;">Payment Methods</h3>
                            <?php 
                            $payment_methods_default = array('VisaMastercard', 'Check', 'Amex', 'Discover', 'CertifiedCheck', 'USPSMoneyOrder', 'MoneyOrder');
                            $payment_methods = get_option('gunbroker_payment_methods', $payment_methods_default);
                            if (!is_array($payment_methods) || count($payment_methods) === 0) {
                                $payment_methods = $payment_methods_default;
                            }
                            $payment_options = array(
                                'VisaMastercard' => 'Visa/Mastercard',
                                'Check' => 'Check',
                                'COD' => 'COD',
                                'Escrow' => 'Escrow',
                                'Amex' => 'Amex',
                                'PayPal' => 'PayPal',
                                'Discover' => 'Discover',
                                'SeeItemDesc' => 'See Item Description',
                                'CertifiedCheck' => 'Certified Check',
                                'USPSMoneyOrder' => 'USPS Money Order',
                                'MoneyOrder' => 'Money Order'
                            );
                            ?>
                            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 8px;">
                                <?php foreach ($payment_options as $value => $label): ?>
                                    <label>
                                        <input type="checkbox" name="gunbroker_payment_methods[]" value="<?php echo esc_attr($value); ?>" 
                                               <?php checked(in_array($value, $payment_methods)); ?> />
                                        <?php echo esc_html($label); ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>

                            <div style="margin-top: 25px;">
                                <h3 style="margin: 0 0 8px;">Standard Text ID</h3>
                                <p style="margin: 0 0 6px;">
                                    <a href="https://www.gunbroker.com/User/StandardText" target="_blank">Click here to view your GunBroker standard text entries</a>
                                </p>
                                <input type="text" name="gunbroker_standard_text_id" 
                                       value="<?php echo esc_attr(get_option('gunbroker_standard_text_id', '2769')); ?>"
                                       class="regular-text" placeholder="2769" />
                            </div>
                        </div>

                        <!-- Right column: Shipping Profiles + Mapping -->
                        <div>
                            <h3 style="margin-top: 0;">Shipping Profiles</h3>
                            <p style="margin: 0 0 6px;"><a href="https://www.gunbroker.com/User/ShippingProfiles" target="_blank">Click here to view your Gunbroker Shipping Profile IDs</a></p>
                            <label style="display:block; margin-bottom:6px; font-weight:600;">GunBroker Shipping Profile IDs</label>
                            <input type="text" name="gunbroker_shipping_profile_ids" 
                                   value="<?php echo esc_attr(get_option('gunbroker_shipping_profile_ids', '3153,4018,2814')); ?>"
                                   class="regular-text" placeholder="3153,4018,2814" />

                            <div style="margin-top: 22px;">
                                <h3 style="margin: 0 0 8px;">Shipping Profile Mapping</h3>
                                <p style="margin: 0 0 14px; color:#555;">Map each of your Gunbroker shipping profiles to a Gunprime shipping category. Your Gunbroker listing shipping profile will default to the corresponding shipping category assigned to the product on Gunprime.</p>

                                <div style="display: grid; grid-template-columns: 1fr; gap: 14px;">
                                    <div>
                                        <label style="display:block; margin-bottom:6px; font-weight:600;">ACCESSORIES $15 +7.5</label>
                                        <select name="gunbroker_accessories_shipping" class="regular-text">
                                            <option value="Accessories" <?php selected(get_option('gunbroker_accessories_shipping', 'Accessories'), 'Accessories'); ?>>Accessories</option>
                                            <option value="Guns" <?php selected(get_option('gunbroker_accessories_shipping', 'Accessories'), 'Guns'); ?>>Guns</option>
                                            <option value="Long Guns (UGB and similar)" <?php selected(get_option('gunbroker_accessories_shipping', 'Accessories'), 'Long Guns (UGB and similar)'); ?>>Long Guns (UGB and similar)</option>
                                            <option value="Free Shipping" <?php selected(get_option('gunbroker_accessories_shipping', 'Accessories'), 'Free Shipping'); ?>>Free Shipping</option>
                                            <option value="Small Package" <?php selected(get_option('gunbroker_accessories_shipping', 'Accessories'), 'Small Package'); ?>>Small Package</option>
                                            <option value="$10 per shipment" <?php selected(get_option('gunbroker_accessories_shipping', 'Accessories'), '$10 per shipment'); ?>>$10 per shipment</option>
                                            <option value="$15 per shipment" <?php selected(get_option('gunbroker_accessories_shipping', 'Accessories'), '$15 per shipment'); ?>>$15 per shipment</option>
                                            <option value="$25 per shipment lower 48" <?php selected(get_option('gunbroker_accessories_shipping', 'Accessories'), '$25 per shipment lower 48'); ?>>$25 per shipment lower 48</option>
                                            <option value="$20 per shipment" <?php selected(get_option('gunbroker_accessories_shipping', 'Accessories'), '$20 per shipment'); ?>>$20 per shipment</option>
                                            <option value="$40 Ammo" <?php selected(get_option('gunbroker_accessories_shipping', 'Accessories'), '$40 Ammo'); ?>>$40 Ammo</option>
                                            <option value="$15 per item" <?php selected(get_option('gunbroker_accessories_shipping', 'Accessories'), '$15 per item'); ?>>$15 per item</option>
                                            <option value="250 per item" <?php selected(get_option('gunbroker_accessories_shipping', 'Accessories'), '250 per item'); ?>>250 per item</option>
                                            <option value="250 per shipment" <?php selected(get_option('gunbroker_accessories_shipping', 'Accessories'), '250 per shipment'); ?>>250 per shipment</option>
                                            <option value="500 per shipment" <?php selected(get_option('gunbroker_accessories_shipping', 'Accessories'), '500 per shipment'); ?>>500 per shipment</option>
                                            <option value="500 per item" <?php selected(get_option('gunbroker_accessories_shipping', 'Accessories'), '500 per item'); ?>>500 per item</option>
                                            <option value="17.50 per shipment" <?php selected(get_option('gunbroker_accessories_shipping', 'Accessories'), '17.50 per shipment'); ?>>17.50 per shipment</option>
                                            <option value="Flexible Rate (Small Cases)" <?php selected(get_option('gunbroker_accessories_shipping', 'Accessories'), 'Flexible Rate (Small Cases)'); ?>>Flexible Rate (Small Cases)</option>
                                            <option value="Flexible Rate (Large Boxes)" <?php selected(get_option('gunbroker_accessories_shipping', 'Accessories'), 'Flexible Rate (Large Boxes)'); ?>>Flexible Rate (Large Boxes)</option>
                                            <option value="Flexible Rate (Small Boxes)" <?php selected(get_option('gunbroker_accessories_shipping', 'Accessories'), 'Flexible Rate (Small Boxes)'); ?>>Flexible Rate (Small Boxes)</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label style="display:block; margin-bottom:6px; font-weight:600;">FREE SHIPPING GROUND</label>
                                        <select name="gunbroker_free_shipping_ground" class="regular-text">
                                            <option value="Free Shipping" <?php selected(get_option('gunbroker_free_shipping_ground', 'Free Shipping'), 'Free Shipping'); ?>>Free Shipping</option>
                                            <option value="Guns" <?php selected(get_option('gunbroker_free_shipping_ground', 'Free Shipping'), 'Guns'); ?>>Guns</option>
                                            <option value="Accessories" <?php selected(get_option('gunbroker_free_shipping_ground', 'Free Shipping'), 'Accessories'); ?>>Accessories</option>
                                            <option value="Small Package" <?php selected(get_option('gunbroker_free_shipping_ground', 'Free Shipping'), 'Small Package'); ?>>Small Package</option>
                                            <option value="$10 per shipment" <?php selected(get_option('gunbroker_free_shipping_ground', 'Free Shipping'), '$10 per shipment'); ?>>$10 per shipment</option>
                                            <option value="$15 per shipment" <?php selected(get_option('gunbroker_free_shipping_ground', 'Free Shipping'), '$15 per shipment'); ?>>$15 per shipment</option>
                                            <option value="$25 per shipment lower 48" <?php selected(get_option('gunbroker_free_shipping_ground', 'Free Shipping'), '$25 per shipment lower 48'); ?>>$25 per shipment lower 48</option>
                                            <option value="$20 per shipment" <?php selected(get_option('gunbroker_free_shipping_ground', 'Free Shipping'), '$20 per shipment'); ?>>$20 per shipment</option>
                                            <option value="$40 Ammo" <?php selected(get_option('gunbroker_free_shipping_ground', 'Free Shipping'), '$40 Ammo'); ?>>$40 Ammo</option>
                                            <option value="Long Guns (UGB and similar)" <?php selected(get_option('gunbroker_free_shipping_ground', 'Free Shipping'), 'Long Guns (UGB and similar)'); ?>>Long Guns (UGB and similar)</option>
                                            <option value="$15 per item" <?php selected(get_option('gunbroker_free_shipping_ground', 'Free Shipping'), '$15 per item'); ?>>$15 per item</option>
                                            <option value="250 per item" <?php selected(get_option('gunbroker_free_shipping_ground', 'Free Shipping'), '250 per item'); ?>>250 per item</option>
                                            <option value="250 per shipment" <?php selected(get_option('gunbroker_free_shipping_ground', 'Free Shipping'), '250 per shipment'); ?>>250 per shipment</option>
                                            <option value="500 per shipment" <?php selected(get_option('gunbroker_free_shipping_ground', 'Free Shipping'), '500 per shipment'); ?>>500 per shipment</option>
                                            <option value="500 per item" <?php selected(get_option('gunbroker_free_shipping_ground', 'Free Shipping'), '500 per item'); ?>>500 per item</option>
                                            <option value="17.50 per shipment" <?php selected(get_option('gunbroker_free_shipping_ground', 'Free Shipping'), '17.50 per shipment'); ?>>17.50 per shipment</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label style="display:block; margin-bottom:6px; font-weight:600;">EVERYTHING</label>
                                        <select name="gunbroker_everything" class="regular-text">
                                            <option value="Guns" <?php selected(get_option('gunbroker_everything', 'Guns'), 'Guns'); ?>>Guns</option>
                                            <option value="Accessories" <?php selected(get_option('gunbroker_everything', 'Guns'), 'Accessories'); ?>>Accessories</option>
                                            <option value="Free Shipping" <?php selected(get_option('gunbroker_everything', 'Guns'), 'Free Shipping'); ?>>Free Shipping</option>
                                            <option value="Small Package" <?php selected(get_option('gunbroker_everything', 'Guns'), 'Small Package'); ?>>Small Package</option>
                                            <option value="$10 per shipment" <?php selected(get_option('gunbroker_everything', 'Guns'), '$10 per shipment'); ?>>$10 per shipment</option>
                                            <option value="$15 per shipment" <?php selected(get_option('gunbroker_everything', 'Guns'), '$15 per shipment'); ?>>$15 per shipment</option>
                                            <option value="$25 per shipment lower 48" <?php selected(get_option('gunbroker_everything', 'Guns'), '$25 per shipment lower 48'); ?>>$25 per shipment lower 48</option>
                                            <option value="$20 per shipment" <?php selected(get_option('gunbroker_everything', 'Guns'), '$20 per shipment'); ?>>$20 per shipment</option>
                                            <option value="$40 Ammo" <?php selected(get_option('gunbroker_everything', 'Guns'), '$40 Ammo'); ?>>$40 Ammo</option>
                                            <option value="Long Guns (UGB and similar)" <?php selected(get_option('gunbroker_everything', 'Guns'), 'Long Guns (UGB and similar)'); ?>>Long Guns (UGB and similar)</option>
                                            <option value="$15 per item" <?php selected(get_option('gunbroker_everything', 'Guns'), '$15 per item'); ?>>$15 per item</option>
                                            <option value="250 per item" <?php selected(get_option('gunbroker_everything', 'Guns'), '250 per item'); ?>>250 per item</option>
                                            <option value="250 per shipment" <?php selected(get_option('gunbroker_everything', 'Guns'), '250 per shipment'); ?>>250 per shipment</option>
                                            <option value="500 per shipment" <?php selected(get_option('gunbroker_everything', 'Guns'), '500 per shipment'); ?>>500 per shipment</option>
                                            <option value="500 per item" <?php selected(get_option('gunbroker_everything', 'Guns'), '500 per item'); ?>>500 per item</option>
                                            <option value="17.50 per shipment" <?php selected(get_option('gunbroker_everything', 'Guns'), '17.50 per shipment'); ?>>17.50 per shipment</option>
                                            <option value="Flexible Rate (Small Cases)" <?php selected(get_option('gunbroker_everything', 'Guns'), 'Flexible Rate (Small Cases)'); ?>>Flexible Rate (Small Cases)</option>
                                            <option value="Flexible Rate (Large Boxes)" <?php selected(get_option('gunbroker_everything', 'Guns'), 'Flexible Rate (Large Boxes)'); ?>>Flexible Rate (Large Boxes)</option>
                                            <option value="Flexible Rate (Small Boxes)" <?php selected(get_option('gunbroker_everything', 'Guns'), 'Flexible Rate (Small Boxes)'); ?>>Flexible Rate (Small Boxes)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; margin-bottom: 20px;">
                    <h2 style="margin-top: 0;">Header/Footer</h2>
                    <p>The header text will be added to the top of each of your GunBroker listings and the footer will be added to the bottom. You can place your brand logo, auction terms, etc., and it will show up on each auction listing by default. HTML content will work in both.</p>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row" style="width: 50%; vertical-align: top;">
                                <label for="gunbroker_header_content">HEADER</label>
                            </th>
                            <th scope="row" style="width: 50%; vertical-align: top;">
                                <label for="gunbroker_footer_content">FOOTER</label>
                            </th>
                        </tr>
                        <tr>
                            <td style="width: 50%; vertical-align: top; padding-right: 10px;">
                                <?php
                                $header_content = get_option('gunbroker_header_content', '');
                                wp_editor($header_content, 'gunbroker_header_content', array(
                                    'textarea_name' => 'gunbroker_header_content',
                                    'media_buttons' => true,
                                    'textarea_rows' => 10,
                                    'teeny' => false,
                                    'tinymce' => array(
                                        'toolbar1' => 'formatselect,bold,italic,underline,strikethrough,|,bullist,numlist,blockquote,|,link,unlink,|,spellchecker,fullscreen,wp_adv',
                                        'toolbar2' => 'forecolor,backcolor,|,fontselect,fontsizeselect,|,pastetext,removeformat,|,charmap,|,outdent,indent,|,undo,redo,wp_help'
                                    )
                                ));
                                ?>
                            </td>
                            <td style="width: 50%; vertical-align: top; padding-left: 10px;">
                                <?php
                                $footer_content = get_option('gunbroker_footer_content', '');
                                wp_editor($footer_content, 'gunbroker_footer_content', array(
                                    'textarea_name' => 'gunbroker_footer_content',
                                    'media_buttons' => true,
                                    'textarea_rows' => 10,
                                    'teeny' => false,
                                    'tinymce' => array(
                                        'toolbar1' => 'formatselect,bold,italic,underline,strikethrough,|,bullist,numlist,blockquote,|,link,unlink,|,spellchecker,fullscreen,wp_adv',
                                        'toolbar2' => 'forecolor,backcolor,|,fontselect,fontsizeselect,|,pastetext,removeformat,|,charmap,|,outdent,indent,|,undo,redo,wp_help'
                                    )
                                ));
                                ?>
                            </td>
                        </tr>
                    </table>
                </div>

                <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; margin-bottom: 20px;">
                    <h2 style="margin-top: 0;">Advanced Options</h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">Public Domain for Images</th>
                            <td>
                                <input type="text" name="gunbroker_public_domain" value="<?php echo esc_attr(get_option('gunbroker_public_domain', '')); ?>" 
                                       placeholder="your-domain.com" class="regular-text" />
                                <p class="description">
                                    Enter your public domain (e.g., your-domain.com) for image URLs. 
                                    Required for GunBroker to access product images. Leave empty to skip images in localhost environment.
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>

                <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4;">
                    <h2 style="margin-top: 0;">Advanced Options</h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">Mode</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="gunbroker_sandbox_mode" value="1"
                                        <?php checked(get_option('gunbroker_sandbox_mode', true)); ?> />
                                    Sandbox/Test Mode
                                </label>
                                <p class="description">
                                    <strong style="color: #d63638;">Important:</strong> Uncheck this when you're ready to create live listings
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>

                <?php submit_button('Save Settings'); ?>
            </form>

            <!-- DEBUG TOOLS -->
            <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 20px; margin: 20px 0;">
                <h3>Debug Tools</h3>
                <button type="button" id="debug-credentials" class="button">Check Stored Credentials</button>
                <button type="button" id="test-raw-auth" class="button">Test Raw Authentication</button>

                <div id="debug-results" style="margin-top: 15px; font-family: monospace; font-size: 12px; background: #f9f9f9; padding: 10px; border: 1px solid #ddd; max-height: 400px; overflow-y: auto;">
                    Click a button above to see debug information...
                </div>
            </div>

            <!-- CACHE MANAGEMENT -->
            <div style="background: #e7f3ff; border: 1px solid #b3d9ff; padding: 20px; margin: 20px 0;">
                <h3>Category Cache Management</h3>
                <p style="margin: 0 0 15px 0; color: #666;">
                    Categories are cached for 24 hours to improve performance. Clear cache if you need fresh data.
                </p>
                <button type="button" id="clear-category-cache" class="button button-secondary">Clear Category Cache</button>
                <div id="cache-results" style="margin-top: 15px; font-family: monospace; font-size: 12px; background: #f9f9f9; padding: 10px; border: 1px solid #ddd; max-height: 200px; overflow-y: auto; display: none;">
                    <!-- Cache results will appear here -->
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div>
            <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; margin-bottom: 20px;">
                <h3 style="margin-top: 0;">Connection Status</h3>
                <?php
                $settings = new GunBroker_Settings();
                $api = new GunBroker_API();
                ?>

                <p><strong>Configuration:</strong>
                    <?php if ($settings->is_configured()): ?>
                        <span style="color: green;">✓ Complete</span>
                    <?php else: ?>
                        <span style="color: red;">✗ Incomplete</span>
                    <?php endif; ?>
                </p>

                <p><strong>API Connection:</strong>
                    <span id="connection-status">Testing...</span>
                </p>

                <button type="button" id="test-connection" class="button button-secondary" style="width: 100%;">
                    Test Connection
                </button>

                <div id="connection-result" style="margin-top: 10px;"></div>
            </div>

            <div style="background: #f0f8ff; border: 1px solid #b8daff; padding: 20px;">
                <h3 style="margin-top: 0; color: #0066cc;">Quick Start</h3>
                <ol style="margin: 0; padding-left: 20px;">
                    <li>Enter your API credentials above</li>
                    <li>Test the connection</li>
                    <li>Go to <strong>List Products</strong> to start listing</li>
                    <li>Monitor orders in <strong>Orders</strong> page</li>
                </ol>

                <div style="margin-top: 15px; padding: 10px; background: rgba(0,102,204,0.1); border-radius: 4px;">
                    <strong>Need help?</strong><br>
                    <a href="<?php echo admin_url('admin.php?page=gunbroker-help'); ?>">View setup guide →</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    jQuery(document).ready(function($) {
        console.log('GunBroker Settings page loaded');

        // Test connection on page load
        testConnection();

        $('#test-connection').click(function() {
            testConnection();
        });

        function testConnection() {
            const $status = $('#connection-status');
            const $button = $('#test-connection');
            const $result = $('#connection-result');

            $status.html('<span style="color: #666;">Testing...</span>');
            $button.prop('disabled', true).text('Testing...');
            $result.html('');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'gunbroker_test_connection',
                    nonce: '<?php echo wp_create_nonce("gunbroker_ajax_nonce"); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        $status.html('<span style="color: green;">✓ Connected</span>');
                        $result.html('<div style="padding: 10px; background: #d4edda; color: #155724; border-radius: 4px; margin-top: 10px;">Connection successful! You can now list products.</div>');
                    } else {
                        $status.html('<span style="color: red;">✗ Failed</span>');
                        $result.html('<div style="padding: 10px; background: #f8d7da; color: #721c24; border-radius: 4px; margin-top: 10px;">Error: ' + response.data + '</div>');
                    }
                },
                error: function() {
                    $status.html('<span style="color: red;">✗ Error</span>');
                    $result.html('<div style="padding: 10px; background: #f8d7da; color: #721c24; border-radius: 4px; margin-top: 10px;">Network error occurred</div>');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Test Connection');
                }
            });
        }

        // Debug buttons
        $('#debug-credentials').click(function() {
            console.log('Debug credentials clicked');
            $('#debug-results').html('Loading credentials...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'gunbroker_debug_credentials',
                    nonce: '<?php echo wp_create_nonce("gunbroker_ajax_nonce"); ?>'
                },
                success: function(response) {
                    console.log('Debug credentials response:', response);
                    $('#debug-results').html('<h4>Stored Credentials:</h4><pre>' + JSON.stringify(response.data, null, 2) + '</pre>');
                },
                error: function(xhr, status, error) {
                    console.log('Debug credentials error:', error);
                    $('#debug-results').html('<div style="color: red;">Error: ' + error + '</div>');
                }
            });
        });

        $('#test-raw-auth').click(function() {
            console.log('Test raw auth clicked');
            var button = $(this);
            button.prop('disabled', true).text('Testing...');
            $('#debug-results').html('Testing authentication...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'gunbroker_test_raw_auth',
                    nonce: '<?php echo wp_create_nonce("gunbroker_ajax_nonce"); ?>'
                },
                success: function(response) {
                    console.log('Test raw auth response:', response);
                    $('#debug-results').html('<h4>Authentication Test Results:</h4><pre>' + JSON.stringify(response.data, null, 2) + '</pre>');
                },
                error: function(xhr, status, error) {
                    console.log('Test raw auth error:', error);
                    $('#debug-results').html('<div style="color: red;">Error: ' + error + '</div>');
                },
                complete: function() {
                    button.prop('disabled', false).text('Test Raw Authentication');
                }
            });
        });

        $('#clear-category-cache').click(function() {
            clearCategoryCache();
        });

        function clearCategoryCache() {
            console.log('Clear category cache clicked');
            var button = $('#clear-category-cache');
            var $results = $('#cache-results');
            
            button.prop('disabled', true).text('Clearing...');
            $results.show().html('Clearing category cache...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'gunbroker_clear_category_cache',
                    nonce: '<?php echo wp_create_nonce("gunbroker_ajax_nonce"); ?>'
                },
                success: function(response) {
                    console.log('Clear cache response:', response);
                    if (response.success) {
                        $results.html('<div style="color: green;">✓ ' + response.data + '</div>');
                    } else {
                        $results.html('<div style="color: red;">✗ Error: ' + response.data + '</div>');
                    }
                },
                error: function(xhr, status, error) {
                    console.log('Clear cache error:', error);
                    $results.html('<div style="color: red;">✗ Error: ' + error + '</div>');
                },
                complete: function() {
                    button.prop('disabled', false).text('Clear Category Cache');
                }
            });
        }

    });
</script>