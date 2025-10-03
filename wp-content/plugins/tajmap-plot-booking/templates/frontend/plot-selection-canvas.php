<?php
if (!defined('ABSPATH')) { exit; }
?>
<div class="tajmap-canvas-plot-selection">
    <!-- Header -->
    <div class="plot-header">
        <h1>Available Plots</h1>
        <p>Interactive Plot Viewer - Click and drag to explore, zoom to see details</p>
    </div>

    <!-- Main Content -->
    <div class="plot-main">
        <!-- Interactive Canvas Container -->
        <div class="canvas-container">
            <div class="canvas-toolbar">
                <div class="toolbar-left">
                    <span class="canvas-status" id="canvas-status">Loading plots...</span>
                </div>
                <div class="toolbar-center">
                    <div class="zoom-controls">
                        <span class="zoom-level" id="zoom-level">100%</span>
                    </div>
                </div>
                <div class="toolbar-right">
                    <button class="btn small" id="show-grid-toggle">Show Grid</button>
                </div>
            </div>

            <!-- Canvas -->
            <div class="canvas-wrapper">
                <canvas id="plot-viewer-canvas" width="1200" height="800"></canvas>
                
                <!-- Grid Overlay -->
                <div class="grid-overlay" id="grid-overlay" style="display: none;"></div>
            </div>

            <!-- Loading Overlay -->
            <div class="loading-overlay" id="loading-overlay">
                <div class="loading-spinner"></div>
                <p>Loading plots...</p>
            </div>
        </div>

        <!-- Plot Details Panel -->
        <div class="plot-details-panel" id="plot-details-panel" style="display: none;">
            <div class="panel-header">
                <h3>Plot Details</h3>
                <button class="panel-close" id="panel-close">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <div class="panel-content" id="panel-content">
                <!-- Plot details will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Plot List -->
    <div class="plot-list-section">
        <h3>All Plots (<span id="plot-count">0</span>)</h3>
        <div class="plot-filters">
            <select id="filter-sector" class="filter-select">
                <option value="">All Sectors</option>
            </select>
            <select id="filter-block" class="filter-select">
                <option value="">All Blocks</option>
            </select>
            <select id="filter-status" class="filter-select">
                <option value="">All Status</option>
                <option value="available">Available</option>
                <option value="sold">Sold</option>
            </select>
            <button class="btn secondary" id="apply-filters">Apply Filters</button>
            <button class="btn secondary" id="clear-filters">Clear</button>
        </div>
        <div id="plot-list" class="plot-list">
            <!-- Plots will be loaded here -->
        </div>
    </div>
</div>

<style>
.tajmap-canvas-plot-selection {
    max-width: 100%;
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
    grid-template-columns: 1fr;
    gap: 20px;
    margin-bottom: 30px;
}

.canvas-container {
    background: #f8fafc;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
    position: relative;
}

.canvas-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    background: #f8fafc;
    border-bottom: 1px solid #e5e7eb;
}

.toolbar-left, .toolbar-center, .toolbar-right {
    display: flex;
    align-items: center;
    gap: 10px;
}

.canvas-status {
    color: #6b7280;
    font-size: 14px;
    font-weight: 500;
}

.zoom-controls {
    display: flex;
    align-items: center;
    gap: 10px;
}

.zoom-level {
    color: #6b7280;
    font-size: 14px;
    font-weight: 500;
    min-width: 50px;
    text-align: center;
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
}

.btn:hover {
    background: #2563eb;
    transform: translateY(-1px);
}

.btn.secondary {
    background: #6b7280;
}

.btn.secondary:hover {
    background: #4b5563;
}

.btn.small {
    padding: 6px 12px;
    font-size: 12px;
}

.canvas-wrapper {
    position: relative;
    width: 100%;
    height: 600px;
    background: #f8fafc;
    overflow: hidden;
}

#plot-viewer-canvas {
    width: 100%;
    height: 100%;
    cursor: grab;
    display: block;
}

#plot-viewer-canvas:active {
    cursor: grabbing;
}

