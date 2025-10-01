<?php
if (!defined('ABSPATH')) { exit; }
?>
<div class="tajmap-plot-selection">
    <!-- Header -->
    <div class="plot-header">
        <h1>Available Plots</h1>
        <p>Select a plot to view details and express interest</p>
    </div>

    <!-- Main Content -->
    <div class="plot-main">
        <!-- Map Container -->
        <div class="map-container">
            <div id="plot-map" class="plot-map">
                <div class="loading">Loading plots...</div>
            </div>
            
            <!-- Map Controls -->
            <div class="map-controls">
                <button id="zoom-in" class="control-btn">+</button>
                <button id="zoom-out" class="control-btn">-</button>
                <button id="fit-view" class="control-btn">⌂</button>
            </div>
        </div>

        <!-- Plot Details Panel -->
        <div class="plot-details-panel">
            <div class="panel-header">
                <h3>Plot Details</h3>
            </div>
            <div class="panel-content">
                <div id="plot-info" class="plot-info">
                    <p>Click on a plot to view details</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Plot List -->
    <div class="plot-list-section">
        <h3>All Plots</h3>
        <div id="plot-list" class="plot-list">
            <!-- Plots will be loaded here -->
        </div>
    </div>
</div>

<style>
.tajmap-plot-selection {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.plot-header {
    text-align: center;
    margin-bottom: 30px;
}

.plot-header h1 {
    font-size: 2.5rem;
    color: #1f2937;
    margin-bottom: 10px;
}

.plot-header p {
    color: #6b7280;
    font-size: 1.1rem;
}

.plot-main {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 20px;
    margin-bottom: 30px;
}

.map-container {
    position: relative;
    background: #f8fafc;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
    min-height: 500px;
}

.plot-map {
    width: 100%;
    height: 500px;
    position: relative;
}

.plot-map svg {
    width: 100%;
    height: 100%;
    cursor: grab;
}

.plot-map svg:active {
    cursor: grabbing;
}

.loading {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: #6b7280;
    font-size: 1.1rem;
}

.map-controls {
    position: absolute;
    top: 15px;
    right: 15px;
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.control-btn {
    width: 40px;
    height: 40px;
    border: none;
    background: white;
    border-radius: 6px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    cursor: pointer;
    font-size: 18px;
    font-weight: bold;
    color: #374151;
    transition: all 0.2s;
}

.control-btn:hover {
    background: #f3f4f6;
    transform: translateY(-1px);
}

.plot-details-panel {
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
}

.panel-header {
    background: #f8fafc;
    padding: 15px 20px;
    border-bottom: 1px solid #e5e7eb;
}

.panel-header h3 {
    margin: 0;
    color: #1f2937;
    font-size: 1.2rem;
}

.panel-content {
    padding: 20px;
}

.plot-info {
    color: #6b7280;
}

.plot-item {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 10px;
    cursor: pointer;
    transition: all 0.2s;
}

.plot-item:hover {
    border-color: #3b82f6;
    box-shadow: 0 2px 4px rgba(59, 130, 246, 0.1);
}

.plot-item.selected {
    border-color: #3b82f6;
    background: #eff6ff;
}

.plot-name {
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 5px;
}

.plot-status {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 500;
    margin-bottom: 5px;
}

.plot-status.available {
    background: #dcfce7;
    color: #166534;
}

.plot-status.sold {
    background: #fee2e2;
    color: #991b1b;
}

.plot-details {
    font-size: 0.9rem;
    color: #6b7280;
}

.plot-list-section {
    margin-top: 30px;
}

.plot-list-section h3 {
    color: #1f2937;
    margin-bottom: 15px;
}

.plot-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 15px;
}

@media (max-width: 768px) {
    .plot-main {
        grid-template-columns: 1fr;
    }
    
    .plot-details-panel {
        order: -1;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    'use strict';
    
    console.log('🚀 TajMap New Frontend Loaded');
    
    let plots = [];
    let selectedPlot = null;
    let mapScale = 1;
    let mapOffsetX = 0;
    let mapOffsetY = 0;
    let isDragging = false;
    let dragStartX = 0;
    let dragStartY = 0;
    
    // Initialize
    function init() {
        console.log('Initializing new plot selection...');
        loadPlots();
        setupMapControls();
    }
    
    // Load plots
    function loadPlots() {
        console.log('Loading plots...');
        
        $.ajax({
            url: TajMapFrontend.ajaxUrl,
            type: 'POST',
            data: { action: 'tajmap_pb_get_plots' },
            success: function(response) {
                console.log('Plots loaded:', response);
                
                if (response.success && response.data && response.data.plots) {
                    plots = response.data.plots;
                    console.log('Found', plots.length, 'plots');
                    
                    // Debug: Log the first plot to see its structure
                    if (plots.length > 0) {
                        console.log('First plot structure:', plots[0]);
                        console.log('All plot fields:', Object.keys(plots[0]));
                    }
                    
                    renderMap();
                    renderPlotList();
                } else {
                    showError('No plots found');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading plots:', error);
                showError('Failed to load plots: ' + error);
            }
        });
    }
    
    // Render map
    function renderMap() {
        const mapContainer = $('#plot-map');
        mapContainer.empty();
        
        if (plots.length === 0) {
            mapContainer.html('<div class="loading">No plots available</div>');
            return;
        }
        
        // Calculate bounds
        let minX = Infinity, maxX = -Infinity;
        let minY = Infinity, maxY = -Infinity;
        
        plots.forEach(plot => {
            if (plot.coordinates) {
                try {
                    const coords = JSON.parse(plot.coordinates);
                    coords.forEach(point => {
                        minX = Math.min(minX, point.x);
                        maxX = Math.max(maxX, point.x);
                        minY = Math.min(minY, point.y);
                        maxY = Math.max(maxY, point.y);
                    });
                } catch (e) {
                    console.error('Invalid coordinates for plot:', plot);
                }
            }
        });
        
        if (minX === Infinity) {
            mapContainer.html('<div class="loading">No valid coordinates found</div>');
            return;
        }
        
        // Add padding
        const padding = 50;
        minX -= padding;
        maxX += padding;
        minY -= padding;
        maxY += padding;
        
        // Calculate scale
        const containerWidth = mapContainer.width();
        const containerHeight = mapContainer.height();
        const scaleX = containerWidth / (maxX - minX);
        const scaleY = containerHeight / (maxY - minY);
        const scale = Math.min(scaleX, scaleY, 1);
        
        // Center the plots
        const offsetX = (containerWidth - (maxX - minX) * scale) / 2 - minX * scale;
        const offsetY = (containerHeight - (maxY - minY) * scale) / 2 - minY * scale;
        
        // Create SVG
        const svg = $(`
            <svg width="100%" height="100%">
                <g class="plots-group" transform="translate(${offsetX}, ${offsetY}) scale(${scale})">
                </g>
            </svg>
        `);
        
        mapContainer.append(svg);
        
        // Render plots
        plots.forEach((plot, index) => {
            if (!plot.coordinates) return;
            
            try {
                const coords = JSON.parse(plot.coordinates);
                const points = coords.map(point => `${point.x},${point.y}`).join(' ');
                
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
                        style="cursor: pointer;"
                    />
                `);
                
                // Hover effects
                polygon.hover(
                    function() { $(this).attr('opacity', '0.9'); },
                    function() { $(this).attr('opacity', opacity); }
                );
                
                // Click handler
                polygon.click(function() {
                    selectPlot(plot);
                });
                
                $('.plots-group').append(polygon);
            } catch (e) {
                console.error('Error rendering plot:', plot, e);
            }
        });
        
        console.log('Map rendered successfully');
    }
    
    // Render plot list
    function renderPlotList() {
        const plotList = $('#plot-list');
        plotList.empty();
        
        plots.forEach((plot, index) => {
            const plotItem = $(`
                <div class="plot-item" data-plot-id="${plot.id || index}">
                    <div class="plot-name">${plot.plot_name || 'Plot ' + (index + 1)}</div>
                    <div class="plot-status ${plot.status || 'unknown'}">${plot.status || 'Unknown'}</div>
                    <div class="plot-details">
                        Sector: ${plot.sector || 'N/A'} | Block: ${plot.block || 'N/A'} | Street: ${plot.street || 'N/A'}
                    </div>
                </div>
            `);
            
            plotItem.click(function() {
                selectPlot(plot);
            });
            
            plotList.append(plotItem);
        });
    }
    
    // Select plot
    function selectPlot(plot) {
        console.log('Selected plot:', plot);
        selectedPlot = plot;
        
        // Update UI
        $('.plot-item').removeClass('selected');
        $(`.plot-item[data-plot-id="${plot.id}"]`).addClass('selected');
        
        // Update details panel with actual database fields
        const plotInfo = $('#plot-info');
        plotInfo.html(`
            <h4>${plot.plot_name || 'Plot'}</h4>
            <p><strong>Status:</strong> <span class="plot-status ${plot.status}">${plot.status || 'Unknown'}</span></p>
            <p><strong>Sector:</strong> ${plot.sector || 'N/A'}</p>
            <p><strong>Block:</strong> ${plot.block || 'N/A'}</p>
            <p><strong>Street:</strong> ${plot.street || 'N/A'}</p>
            <p><strong>Plot ID:</strong> ${plot.id || 'N/A'}</p>
            <p><strong>Created:</strong> ${plot.created_at || 'N/A'}</p>
            <p><strong>Updated:</strong> ${plot.updated_at || 'N/A'}</p>
            <button class="btn btn-primary" onclick="expressInterest('${plot.id}')">
                Express Interest
            </button>
        `);
    }
    
    // Setup map controls
    function setupMapControls() {
        $('#zoom-in').click(function() {
            mapScale = Math.min(mapScale * 1.2, 3);
            updateMapTransform();
        });
        
        $('#zoom-out').click(function() {
            mapScale = Math.max(mapScale / 1.2, 0.1);
            updateMapTransform();
        });
        
        $('#fit-view').click(function() {
            mapScale = 1;
            mapOffsetX = 0;
            mapOffsetY = 0;
            updateMapTransform();
        });
        
        // Mouse wheel zoom
        $('#plot-map').on('wheel', function(e) {
            e.preventDefault();
            const delta = e.originalEvent.deltaY > 0 ? 0.9 : 1.1;
            mapScale = Math.max(0.1, Math.min(3, mapScale * delta));
            updateMapTransform();
        });
        
        // Pan functionality
        $('#plot-map').on('mousedown', function(e) {
            if (e.target.tagName === 'svg' || e.target.tagName === 'g') {
                isDragging = true;
                dragStartX = e.clientX - mapOffsetX;
                dragStartY = e.clientY - mapOffsetY;
                $(this).css('cursor', 'grabbing');
            }
        });
        
        $(document).on('mousemove', function(e) {
            if (isDragging) {
                mapOffsetX = e.clientX - dragStartX;
                mapOffsetY = e.clientY - dragStartY;
                updateMapTransform();
            }
        });
        
        $(document).on('mouseup', function() {
            isDragging = false;
            $('#plot-map').css('cursor', 'grab');
        });
    }
    
    // Update map transform
    function updateMapTransform() {
        $('.plots-group').attr('transform', `translate(${mapOffsetX}, ${mapOffsetY}) scale(${mapScale})`);
    }
    
    // Show error
    function showError(message) {
        $('#plot-map').html(`<div class="loading" style="color: #ef4444;">❌ ${message}</div>`);
    }
    
    // Express interest function
    window.expressInterest = function(plotId) {
        alert('Interest expressed for plot ID: ' + plotId);
    };
    
    // Initialize
    init();
});
</script>
