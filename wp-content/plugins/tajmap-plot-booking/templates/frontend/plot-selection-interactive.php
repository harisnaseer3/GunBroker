<?php
if (!defined('ABSPATH')) { exit; }
?>
<div class="tajmap-interactive-plot-selection">
    <!-- Header -->
    <div class="plot-header">
        <h1>Available Plots</h1>
        <p>Interactive Plot Selection - Click and drag to explore, zoom to see details</p>
    </div>

    <!-- Main Content -->
    <div class="plot-main">
        <!-- Interactive Map Container -->
        <div class="map-container">
            <div class="map-header">
                <div class="map-controls">
                    <button id="zoom-in" class="control-btn" title="Zoom In">+</button>
                    <button id="zoom-out" class="control-btn" title="Zoom Out">-</button>
                    <button id="fit-view" class="control-btn" title="Fit to View">⌂</button>
                    <button id="reset-view" class="control-btn" title="Reset View">↻</button>
                </div>
                <div class="zoom-level">
                    <span id="zoom-percentage">100%</span>
                </div>
            </div>
            
            <div id="interactive-map" class="interactive-map">
                <div class="loading-overlay" id="loading-overlay">
                    <div class="loading-spinner"></div>
                    <p>Loading plots...</p>
                </div>
                <canvas id="plot-canvas" width="800" height="600"></canvas>
            </div>
        </div>

        <!-- Plot Details Panel -->
        <div class="plot-details-panel">
            <div class="panel-header">
                <h3>Plot Details</h3>
            </div>
            <div class="panel-content">
                <div id="plot-info" class="plot-info">
                    <div class="placeholder">
                        <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                            <polyline points="3.27,6.96 12,12.01 20.73,6.96"></polyline>
                            <line x1="12" y1="22.08" x2="12" y2="12"></line>
                        </svg>
                        <p>Click on a plot to view details</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Plot List -->
    <div class="plot-list-section">
        <h3>All Plots (<span id="plot-count">0</span>)</h3>
        <div id="plot-list" class="plot-list">
            <!-- Plots will be loaded here -->
        </div>
    </div>
</div>

<style>
.tajmap-interactive-plot-selection {
    max-width: 1400px;
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
    grid-template-columns: 1fr 350px;
    gap: 20px;
    margin-bottom: 30px;
}

.map-container {
    background: #f8fafc;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
    position: relative;
}

.map-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    background: #f8fafc;
    border-bottom: 1px solid #e5e7eb;
}

.map-controls {
    display: flex;
    gap: 8px;
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
    display: flex;
    align-items: center;
    justify-content: center;
}

.control-btn:hover {
    background: #f3f4f6;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.zoom-level {
    color: #6b7280;
    font-size: 14px;
    font-weight: 500;
}

.interactive-map {
    position: relative;
    width: 100%;
    height: 600px;
    background: #f8fafc;
    overflow: hidden;
}

#plot-canvas {
    width: 100%;
    height: 100%;
    cursor: grab;
    display: block;
}

#plot-canvas:active {
    cursor: grabbing;
}

.loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(248, 250, 252, 0.9);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    z-index: 10;
}

.loading-spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #e5e7eb;
    border-top: 4px solid #3b82f6;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 15px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.loading-overlay p {
    color: #6b7280;
    font-size: 16px;
    margin: 0;
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

.placeholder {
    text-align: center;
    color: #9ca3af;
}

