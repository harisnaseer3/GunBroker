// TajMap Frontend JavaScript - Premium Customer Experience

(function($) {
    'use strict';

    // Global state
    let plots = [];
    let currentUser = null;
    let selectedPlot = null;
    let inquiryStep = 1;
    let savedPlots = [];

    // Initialize when DOM is ready
    $(document).ready(function() {
        initializeApp();
    });

    function initializeApp() {
        // Check if user is logged in
        checkUserStatus();

        // Initialize based on current page
        const path = window.location.pathname;

        if (path.includes('/plots')) {
            initializePlotSelection();
        } else if (path.includes('/gallery')) {
            initializeGallery();
        } else if (path.includes('/dashboard')) {
            initializeDashboard();
        } else {
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
        $.post(TajMapFrontend.ajaxUrl, {
            action: 'tajmap_pb_get_plots',
            nonce: TajMapFrontend.nonce,
            featured: true
        }, function(response) {
            if (response.success) {
                const featuredPlots = response.data.plots.slice(0, 6);
                renderFeaturedCarousel(featuredPlots);
            }
        });
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
        $.post(TajMapFrontend.ajaxUrl, {
            action: 'tajmap_pb_get_analytics',
            nonce: TajMapFrontend.nonce
        }, function(response) {
            if (response.success) {
                const data = response.data;
                $('#total-plots').text(data.total_plots.toLocaleString());
                $('#available-plots').text(data.available_plots.toLocaleString());
                $('#recent-leads').text(data.recent_leads.toLocaleString());
            }
        });
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
        loadPlots();
        initializeFilters();
        initializeMapControls();
        initializeModals();
    }

    function loadPlots() {
        const params = new URLSearchParams(window.location.search);
        const filters = {
            sector: params.get('sector') || '',
            block: params.get('block') || '',
            status: params.get('status') || '',
            search: params.get('search') || ''
        };

        $.post(TajMapFrontend.ajaxUrl, {
            action: 'tajmap_pb_get_plots',
            nonce: TajMapFrontend.nonce,
            filters: filters
        }, function(response) {
            if (response.success) {
                plots = response.data.plots;
                renderMap();
                updateFiltersList();
                updateResultsCount();
            }
        });
    }

    function renderMap() {
        const mapContainer = $('#interactive-map');
        const svg = mapContainer.find('svg');

        if (svg.length === 0) {
            mapContainer.html('<svg id="plots-svg" xmlns="http://www.w3.org/2000/svg"></svg>');
        }

        const svgElement = $('#plots-svg');
        svgElement.empty();

        plots.forEach(plot => {
            if (plot.coordinates) {
                try {
                    const coordinates = JSON.parse(plot.coordinates);
                    if (coordinates.length >= 3) {
                        const points = coordinates.map(coord => `${coord.x},${coord.y}`).join(' ');
                        const polygon = $(`
                            <polygon
                                points="${points}"
                                data-id="${plot.id}"
                                data-status="${plot.status}"
                                class="plot-polygon ${plot.status}"
                                fill-opacity="0.6"
                                stroke-width="2"
                            ></polygon>
                        `);

                        polygon.on('click', function() {
                            selectPlot(plot.id);
                        }).on('mouseenter', function() {
                            showPlotTooltip(plot, $(this));
                        }).on('mouseleave', function() {
                            hidePlotTooltip();
                        });

                        svgElement.append(polygon);
                    }
                } catch (e) {
                    console.error('Error parsing coordinates for plot:', plot.id);
                }
            }
        });
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
            // Implement zoom in
        });

        $('#zoom-out').on('click', function() {
            // Implement zoom out
        });

        $('#fit-view').on('click', function() {
            // Implement fit to view
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
