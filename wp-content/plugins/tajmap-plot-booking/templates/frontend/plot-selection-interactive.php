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
    background: khaki;
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
    display: none;
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
        console.log('Initializing interactive plot selection...');
        
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
                console.log('📡 AJAX Response:', response);
                
                if (response.success && response.data && response.data.plots) {
                    plots = response.data.plots;
                    console.log('📊 Found', plots.length, 'plots');
                    
                    // Debug each plot with base image info
                    plots.forEach((plot, i) => {
                        console.log(`📊 Plot ${i}:`, {
                            id: plot.id,
                            name: plot.plot_name,
                            status: plot.status,
                            base_image_id: plot.base_image_id,
                            base_image_transform: plot.base_image_transform ? 'YES' : 'NO',
                            coordinates: plot.coordinates,
                            coordinatesType: typeof plot.coordinates
                        });
                    });
                    
                    // Update plot count
                    $('#plot-count').text(plots.length);
                    
                    // Note: Global base map is loaded separately, not per-plot
                    
                    // Render everything and fit to content on initial load
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
            ctx.fillStyle = 'khaki';
            ctx.fillRect(0, 0, canvasWidth, canvasHeight);
            return;
        }
        
        // Clear canvas
        ctx.clearRect(0, 0, canvasWidth, canvasHeight);
        
        // Apply transformations for base map, plots and grid (unified coordinate system)
        ctx.save();
        ctx.translate(panX, panY);
        ctx.scale(scale, scale);

        // Draw global base map image first (background layer) - in world coordinates
        if (globalBaseMapImage) {
            try {
                ctx.drawImage(
                    globalBaseMapImage,
                    globalBaseMapTransform.x,
                    globalBaseMapTransform.y,
                    globalBaseMapTransform.width,
                    globalBaseMapTransform.height
                );
            } catch (e) {
                console.error('Error drawing global base map:', e);
            }
        } else {
            // Fallback to neutral background if no base map
            ctx.fillStyle = '#f8fafc';
            ctx.fillRect(0, 0, canvasWidth / scale, canvasHeight / scale);
        }
        
        console.log('🎨 Transform applied - panX:', panX, 'panY:', panY, 'scale:', scale);
        
        // Draw plots
        plots.forEach((plot, index) => {
            console.log(`🎨 Drawing plot ${index}:`, plot);
            drawPlot(plot, index);
        });
        
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
            const opacity = plot.status === 'available' ? 0.7 : 0.5;
            
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
        $('#zoom-in').click(function() {
            const rect = canvas.getBoundingClientRect();
            const mouseX = rect.left + rect.width / 2;
            const mouseY = rect.top + rect.height / 2;
            const oldScale = scale;
            const newScale = Math.min(scale * 1.2, 5);
            if (newScale !== oldScale) {
                const worldX = (mouseX - rect.left - panX) / oldScale;
                const worldY = (mouseY - rect.top - panY) / oldScale;
                scale = newScale;
                panX = mouseX - rect.left - worldX * scale;
                panY = mouseY - rect.top - worldY * scale;
            }
            updateZoomDisplay();
            drawAll();
        });
        
        $('#zoom-out').click(function() {
            const rect = canvas.getBoundingClientRect();
            const mouseX = rect.left + rect.width / 2;
            const mouseY = rect.top + rect.height / 2;
            const oldScale = scale;
            const newScale = Math.max(scale / 1.2, 0.1);
            if (newScale !== oldScale) {
                const worldX = (mouseX - rect.left - panX) / oldScale;
                const worldY = (mouseY - rect.top - panY) / oldScale;
                scale = newScale;
                panX = mouseX - rect.left - worldX * scale;
                panY = mouseY - rect.top - worldY * scale;
            }
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
        $('#zoom-percentage').text(Math.round(scale * 100) + '%');
    }
    
    // Setup event listeners
    function setupEventListeners() {
        // Mouse wheel zoom
        $('#interactive-map').on('wheel', function(e) {
            e.preventDefault();
            const rect = canvas.getBoundingClientRect();
            const mouseX = e.clientX;
            const mouseY = e.clientY;
            const oldScale = scale;
            const zoomFactor = e.originalEvent.deltaY > 0 ? 0.9 : 1.1;
            const newScale = Math.max(0.1, Math.min(5, scale * zoomFactor));
            if (newScale !== oldScale) {
                const worldX = (mouseX - rect.left - panX) / oldScale;
                const worldY = (mouseY - rect.top - panY) / oldScale;
                scale = newScale;
                panX = mouseX - rect.left - worldX * scale;
                panY = mouseY - rect.top - worldY * scale;
            }
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

    // Base image functions (legacy - for plot-specific images)
    function loadBaseImageForPlot(plot) {
        console.log('loadBaseImageForPlot called for plot:', plot.id, 'base_image_id:', plot.base_image_id);
        if (!plot.base_image_id || plot.base_image_id === 0 || plot.base_image_id === '0') {
            console.log('Plot has no valid base image ID, skipping');
            return;
        }
        
        // Check if already loaded
        if (baseImages[plot.id]) {
            console.log('Base image already loaded for plot:', plot.id);
            return;
        }
        
        console.log('Loading base image for plot:', plot.id);
        
        // Get image URL from WordPress
        $.post(ajaxUrl, {
            action: 'tajmap_pb_get_image_url',
            nonce: TajMapFrontend.nonce,
            image_id: plot.base_image_id
        }, function(response) {
            console.log('AJAX response for plot', plot.id, 'image:', response);
            if (response.success && response.data.url) {
                const img = new Image();
                img.onload = function() {
                    console.log('Base image loaded for plot:', plot.id);
                    baseImages[plot.id] = img;
                    
                    // Load transform data
                    if (plot.base_image_transform) {
                        console.log('Loading transform data for plot:', plot.id, plot.base_image_transform);
                        try {
                            baseImageTransforms[plot.id] = JSON.parse(plot.base_image_transform);
                            console.log('Transform loaded:', baseImageTransforms[plot.id]);
                        } catch (e) {
                            console.error('Error parsing base image transform:', e);
                        }
                    }
                    
                    console.log('Redrawing canvas after base image load');
                    // Redraw canvas
                    drawAll();
                };
                img.onerror = function() {
                    console.error('Failed to load base image for plot:', plot.id, response.data.url);
                };
                img.src = response.data.url;
            } else {
                console.error('Failed to get image URL for plot:', plot.id, response);
            }
        }).fail(function(xhr, status, error) {
            console.error('AJAX failed for plot:', plot.id, status, error);
        });
    }
    
    function drawBaseImages() {
        console.log('Drawing base images, plots with images:', Object.keys(baseImages).length);
        // Draw base images for all plots that have them
        plots.forEach(plot => {
            if (baseImages[plot.id] && baseImageTransforms[plot.id]) {
                const img = baseImages[plot.id];
                const transform = baseImageTransforms[plot.id];
                
                console.log('Drawing base image for plot', plot.id, 'transform:', transform);
                
                // Draw the base image with its transform (in screen coordinates like admin)
                ctx.drawImage(
                    img,
                    transform.x - panX,
                    transform.y - panY,
                    transform.width,
                    transform.height
                );
                
                console.log('Base image drawn at:', transform.x - panX, transform.y - panY, 'size:', transform.width, 'x', transform.height);
            } else {
                if (plot.base_image_id) {
                    console.log('Plot', plot.id, 'has base_image_id but image not loaded:', plot.base_image_id);
                }
            }
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