.placeholder svg {
    margin-bottom: 15px;
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

.plot-item {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 15px;
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
    margin-bottom: 8px;
    font-size: 1.1rem;
}

.plot-status {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 0.85rem;
    font-weight: 500;
    margin-bottom: 8px;
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
    line-height: 1.4;
}

.btn {
    background: #3b82f6;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s;
    margin-top: 15px;
    width: 100%;
}

.btn:hover {
    background: #2563eb;
    transform: translateY(-1px);
}

.btn-primary {
    background: #10b981;
}

.btn-primary:hover {
    background: #059669;
}

@media (max-width: 768px) {
    .plot-main {
        grid-template-columns: 1fr;
    }
    
    .plot-details-panel {
        order: -1;
    }
    
    .interactive-map {
        height: 400px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    'use strict';
    
    console.log('🚀 TajMap Interactive Plot Selection Loaded');
    console.log('TajMapFrontend object:', typeof TajMapFrontend !== 'undefined' ? TajMapFrontend : 'UNDEFINED');
    
    // Global variables
    let plots = [];
    let selectedPlot = null;
    let canvas, ctx;
    let scale = 1;
    let panX = 0;
    let panY = 0;
    let isDragging = false;
    let dragStartX = 0;
    let dragStartY = 0;
    let canvasWidth = 0;
    let canvasHeight = 0;
    
    // Initialize
    function init() {
        console.log('Initializing interactive plot selection...');
        
        // Setup canvas
        setupCanvas();
        
        // Load plots
        loadPlots();
        
        // Setup controls
        setupControls();
        
        // Setup event listeners
        setupEventListeners();
    }
    
    // Setup canvas
    function setupCanvas() {
        canvas = document.getElementById('plot-canvas');
        if (!canvas) {
            console.error('Canvas not found');
            return;
        }
        
        ctx = canvas.getContext('2d');
        resizeCanvas();
        
        // Handle window resize
        $(window).on('resize', resizeCanvas);
    }
    
    // Resize canvas
    function resizeCanvas() {
        const container = $('#interactive-map');
        canvasWidth = container.width();
        canvasHeight = container.height();
        
        canvas.width = canvasWidth;
        canvas.height = canvasHeight;
        
        console.log('Canvas resized:', canvasWidth, 'x', canvasHeight);
        
        // Redraw if plots are loaded
        if (plots.length > 0) {
            drawAll();
        }
    }
    
    // Load plots
    function loadPlots() {
        console.log('Loading plots...');
        showLoading(true);
        
        // Check if TajMapFrontend is defined
        if (typeof TajMapFrontend === 'undefined') {
            console.error('TajMapFrontend not defined, using fallback');
            showError('Configuration error: TajMapFrontend not loaded');
            return;
        }
        
        const ajaxUrl = TajMapFrontend.ajaxUrl || 'http://localhost/Gunbroker/wp-admin/admin-ajax.php';
        console.log('Using AJAX URL:', ajaxUrl);
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: { action: 'tajmap_pb_get_plots' },
            success: function(response) {
                console.log('Plots loaded:', response);
                
                if (response.success && response.data && response.data.plots) {
                    plots = response.data.plots;
                    console.log('Found', plots.length, 'plots');
                    
                    // Update plot count
                    $('#plot-count').text(plots.length);
                    
                    // Render everything
                    renderPlotList();
                    drawAll();
                    showLoading(false);
                } else {
                    console.error('No plots found in response');
                    showError('No plots found');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading plots:', error);
                showError('Failed to load plots: ' + error);
            }
        });
    }
    
    // Show/hide loading
    function showLoading(show) {
        if (show) {
            $('#loading-overlay').show();
        } else {
            $('#loading-overlay').hide();
        }
    }
    
    // Show error
    function showError(message) {
        $('#loading-overlay').html(`
            <div style="text-align: center; color: #ef4444;">
                <div style="font-size: 48px; margin-bottom: 15px;">❌</div>
                <p style="font-size: 16px; margin: 0 0 15px 0;">${message}</p>
                <button onclick="location.reload()" style="
                    background: #3b82f6;
                    color: white;
                    border: none;
                    padding: 8px 16px;
                    border-radius: 6px;
                    cursor: pointer;
                ">Retry</button>
            </div>
        `);
    }
    
    // Draw all plots
    function drawAll() {
        if (!ctx || plots.length === 0) return;
        
        // Clear canvas
        ctx.clearRect(0, 0, canvasWidth, canvasHeight);
        
        // Apply transformations
        ctx.save();
        ctx.translate(panX, panY);
        ctx.scale(scale, scale);
        
        // Draw plots
        plots.forEach((plot, index) => {
            drawPlot(plot, index);
        });
        
        ctx.restore();
    }
    
    // Draw individual plot
    function drawPlot(plot, index) {
        if (!plot.coordinates) return;
        
        try {
            const coords = JSON.parse(plot.coordinates);
            if (coords.length < 3) return;
            
            // Set plot style
            const color = plot.status === 'available' ? '#10b981' : '#ef4444';
            const opacity = plot.status === 'available' ? 0.7 : 0.5;
            
            ctx.fillStyle = color;
            ctx.strokeStyle = '#374151';
            ctx.lineWidth = 2 / scale;
            ctx.globalAlpha = opacity;
            
            // Draw polygon
            ctx.beginPath();
            ctx.moveTo(coords[0].x, coords[0].y);
            for (let i = 1; i < coords.length; i++) {
                ctx.lineTo(coords[i].x, coords[i].y);
            }
            ctx.closePath();
            ctx.fill();
            ctx.stroke();
            
            // Draw plot name
            if (scale > 0.5) {
                const centerX = coords.reduce((sum, p) => sum + p.x, 0) / coords.length;
                const centerY = coords.reduce((sum, p) => sum + p.y, 0) / coords.length;
                
                ctx.fillStyle = '#1f2937';
                ctx.font = `${12 / scale}px Arial`;
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.globalAlpha = 1;
                ctx.fillText(plot.plot_name || `Plot ${index + 1}`, centerX, centerY);
            }
            
            ctx.globalAlpha = 1;
        } catch (e) {
            console.error('Error drawing plot:', plot, e);
        }
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
                        Sector: ${plot.sector || 'N/A'} | Block: ${plot.block || 'N/A'}<br>
                        Street: ${plot.street || 'N/A'}
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
        
        // Update details panel
        const plotInfo = $('#plot-info');
        plotInfo.html(`
            <h4>${plot.plot_name || 'Plot'}</h4>
            <p><strong>Status:</strong> <span class="plot-status ${plot.status}">${plot.status || 'Unknown'}</span></p>
            <p><strong>Sector:</strong> ${plot.sector || 'N/A'}</p>
            <p><strong>Block:</strong> ${plot.block || 'N/A'}</p>
            <p><strong>Street:</strong> ${plot.street || 'N/A'}</p>
            <p><strong>Plot ID:</strong> ${plot.id || 'N/A'}</p>
            <p><strong>Created:</strong> ${plot.created_at || 'N/A'}</p>
            <button class="btn btn-primary" onclick="expressInterest('${plot.id}')">
                Express Interest
            </button>
        `);
        
        // Center on plot
        centerOnPlot(plot);
    }
    
    // Center on plot
    function centerOnPlot(plot) {
        if (!plot.coordinates) return;
        
        try {
            const coords = JSON.parse(plot.coordinates);
            const centerX = coords.reduce((sum, p) => sum + p.x, 0) / coords.length;
            const centerY = coords.reduce((sum, p) => sum + p.y, 0) / coords.length;
            
            // Center the plot
            panX = canvasWidth / 2 - centerX * scale;
            panY = canvasHeight / 2 - centerY * scale;
            
            // Zoom in a bit
            scale = Math.min(2, Math.max(0.5, scale * 1.5));
            
            updateZoomDisplay();
            drawAll();
        } catch (e) {
            console.error('Error centering on plot:', e);
        }
    }
    
    // Setup controls
    function setupControls() {
        $('#zoom-in').click(function() {
            scale = Math.min(scale * 1.2, 5);
            updateZoomDisplay();
            drawAll();
        });
        
        $('#zoom-out').click(function() {
            scale = Math.max(scale / 1.2, 0.1);
            updateZoomDisplay();
            drawAll();
        });
        
        $('#fit-view').click(function() {
            fitToView();
        });
        
        $('#reset-view').click(function() {
            scale = 1;
            panX = 0;
            panY = 0;
            updateZoomDisplay();
            drawAll();
        });
    }
    
    // Fit to view
    function fitToView() {
        if (plots.length === 0) return;
        
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
                    console.error('Invalid coordinates:', plot);
                }
            }
        });
        
        if (minX === Infinity) return;
        
        // Add padding
        const padding = 50;
        minX -= padding;
        maxX += padding;
        minY -= padding;
        maxY += padding;
        
        // Calculate scale
        const scaleX = canvasWidth / (maxX - minX);
        const scaleY = canvasHeight / (maxY - minY);
        scale = Math.min(scaleX, scaleY, 1);
        
        // Center
        panX = (canvasWidth - (maxX - minX) * scale) / 2 - minX * scale;
        panY = (canvasHeight - (maxY - minY) * scale) / 2 - minY * scale;
        
        updateZoomDisplay();
        drawAll();
    }
    
    // Update zoom display
    function updateZoomDisplay() {
        $('#zoom-percentage').text(Math.round(scale * 100) + '%');
    }
    
    // Setup event listeners
    function setupEventListeners() {
        // Mouse wheel zoom
        $('#interactive-map').on('wheel', function(e) {
            e.preventDefault();
            const delta = e.originalEvent.deltaY > 0 ? 0.9 : 1.1;
            scale = Math.max(0.1, Math.min(5, scale * delta));
            updateZoomDisplay();
            drawAll();
        });
        
        // Pan functionality
        $('#interactive-map').on('mousedown', function(e) {
            if (e.target === canvas) {
                isDragging = true;
                dragStartX = e.clientX - panX;
                dragStartY = e.clientY - panY;
                canvas.style.cursor = 'grabbing';
            }
        });
        
        $(document).on('mousemove', function(e) {
            if (isDragging) {
                panX = e.clientX - dragStartX;
                panY = e.clientY - dragStartY;
                drawAll();
            }
        });
        
        $(document).on('mouseup', function() {
            isDragging = false;
            canvas.style.cursor = 'grab';
        });
        
        // Click on plot
        $('#plot-canvas').on('click', function(e) {
            if (isDragging) return;
            
            const rect = canvas.getBoundingClientRect();
            const x = (e.clientX - rect.left - panX) / scale;
            const y = (e.clientY - rect.top - panY) / scale;
            
            // Find clicked plot
            plots.forEach(plot => {
                if (plot.coordinates) {
                    try {
                        const coords = JSON.parse(plot.coordinates);
                        if (isPointInPolygon(x, y, coords)) {
                            selectPlot(plot);
                        }
                    } catch (e) {
                        console.error('Error checking plot click:', e);
                    }
                }
            });
        });
    }
    
    // Point in polygon test
    function isPointInPolygon(x, y, coords) {
        let inside = false;
        for (let i = 0, j = coords.length - 1; i < coords.length; j = i++) {
            if (((coords[i].y > y) !== (coords[j].y > y)) &&
                (x < (coords[j].x - coords[i].x) * (y - coords[i].y) / (coords[j].y - coords[i].y) + coords[i].x)) {
                inside = !inside;
            }
        }
        return inside;
    }
    
    // Express interest function
    window.expressInterest = function(plotId) {
        alert('Interest expressed for plot ID: ' + plotId);
        console.log('Interest expressed for plot:', plotId);
    };
    
    // Initialize
    init();
});
</script>
