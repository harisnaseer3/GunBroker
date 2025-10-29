jQuery(document).ready(function($) {
    'use strict';
    
    console.log('🚀 TajMap Interactive Plot Selection Loaded');
    console.log('TajMapFrontend object:', typeof TajMapFrontend !== 'undefined' ? TajMapFrontend : 'UNDEFINED');
    
    // Global variables
    window.plots = [];
    window.currentPlotId = null;
    window.isPopupVisible = false;
    window.isMouseOverPopup = false;
    let pagedPlots = [];
    let currentPage = 1;
    const pageSize = 12; // paginate after 12 plots
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

    // Mobile device detection
    function isMobileDevice() {
        const userAgent = navigator.userAgent || navigator.vendor || window.opera;
        const mobileRegex = /android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini|mobile|tablet/i;
        return mobileRegex.test(userAgent.toLowerCase());
    }

    const IS_MOBILE = isMobileDevice();
    console.log('📱 Device type:', IS_MOBILE ? 'MOBILE' : 'DESKTOP');

    // Desktop configuration
    const DESKTOP_CONFIG = {
        REFERENCE_CANVAS_WIDTH: 1200,
        REFERENCE_CANVAS_HEIGHT: 600,
        RENDER_SCALE: 2,
        VIEW_OFFSET_RATIO: 0,
        VIEW_OFFSET_Y_RATIO: 0,
        BASE_PLOT_SCALE_X: 1.71,
        BASE_PLOT_SCALE_Y: 1.72,
        PLOT_OFFSET_X_RATIO: 0.00001,
        PLOT_OFFSET_Y_RATIO: 0.00
    };

    // Mobile configuration (you can adjust these values as needed)
    const MOBILE_CONFIG = {
        REFERENCE_CANVAS_WIDTH: 600,  // Smaller reference width for mobile
        REFERENCE_CANVAS_HEIGHT: 800, // Taller reference height for mobile
        RENDER_SCALE: 1.5,             // Lower render scale for performance
        VIEW_OFFSET_RATIO: 0,          // Adjust if needed
        VIEW_OFFSET_Y_RATIO: 0,        // Adjust if needed
        BASE_PLOT_SCALE_X: 0.85,       // Adjust if needed for mobile
        BASE_PLOT_SCALE_Y: 2.3,       // Adjust if needed for mobile
        PLOT_OFFSET_X_RATIO: 0.0,  // Adjust if needed
        PLOT_OFFSET_Y_RATIO: 0.240     // Adjust if needed
    };

    // Select configuration based on device type
    const CONFIG = IS_MOBILE ? MOBILE_CONFIG : DESKTOP_CONFIG;

    // Reference canvas size that plot scales were tuned for
    const REFERENCE_CANVAS_WIDTH = CONFIG.REFERENCE_CANVAS_WIDTH;
    const REFERENCE_CANVAS_HEIGHT = CONFIG.REFERENCE_CANVAS_HEIGHT;
    // Render scale for higher-resolution drawing without changing visual size
    const RENDER_SCALE = CONFIG.RENDER_SCALE;
    // Global view offset: X moves left/right in % of width; Y moves up/down in % of height
    const VIEW_OFFSET_RATIO = CONFIG.VIEW_OFFSET_RATIO; // Center horizontally (0 = no offset)
    const VIEW_OFFSET_Y_RATIO = CONFIG.VIEW_OFFSET_Y_RATIO; // Center vertically (0 = no offset)
    // Base scale for plots layer to match base map regions (fine-tuned for reference canvas size)
    const BASE_PLOT_SCALE_X = CONFIG.BASE_PLOT_SCALE_X; // Horizontal scaling
    const BASE_PLOT_SCALE_Y = CONFIG.BASE_PLOT_SCALE_Y; // Vertical scaling
    // Dynamic plot scale that adjusts with canvas size
    let PLOT_SCALE_X = BASE_PLOT_SCALE_X;
    let PLOT_SCALE_Y = BASE_PLOT_SCALE_Y;
    // Transform plots layer for alignment (negative = left/up, positive = right/down)
    const PLOT_OFFSET_X_RATIO = CONFIG.PLOT_OFFSET_X_RATIO;
    const PLOT_OFFSET_Y_RATIO = CONFIG.PLOT_OFFSET_Y_RATIO;
    
    function getViewOffsetScreen() {
        return canvasWidth * VIEW_OFFSET_RATIO;
    }
    function getViewOffsetScreenY() {
        return canvasHeight * VIEW_OFFSET_Y_RATIO;
    }
    function getPlotOffsetScreenY() {
        return canvasHeight * PLOT_OFFSET_Y_RATIO;
    }
    function getPlotOffsetScreenX() {
        return canvasWidth * PLOT_OFFSET_X_RATIO;
    }

    // Recalculate plot scales based on current canvas size vs reference size
    function updatePlotScales() {
        if (!globalBaseMapImage || !globalBaseMapTransform.width || !globalBaseMapTransform.height) {
            console.log('⚠️ Cannot update plot scales - base map not ready');
            return;
        }

        // Calculate scale ratio between current base map size and reference size
        // We use base map dimensions rather than canvas dimensions because base map might not fill entire canvas
        const referenceBaseMapWidth = REFERENCE_CANVAS_WIDTH;
        const referenceBaseMapHeight = REFERENCE_CANVAS_HEIGHT;

        const scaleRatioX = globalBaseMapTransform.width / referenceBaseMapWidth;
        const scaleRatioY = globalBaseMapTransform.height / referenceBaseMapHeight;

        // Apply base scales with dynamic adjustments
        // Scale X-axis +15%, Y-axis -15%
        PLOT_SCALE_X = BASE_PLOT_SCALE_X * scaleRatioX * 1.175;
        PLOT_SCALE_Y = BASE_PLOT_SCALE_Y * scaleRatioY * 0.835;

        console.log('📐 Plot scales updated:');
        console.log('   Base map size:', globalBaseMapTransform.width, 'x', globalBaseMapTransform.height);
        console.log('   Reference size:', referenceBaseMapWidth, 'x', referenceBaseMapHeight);
        console.log('   Scale ratio X:', scaleRatioX, 'Y:', scaleRatioY);
        console.log('   Final PLOT_SCALE_X:', PLOT_SCALE_X, '(+15% adjustment)');
        console.log('   Final PLOT_SCALE_Y:', PLOT_SCALE_Y, '(-15% adjustment)');
    }

    // Auto-fit coordination flags
    let baseMapReady = false;
    let plotsReady = false;

    function maybeAutoFit() {
        // Auto-fit once both base map and plots are ready (on first load/refresh)
        if (baseMapReady && plotsReady) {
            scale = 1.5;
            panX = 0;
            panY = 0;
            fitToView();
        }
    }
    
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

        // Increase drawing buffer for sharper render; keep CSS size the same
        canvas.style.width = canvasWidth + 'px';
        canvas.style.height = canvasHeight + 'px';
        canvas.width = Math.floor(canvasWidth * RENDER_SCALE);
        canvas.height = Math.floor(canvasHeight * RENDER_SCALE);

        console.log('🔄 Canvas resized:', canvasWidth, 'x', canvasHeight);

        // Refit base map to canvas on resize so it always fits view
        if (globalBaseMapImage) {
            const imageAspect = globalBaseMapImage.width / globalBaseMapImage.height;
            const canvasAspect = canvasWidth / canvasHeight;
            if (imageAspect > canvasAspect) {
                globalBaseMapTransform.width = canvasWidth;
                globalBaseMapTransform.height = globalBaseMapImage.height * (canvasWidth / globalBaseMapImage.width);
            } else {
                globalBaseMapTransform.height = canvasHeight;
                globalBaseMapTransform.width = globalBaseMapImage.width * (canvasHeight / globalBaseMapImage.height);
            }
            // Position at top-left corner
            globalBaseMapTransform.x = 0;
            globalBaseMapTransform.y = 0;

            // Update plot scales to match new base map size
            updatePlotScales();
        }

        // Redraw
        drawAll();
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
        
        const ajaxUrl = TajMapFrontend.ajaxUrl || '/wp-admin/admin-ajax.php';
        console.log('Using AJAX URL:', ajaxUrl);
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: { action: 'tajmap_pb_get_plots' },
            success: function(response) {
                console.log('📡 AJAX Response:', response);
                
                if (response.success && response.data && response.data.plots) {
                    window.plots = response.data.plots;
                    console.log('📊 Found', window.plots.length, 'plots');
                    
                    // Debug each plot with base image info
                    window.plots.forEach((plot, i) => {
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
                    $('#plot-count').text(window.plots.length);
                    
                    // Note: Global base map is loaded separately, not per-plot
                    
                    // Render everything; mark plots ready and auto-fit when base map also ready
                    updatePagination();
                    plotsReady = true;
                    maybeAutoFit();
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
        console.log('🎨 drawAll called - plots:', window.plots.length, 'canvas:', canvasWidth, 'x', canvasHeight);
        
        if (!ctx) {
            console.error('❌ No canvas context');
            return;
        }
        
        if (window.plots.length === 0) {
            console.log('⚠️ No plots to draw');
            // Still draw background
            ctx.fillStyle = 'khaki';
            ctx.fillRect(0, 0, canvasWidth, canvasHeight);
            return;
        }
        
        // Reset to render scale and clear canvas
        ctx.setTransform(RENDER_SCALE, 0, 0, RENDER_SCALE, 0, 0);
        ctx.clearRect(0, 0, canvasWidth, canvasHeight);
        
        // Apply transformations for base map, plots and grid (unified coordinate system)
        ctx.save();
        ctx.translate(panX + getViewOffsetScreen(), panY + getViewOffsetScreenY());
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
        
        // Draw plots with plots-only scaling and X/Y offsets (separate X and Y scaling)
        ctx.save();
        ctx.scale(PLOT_SCALE_X, PLOT_SCALE_Y);
        ctx.translate(getPlotOffsetScreenX() / PLOT_SCALE_X, getPlotOffsetScreenY() / PLOT_SCALE_Y);
        window.plots.forEach((plot, index) => {
            console.log(`🎨 Drawing plot ${index}:`, plot);
            drawPlot(plot, index);
        });
        ctx.restore();
        
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
            let color, opacity;
            if (plot.status === 'available') {
                color = '#10b981'; // Green for available
                opacity = 0; // Fully transparent for available
            } else if (plot.status === 'reserved') {
                color = '#f59e0b'; // Yellow for reserved
                opacity = 0.7; // Solid yellow for reserved
            } else {
                color = '#ef4444'; // Red for sold
                opacity = 0.5;
            }
            
            console.log(`🎨 Plot ${index} style - color: ${color}, opacity: ${opacity}`);
            
            ctx.fillStyle = color;
            ctx.strokeStyle = '#374151';
            ctx.lineWidth = (2 / scale); // base thickness
            ctx.globalAlpha = opacity;
            
            // Draw polygon - apply vertical offset to move plots up for alignment
            const PLOT_OFFSET_Y = -44; // Fine-tuned vertical offset (adjusted from -46, translated -2 on Y-axis)
            
            ctx.beginPath();
            ctx.moveTo(coords[0].x, coords[0].y + PLOT_OFFSET_Y);
            console.log(`🎨 Plot ${index} starting at:`, coords[0].x, coords[0].y + PLOT_OFFSET_Y);
            
            for (let i = 1; i < coords.length; i++) {
                ctx.lineTo(coords[i].x, coords[i].y + PLOT_OFFSET_Y);
                console.log(`🎨 Plot ${index} line to:`, coords[i].x, coords[i].y + PLOT_OFFSET_Y);
            }
            ctx.closePath();
            ctx.fill();
            ctx.stroke();
            
            console.log(`🎨 Plot ${index} polygon drawn`);
            
            // Draw plot name
            if (scale > 0.5) {
                const centerX = coords.reduce((sum, p) => sum + p.x, 0) / coords.length;
                const centerY = (coords.reduce((sum, p) => sum + p.y, 0) / coords.length) + PLOT_OFFSET_Y;
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
    
    // Update pagination data
    function updatePagination() {
        const total = window.plots.length;
        const totalPages = Math.max(1, Math.ceil(total / pageSize));
        if (currentPage > totalPages) currentPage = totalPages;
        const start = (currentPage - 1) * pageSize;
        const end = start + pageSize;
        pagedPlots = window.plots.slice(start, end);
        renderPlotList(totalPages);
    }

    // Render plot list
    function renderPlotList(totalPages = Math.max(1, Math.ceil(window.plots.length / pageSize))) {
        const plotList = $('#plot-list');
        plotList.empty();
        
        pagedPlots.forEach((plot, index) => {
            const plotItem = $(`
                <div class="plot-item" data-plot-id="${plot.id || index}">
                    <div class="plot-name">${plot.plot_name || 'Plot ' + (index + 1)}</div>
                    <div class="plot-status ${plot.status || 'unknown'}">${plot.status || 'Unknown'}</div>
                    <div class="plot-details">
                        Sector: ${plot.sector || 'N/A'} | Type: ${plot.type || 'N/A'}<br>
                        Category: ${plot.category || 'N/A'} | Street: ${plot.street || 'N/A'}
                    </div>
                </div>
            `);
            
            plotItem.click(function() {
                selectPlot(plot);
            });
            
            plotList.append(plotItem);
        });

        // Pagination controls
        const controlsId = 'plot-pagination-controls';
        $('#' + controlsId).remove();
        if (window.plots.length > pageSize) {
            const pagination = $(`
                <div id="${controlsId}" class="pagination">
                    <button id="plot-prev" class="page-btn" ${currentPage === 1 ? 'disabled' : ''} aria-label="Previous page">‹</button>
                    ${renderPageNumbers(totalPages)}
                    <button id="plot-next" class="page-btn" ${currentPage === totalPages ? 'disabled' : ''} aria-label="Next page">›</button>
                </div>
            `);
            plotList.after(pagination);
            $('#plot-prev').on('click', function(){ if (currentPage > 1) { currentPage--; updatePagination(); }});
            $('#plot-next').on('click', function(){ const max = Math.ceil(window.plots.length / pageSize); if (currentPage < max) { currentPage++; updatePagination(); }});
            // number buttons
            $('.page-btn[data-page]').on('click', function(){
                const p = parseInt($(this).data('page'));
                if (!Number.isNaN(p)) {
                    if (p !== currentPage) {
                        currentPage = p;
                        updatePagination();
                    }
                }
            });
        }
    }

    function renderPageNumbers(totalPages) {
        // compact pagination: 1 ... prev current next ... N
        const pages = [];
        const addBtn = (p) => `<button class="page-btn ${p===currentPage?'active':''}" data-page="${p}">${p}</button>`;
        const addDots = () => '<span class="page-dots">…</span>';
        if (totalPages <= 5) {
            for (let p=1;p<=totalPages;p++) pages.push(addBtn(p));
        } else {
            pages.push(addBtn(1));
            if (currentPage > 3) pages.push(addDots());
            const start = Math.max(2, currentPage - 1);
            const end = Math.min(totalPages - 1, currentPage + 1);
            for (let p=start;p<=end;p++) pages.push(addBtn(p));
            if (currentPage < totalPages - 2) pages.push(addDots());
            pages.push(addBtn(totalPages));
        }
        return pages.join('');
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
            <p><strong>Type:</strong> ${plot.type || 'N/A'}</p>
            <p><strong>Category:</strong> ${plot.category || 'N/A'}</p>
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

            // updateZoomDisplay(); // DISABLED
            drawAll();
        } catch (e) {
            console.error('Error centering on plot:', e);
        }
    }
    
    // Setup controls
    function setupControls() {
        // Zoom buttons - DISABLED to prevent misalignment
        // $('#zoom-in').click(function() {
        //     const rect = canvas.getBoundingClientRect();
        //     const mouseX = rect.left + rect.width / 2;
        //     const mouseY = rect.top + rect.height / 2;
        //     const oldScale = scale;
        //     const newScale = Math.min(scale * 1.2, 5);
        //     if (newScale !== oldScale) {
        //         const worldX = (mouseX - rect.left - panX) / oldScale;
        //         const worldY = (mouseY - rect.top - panY) / oldScale;
        //         scale = newScale;
        //         panX = mouseX - rect.left - worldX * scale;
        //         panY = mouseY - rect.top - worldY * scale;
        //     }
        //     updateZoomDisplay();
        //     drawAll();
        // });

        // $('#zoom-out').click(function() {
        //     const rect = canvas.getBoundingClientRect();
        //     const mouseX = rect.left + rect.width / 2;
        //     const mouseY = rect.top + rect.height / 2;
        //     const oldScale = scale;
        //     const newScale = Math.max(scale / 1.2, 0.1);
        //     if (newScale !== oldScale) {
        //         const worldX = (mouseX - rect.left - panX) / oldScale;
        //         const worldY = (mouseY - rect.top - panY) / oldScale;
        //         scale = newScale;
        //         panX = mouseX - rect.left - worldX * scale;
        //         panY = mouseY - rect.top - worldY * scale;
        //     }
        //     updateZoomDisplay();
        //     drawAll();
        // });
        
        $('#fit-view').click(function() {
            fitToView();
        });
        
        $('#reset-view').click(function() {
            scale = 1;
            panX = 0;
            panY = 0;
            // updateZoomDisplay(); // DISABLED
            drawAll();
        });
    }
    
    // Fit to view
    function fitToView() {
        if (window.plots.length === 0) return;
        
        // Calculate bounds
        let minX = Infinity, maxX = -Infinity;
        let minY = Infinity, maxY = -Infinity;
        
        window.plots.forEach(plot => {
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

        // updateZoomDisplay(); // DISABLED
        drawAll();
    }
    
    // Update zoom display - DISABLED (zoom removed)
    // function updateZoomDisplay() {
    //     $('#zoom-percentage').text(Math.round(scale * 100) + '%');
    // }

    // Setup event listeners
    function setupEventListeners() {
        // Mouse wheel zoom - DISABLED to prevent misalignment
        // $('#interactive-map').on('wheel', function(e) {
        //     e.preventDefault();
        //     const rect = canvas.getBoundingClientRect();
        //     const mouseX = e.clientX;
        //     const mouseY = e.clientY;
        //     const oldScale = scale;
        //     const zoomFactor = e.originalEvent.deltaY > 0 ? 0.9 : 1.1;
        //     const newScale = Math.max(0.1, Math.min(5, scale * zoomFactor));
        //     if (newScale !== oldScale) {
        //         const worldX = (mouseX - rect.left - panX - getViewOffsetScreen()) / oldScale;
        //         const worldY = (mouseY - rect.top - panY - getViewOffsetScreenY()) / oldScale;
        //         scale = newScale;
        //         panX = mouseX - rect.left - worldX * scale - getViewOffsetScreen();
        //         panY = mouseY - rect.top - worldY * scale - getViewOffsetScreenY();
        //     }
        //     updateZoomDisplay();
        //     drawAll();
        // });
        
        // Pan functionality - with drag threshold to prevent accidental dragging
        let mouseDownX = 0;
        let mouseDownY = 0;
        let isMouseDown = false;
        const DRAG_THRESHOLD = 5; // pixels - must move this far to start dragging
        
        $('#interactive-map').on('mousedown', function(e) {
            if (e.target === canvas) {
                isMouseDown = true;
                mouseDownX = e.clientX;
                mouseDownY = e.clientY;
                dragStartX = e.clientX - panX;
                dragStartY = e.clientY - panY;
            }
        });
        
        $(document).on('mousemove', function(e) {
            if (isMouseDown && !isDragging) {
                // Check if moved beyond threshold
                const deltaX = Math.abs(e.clientX - mouseDownX);
                const deltaY = Math.abs(e.clientY - mouseDownY);
                if (deltaX > DRAG_THRESHOLD || deltaY > DRAG_THRESHOLD) {
                    isDragging = true;
                    canvas.style.cursor = 'grabbing';
                }
            }
            
            if (isDragging) {
                panX = e.clientX - dragStartX;
                panY = e.clientY - dragStartY;
                drawAll();
            }
        });
        
        $(document).on('mouseup', function() {
            isDragging = false;
            isMouseDown = false;
            canvas.style.cursor = 'default';
        });
        
        // Mouse move for hover popup - immediate show
        let hoverTimeout;
        let lastHoveredPlot = null;

        $('#plot-canvas').on('mousemove', function(e) {
            if (isDragging) return;

            // Clear previous timeout
            clearTimeout(hoverTimeout);

            const rect = canvas.getBoundingClientRect();
            // Convert screen coordinates to world coordinates (account for all transforms)
            const mouseX = (e.clientX - rect.left - panX - getViewOffsetScreen()) / scale;
            const mouseY = (e.clientY - rect.top - panY - getViewOffsetScreenY()) / scale;

            // Find hovered plot - account for plot offset and PLOT_SCALE
            const PLOT_OFFSET_Y = -44; // Same offset used in drawPlot (match drawing offset)
            let hoveredPlot = null;

            // Loop through plots in reverse order (last drawn = on top)
            for (let i = window.plots.length - 1; i >= 0; i--) {
                const plot = window.plots[i];
                if (plot.coordinates) {
                    try {
                        const coords = parseCoordinates(plot.coordinates);
                        // Apply PLOT_SCALE_X/Y and offset to coordinates for hit testing (same as drawing)
                        // First apply the offset, then scale, then apply plot offset X/Y
                        const scaledMouseX = (mouseX - getPlotOffsetScreenX() / scale) / PLOT_SCALE_X;
                        const scaledMouseY = (mouseY - getPlotOffsetScreenY() / scale) / PLOT_SCALE_Y;
                        const offsetCoords = coords.map(p => ({ x: p.x, y: p.y + PLOT_OFFSET_Y }));
                        if (isPointInPolygon(scaledMouseX, scaledMouseY, offsetCoords)) {
                            hoveredPlot = plot;
                            console.log('🎯 Hovered plot:', plot.plot_name, 'ID:', plot.id);
                            break; // Stop at first match (topmost plot)
                        }
                    } catch (e) {
                        console.error('Error checking plot hover:', e);
                    }
                }
            }

            if (hoveredPlot) {
                // Plot is hovered - change cursor to pointer
                canvas.style.cursor = 'pointer';

                if (hoveredPlot.id !== lastHoveredPlot?.id) {
                    // New plot hovered, show popup immediately
                    showHoverPopup(e, hoveredPlot);
                    lastHoveredPlot = hoveredPlot;
                    window.isPopupVisible = true;
                }
                // If same plot, keep popup visible (do nothing)
            } else {
                // No plot hovered - reset cursor
                canvas.style.cursor = isDragging ? 'grabbing' : 'default';

                if (window.isPopupVisible && !window.isMouseOverPopup) {
                    // Hide popup after delay
                    hoverTimeout = setTimeout(() => {
                        if (!window.isMouseOverPopup) {
                            hideHoverPopup();
                            lastHoveredPlot = null;
                            window.isPopupVisible = false;
                        }
                    }, 300); // 300ms delay to allow moving to popup
                }
            }
        });
        
        // Mouse leave canvas - only hide if not over popup
        $('#plot-canvas').on('mouseleave', function() {
            clearTimeout(hoverTimeout);
            // Only hide if mouse is not over popup
            if (!window.isMouseOverPopup) {
                hoverTimeout = setTimeout(() => {
                    if (!window.isMouseOverPopup) {
                        hideHoverPopup();
                        lastHoveredPlot = null;
                        window.isPopupVisible = false;
                    }
                }, 1000); // Give time to move to popup
            }
        });
        
        // Track when mouse enters popup - keep it visible
        $('#plot-hover-popup').on('mouseenter', function() {
            clearTimeout(hoverTimeout);
            window.isMouseOverPopup = true;
            window.isPopupVisible = true; // Ensure popup stays visible
            // Keep popup visible when mouse is over it
        });
        
        // Track when mouse leaves popup - hide after small delay
        $('#plot-hover-popup').on('mouseleave', function() {
            window.isMouseOverPopup = false;
            // Hide popup after small delay when mouse leaves popup
            hoverTimeout = setTimeout(() => {
                if (!window.isMouseOverPopup) {
                    hideHoverPopup();
                    lastHoveredPlot = null;
                    window.isPopupVisible = false;
                }
            }, 200); // Small delay to prevent accidental closing
        });
        
        // Click on plot - only if not dragging and mouse hasn't moved much
        $('#plot-canvas').on('click', function(e) {
            // Ignore clicks if dragging or if mouse moved significantly
            if (isDragging) return;

            // Check if mouse moved significantly since mousedown
            if (isMouseDown) {
                const deltaX = Math.abs(e.clientX - mouseDownX);
                const deltaY = Math.abs(e.clientY - mouseDownY);
                if (deltaX > DRAG_THRESHOLD || deltaY > DRAG_THRESHOLD) {
                    return; // Was a drag attempt, not a click
                }
            }

            const rect = canvas.getBoundingClientRect();
            // Convert screen coordinates to world coordinates (account for all transforms)
            const mouseX = (e.clientX - rect.left - panX - getViewOffsetScreen()) / scale;
            const mouseY = (e.clientY - rect.top - panY - getViewOffsetScreenY()) / scale;

            // Find clicked plot - account for plot offset and PLOT_SCALE
            const PLOT_OFFSET_Y = -44; // Same offset used in drawPlot (match drawing offset)
            window.plots.forEach(plot => {
                if (plot.coordinates) {
                    try {
                        const coords = parseCoordinates(plot.coordinates);
                        // Apply PLOT_SCALE_X/Y and offset to coordinates for hit testing (same as drawing)
                        const scaledMouseX = (mouseX - getPlotOffsetScreenX() / scale) / PLOT_SCALE_X;
                        const scaledMouseY = (mouseY - getPlotOffsetScreenY() / scale) / PLOT_SCALE_Y;
                        const offsetCoords = coords.map(p => ({ x: p.x, y: p.y + PLOT_OFFSET_Y }));
                        if (isPointInPolygon(scaledMouseX, scaledMouseY, offsetCoords)) {
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
        
        const ajaxUrl = TajMapFrontend.ajaxUrl || '/wp-admin/admin-ajax.php';
        
        $.post(ajaxUrl, {
            action: 'tajmap_pb_get_global_base_map',
            nonce: TajMapFrontend.nonce
        }, function(response) {
            console.log('🗺️ AJAX response for base map:', response);
            if (response.success && response.data.base_map_image_id) {
                console.log('🗺️ Found global base map ID:', response.data.base_map_image_id);
                console.log('🗺️ Raw base_map_transform from DB:', response.data.base_map_transform);
                
                // Load saved transform if available (CRITICAL - this is what admin uses!)
                let savedTransform = null;
                if (response.data.base_map_transform) {
                    try {
                        savedTransform = JSON.parse(response.data.base_map_transform);
                        console.log('✅ Successfully parsed saved transform:', savedTransform);
                    } catch (e) {
                        console.error('❌ Error parsing saved base map transform:', e);
                        console.error('❌ Raw value was:', response.data.base_map_transform);
                    }
                } else {
                    console.warn('⚠️ No base_map_transform found in response.data');
                }
                
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
                            console.log('🗺️ Image dimensions:', globalBaseMapImage.width, 'x', globalBaseMapImage.height);
                            console.log('🗺️ Canvas dimensions:', canvasWidth, 'x', canvasHeight);

                            // ALWAYS fit and center the base map to current canvas (same as admin initial setup)
                            // This ensures plots align correctly regardless of canvas size differences
                            const imageAspect = globalBaseMapImage.width / globalBaseMapImage.height;
                            const canvasAspect = canvasWidth / canvasHeight;

                            let imageScale;
                            if (imageAspect > canvasAspect) {
                                // Image is wider - fit to width
                                imageScale = canvasWidth / globalBaseMapImage.width;
                                globalBaseMapTransform.width = canvasWidth;
                                globalBaseMapTransform.height = globalBaseMapImage.height * imageScale;
                            } else {
                                // Image is taller - fit to height
                                imageScale = canvasHeight / globalBaseMapImage.height;
                                globalBaseMapTransform.width = globalBaseMapImage.width * imageScale;
                                globalBaseMapTransform.height = canvasHeight;
                            }

                            // Center the image
                            globalBaseMapTransform.x = (canvasWidth - globalBaseMapTransform.width) / 2;
                            globalBaseMapTransform.y = (canvasHeight - globalBaseMapTransform.height) / 2;
                            globalBaseMapTransform.scale = imageScale;

                            console.log('📐 Calculated transform for user canvas:');
                            console.log('   - x:', globalBaseMapTransform.x);
                            console.log('   - y:', globalBaseMapTransform.y);
                            console.log('   - width:', globalBaseMapTransform.width);
                            console.log('   - height:', globalBaseMapTransform.height);
                            console.log('   - scale:', globalBaseMapTransform.scale);

                            // Update plot scales to match base map size
                            updatePlotScales();

                            // Mark base map ready
                            baseMapReady = true;
                            // Set default view (no pan/zoom - show full map)
                            scale = 1;
                            panX = 0;
                            panY = 0;
                            console.log('🎯 View settings - scale:', scale, 'panX:', panX, 'panY:', panY);
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
        window.plots.forEach(plot => {
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
    
    // Hover popup functions (removed duplicate - using the one below)
    
    function hideHoverPopup() {
        const popup = $('#plot-hover-popup');
        popup.removeClass('show');
        setTimeout(() => {
            popup.hide();
            window.isPopupVisible = false;
            window.isMouseOverPopup = false;
        }, 200);
    }
    
    // Contact form functions (moved to global scope)
    
    // Set the current hovered plot ID when showing popup
    function showHoverPopup(event, plot) {
        console.log('📋 Showing popup for plot:', {
            id: plot.id,
            name: plot.plot_name,
            status: plot.status,
            sector: plot.sector,
            block: plot.block,
            street: plot.street
        });

        const popup = $('#plot-hover-popup');
        const rect = canvas.getBoundingClientRect();

        // Store the current plot ID for contact form
        window.currentHoveredPlotId = plot.id;

        // Update popup content with fallbacks
        $('#popup-plot-name').text(plot.plot_name || 'Unknown Plot');
        $('#popup-plot-status').text(plot.status || 'Unknown').removeClass('available sold').addClass(plot.status || 'available');
        $('#popup-plot-sector').text(plot.sector || 'N/A');
        $('#popup-plot-type').text(plot.type || 'N/A');
        $('#popup-plot-category').text(plot.category || 'N/A');
        $('#popup-plot-street').text(plot.street || 'N/A');

        // Show description if available
        if (plot.description && plot.description.trim()) {
            $('#popup-plot-description').text(plot.description);
            $('#popup-description-row').show();
        } else {
            $('#popup-description-row').hide();
        }

        // Position popup - use absolute positioning relative to the page
        const mouseX = event.clientX;
        const mouseY = event.clientY;

        // Calculate popup position (very close to mouse)
        const popupWidth = 300;
        const popupHeight = 250;
        const offsetX = 15; // Offset from cursor
        const offsetY = 15; // Offset from cursor

        let left = mouseX + offsetX;
        let top = mouseY + offsetY;

        // Adjust if popup would go off screen (check against window dimensions)
        const windowWidth = $(window).width();
        const windowHeight = $(window).height();

        if (left + popupWidth > windowWidth) {
            left = mouseX - popupWidth - offsetX;
        }
        if (top + popupHeight > windowHeight) {
            top = mouseY - popupHeight - offsetY;
        }

        // Ensure popup stays within bounds
        left = Math.max(10, Math.min(left, windowWidth - popupWidth - 10));
        top = Math.max(10, Math.min(top, windowHeight - popupHeight - 10));

        popup.css({
            left: left + 'px',
            top: top + 'px',
            display: 'block',
            position: 'fixed' // Use fixed positioning to position relative to viewport
        });

        // Show with animation
        setTimeout(() => {
            popup.addClass('show');
        }, 10);
    }
    
    // Contact form submission
    $(document).ready(function() {
        $('#contact-form').on('submit', function(e) {
            e.preventDefault();
            
            const formData = {
                action: 'tajmap_pb_save_lead',
                plot_id: window.currentPlotId,
                name: $('#contact-name').val(),
                email: $('#contact-email').val(),
                phone: $('#contact-phone').val(),
                message: $('#contact-message').val(),
                nonce: TajMapFrontend ? TajMapFrontend.nonce : ''
            };
            
            // Validate required fields
            if (!formData.name || !formData.phone || !formData.message) {
                alert('Please fill in all required fields.');
                return;
            }
            
            // Disable submit button
            const submitBtn = $(this).find('button[type="submit"]');
            submitBtn.prop('disabled', true).text('Sending...');
            
            // Send AJAX request
            $.ajax({
                url: TajMapFrontend ? TajMapFrontend.ajaxUrl : '/wp-admin/admin-ajax.php',
                type: 'POST',
                data: formData,
                success: function(response) {
                    console.log('Contact form response:', response);
                    
                    if (response.success) {
                        alert('Thank you! Your message has been sent to the admin.');
                        closeContactForm();
                    } else {
                        alert('Error: ' + (response.data || 'Failed to send message. Please try again.'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Contact form error:', status, error);
                    alert('Error sending message. Please try again.');
                },
                complete: function() {
                    // Re-enable submit button
                    submitBtn.prop('disabled', false).text('Send Message');
                }
            });
        });
    });
    
    // Express interest function
    window.expressInterest = function(plotId) {
        alert('Interest expressed for plot ID: ' + plotId);
        console.log('Interest expressed for plot:', plotId);
    };
    
    // Initialize
    init();
});

// Global functions for contact form
function openContactForm() {
    console.log('openContactForm called - jQuery available:', typeof jQuery !== 'undefined');
    // Get the currently hovered plot ID
    window.currentPlotId = getCurrentHoveredPlotId();
    console.log('Current plot ID:', window.currentPlotId);
    
    // Get plot details for auto-filling message
    const plot = window.plots.find(p => p.id == window.currentPlotId);
    console.log('Found plot:', plot);
    
    if (plot) {
        // Update modal header with plot name
        jQuery('.modal-header h3').text(`Inquire about Plot: ${plot.plot_name || 'N/A'}`);
        
        // Auto-fill message with plot details
        const plotDetails = `Plot: ${plot.plot_name || 'N/A'}\nSector: ${plot.sector || 'N/A'}\nType: ${plot.type || 'N/A'}\nCategory: ${plot.category || 'N/A'}\nStreet: ${plot.street || 'N/A'}\n\nI am interested in this plot. Please provide more information about availability and pricing.`;
        jQuery('#contact-message').val(plotDetails);
    }
    
    console.log('Showing contact modal');
    jQuery('#contact-modal').show();
    jQuery('body').css('overflow', 'hidden'); // Prevent background scrolling
}

function closeContactForm() {
    jQuery('#contact-modal').hide();
    jQuery('body').css('overflow', ''); // Restore scrolling
    jQuery('#contact-form')[0].reset(); // Reset form
    jQuery('.modal-header h3').text('Contact Admin'); // Reset header
    window.currentPlotId = null;
}

function getCurrentHoveredPlotId() {
    // This would need to be set when hovering over a plot
    // For now, we'll use a global variable or find another way
    return window.currentHoveredPlotId || null;
}
