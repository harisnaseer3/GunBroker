// TajMap Frontend - Lightweight Plot Selection
jQuery(document).ready(function($) {
    'use strict';
    
    console.log('🚀 TajMap Simple Frontend Loaded');
    
    // Global variables
    let plots = [];
    let selectedPlot = null;
    
    // Initialize
    function init() {
        console.log('Initializing plot selection...');
        console.log('AJAX URL:', TajMapFrontend.ajaxUrl);
        
        // Test AJAX endpoint first
        testAjaxEndpoint();
        
        // Setup map
        setupMap();
    }
    
    // Test AJAX endpoint
    function testAjaxEndpoint() {
        console.log('Testing AJAX endpoint...');
        
        const ajaxUrl = TajMapFrontend.ajaxUrl || 'http://localhost/Gunbroker/wp-admin/admin-ajax.php';
        console.log('Using AJAX URL:', ajaxUrl);
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'tajmap_pb_test_ajax'
            },
            success: function(response) {
                console.log('AJAX Test Success:', response);
                // If test works, load plots
                loadPlots();
            },
            error: function(xhr, status, error) {
                console.error('AJAX Test Failed:', error, 'Status:', xhr.status);
                console.error('Response:', xhr.responseText);
                // Try to load plots anyway
                loadPlots();
            }
        });
    }
    
    // Load plots from server
    function loadPlots() {
        console.log('Loading plots...');
        console.log('AJAX URL:', TajMapFrontend.ajaxUrl);
        console.log('Action:', 'tajmap_pb_get_plots');
        
        const ajaxUrl = TajMapFrontend.ajaxUrl || 'http://localhost/Gunbroker/wp-admin/admin-ajax.php';
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'tajmap_pb_get_plots'
            },
            success: function(response) {
                console.log('AJAX Success - Response:', response);
                
                if (response.success && response.data && response.data.plots) {
                    plots = response.data.plots;
                    console.log('Found', plots.length, 'plots');
                    renderPlots();
                } else {
                    console.error('Response structure:', response);
                    showError('No plots found in response');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error Details:');
                console.error('Status:', status);
                console.error('Error:', error);
                console.error('Response Text:', xhr.responseText);
                console.error('Status Code:', xhr.status);
                console.error('Full XHR:', xhr);
                showError('Failed to load plots: ' + error + ' (Status: ' + xhr.status + ')');
            }
        });
    }
    
    // Setup the map container
    function setupMap() {
        const mapContainer = $('#interactive-map');
        if (mapContainer.length === 0) {
            console.error('Map container not found');
            return;
        }
        
        // Create SVG
        const svg = $(`
            <svg width="100%" height="100%" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                <g class="plots-group"></g>
            </svg>
        `);
        
        mapContainer.html(svg);
        console.log('Map setup complete');
    }
    
    // Render plots on the map
    function renderPlots() {
        console.log('Rendering', plots.length, 'plots');
        
        const plotsGroup = $('.plots-group');
        plotsGroup.empty();
        
        if (plots.length === 0) {
            plotsGroup.append(`
                <text x="50%" y="50%" text-anchor="middle" fill="#666" font-size="16">
                    No plots available
                </text>
            `);
            return;
        }
        
        // Calculate bounds
        let minX = Infinity, maxX = -Infinity;
        let minY = Infinity, maxY = -Infinity;
        
        plots.forEach(plot => {
            if (plot.coordinates) {
                const coords = JSON.parse(plot.coordinates);
                coords.forEach(point => {
                    minX = Math.min(minX, point.x);
                    maxX = Math.max(maxX, point.x);
                    minY = Math.min(minY, point.y);
                    maxY = Math.max(maxY, point.y);
                });
            }
        });
        
        if (minX === Infinity) {
            console.log('No valid coordinates found');
            return;
        }
        
        // Add padding
        const padding = 50;
        minX -= padding;
        maxX += padding;
        minY -= padding;
        maxY += padding;
        
        // Calculate scale to fit in container
        const containerWidth = $('#interactive-map').width();
        const containerHeight = $('#interactive-map').height();
        const scaleX = containerWidth / (maxX - minX);
        const scaleY = containerHeight / (maxY - minY);
        const scale = Math.min(scaleX, scaleY, 1);
        
        // Center the plots
        const offsetX = (containerWidth - (maxX - minX) * scale) / 2 - minX * scale;
        const offsetY = (containerHeight - (maxY - minY) * scale) / 2 - minY * scale;
        
        // Render each plot
        plots.forEach((plot, index) => {
            if (!plot.coordinates) return;
            
            const coords = JSON.parse(plot.coordinates);
            const points = coords.map(point => 
                `${(point.x * scale + offsetX)},${(point.y * scale + offsetY)}`
            ).join(' ');
            
            const color = plot.status === 'available' ? '#10b981' : '#ef4444';
            const opacity = plot.status === 'available' ? 0.7 : 0.5;
            
            const polygon = $(`
                <polygon 
                    points="${points}" 
                    fill="${color}" 
                    stroke="#374151" 
                    stroke-width="2" 
                    opacity="${opacity}"
                    data-plot-id="${plot.id || index}"
                    data-plot-name="${plot.plot_name || 'Plot ' + (index + 1)}"
                    data-plot-status="${plot.status || 'unknown'}"
                    style="cursor: pointer;"
                />
            `);
            
            // Add hover effects
            polygon.hover(
                function() {
                    $(this).attr('opacity', '0.9');
                    showTooltip($(this), plot);
                },
                function() {
                    $(this).attr('opacity', opacity);
                    hideTooltip();
                }
            );
            
            // Add click handler
            polygon.click(function() {
                selectPlot(plot);
            });
            
            plotsGroup.append(polygon);
        });
        
        console.log('Plots rendered successfully');
    }
    
    // Show tooltip
    function showTooltip(element, plot) {
        const tooltip = $(`
            <div class="plot-tooltip" style="
                position: absolute;
                background: #1f2937;
                color: white;
                padding: 8px 12px;
                border-radius: 6px;
                font-size: 14px;
                pointer-events: none;
                z-index: 1000;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            ">
                <strong>${plot.plot_name || 'Plot'}</strong><br>
                Status: ${plot.status || 'Unknown'}<br>
                Sector: ${plot.sector || 'N/A'}<br>
                Block: ${plot.block || 'N/A'}
            </div>
        `);
        
        $('body').append(tooltip);
        
        // Position tooltip
        const rect = element[0].getBoundingClientRect();
        const containerRect = $('#interactive-map')[0].getBoundingClientRect();
        
        tooltip.css({
            left: rect.left + rect.width/2 - tooltip.outerWidth()/2,
            top: rect.top - tooltip.outerHeight() - 10
        });
    }
    
    // Hide tooltip
    function hideTooltip() {
        $('.plot-tooltip').remove();
    }
    
    // Select a plot
    function selectPlot(plot) {
        console.log('Selected plot:', plot);
        selectedPlot = plot;
        
        // Update UI
        updatePlotDetails(plot);
        
        // Highlight selected plot
        $('.plots-group polygon').removeClass('selected');
        $(`.plots-group polygon[data-plot-id="${plot.id}"]`).addClass('selected');
    }
    
    // Update plot details panel
    function updatePlotDetails(plot) {
        const detailsPanel = $('#plot-details');
        if (detailsPanel.length === 0) return;
        
        detailsPanel.html(`
            <h3>${plot.plot_name || 'Plot'}</h3>
            <div class="plot-info">
                <p><strong>Status:</strong> <span class="status-${plot.status}">${plot.status || 'Unknown'}</span></p>
                <p><strong>Sector:</strong> ${plot.sector || 'N/A'}</p>
                <p><strong>Block:</strong> ${plot.block || 'N/A'}</p>
                <p><strong>Street:</strong> ${plot.street || 'N/A'}</p>
                <p><strong>Price:</strong> ${plot.price || 'N/A'}</p>
                <p><strong>Area:</strong> ${plot.area || 'N/A'}</p>
            </div>
            <button class="btn btn-primary" onclick="expressInterest('${plot.id}')">
                Express Interest
            </button>
        `);
    }
    
    // Show error message
    function showError(message) {
        const mapContainer = $('#interactive-map');
        mapContainer.html(`
            <div style="
                display: flex;
                align-items: center;
                justify-content: center;
                height: 100%;
                color: #ef4444;
                font-size: 16px;
                text-align: center;
            ">
                <div>
                    <p>❌ ${message}</p>
                    <button onclick="location.reload()" style="
                        background: #3b82f6;
                        color: white;
                        border: none;
                        padding: 8px 16px;
                        border-radius: 4px;
                        cursor: pointer;
                    ">Retry</button>
                </div>
            </div>
        `);
    }
    
    // Express interest function (global)
    window.expressInterest = function(plotId) {
        alert('Interest expressed for plot ID: ' + plotId);
        console.log('Interest expressed for plot:', plotId);
    };
    
    // Initialize when ready
    init();
});
