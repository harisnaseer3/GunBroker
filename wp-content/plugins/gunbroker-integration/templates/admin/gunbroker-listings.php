<div class="wrap">
    <h1>Browse GunBroker Listings</h1>

    <!-- Search Controls -->
    <div style="background: #fff; padding: 15px; border: 1px solid #ccd0d4; margin: 20px 0;">
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 15px; align-items: end;">
            <div>
                <label for="listing-type"><strong>View:</strong></label>
                <select id="listing-type">
                    <option value="user">My Listings</option>
                    <option value="search">Search All Listings</option>
                </select>
            </div>
            <div id="search-controls" style="display: none;">
                <label for="search-term"><strong>Search Term:</strong></label>
                <input type="text" id="search-term" placeholder="Enter keywords..." class="regular-text">
            </div>
            <div id="category-controls" style="display: none;">
                <label for="category-filter"><strong>Category:</strong></label>
                <select id="category-filter">
                    <option value="3022">Firearms - General</option>
                    <option value="3023">Handguns</option>
                    <option value="3024">Rifles</option>
                    <option value="3025">Shotguns</option>
                    <option value="3026">Accessories</option>
                    <option value="3027">Ammunition</option>
                </select>
            </div>
            <div>
                <button type="button" id="fetch-listings" class="button button-primary">Load Listings</button>
            </div>
        </div>
    </div>

    <!-- Results -->
    <div id="listings-container" style="background: #fff; border: 1px solid #ccd0d4; min-height: 400px;">
        <div id="listings-loading" style="display: none; padding: 40px; text-align: center;">
            <div class="spinner is-active" style="float: none; margin: 0 auto 20px;"></div>
            <p>Loading listings from GunBroker...</p>
        </div>

        <div id="listings-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; padding: 20px;">
            <!-- Listings will be loaded here -->
        </div>

        <div id="no-listings" style="padding: 40px; text-align: center; color: #666;">
            <h3>Click "Load Listings" to fetch data from GunBroker</h3>
        </div>
    </div>
</div>

<script>
    jQuery(document).ready(function($) {
        // Show/hide search controls
        $('#listing-type').change(function() {
            if ($(this).val() === 'search') {
                $('#search-controls, #category-controls').show();
            } else {
                $('#search-controls, #category-controls').hide();
            }
        });

        // Fetch listings
        $('#fetch-listings').click(function() {
            const listingType = $('#listing-type').val();
            const searchTerm = $('#search-term').val();
            const category = $('#category-filter').val();

            $('#listings-loading').show();
            $('#listings-grid').empty();
            $('#no-listings').hide();

            const data = {
                action: 'gunbroker_fetch_listings',
                listing_type: listingType,
                nonce: '<?php echo wp_create_nonce("gunbroker_ajax_nonce"); ?>'
            };

            if (listingType === 'search') {
                data.search_term = searchTerm;
                data.category = category;
            }

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: data,
                success: function(response) {
                    $('#listings-loading').hide();

                    if (response.success && response.data.listings.length > 0) {
                        displayListings(response.data.listings);
                    } else if (response.success) {
                        $('#no-listings').html('<h3>No listings found</h3><p>Try different search terms or check your connection.</p>').show();
                    } else {
                        $('#no-listings').html('<h3>Error loading listings</h3><p>' + response.data + '</p>').show();
                    }
                },
                error: function() {
                    $('#listings-loading').hide();
                    $('#no-listings').html('<h3>Network Error</h3><p>Failed to connect to GunBroker API.</p>').show();
                }
            });
        });

        function displayListings(listings) {
            const grid = $('#listings-grid');

            listings.forEach(function(listing) {
                const card = `
                <div style="border: 1px solid #ddd; border-radius: 8px; overflow: hidden; background: #fff;">
                    ${listing.image_url ?
                    `<img src="${listing.image_url}" style="width: 100%; height: 200px; object-fit: cover;">` :
                    `<div style="height: 200px; background: #f5f5f5; display: flex; align-items: center; justify-content: center; color: #999;">No Image</div>`
                }
                    <div style="padding: 15px;">
                        <h4 style="margin: 0 0 10px 0; font-size: 14px; line-height: 1.3;">
                            <a href="${listing.url}" target="_blank" style="text-decoration: none; color: #0073aa;">
                                ${listing.title}
                            </a>
                        </h4>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <strong>$${parseFloat(listing.price).toFixed(2)}</strong>
                            <span style="font-size: 12px; color: #666;">${listing.category}</span>
                        </div>
                        <div style="font-size: 12px; color: #666; margin-bottom: 10px;">
                            Condition: ${listing.condition}
                        </div>
                        <div style="font-size: 11px; color: #999;">
                            Ends: ${new Date(listing.end_date).toLocaleDateString()}
                        </div>
                    </div>
                </div>
            `;
                grid.append(card);
            });
        }
    });
</script>