.grid-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    pointer-events: none;
    background-image: 
        linear-gradient(rgba(59, 130, 246, 0.1) 1px, transparent 1px),
        linear-gradient(90deg, rgba(59, 130, 246, 0.1) 1px, transparent 1px);
    background-size: 20px 20px;
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
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    overflow: hidden;
}

.panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    background: #f8fafc;
    border-bottom: 1px solid #e5e7eb;
}

.panel-header h3 {
    margin: 0;
    color: #1f2937;
    font-size: 1.2rem;
}

.panel-close {
    background: none;
    border: none;
    cursor: pointer;
    color: #6b7280;
    padding: 5px;
    border-radius: 4px;
    transition: all 0.2s;
}

.panel-close:hover {
    background: #f3f4f6;
    color: #374151;
}

.panel-content {
    padding: 20px;
}

.plot-list-section {
    margin-top: 30px;
}

.plot-list-section h3 {
    color: #1f2937;
    margin-bottom: 15px;
}

.plot-filters {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.filter-select {
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    background: white;
    font-size: 14px;
    min-width: 120px;
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

/* Hide common theme sidebars/widgets on this page */
body .sidebar,
body #secondary,
body .widget-area,
body .right-sidebar,
body .site-sidebar {
    display: none !important;
}

/* Expand content area if theme uses a grid with sidebar */
body .content-area,
body .site-main,
body .site-content,
body .container,
body .wrap {
    max-width: 100% !important;
    width: 100% !important;
}

@media (max-width: 768px) {
    .plot-main {
        grid-template-columns: 1fr;
    }
    
    .canvas-wrapper {
        height: 400px;
    }
    
    .plot-filters {
        flex-direction: column;
    }
    
    .filter-select {
        min-width: 100%;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    'use strict';
    
    console.log('🚀 TajMap Canvas Plot Viewer Loaded');
    
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
    let showGrid = false;
    
    // Global base map image variables (canvas background)
    let globalBaseMapImage = null;
    let globalBaseMapTransform = {
        x: 0,
        y: 0,
        scale: 1,
        rotation: 0,
        width: 0,
        height: 0
    };
    
    // Initialize
    function init() {
        console.log('Initializing canvas plot viewer...');
        
        // Setup canvas
        setupCanvas();
        
        // Load global base map first
        loadGlobalBaseMap();
        
        // Load plots
        loadPlots();
        
        // Setup controls
        setupControls();
        
        // Setup event listeners
        setupEventListeners();
    }
    
    // Setup canvas
    function setupCanvas() {
        canvas = document.getElementById('plot-viewer-canvas');
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
        const container = $('.canvas-wrapper');
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
                console.log('📡 AJAX Response:', response);
                
                if (response.success && response.data && response.data.plots) {
                    plots = response.data.plots;
                    console.log('📊 Found', plots.length, 'plots');
                    
                    // Update plot count
                    $('#plot-count').text(plots.length);
                    
                    // Render everything and fit to view so plots are visible
                    renderPlotList();
                    fitToView();
                    drawAll();
                    
                    // Force hide loading overlay after a small delay to ensure drawing completes
                    setTimeout(() => {
                        showLoading(false);
                        console.log('🔄 Loading overlay should be hidden now');
                    }, 100);
                } else {
                    console.error('❌ No plots found in response');
                    console.error('Response structure:', response);
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
        console.log('🔄 Loading overlay:', show ? 'SHOW' : 'HIDE');
        const overlay = $('#loading-overlay');
        if (show) {
            overlay.show();
        } else {
            overlay.hide();
            console.log('🔄 Loading overlay hidden');
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
    
    // Robustly parse coordinates from various formats
    function parseCoordinates(raw) {
        if (!raw) return [];
        try {
            const parsed = typeof raw === 'string' ? JSON.parse(raw) : raw;
            if (Array.isArray(parsed) && parsed.length) {
                // If array of objects with x,y
                if (typeof parsed[0] === 'object' && parsed[0] !== null && 'x' in parsed[0] && 'y' in parsed[0]) {
                    return parsed.map(p => ({ x: Number(p.x), y: Number(p.y) }));
                }
                // If array of arrays like [[x,y], [x,y]]
                if (Array.isArray(parsed[0]) && parsed[0].length >= 2) {
                    return parsed.map(p => ({ x: Number(p[0]), y: Number(p[1]) }));
                }
            }
        } catch (e) {
            // Try simple delimiter format: "x,y|x,y|x,y"
            if (typeof raw === 'string' && raw.includes(',')) {
                const parts = raw.split('|').map(pair => pair.trim());
                const coords = parts.map(pair => {
                    const [px, py] = pair.split(',');
                    return { x: Number(px), y: Number(py) };
                }).filter(p => !Number.isNaN(p.x) && !Number.isNaN(p.y));
                if (coords.length) return coords;
            }
        }
        return [];
    }

    // Draw all plots
    function drawAll() {
        console.log('🎨 drawAll called - plots:', plots.length, 'canvas:', canvasWidth, 'x', canvasHeight);
        
        if (!ctx) {
            console.error('❌ No canvas context');
            return;
        }
        
        if (plots.length === 0) {
            console.log('⚠️ No plots to draw');
            // Still draw background
            ctx.fillStyle = '#f8fafc';
            ctx.fillRect(0, 0, canvasWidth, canvasHeight);
            return;
        }
        
        // Clear canvas
        ctx.clearRect(0, 0, canvasWidth, canvasHeight);
        
        // Apply transformations for base map, plots and grid (unified coordinate system)
        ctx.save();
        ctx.translate(panX, panY);
        ctx.scale(scale, scale);

        // Draw global base map image first (background layer) - now in world coordinates
        if (globalBaseMapImage) {
            try {
                ctx.drawImage(
                    globalBaseMapImage,
                    globalBaseMapTransform.x,
                    globalBaseMapTransform.y,
                    globalBaseMapTransform.width,
                    globalBaseMapTransform.height
                );
                console.log('🗺️ Global base map drawn');
            } catch (e) {
                console.error('Error drawing global base map:', e);
            }
        } else {
            // Fallback to light background if no base map
            ctx.fillStyle = '#f8fafc';
            ctx.fillRect(0, 0, canvasWidth / scale, canvasHeight / scale);
            console.log('🎨 Background painted light gray (no base map)');
        }
        
        // Draw grid if enabled
        if (showGrid) {
            drawGrid();
        }
        
        // Draw plots
        plots.forEach((plot, index) => {
            console.log(`🎨 Drawing plot ${index}:`, plot);
            drawPlot(plot, index);
        });
        
        // Draw selection highlight
        if (selectedPlot) {
            drawSelectionHighlight(selectedPlot);
        }
        
        ctx.restore();
        console.log('🎨 drawAll completed');
    }
    
    // Draw individual plot
    function drawPlot(plot, index) {
        console.log(`🎨 drawPlot ${index} - raw coordinates:`, plot.coordinates);
        
        if (!plot.coordinates) {
            console.log(`⚠️ Plot ${index} has no coordinates`);
            return;
        }
        
        try {
            const coords = parseCoordinates(plot.coordinates);
            console.log(`🎨 Plot ${index} parsed coords:`, coords);
            
            if (coords.length < 3) {
                console.log(`⚠️ Plot ${index} has only ${coords.length} coordinates, need at least 3`);
                return;
            }
            
            // Set plot style
            const color = plot.status === 'available' ? '#10b981' : '#ef4444';
            const opacity = plot.status === 'available' ? 0.6 : 0.4;
            
            console.log(`🎨 Plot ${index} style - color: ${color}, opacity: ${opacity}`);
            
            ctx.fillStyle = color;
            ctx.strokeStyle = '#374151';
            ctx.lineWidth = 2 / scale;
            ctx.globalAlpha = opacity;
            
            // Draw polygon
            ctx.beginPath();
            ctx.moveTo(coords[0].x, coords[0].y);
            console.log(`🎨 Plot ${index} starting at:`, coords[0]);
            
            for (let i = 1; i < coords.length; i++) {
                ctx.lineTo(coords[i].x, coords[i].y);
                console.log(`🎨 Plot ${index} line to:`, coords[i]);
            }
            ctx.closePath();
            ctx.fill();
            ctx.stroke();
            
            console.log(`🎨 Plot ${index} polygon drawn`);
            
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
                console.log(`🎨 Plot ${index} text drawn at:`, centerX, centerY);
            }
            
            ctx.globalAlpha = 1;
        } catch (e) {
            console.error(`❌ Error drawing plot ${index}:`, plot, e);
        }
    }
    
    // Draw selection highlight
    function drawSelectionHighlight(plot) {
        if (!plot.coordinates) return;
        
        try {
            const coords = parseCoordinates(plot.coordinates);
            if (coords.length < 3) return;
            
            ctx.strokeStyle = '#3b82f6';
            ctx.lineWidth = 3 / scale;
            ctx.setLineDash([5 / scale, 5 / scale]);
            ctx.globalAlpha = 1;
            
            ctx.beginPath();
            ctx.moveTo(coords[0].x, coords[0].y);
            for (let i = 1; i < coords.length; i++) {
                ctx.lineTo(coords[i].x, coords[i].y);
            }
            ctx.closePath();
            ctx.stroke();
            
            ctx.setLineDash([]);
        } catch (e) {
            console.error('Error drawing selection highlight:', e);
        }
    }
    
    // Draw grid
    function drawGrid() {
        ctx.strokeStyle = 'rgba(59, 130, 246, 0.2)';
        ctx.lineWidth = 1 / scale;

        const gridSize = 20;
        const startX = Math.floor(-panX / scale / gridSize) * gridSize;
        const startY = Math.floor(-panY / scale / gridSize) * gridSize;
        const endX = startX + (canvasWidth / scale) + gridSize;
        const endY = startY + (canvasHeight / scale) + gridSize;

        for (let x = startX; x <= endX; x += gridSize) {
            ctx.beginPath();
            ctx.moveTo(x, startY);
            ctx.lineTo(x, endY);
            ctx.stroke();
        }

        for (let y = startY; y <= endY; y += gridSize) {
            ctx.beginPath();
            ctx.moveTo(startX, y);
            ctx.lineTo(endX, y);
            ctx.stroke();
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
        showPlotDetails(plot);
        
        // Center on plot
        centerOnPlot(plot);
        
        // Redraw to show selection
        drawAll();
    }
    
    // Show plot details
    function showPlotDetails(plot) {
        const panel = $('#plot-details-panel');
        const content = $('#panel-content');
        
        content.html(`
            <div class="plot-details">
                <h4>${plot.plot_name || 'Plot'}</h4>
                <p><strong>Status:</strong> <span class="plot-status ${plot.status}">${plot.status || 'Unknown'}</span></p>
                <p><strong>Sector:</strong> ${plot.sector || 'N/A'}</p>
                <p><strong>Block:</strong> ${plot.block || 'N/A'}</p>
                <p><strong>Street:</strong> ${plot.street || 'N/A'}</p>
                <p><strong>Plot ID:</strong> ${plot.id || 'N/A'}</p>
                <p><strong>Created:</strong> ${plot.created_at || 'N/A'}</p>
                <button class="btn primary" onclick="expressInterest('${plot.id}')">
                    Express Interest
                </button>
            </div>
        `);
        
        panel.show();
    }
    
    // Center on plot
    function centerOnPlot(plot) {
        if (!plot.coordinates) return;
        
        try {
            const coords = parseCoordinates(plot.coordinates);
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
        $('#show-grid-toggle').click(function() {
            showGrid = !showGrid;
            $(this).toggleClass('active', showGrid);
            $('#grid-overlay').toggle(showGrid);
            drawAll();
        });
        
        // Panel close
        $('#panel-close').click(function() {
            $('#plot-details-panel').hide();
            selectedPlot = null;
            $('.plot-item').removeClass('selected');
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
                    const coords = parseCoordinates(plot.coordinates);
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
        $('#zoom-level').text(Math.round(scale * 100) + '%');
    }
    
    // Setup event listeners
    function setupEventListeners() {
        // Mouse wheel zoom
        $('.canvas-wrapper').on('wheel', function(e) {
            e.preventDefault();
            const delta = e.originalEvent.deltaY > 0 ? 0.9 : 1.1;
            scale = Math.max(0.1, Math.min(5, scale * delta));
            updateZoomDisplay();
            drawAll();
        });
        
        // Pan functionality
        $('.canvas-wrapper').on('mousedown', function(e) {
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
        $('#plot-viewer-canvas').on('click', function(e) {
            if (isDragging) return;
            
            const rect = canvas.getBoundingClientRect();
            const x = (e.clientX - rect.left - panX) / scale;
            const y = (e.clientY - rect.top - panY) / scale;
            
            // Find clicked plot
            plots.forEach(plot => {
                if (plot.coordinates) {
                    try {
                        const coords = parseCoordinates(plot.coordinates);
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
    
    // Global base map functions
    function loadGlobalBaseMap() {
        console.log('🗺️ Loading global base map...');
        
        const ajaxUrl = TajMapFrontend.ajaxUrl || 'http://localhost/Gunbroker/wp-admin/admin-ajax.php';
        
        $.post(ajaxUrl, {
            action: 'tajmap_pb_get_global_base_map',
            nonce: TajMapFrontend.nonce
        }, function(response) {
            if (response.success && response.data.base_map_image_id) {
                console.log('🗺️ Found global base map ID:', response.data.base_map_image_id);
                
                // Get image URL
                $.post(ajaxUrl, {
                    action: 'tajmap_pb_get_image_url',
                    nonce: TajMapFrontend.nonce,
                    image_id: response.data.base_map_image_id
                }, function(imageResponse) {
                    if (imageResponse.success && imageResponse.data.url) {
                        console.log('🗺️ Loading global base map image:', imageResponse.data.url);
                        
                        globalBaseMapImage = new Image();
                        globalBaseMapImage.onload = function() {
                            console.log('🗺️ Global base map image loaded successfully');
                            
                            // Load saved transform if available
                            if (response.data.base_map_transform) {
                                try {
                                    globalBaseMapTransform = JSON.parse(response.data.base_map_transform);
                                    console.log('🗺️ Loaded saved transform:', globalBaseMapTransform);
                                } catch (e) {
                                    console.error('Error parsing base map transform:', e);
                                    // Use default transform
                                    globalBaseMapTransform = {
                                        x: 0, y: 0, scale: 1, rotation: 0,
                                        width: canvasWidth, height: canvasHeight
                                    };
                                }
                            } else {
                                // Calculate default transform to fit canvas
                                const imageAspect = globalBaseMapImage.width / globalBaseMapImage.height;
                                const canvasAspect = canvasWidth / canvasHeight;
                                
                                if (imageAspect > canvasAspect) {
                                    globalBaseMapTransform.width = canvasWidth;
                                    globalBaseMapTransform.height = globalBaseMapImage.height * (canvasWidth / globalBaseMapImage.width);
                                } else {
                                    globalBaseMapTransform.height = canvasHeight;
                                    globalBaseMapTransform.width = globalBaseMapImage.width * (canvasHeight / globalBaseMapImage.height);
                                }
                                
                                globalBaseMapTransform.x = (canvasWidth - globalBaseMapTransform.width) / 2;
                                globalBaseMapTransform.y = (canvasHeight - globalBaseMapTransform.height) / 2;
                            }
                            
                            // Redraw canvas with base map
                            drawAll();
                        };
                        globalBaseMapImage.onerror = function() {
                            console.error('🗺️ Failed to load global base map image');
                        };
                        globalBaseMapImage.src = imageResponse.data.url;
                    } else {
                        console.error('🗺️ Failed to get base map image URL');
                    }
                });
            } else {
                console.log('🗺️ No global base map configured');
            }
        }).fail(function(xhr, status, error) {
            console.error('🗺️ Failed to load global base map setting:', status, error);
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
