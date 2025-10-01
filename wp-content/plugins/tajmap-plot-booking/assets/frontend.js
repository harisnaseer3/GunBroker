// TajMap Frontend JavaScript - Premium Customer Experience

(function($) {
    'use strict';

    console.log('TajMap Frontend JS loaded at:', new Date().toISOString());
    console.log('TajMap Frontend JS VERSION 2.0.3 - CACHE BUST TEST');
    console.log('If you see this message, the new version is loaded!');
    console.log('TajMapFrontend object:', typeof TajMapFrontend !== 'undefined' ? TajMapFrontend : 'UNDEFINED');

    // Global state
    let plots = [];
    let currentUser = null;
    let selectedPlot = null;
    let inquiryStep = 1;
    let savedPlots = [];
    
    // Map state
    let mapScale = 1;
    let mapPanX = 0;
    let mapPanY = 0;
    let mapWidth = 0;
    let mapHeight = 0;

    // Initialize when DOM is ready
    $(document).ready(function() {
        initializeApp();
    });

    function initializeApp() {
        // Check if user is logged in
        checkUserStatus();

        // Initialize based on current page
        const path = window.location.pathname;

        console.log('Current path:', path);
        console.log('TajMapFrontend.ajaxUrl:', TajMapFrontend.ajaxUrl);
        console.log('Checking routing...');
        
        if (path.includes('/plots') || path.includes('available-plots') || $('#interactive-map').length > 0) {
            console.log('Initializing Plot Selection page');
            initializePlotSelection();
        } else if (path.includes('/gallery')) {
            console.log('Initializing Gallery page');
            initializeGallery();
        } else if (path.includes('/dashboard')) {
            console.log('Initializing Dashboard page');
            initializeDashboard();
        } else {
            console.log('Initializing Landing page');
            // Landing page
            initializeLandingPage();
        }
    }

    // User Management
    function checkUserStatus() {
        $.post(TajMapFrontend.ajaxUrl, {
            action: 'tajmap_pb_check_user_status',
            nonce: TajMapFrontend.nonce
        }, function(response) {
            if (response.success && response.data.user) {
                currentUser = response.data.user;
                updateUserInterface();
            }
        });
    }

    function updateUserInterface() {
        if (currentUser) {
            $('.user-menu').show();
            $('.auth-required').prop('disabled', false);
            $('#user-name').text(currentUser.first_name + ' ' + currentUser.last_name);
        } else {
            $('.user-menu').hide();
            $('.auth-required').prop('disabled', true);
        }
    }

    // Landing Page Functions
    function initializeLandingPage() {
        loadFeaturedPlots();
        loadStats();
        initializeSearch();
    }

    function loadFeaturedPlots() {
        console.log('loadFeaturedPlots called but disabled to prevent errors');
        // Disabled to prevent 403 errors and slice errors
        return;
    }

    function renderFeaturedCarousel(plots) {
        const track = $('#carousel-track');
        track.empty();

        plots.forEach(plot => {
            const card = $(`
                <div class="featured-plot-card">
                    <div class="plot-image">
                        ${plot.base_image_url ? `<img src="${plot.base_image_url}" alt="${plot.plot_name}">` : '<div class="no-image">No Image</div>'}
                    </div>
                    <div class="plot-info">
                        <h3>${plot.plot_name}</h3>
                        <div class="plot-details">
                            <span class="plot-sector">${plot.sector || 'N/A'}</span>
                            <span class="plot-block">${plot.block || 'N/A'}</span>
                            <span class="plot-status ${plot.status}">${plot.status}</span>
                        </div>
                        <button class="btn primary small" onclick="viewPlotDetails(${plot.id})">View Details</button>
                    </div>
                </div>
            `);
            track.append(card);
        });

        initializeCarousel();
    }

    function initializeCarousel() {
        const track = $('#carousel-track');
        const prevBtn = $('#carousel-prev');
        const nextBtn = $('#carousel-next');
        let currentIndex = 0;
        const cardWidth = 320; // card width + gap
        const visibleCards = 3;

        function updateCarousel() {
            const translateX = -currentIndex * cardWidth;
            track.css('transform', `translateX(${translateX}px)`);
        }

        prevBtn.on('click', function() {
            currentIndex = Math.max(0, currentIndex - 1);
            updateCarousel();
        });

        nextBtn.on('click', function() {
            const maxIndex = Math.max(0, plots.length - visibleCards);
            currentIndex = Math.min(maxIndex, currentIndex + 1);
            updateCarousel();
        });
    }

    function loadStats() {
        console.log('loadStats called but disabled to prevent errors');
        // Disabled to prevent 403 errors
        return;
    }

    function initializeSearch() {
        $('#search-btn').on('click', function() {
            const sector = $('#search-sector').val();
            const block = $('#search-block').val();
            const status = $('#search-status').val();

            window.location.href = `/plots?sector=${sector}&block=${block}&status=${status}`;
        });

        $('#search-sector, #search-block, #search-status').on('keypress', function(e) {
            if (e.which === 13) {
                $('#search-btn').click();
            }
        });
    }

    // Plot Selection Page Functions
    function initializePlotSelection() {
        console.log('Initializing plot selection...');
        initializeMap();
        
        // Test AJAX connection first
        testAjaxConnection();
        
        loadPlots();
        initializeFilters();
        initializeMapControls();
        initializeModals();
        
        // Fallback timeout in case AJAX fails
        setTimeout(function() {
            if ($('#loading-overlay').is(':visible')) {
                console.log('AJAX timeout - trying fallback');
                $('#loading-overlay').html('<p>Loading timeout. Please refresh the page.</p>');
            }
        }, 10000);
    }
    
    function testAjaxConnection() {
        console.log('Testing AJAX connection...');
        $.post(TajMapFrontend.ajaxUrl, {
            action: 'tajmap_pb_test_ajax'
        }, function(response) {
            console.log('AJAX Test Response:', response);
        }).fail(function(xhr, status, error) {
            console.error('AJAX Test Failed:', status, error, xhr.responseText);
        });
    }

    function initializeMap() {
        const mapContainer = $('#interactive-map');
        
        // Set up map container dimensions
        mapWidth = mapContainer.width();
        mapHeight = mapContainer.height();
        
        // Create SVG if it doesn't exist
        if (mapContainer.find('svg').length === 0) {
            mapContainer.html(`
                <svg id="plots-svg" xmlns="http://www.w3.org/2000/svg" 
                     width="${mapWidth}" height="${mapHeight}" 
                     viewBox="0 0 ${mapWidth} ${mapHeight}"
                     style="background: #f8fafc;">
                </svg>
            `);
        }
        
        // Add mouse wheel zoom
        mapContainer.on('wheel', handleMapWheel);
        
        // Add pan functionality
        let isPanning = false;
        let startX = 0, startY = 0;
        
        mapContainer.on('mousedown', function(e) {
            if (e.target.tagName === 'svg' || e.target.tagName === 'g') {
                isPanning = true;
                startX = e.clientX - mapPanX;
                startY = e.clientY - mapPanY;
                mapContainer.css('cursor', 'grabbing');
            }
        });
        
        $(document).on('mousemove', function(e) {
            if (isPanning) {
                mapPanX = e.clientX - startX;
                mapPanY = e.clientY - startY;
                updateMapTransform();
            }
        });
        
        $(document).on('mouseup', function() {
            isPanning = false;
            mapContainer.css('cursor', 'grab');
        });
        
        // Hide loading overlay
        $('#loading-overlay').hide();
        
        // Handle window resize
        $(window).on('resize', function() {
            const mapContainer = $('#interactive-map');
            mapWidth = mapContainer.width();
            mapHeight = mapContainer.height();
            
            const svg = mapContainer.find('svg');
            if (svg.length > 0) {
                svg.attr('width', mapWidth).attr('height', mapHeight);
            }
        });
    }

    function loadPlots() {
        // Check if required variables are available
        if (typeof TajMapFrontend === 'undefined') {
            console.error('TajMapFrontend not defined');
            $('#loading-overlay').html('<p>Configuration error. Please refresh the page.</p>');
            return;
        }
        
        if (!TajMapFrontend.ajaxUrl || !TajMapFrontend.nonce) {
            console.error('Missing AJAX configuration:', TajMapFrontend);
            $('#loading-overlay').html('<p>Configuration error. Please refresh the page.</p>');
            return;
        }

        const params = new URLSearchParams(window.location.search);
        const filters = {
            sector: params.get('sector') || '',
            block: params.get('block') || '',
            status: params.get('status') || '',
            search: params.get('search') || ''
        };

        // Show loading overlay
        $('#loading-overlay').show();

        console.log('Loading plots with filters:', filters);
        console.log('AJAX URL:', TajMapFrontend.ajaxUrl);
        console.log('Nonce:', TajMapFrontend.nonce);
        console.log('Request data:', {
            action: 'tajmap_pb_get_plots',
            filters: filters
        });
        
        // URL should now be correct
        console.log('Using AJAX URL:', TajMapFrontend.ajaxUrl);
        
        $.post(TajMapFrontend.ajaxUrl, {
            action: 'tajmap_pb_get_plots',
            filters: filters
        }, function(response) {
            console.log('AJAX Response:', response);
            if (response && response.success) {
                plots = response.data && response.data.plots ? response.data.plots : [];
                console.log('Plots loaded:', plots.length);
                renderMap();
                updateFiltersList();
                updateResultsCount();
                $('#loading-overlay').hide();
            } else {
                console.error('Failed to load plots:', response);
                $('#loading-overlay').html('<p>Error loading plots: ' + (response && response.data ? response.data : 'Unknown error') + '</p>');
            }
        }).fail(function(xhr, status, error) {
            console.error('AJAX request failed:', status, error);
            console.error('Response:', xhr.responseText);
            console.error('Status Code:', xhr.status);
            console.error('Response Headers:', xhr.getAllResponseHeaders());
            $('#loading-overlay').html('<p>Network error (Status: ' + xhr.status + '). Please check console for details.</p>');
        });
    }

    function renderMap() {
        const svgElement = $('#plots-svg');
        if (svgElement.length === 0) {
            console.error('SVG element not found');
            return;
        }

        // Clear existing plots
        svgElement.find('.plot-polygon').remove();

        // Calculate bounds for all plots
        let minX = Infinity, maxX = -Infinity, minY = Infinity, maxY = -Infinity;
        let hasValidPlots = false;

        plots.forEach(plot => {
            if (plot.coordinates) {
                try {
                    const coordinates = JSON.parse(plot.coordinates);
                    if (coordinates.length >= 3) {
                        hasValidPlots = true;
                        coordinates.forEach(coord => {
                            minX = Math.min(minX, coord.x);
                            maxX = Math.max(maxX, coord.x);
                            minY = Math.min(minY, coord.y);
                            maxY = Math.max(maxY, coord.y);
                        });
                    }
                } catch (e) {
                    console.error('Error parsing coordinates for plot:', plot.id);
                }
            }
        });

        if (!hasValidPlots) {
            // Show message when no plots
            svgElement.html(`
                <text x="50%" y="50%" text-anchor="middle" fill="#64748b" font-size="18">
                    No plots available
                </text>
            `);
            return;
        }

        // Add some padding
        const padding = 50;
        minX -= padding;
        maxX += padding;
        minY -= padding;
        maxY += padding;

        // Calculate scale to fit all plots
        const plotWidth = maxX - minX;
        const plotHeight = maxY - minY;
        const scaleX = mapWidth / plotWidth;
        const scaleY = mapHeight / plotHeight;
        const scale = Math.min(scaleX, scaleY, 1) * 0.8; // 80% of available space

        // Center the plots
        const centerX = (mapWidth - plotWidth * scale) / 2;
        const centerY = (mapHeight - plotHeight * scale) / 2;

        // Create a group for all plots with transform
        const plotGroup = svgElement.find('.plot-group');
        if (plotGroup.length === 0) {
            svgElement.append('<g class="plot-group"></g>');
        }

        const group = svgElement.find('.plot-group');
        group.attr('transform', `translate(${centerX - minX * scale}, ${centerY - minY * scale}) scale(${scale})`);

        // Render each plot
        plots.forEach(plot => {
            if (plot.coordinates) {
                try {
                    const coordinates = JSON.parse(plot.coordinates);
                    if (coordinates.length >= 3) {
                        const points = coordinates.map(coord => `${coord.x},${coord.y}`).join(' ');
                        
                        // Determine colors based on status
                        let fillColor, strokeColor;
                        switch (plot.status) {
                            case 'sold':
                                fillColor = '#ef4444';
                                strokeColor = '#dc2626';
                                break;
                            case 'available':
                            default:
                                fillColor = '#10b981';
                                strokeColor = '#059669';
                                break;
                        }

                        const polygon = $(`
                            <polygon
                                points="${points}"
                                data-id="${plot.id}"
                                data-status="${plot.status}"
                                class="plot-polygon ${plot.status}"
                                fill="${fillColor}"
                                stroke="${strokeColor}"
                                fill-opacity="0.6"
                                stroke-width="2"
                                stroke-linejoin="round"
                            ></polygon>
                        `);

                        polygon.on('click', function(e) {
                            e.stopPropagation();
                            selectPlot(plot.id);
                        }).on('mouseenter', function() {
                            $(this).attr('fill-opacity', '0.8');
                            showPlotTooltip(plot, $(this));
                        }).on('mouseleave', function() {
                            $(this).attr('fill-opacity', '0.6');
                            hidePlotTooltip();
                        });

                        group.append(polygon);
                    }
                } catch (e) {
                    console.error('Error parsing coordinates for plot:', plot.id);
                }
            }
        });

        // Reset map transform
        mapScale = 1;
        mapPanX = 0;
        mapPanY = 0;
        updateMapTransform();
    }

    function selectPlot(plotId) {
        const plot = plots.find(p => p.id == plotId);
        if (!plot) return;

        selectedPlot = plot;

        // Update visual selection
        $('.plot-polygon').removeClass('selected');
        $(`.plot-polygon[data-id="${plotId}"]`).addClass('selected');

        // Show plot details
        showPlotDetails(plot);

        // Open inquiry modal if plot is available
        if (plot.status === 'available') {
            openInquiryModal();
        }
    }

    function showPlotDetails(plot) {
        const panel = $('#plot-details-panel');
        const content = $('#panel-content');

        content.html(`
            <div class="plot-details">
                <h3>${plot.plot_name}</h3>
                <div class="plot-meta">
                    <div class="meta-item">
                        <label>Sector:</label>
                        <span>${plot.sector || 'N/A'}</span>
                    </div>
                    <div class="meta-item">
                        <label>Block:</label>
                        <span>${plot.block || 'N/A'}</span>
                    </div>
                    <div class="meta-item">
                        <label>Street:</label>
                        <span>${plot.street || 'N/A'}</span>
                    </div>
                    <div class="meta-item">
                        <label>Status:</label>
                        <span class="status-badge ${plot.status}">${plot.status}</span>
                    </div>
                </div>
                <div class="plot-actions">
                    <button class="btn primary" onclick="openInquiryModal()">Express Interest</button>
                    ${currentUser ? `<button class="btn secondary" onclick="savePlot(${plot.id})">Save Plot</button>` : ''}
                </div>
            </div>
        `);

        panel.show();
    }

    function initializeFilters() {
        $('#apply-filters').on('click', function() {
            applyFilters();
        });

        $('#clear-filters').on('click', function() {
            clearFilters();
        });

        $('#filter-search').on('input', debounce(function() {
            applyFilters();
        }, 300));
    }

    function applyFilters() {
        const filters = {
            sector: $('#filter-sector').val(),
            block: $('#filter-block').val(),
            status: $('#filter-status').val(),
            search: $('#filter-search').val()
        };

        // Update URL
        const params = new URLSearchParams();
        Object.keys(filters).forEach(key => {
            if (filters[key]) {
                params.set(key, filters[key]);
            }
        });

        const newUrl = `${window.location.pathname}?${params.toString()}`;
        window.history.pushState({}, '', newUrl);

        // Reload plots
        loadPlots();
    }

    function clearFilters() {
        $('#filter-sector, #filter-block, #filter-status, #filter-search').val('');
        window.history.pushState({}, '', window.location.pathname);
        loadPlots();
    }

    function updateFiltersList() {
        const sectors = [...new Set(plots.map(p => p.sector).filter(Boolean))];
        const blocks = [...new Set(plots.map(p => p.block).filter(Boolean))];

        const sectorSelect = $('#filter-sector');
        const blockSelect = $('#filter-block');

        sectorSelect.empty();
        sectorSelect.append('<option value="">All Sectors</option>');
        sectors.forEach(sector => {
            sectorSelect.append(`<option value="${sector}">${sector}</option>`);
        });

        blockSelect.empty();
        blockSelect.append('<option value="">All Blocks</option>');
        blocks.forEach(block => {
            blockSelect.append(`<option value="${block}">${block}</option>`);
        });
    }

    function updateResultsCount() {
        $('#results-count').text(`Showing ${plots.length} plot${plots.length !== 1 ? 's' : ''}`);
    }

    function initializeMapControls() {
        $('#zoom-in').on('click', function() {
            mapScale = Math.min(mapScale * 1.2, 5);
            updateMapTransform();
        });

        $('#zoom-out').on('click', function() {
            mapScale = Math.max(mapScale / 1.2, 0.1);
            updateMapTransform();
        });

        $('#fit-view').on('click', function() {
            fitToView();
        });

        $('#view-map').on('click', function() {
            $('#list-view').hide();
            $('#interactive-map').show();
            $(this).addClass('active');
            $('#view-list').removeClass('active');
        });

        $('#view-list').on('click', function() {
            $('#interactive-map').hide();
            $('#list-view').show();
            renderListView();
            $(this).addClass('active');
            $('#view-map').removeClass('active');
        });
    }

    function handleMapWheel(e) {
        e.preventDefault();
        const delta = e.originalEvent.deltaY;
        const zoomFactor = delta > 0 ? 0.9 : 1.1;
        mapScale = Math.max(0.1, Math.min(5, mapScale * zoomFactor));
        updateMapTransform();
    }

    function updateMapTransform() {
        const group = $('#plots-svg .plot-group');
        if (group.length > 0) {
            const currentTransform = group.attr('transform') || '';
            const translateMatch = currentTransform.match(/translate\(([^,]+),([^)]+)\)/);
            const scaleMatch = currentTransform.match(/scale\(([^)]+)\)/);
            
            let translateX = translateMatch ? parseFloat(translateMatch[1]) : 0;
            let translateY = translateMatch ? parseFloat(translateMatch[2]) : 0;
            let baseScale = scaleMatch ? parseFloat(scaleMatch[1]) : 1;
            
            // Apply pan
            translateX += mapPanX;
            translateY += mapPanY;
            
            // Apply zoom
            const finalScale = baseScale * mapScale;
            
            group.attr('transform', `translate(${translateX}, ${translateY}) scale(${finalScale})`);
        }
    }

    function fitToView() {
        mapScale = 1;
        mapPanX = 0;
        mapPanY = 0;
        updateMapTransform();
    }

    function renderListView() {
        const container = $('#plots-list');
        container.empty();

        plots.forEach(plot => {
            const item = $(`
                <div class="plot-list-item" data-id="${plot.id}">
                    <div class="plot-info">
                        <h4>${plot.plot_name}</h4>
                        <div class="plot-meta">
                            <span class="plot-sector">${plot.sector || 'N/A'}</span>
                            <span class="plot-block">${plot.block || 'N/A'}</span>
                            <span class="plot-status ${plot.status}">${plot.status}</span>
                        </div>
                    </div>
                    <div class="plot-actions">
                        <button class="btn small primary" onclick="selectPlot(${plot.id})">View</button>
                    </div>
                </div>
            `);
            container.append(item);
        });
    }

    function showPlotTooltip(plot, element) {
        const tooltip = $('#plot-tooltip');
        tooltip.html(`
            <strong>${plot.plot_name}</strong><br>
            Sector: ${plot.sector || 'N/A'}<br>
            Block: ${plot.block || 'N/A'}<br>
            Status: ${plot.status}
        `).show();

        const rect = element[0].getBoundingClientRect();
        tooltip.css({
            left: rect.left + rect.width / 2,
            top: rect.top - 10
        });
    }

    function hidePlotTooltip() {
        $('#plot-tooltip').hide();
    }

    // Modal Functions
    function initializeModals() {
        $('#panel-close').on('click', function() {
            $('#plot-details-panel').hide();
            $('.plot-polygon').removeClass('selected');
            selectedPlot = null;
        });

        $('#modal-close').on('click', closeInquiryModal);
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                closeInquiryModal();
            }
        });
    }

    function openInquiryModal() {
        if (!selectedPlot) return;

        $('#inquiry-modal').addClass('active');
        inquiryStep = 1;
        showInquiryStep(1);
    }

    function closeInquiryModal() {
        $('#inquiry-modal').removeClass('active');
        selectedPlot = null;
    }

    function showInquiryStep(step) {
        $('.modal-step').removeClass('active');
        $(`#step-${step}`).addClass('active');
        inquiryStep = step;

        // Update progress indicator
        $('.progress-step').removeClass('active');
        $(`.progress-step[data-step="${step}"]`).addClass('active');
    }

    function nextStep() {
        if (inquiryStep < 3) {
            showInquiryStep(inquiryStep + 1);
        }
    }

    function prevStep() {
        if (inquiryStep > 1) {
            showInquiryStep(inquiryStep - 1);
        }
    }

    $('#inquiry-form').on('submit', function(e) {
        e.preventDefault();

        const formData = $(this).serializeArray();
        const data = {};
        formData.forEach(field => {
            data[field.name] = field.value;
        });
        data.plot_id = selectedPlot.id;

        // Save user if not exists
        if (!currentUser) {
            data.email = data.email;
            data.first_name = data.first_name;
            data.last_name = data.last_name;
            data.phone = data.phone;

            $.post(TajMapFrontend.ajaxUrl, {
                action: 'tajmap_pb_save_user',
                nonce: TajMapFrontend.nonce,
                ...data
            }, function(response) {
                if (response.success) {
                    currentUser = { id: response.data.user_id };
                    submitInquiry(data);
                } else {
                    alert('Error saving user information');
                }
            });
        } else {
            submitInquiry(data);
        }
    });

    function submitInquiry(data) {
        $.post(TajMapFrontend.ajaxUrl, {
            action: 'tajmap_pb_save_lead',
            nonce: TajMapFrontend.nonce,
            ...data
        }, function(response) {
            if (response.success) {
                showInquiryStep(3);
                // Show confirmation with plot details
                $('#confirmation-details').html(`
                    <div class="confirmation-plot">
                        <h4>${selectedPlot.plot_name}</h4>
                        <p>Sector: ${selectedPlot.sector || 'N/A'}</p>
                        <p>Block: ${selectedPlot.block || 'N/A'}</p>
                    </div>
                `);
            } else {
                alert('Error submitting inquiry');
            }
        });
    }

    // Gallery Functions
    function initializeGallery() {
        loadGalleryItems();
    }

    function loadGalleryItems() {
        // Mock gallery data - in real implementation, this would come from server
        const galleryItems = [
            { id: 1, title: 'Aerial View', category: 'aerial', image: 'https://via.placeholder.com/400x300', description: 'Beautiful aerial view of our development' },
            { id: 2, title: 'Club House', category: 'amenities', image: 'https://via.placeholder.com/400x300', description: 'Modern clubhouse facilities' },
            { id: 3, title: 'Infrastructure', category: 'infrastructure', image: 'https://via.placeholder.com/400x300', description: 'Well-planned infrastructure' }
        ];

        renderGallery(galleryItems);
        initializeLightbox();
    }

    function renderGallery(items) {
        const grid = $('#gallery-grid');
        grid.empty();

        items.forEach(item => {
            const galleryItem = $(`
                <div class="gallery-item" data-category="${item.category}" data-id="${item.id}">
                    <img src="${item.image}" alt="${item.title}" class="gallery-image">
                    <div class="gallery-info">
                        <h3 class="gallery-title">${item.title}</h3>
                        <p class="gallery-description">${item.description}</p>
                        <div class="gallery-meta">
                            <span>${item.category}</span>
                            <span>${new Date().toLocaleDateString()}</span>
                        </div>
                    </div>
                </div>
            `);

            galleryItem.on('click', function() {
                openLightbox(item.id);
            });

            grid.append(galleryItem);
        });
    }

    function initializeLightbox() {
        const overlay = $('#lightbox-modal');
        const closeBtn = $('#lightbox-close');
        const prevBtn = $('#lightbox-prev');
        const nextBtn = $('#lightbox-next');

        closeBtn.on('click', closeLightbox);
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                closeLightbox();
            }
        });

        // Navigation will be implemented based on current item
    }

    function openLightbox(itemId) {
        // Implementation for lightbox
        $('#lightbox-modal').addClass('active');
    }

    function closeLightbox() {
        $('#lightbox-modal').removeClass('active');
    }

    // Dashboard Functions
    function initializeDashboard() {
        if (!currentUser) {
            window.location.href = '/wp-login.php?redirect_to=' + encodeURIComponent(window.location.href);
            return;
        }

        loadDashboardData();
    }

    function loadDashboardData() {
        // Load saved plots
        $.post(TajMapFrontend.ajaxUrl, {
            action: 'tajmap_pb_get_saved_plots',
            nonce: TajMapFrontend.nonce,
            user_id: currentUser.id
        }, function(response) {
            if (response.success) {
                savedPlots = response.data.saved_plots;
                renderSavedPlots();
                updateDashboardStats();
            }
        });

        // Load inquiries
        loadInquiries();
    }

    function renderSavedPlots() {
        const grid = $('#saved-plots-grid');
        grid.empty();

        if (savedPlots.length === 0) {
            grid.html('<div class="no-saved-plots"><p>No saved plots yet</p></div>');
            return;
        }

        savedPlots.forEach(plot => {
            const item = $(`
                <div class="saved-plot-item" data-id="${plot.id}">
                    <h4>${plot.plot_name}</h4>
                    <p>Sector: ${plot.sector || 'N/A'}</p>
                    <p>Block: ${plot.block || 'N/A'}</p>
                    <span class="status-badge ${plot.status}">${plot.status}</span>
                </div>
            `);
            grid.append(item);
        });
    }

    function updateDashboardStats() {
        $('#saved-count').text(savedPlots.length);
        // Update other stats based on data
    }

    function loadInquiries() {
        // Load user inquiries - implementation would depend on data structure
    }

    // Utility Functions
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    function viewPlotDetails(plotId) {
        window.location.href = `/plots/${plotId}`;
    }

    function savePlot(plotId) {
        if (!currentUser) {
            alert('Please log in to save plots');
            return;
        }

        $.post(TajMapFrontend.ajaxUrl, {
            action: 'tajmap_pb_save_saved_plot',
            nonce: TajMapFrontend.nonce,
            user_id: currentUser.id,
            plot_id: plotId
        }, function(response) {
            if (response.success) {
                alert('Plot saved successfully!');
            } else {
                alert('Error saving plot');
            }
        });
    }

    // Global functions for onclick handlers
    window.viewPlotDetails = viewPlotDetails;
    window.selectPlot = selectPlot;
    window.savePlot = savePlot;
    window.openInquiryModal = openInquiryModal;
    window.closeModal = closeInquiryModal;
    window.nextStep = nextStep;
    window.prevStep = prevStep;

})(jQuery);
