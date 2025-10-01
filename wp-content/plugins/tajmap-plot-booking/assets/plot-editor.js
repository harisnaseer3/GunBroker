// TajMap Advanced Plot Editor JavaScript

(function($) {
    'use strict';

    // Global editor state
    let canvas, ctx, image, scale = 1, panX = 0, panY = 0;
    let currentTool = 'select';
    let drawing = false;
    let currentShape = null;
    let selectedPlot = null;
    let plots = [];
    let isFullscreen = false;
    let showGrid = false;
    let snapToGrid = false;
    let gridSize = 20;
    let history = [];
    let historyIndex = -1;
    let isInitialized = false;

    // Mouse state
    let mouseX = 0, mouseY = 0;
    let startX = 0, startY = 0;
    let isDragging = false;
    let dragOffsetX = 0, dragOffsetY = 0;

    $(document).ready(function() {
        initializePlotEditor();
    });

    function initializePlotEditor() {
        // Prevent multiple initializations
        if (isInitialized) {
            return;
        }

        canvas = $('#plot-editor-canvas')[0];
        
        if (!canvas) {
            console.error('Canvas element not found');
            return;
        }
        
        ctx = canvas.getContext('2d');

        if (!ctx) {
            console.error('Canvas context not available');
            return;
        }

        // Set canvas size
        resizeCanvas();

        // Load existing plots from the page data
        loadPlotsData();

        // Bind event listeners
        bindEvents();

        // Initialize tool palette
        initializeToolPalette();

        // Mark as initialized
        isInitialized = true;

        // Draw everything
        drawAll();
    }

    function loadPlotsData() {
        // Try to get plots data from a global variable or data attribute
        if (typeof window.plotEditorPlots !== 'undefined') {
            plots = window.plotEditorPlots;
        } else {
            // Fallback: try to get from data attribute
            const plotsData = $('#plot-editor-canvas').data('plots');
            if (plotsData) {
                plots = plotsData;
            }
        }

        // Parse coordinates for each plot
        plots.forEach(plot => {
            if (typeof plot.coordinates === 'string') {
                try {
                    plot.points = JSON.parse(plot.coordinates);
                } catch (e) {
                    console.error('Error parsing coordinates for plot:', plot.id);
                    plot.points = [];
                }
            }
        });
    }

    function resizeCanvas() {
        const container = $('.editor-canvas-wrapper');
        if (!container.length) return;

        const rect = container[0].getBoundingClientRect();

        canvas.width = rect.width - 2; // Account for border
        canvas.height = rect.height - 2;

        $('#canvas-info').text(`Canvas: ${canvas.width}x${canvas.height}px`);
        drawAll();
    }

    function bindEvents() {
        // Unbind existing events to prevent duplicates
        if (canvas) {
            $(canvas).off('mousedown mousemove mouseup wheel');
        }
        $('.tool-btn').off('click');
        $('#fullscreen-toggle').off('click');
        $('#zoom-in').off('click');
        $('#zoom-out').off('click');
        $('#fit-view').off('click');
        $('#snap-grid-toggle').off('click');
        $('#show-grid-toggle').off('click');
        $('#undo-btn').off('click');
        $('#clear-btn').off('click');
        $('#save-plot').off('click');
        $('#delete-plot').off('click');
        $('#duplicate-plot').off('click');
        $('#upload-base-image').off('click');
        $('#upload-plot-image').off('click');
        $('#plots-list').off('click');
        $(window).off('resize');

        // Canvas events
        if (canvas) {
            $(canvas).on('mousedown', handleMouseDown);
            $(canvas).on('mousemove', handleMouseMove);
            $(canvas).on('mouseup', handleMouseUp);
            $(canvas).on('dblclick', handleDoubleClick);
            $(canvas).on('wheel', handleMouseWheel);
        } else {
            console.error('Canvas not available for event binding');
        }

        // Tool palette events
        $('.tool-btn').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const tool = $(this).data('tool');
            // Only handle tool selection for buttons with data-tool attribute
            if (tool) {
                selectTool(tool);
            }
        });

        // Control events
        $('#fullscreen-toggle').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            toggleFullscreen();
        });
        $('#zoom-in').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            zoom(1.2);
        });
        $('#zoom-out').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            zoom(0.8);
        });
        $('#fit-view').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            fitToView();
        });
        $('#snap-grid-toggle').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            toggleSnapToGrid();
        });
        $('#show-grid-toggle').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            toggleShowGrid();
        });
        $('#undo-btn').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            undo();
        });
        $('#clear-btn').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            clearAll();
        });

        // Form and button events - ensure single execution per action
        $('#save-plot').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            savePlot(e);
        });
        $('#delete-plot').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const plotId = $('#plot-id').val();
            if (plotId) {
                deletePlot(plotId);
            } else if (selectedPlot) {
                // For unsaved plots, call deletePlot with null ID (it will handle the confirmation)
                deletePlot(null);
            }
        });
        $('#duplicate-plot').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            duplicatePlot();
        });
        $('#upload-base-image').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            uploadBaseImage();
        });
        $('#upload-plot-image').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            uploadPlotImage();
        });

        // Plot list events (using event delegation)
        $('#plots-list').on('click', '.plot-list-item', function() {
            const plotId = $(this).data('id');
            loadPlot(plotId);
        });

        $('#plots-list').on('click', '.btn-icon.edit', function(e) {
            e.stopPropagation();
            const plotId = $(this).closest('.plot-list-item').data('id');
            loadPlot(plotId);
        });

        $('#plots-list').on('click', '.btn-icon.delete', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const plotId = $(this).closest('.plot-list-item').data('id');
            if (plotId) {
                deletePlot(plotId);
            }
        });

        // Plot details panel events
        $('#close-plot-details').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            hidePlotDetails();
        });

        $('#edit-plot-from-details').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (selectedPlot) {
                // Switch to select tool and focus on the plot
                selectTool('select');
                $('#editor-status').text(`Editing: ${selectedPlot.plot_name || 'Unnamed Plot'}`);
            }
        });

        $('#delete-plot-from-details').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (selectedPlot) {
                const plotId = selectedPlot.id;
                if (plotId) {
                    deletePlot(plotId);
                } else {
                    deletePlot(null);
                }
                hidePlotDetails();
            }
        });

        // Window resize
        $(window).on('resize', resizeCanvas);
        
        // Keyboard events
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && drawing) {
                cancelDrawing();
            }
        });
    }

    function initializeToolPalette() {
        // Set initial tool
        selectTool('select');
    }

    function selectTool(tool) {
        // Cancel any active drawing when switching tools
        if (drawing && currentShape) {
            finishCurrentShape();
        }
        
        currentTool = tool;
        $('.tool-btn').removeClass('active');
        $(`.tool-btn[data-tool="${tool}"]`).addClass('active');
        
        // Provide specific instructions for each tool
        let statusText = `Tool: ${tool.charAt(0).toUpperCase() + tool.slice(1)}`;
        if (tool === 'vertex' && selectedPlot) {
            statusText += ' - Click and drag vertices to edit';
        } else if (tool === 'vertex' && !selectedPlot) {
            statusText += ' - Select a plot first to edit vertices';
        } else if (tool === 'move' && selectedPlot) {
            statusText += ' - Click and drag to move the entire plot';
        } else if (tool === 'move' && !selectedPlot) {
            statusText += ' - Select a plot first to move it';
        } else if (tool === 'select') {
            statusText += ' - Click on plots to select them';
        } else if (tool === 'polygon') {
            statusText += ' - Click to add points, double-click to finish';
        } else if (tool === 'rectangle') {
            statusText += ' - Click and drag to create rectangle';
        }
        
        $('#editor-status').text(statusText);
    }

    function handleMouseDown(e) {
        const rect = canvas.getBoundingClientRect();
        mouseX = (e.clientX - rect.left - panX) / scale;
        mouseY = (e.clientY - rect.top - panY) / scale;

        if (snapToGrid) {
            mouseX = Math.round(mouseX / gridSize) * gridSize;
            mouseY = Math.round(mouseY / gridSize) * gridSize;
        }

        startX = mouseX;
        startY = mouseY;

        switch (currentTool) {
            case 'polygon':
                if (!drawing) {
                    startPolygon();
                } else {
                    addPolygonPoint();
                }
                break;
            case 'rectangle':
                startRectangle();
                break;
            case 'select':
                selectShape();
                break;
            case 'vertex':
                editVertices();
                break;
            case 'move':
                startMove();
                break;
        }
    }

    function handleMouseMove(e) {
        const rect = canvas.getBoundingClientRect();
        mouseX = (e.clientX - rect.left - panX) / scale;
        mouseY = (e.clientY - rect.top - panY) / scale;

        if (snapToGrid) {
            mouseX = Math.round(mouseX / gridSize) * gridSize;
            mouseY = Math.round(mouseY / gridSize) * gridSize;
        }

        if (drawing && currentShape) {
            updateCurrentShape();
        } else if (isDragging) {
            updateDrag();
        }

        drawAll();
    }

    function handleMouseUp(e) {
        if (drawing && currentShape) {
            if (currentShape.type === 'polygon') {
                // For polygons, don't finish on mouse up - let user click to add points
            } else {
                finishCurrentShape();
            }
        } else if (isDragging) {
            finishDrag();
        }
    }

    function handleMouseWheel(e) {
        e.preventDefault();
        
        // Get mouse position relative to canvas
        const rect = canvas.getBoundingClientRect();
        const mouseX = e.clientX - rect.left;
        const mouseY = e.clientY - rect.top;
        
        // Use originalEvent to get the actual wheel event properties
        const originalEvent = e.originalEvent || e;
        
        // Debug: Log all available properties
        console.log('Wheel event properties:', {
            deltaY: originalEvent.deltaY,
            deltaX: originalEvent.deltaX,
            deltaZ: originalEvent.deltaZ,
            deltaMode: originalEvent.deltaMode,
            wheelDelta: originalEvent.wheelDelta,
            detail: originalEvent.detail,
            type: originalEvent.type
        });
        
        // Try to detect scroll direction using multiple methods
        let scrollUp = false;
        
        // Method 1: Check deltaY (modern browsers)
        if (originalEvent.deltaY !== undefined && originalEvent.deltaY !== 0) {
            scrollUp = originalEvent.deltaY < 0;
        }
        // Method 2: Check wheelDelta (older browsers)
        else if (originalEvent.wheelDelta !== undefined) {
            scrollUp = originalEvent.wheelDelta > 0;
        }
        // Method 3: Check detail (Firefox)
        else if (originalEvent.detail !== undefined) {
            scrollUp = originalEvent.detail < 0;
        }
        // Method 4: Check deltaX (horizontal scroll)
        else if (originalEvent.deltaX !== undefined && originalEvent.deltaX !== 0) {
            scrollUp = originalEvent.deltaX < 0;
        }
        // Method 5: Use a simple toggle for testing
        else {
            // If we can't detect direction, use a simple toggle
            scrollUp = Math.random() > 0.5; // This is just for testing
        }
        
        const zoomFactor = scrollUp ? 1.1 : 0.9;
        console.log('Scroll up:', scrollUp, 'Zoom factor:', zoomFactor);
        
        const oldScale = scale;
        
        // Calculate new scale with limits
        const newScale = Math.max(0.1, Math.min(5, scale * zoomFactor));
        
        if (newScale !== oldScale) {
            // Calculate zoom point in world coordinates
            const worldX = (mouseX - panX) / oldScale;
            const worldY = (mouseY - panY) / oldScale;
            
            // Update scale
            scale = newScale;
            
            // Adjust pan to zoom towards mouse cursor
            panX = mouseX - worldX * scale;
            panY = mouseY - worldY * scale;
            
            // Update zoom level display
            $('#zoom-level').text(Math.round(scale * 100) + '%');
            
            // Redraw
            drawAll();
        }
    }

    function handleDoubleClick(e) {
        if (drawing && currentShape && currentShape.type === 'polygon') {
            finishCurrentShape();
        }
    }

    // Drawing functions
    function startPolygon() {
        if (drawing) {
            return;
        }

        drawing = true;
        currentShape = {
            type: 'polygon',
            points: [{x: mouseX, y: mouseY}],
            status: 'available'
        };

        addToHistory();
    }

    function addPolygonPoint() {
        if (!currentShape || currentShape.type !== 'polygon') return;
        
        currentShape.points.push({x: mouseX, y: mouseY});
    }

    function startRectangle() {
        if (drawing) {
            return;
        }

        drawing = true;
        currentShape = {
            type: 'rectangle',
            startX: mouseX,
            startY: mouseY,
            endX: mouseX,
            endY: mouseY,
            status: 'available'
        };

        addToHistory();
    }

    function updateCurrentShape() {
        if (!currentShape) {
            return;
        }

        if (currentShape.type === 'polygon') {
            // For polygon, only update the last point to show preview
            // Don't add new points on mouse move - only on mouse clicks
            if (currentShape.points.length > 0) {
                currentShape.points[currentShape.points.length - 1] = {x: mouseX, y: mouseY};
            }
        } else if (currentShape.type === 'rectangle') {
            currentShape.endX = mouseX;
            currentShape.endY = mouseY;
        }
    }

    function finishCurrentShape() {
        if (!currentShape) return;

        if (currentShape.type === 'polygon') {
            if (currentShape.points.length >= 3) {
                // Close the polygon
                currentShape.points.push({x: startX, y: startY});

                // If we have a temporary plot (from createNewPlot), update it
                if (selectedPlot && !selectedPlot.id) {
                    selectedPlot.points = currentShape.points;
                    selectedPlot.type = 'polygon';
                } else {
                    // Otherwise, add as a new plot
                    plots.push(currentShape);
                }

                // Update form with plot coordinates
                $('#plot-coordinates').val(JSON.stringify(currentShape.points, null, 2));

                // Update the plot list
                updatePlotList();

                currentShape = null;
                drawing = false;
            }
        } else if (currentShape.type === 'rectangle') {
            const width = Math.abs(currentShape.endX - currentShape.startX);
            const height = Math.abs(currentShape.endY - currentShape.startY);

            if (width > 5 && height > 5) {
                // Convert rectangle to polygon points
                currentShape.points = [
                    {x: currentShape.startX, y: currentShape.startY},
                    {x: currentShape.endX, y: currentShape.startY},
                    {x: currentShape.endX, y: currentShape.endY},
                    {x: currentShape.startX, y: currentShape.endY}
                ];
                currentShape.type = 'polygon';

                // If we have a temporary plot (from createNewPlot), update it
                if (selectedPlot && !selectedPlot.id) {
                    selectedPlot.points = currentShape.points;
                    selectedPlot.type = 'polygon';
                } else {
                    // Otherwise, add as a new plot
                    plots.push(currentShape);
                }

                // Update form with plot coordinates
                $('#plot-coordinates').val(JSON.stringify(currentShape.points, null, 2));

                // Update the plot list
                updatePlotList();

                currentShape = null;
                drawing = false;
            }
        }

        drawAll();
    }

    function cancelDrawing() {
        drawing = false;
        currentShape = null;
        drawAll();
    }

    function selectShape() {
        // Find shape under cursor
        for (let i = plots.length - 1; i >= 0; i--) {
            const plot = plots[i];
            if (isPointInPolygon({x: mouseX, y: mouseY}, plot.points)) {
                selectedPlot = plot;
                break;
            }
        }

        if (selectedPlot) {
            loadPlotIntoForm(selectedPlot);
            showPlotDetails(selectedPlot);
            
            // Update plot list selection
            $('.plot-list-item').removeClass('selected');
            if (selectedPlot.id) {
                $(`.plot-list-item[data-id="${selectedPlot.id}"]`).addClass('selected');
            }
        } else {
            hidePlotDetails();
        }
    }

    function editVertices() {
        if (!selectedPlot || !selectedPlot.points) return;

        // Find closest vertex
        let closestVertex = null;
        let closestDistance = Infinity;

        selectedPlot.points.forEach((point, index) => {
            const distance = Math.sqrt(Math.pow(point.x - mouseX, 2) + Math.pow(point.y - mouseY, 2));
            if (distance < 10 && distance < closestDistance) {
                closestDistance = distance;
                closestVertex = {point, index};
            }
        });

        if (closestVertex) {
            // Start dragging vertex
            isDragging = true;
            dragOffsetX = closestVertex.index; // Store the vertex index
            dragOffsetY = 0; // Not used for vertex editing
            startX = mouseX;
            startY = mouseY;
        }
    }

    function startMove() {
        if (!selectedPlot) return;

        isDragging = true;
        dragOffsetX = mouseX - selectedPlot.points[0].x;
        dragOffsetY = mouseY - selectedPlot.points[0].y;
    }

    function updateDrag() {
        if (!isDragging) return;

        if (currentTool === 'vertex' && selectedPlot && selectedPlot.points) {
            // Update vertex position
            const vertexIndex = dragOffsetX;
            if (selectedPlot.points[vertexIndex]) {
                selectedPlot.points[vertexIndex].x = mouseX;
                selectedPlot.points[vertexIndex].y = mouseY;
                
                // Update coordinates field in real-time
                updateCoordinatesField();
            }
        } else if (currentTool === 'move' && selectedPlot && selectedPlot.points) {
            // Move entire shape
            const deltaX = mouseX - startX;
            const deltaY = mouseY - startY;

            selectedPlot.points.forEach(point => {
                point.x += deltaX;
                point.y += deltaY;
            });

            startX = mouseX;
            startY = mouseY;
            
            // Update coordinates field in real-time
            updateCoordinatesField();
        }
    }

    function finishDrag() {
        isDragging = false;
        if (selectedPlot) {
            addToHistory();
            // Update coordinates field when plot is modified
            updateCoordinatesField();
        }
    }

    // View controls
    function toggleFullscreen() {
        if (!document.fullscreenElement && !document.webkitFullscreenElement && !document.mozFullScreenElement && !document.msFullscreenElement) {
            // Enter fullscreen
            if (document.documentElement.requestFullscreen) {
                document.documentElement.requestFullscreen();
            } else if (document.documentElement.webkitRequestFullscreen) {
                document.documentElement.webkitRequestFullscreen();
            } else if (document.documentElement.mozRequestFullScreen) {
                document.documentElement.mozRequestFullScreen();
            } else if (document.documentElement.msRequestFullscreen) {
                document.documentElement.msRequestFullscreen();
            }
        } else {
            // Exit fullscreen
            if (document.exitFullscreen) {
                document.exitFullscreen();
            } else if (document.webkitExitFullscreen) {
                document.webkitExitFullscreen();
            } else if (document.mozCancelFullScreen) {
                document.mozCancelFullScreen();
            } else if (document.msExitFullscreen) {
                document.msExitFullscreen();
            }
        }
    }

    function zoom(factor) {
        scale *= factor;
        scale = Math.max(0.1, Math.min(5, scale));
        $('#zoom-level').text(Math.round(scale * 100) + '%');
        drawAll();
    }

    function fitToView() {
        if (plots.length === 0) {
            // If no plots, reset to default view
            scale = 1;
            panX = 0;
            panY = 0;
            $('#zoom-level').text(Math.round(scale * 100) + '%');
            drawAll();
            return;
        }

        let minX = Infinity, maxX = -Infinity, minY = Infinity, maxY = -Infinity;

        plots.forEach(plot => {
            if (plot.points) {
                plot.points.forEach(point => {
                    minX = Math.min(minX, point.x);
                    maxX = Math.max(maxX, point.x);
                    minY = Math.min(minY, point.y);
                    maxY = Math.max(maxY, point.y);
                });
            }
        });

        const padding = 50;
        const canvasWidth = canvas.width;
        const canvasHeight = canvas.height;

        const scaleX = (canvasWidth - padding * 2) / (maxX - minX);
        const scaleY = (canvasHeight - padding * 2) / (maxY - minY);
        scale = Math.min(scaleX, scaleY, 5); // Limit max zoom
        scale = Math.max(scale, 0.1); // Limit min zoom

        panX = (canvasWidth - (maxX + minX) * scale) / 2;
        panY = (canvasHeight - (maxY + minY) * scale) / 2;

        $('#zoom-level').text(Math.round(scale * 100) + '%');
        drawAll();
    }

    function toggleSnapToGrid() {
        snapToGrid = !snapToGrid;
        $('#snap-grid-toggle').toggleClass('active', snapToGrid);
    }

    function toggleShowGrid() {
        showGrid = !showGrid;
        $('#show-grid-toggle').toggleClass('active', showGrid);
        drawAll();
    }

    function undo() {
        if (historyIndex > 0) {
            historyIndex--;
            plots = JSON.parse(JSON.stringify(history[historyIndex]));
            drawAll();
        }
    }

    function clearAll() {
        if (confirm('Are you sure you want to clear all plots? This cannot be undone.')) {
            addToHistory();
            plots = [];
            selectedPlot = null;
            $('#plot-properties-form')[0].reset();
            drawAll();
        }
    }

    function addToHistory() {
        history = history.slice(0, historyIndex + 1);
        history.push(JSON.parse(JSON.stringify(plots)));
        historyIndex = history.length - 1;

        // Limit history size
        if (history.length > 50) {
            history = history.slice(-50);
            historyIndex = 49;
        }
    }

    // File upload functions
    function uploadBaseImage() {
        if (typeof wp === 'undefined' || !wp.media) {
            alert('WordPress media library not available');
            return;
        }

        const frame = wp.media({
            title: 'Select Base Map Image',
            button: { text: 'Use this image' },
            multiple: false,
            library: { type: 'image' }
        });

        frame.on('select', function() {
            const attachment = frame.state().get('selection').first().toJSON();
            $('#base-image-id').val(attachment.id);
            loadBaseImage(attachment.id);
        });

        frame.open();
    }

    function uploadPlotImage() {
        if (typeof wp === 'undefined' || !wp.media) {
            alert('WordPress media library not available');
            return;
        }

        const frame = wp.media({
            title: 'Select Plot Preview Image',
            button: { text: 'Use this image' },
            multiple: false,
            library: { type: 'image' }
        });

        frame.on('select', function() {
            const attachment = frame.state().get('selection').first().toJSON();
            $('#plot-image-id').val(attachment.id);
            $('#plot-image-preview').html(`<img src="${attachment.url}" alt="Plot preview" style="max-width: 100px; max-height: 100px;">`);
        });

        frame.open();
    }

    function loadBaseImage(imageId) {
        if (!imageId) {
            image = null;
            drawAll();
            return;
        }

        if (typeof wp === 'undefined' || !wp.media) {
            console.error('WordPress media library not available');
            return;
        }

        wp.media.attachment(imageId).fetch().then(function() {
            const url = wp.media.attachment(imageId).get('url');
            image = new Image();
            image.onload = function() {
                drawAll();
            };
            image.onerror = function() {
                console.error('Failed to load image:', url);
            };
            image.src = url;
            $('#base-image-preview').html(`<img src="${url}" alt="Base map" style="max-width: 100px; max-height: 100px;">`);
        }).catch(function(err) {
            console.error('Error loading image:', err);
        });
    }

    // Plot management
    function loadPlot(plotId) {
        let plot;

        // Handle both saved plots (with ID) and new plots (without ID)
        if (plotId) {
            plot = plots.find(p => p.id == plotId);
        } else {
            // For new plots, find the most recently created one
            plot = plots.find(p => !p.id);
        }

        if (!plot) return;

        selectedPlot = plot;
        loadPlotIntoForm(plot);
        showPlotDetails(plot);

        // Visual feedback
        $('.plot-list-item').removeClass('selected');
        if (plotId) {
            $(`.plot-list-item[data-id="${plotId}"]`).addClass('selected');
        } else {
            // For new plots, highlight the first item or find by content
            $('.plot-list-item').first().addClass('selected');
        }
    }

    function loadPlotIntoForm(plot) {
        $('#plot-id').val(plot.id || '');
        $('#plot-name').val(plot.plot_name || '');
        $('#plot-sector').val(plot.sector || '');
        $('#plot-block').val(plot.block || '');
        $('#plot-street').val(plot.street || '');
        $('#plot-status').val(plot.status || 'available');
        $('#plot-price').val(plot.price || '');
        $('#plot-area').val(plot.area || '');

        // Update coordinates field with current plot points
        if (plot.points && plot.points.length > 0) {
            $('#plot-coordinates').val(JSON.stringify(plot.points, null, 2));
        } else {
            $('#plot-coordinates').val('');
        }

        if (plot.base_image_id) {
            $('#base-image-id').val(plot.base_image_id);
            loadBaseImage(plot.base_image_id);
        }

        // Update status message
        $('#editor-status').text(`Editing plot: ${plot.plot_name || 'Unnamed Plot'}`);
    }

    function savePlot(e) {
        e.preventDefault();

        const formData = $('#plot-properties-form').serializeArray();
        const plotData = {};

        formData.forEach(field => {
            plotData[field.name] = field.value;
        });

        // Get coordinates
        try {
            plotData.coordinates = $('#plot-coordinates').val();
            if (!plotData.coordinates || plotData.coordinates.trim() === '') {
                alert('Please draw a plot shape first.');
                return;
            }
        } catch (e) {
            alert('Invalid coordinates format');
            return;
        }

        // Validate required fields
        if (!plotData.plot_name || plotData.plot_name.trim() === '') {
            alert('Please enter a plot name.');
            $('#plot-name').focus();
            return;
        }

        // Show loading spinner
        const $saveBtn = $('#save-plot');
        $saveBtn.addClass('loading');
        $saveBtn.find('.spinner').show();

        // Save via AJAX
        $.post(TajMapPB.ajaxUrl, {
            action: 'tajmap_pb_save_plot',
            nonce: TajMapPB.nonce,
            ...plotData
        }, function(response) {
            if (response.success) {
                alert('Plot saved successfully!');

                // Update local plot data
                if (response.data && response.data.id) {
                    // Update existing plot or add new plot
                    const existingIndex = plots.findIndex(p => p.id == response.data.id);
                    if (existingIndex >= 0) {
                        // Update existing plot
                        plots[existingIndex] = { ...plots[existingIndex], ...plotData, id: response.data.id };
                    } else {
                        // Add new plot
                        plots.push({ ...plotData, id: response.data.id });
                    }

                    // Update selected plot reference
                    if (selectedPlot) {
                        selectedPlot.id = response.data.id;
                    }

                    // Update form with new ID
                    $('#plot-id').val(response.data.id);

                    // Update plot list
                    updatePlotList();

                    // Redraw canvas
                    drawAll();
                }
            } else {
                alert('Error saving plot: ' + (response.data || 'Unknown error'));
            }
        }).fail(function() {
            alert('Network error. Please try again.');
        }).always(function() {
            // Hide loading spinner
            $saveBtn.removeClass('loading');
            $saveBtn.find('.spinner').hide();
        });
    }

    function deletePlot(plotId) {
        // Handle unsaved plots (plotId is null)
        if (plotId === null || plotId === undefined || plotId === '') {
            if (confirm('Delete this unsaved plot?')) {
                // Remove from local plots array
                if (selectedPlot) {
                    const index = plots.indexOf(selectedPlot);
                    if (index > -1) {
                        plots.splice(index, 1);
                    }
                }
                selectedPlot = null;
                $('#plot-properties-form')[0].reset();
                $('#plot-coordinates').val('');
                $('.plot-list-item').removeClass('selected');
                updatePlotList();
                drawAll();
            }
            return;
        }

        // Handle saved plots (plotId is a valid ID)
        if (!confirm('Are you sure you want to delete this plot?')) return;

        // Show loading spinner
        const $deleteBtn = $('#delete-plot');
        $deleteBtn.addClass('loading');
        $deleteBtn.find('.spinner').show();

        $.post(TajMapPB.ajaxUrl, {
            action: 'tajmap_pb_delete_plot',
            nonce: TajMapPB.nonce,
            id: plotId
        }, function(response) {
            if (response.success) {
                alert('Plot deleted successfully!');
                // Remove from local plots array
                plots = plots.filter(p => p.id != plotId);
                // Clear form if this was the selected plot
                if (selectedPlot && selectedPlot.id == plotId) {
                    selectedPlot = null;
                    $('#plot-properties-form')[0].reset();
                    $('#plot-coordinates').val('');
                    $('.plot-list-item').removeClass('selected');
                }
                // Update plot list
                updatePlotList();
                // Redraw canvas
                drawAll();
            } else {
                alert('Error deleting plot: ' + (response.data || 'Unknown error'));
            }
        }).fail(function() {
            alert('Network error. Please try again.');
        }).always(function() {
            // Hide loading spinner
            $deleteBtn.removeClass('loading');
            $deleteBtn.find('.spinner').hide();
        });
    }

    function updateCoordinatesField() {
        if (selectedPlot && selectedPlot.points) {
            $('#plot-coordinates').val(JSON.stringify(selectedPlot.points, null, 2));
        }
    }

    function duplicatePlot() {
        if (!selectedPlot) {
            alert('Please select a plot to duplicate.');
            return;
        }

        // Create a copy of the selected plot
        const duplicatedPlot = JSON.parse(JSON.stringify(selectedPlot));

        // Generate a new name
        const baseName = duplicatedPlot.plot_name || 'Plot';
        let counter = 1;
        let newName = `${baseName} Copy`;

        while (plots.some(p => p.plot_name === newName)) {
            counter++;
            newName = `${baseName} Copy ${counter}`;
        }

        duplicatedPlot.plot_name = newName;
        duplicatedPlot.id = null; // Mark as new plot

        // Offset the position slightly so it's visible
        if (duplicatedPlot.points) {
            duplicatedPlot.points.forEach(point => {
                point.x += 20;
                point.y += 20;
            });
        }

        // Add to plots array
        plots.push(duplicatedPlot);

        // Select the duplicated plot
        selectedPlot = duplicatedPlot;
        loadPlotIntoForm(duplicatedPlot);

        // Update the plot list
        updatePlotList();

        // Update coordinates field
        updateCoordinatesField();

        // Redraw canvas
        drawAll();

        $('#editor-status').text(`Created duplicate: ${newName}`);
    }

    function showPlotDetails(plot) {
        if (!plot) {
            hidePlotDetails();
            return;
        }

        // Update detail values
        $('#detail-plot-name').text(plot.plot_name || 'Unnamed Plot');
        
        // Update status badge
        const status = plot.status || 'available';
        const $statusBadge = $('#detail-status');
        $statusBadge.removeClass('available sold reserved').addClass(status);
        $statusBadge.text(status.charAt(0).toUpperCase() + status.slice(1));
        
        $('#detail-sector').text(plot.sector || '-');
        $('#detail-block').text(plot.block || '-');
        $('#detail-street').text(plot.street || '-');
        $('#detail-price').text(plot.price ? `Rs. ${plot.price}` : '-');
        $('#detail-area').text(plot.area ? `${plot.area} sq ft` : '-');
        
        // Update coordinates
        if (plot.points && plot.points.length > 0) {
            const coordsText = plot.points.map(point => 
                `(${point.x.toFixed(2)}, ${point.y.toFixed(2)})`
            ).join('\n');
            $('#detail-coordinates').text(coordsText);
        } else {
            $('#detail-coordinates').text('-');
        }

        // Show the panel
        $('#plot-details-panel').addClass('show').show();
    }

    function hidePlotDetails() {
        $('#plot-details-panel').removeClass('show');
        setTimeout(() => {
            $('#plot-details-panel').hide();
        }, 300);
    }

    function updatePlotList() {
        // Update the plot count in the sidebar header
        $('.sidebar-header h3').text(`Existing Plots (${plots.length})`);

        // If no plots exist, show the create first plot message
        if (plots.length === 0) {
            $('#plots-list').html(`
                <div class="no-plots">
                    <p>No plots created yet.</p>
                    <button class="btn primary" onclick="createNewPlot()">Create First Plot</button>
                </div>
            `);
            return;
        }

        // Generate plot list HTML
        let plotListHtml = '';
        plots.forEach(plot => {
            const statusClass = plot.status || 'available';
            plotListHtml += `
                <div class="plot-list-item" data-id="${plot.id || ''}">
                    <div class="plot-info">
                        <h4>${plot.plot_name || 'Unnamed Plot'}</h4>
                        <div class="plot-meta">
                            <span class="plot-status ${statusClass}">
                                ${statusClass.charAt(0).toUpperCase() + statusClass.slice(1)}
                            </span>
                            ${plot.sector ? `<span class="plot-sector">${plot.sector}</span>` : ''}
                            ${plot.block ? `<span class="plot-block">${plot.block}</span>` : ''}
                        </div>
                    </div>
                    <div class="plot-actions">
                        <button class="btn-icon edit" title="Edit Plot">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                        </button>
                        <button class="btn-icon delete" title="Delete Plot">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="3,6 5,6 21,6"></polyline>
                                <path d="M19,6v14a2,2 0 0,1-2,2H7a2,2 0 0,1-2-2V6m3,0V4a2,2 0 0,1,2-2h4a2,2 0 0,1,2,2v2"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            `;
        });

        $('#plots-list').html(plotListHtml);
    }

    // Drawing functions
    function drawAll() {
        if (!ctx || !canvas) {
            return;
        }

        ctx.clearRect(0, 0, canvas.width, canvas.height);

        // Apply transformations
        ctx.save();
        ctx.translate(panX, panY);
        ctx.scale(scale, scale);

        // Draw grid
        if (showGrid) {
            drawGrid();
        }

        // Draw base image
        if (image) {
            try {
                ctx.drawImage(image, 0, 0, canvas.width / scale, canvas.height / scale);
            } catch (e) {
                console.error('Error drawing image:', e);
            }
        }

        // Draw plots
        plots.forEach(plot => {
            drawPlot(plot);
        });

        // Draw current shape being created
        if (currentShape) {
            drawPlot(currentShape);
        }

        // Draw selection highlight
        if (selectedPlot) {
            ctx.strokeStyle = '#3b82f6';
            ctx.lineWidth = 3 / scale;
            ctx.setLineDash([5 / scale, 5 / scale]);
            if (selectedPlot.points) {
                drawPolygon(selectedPlot.points);
            }
            ctx.setLineDash([]);
        }

        ctx.restore();
    }

    function drawGrid() {
        ctx.strokeStyle = 'rgba(59, 130, 246, 0.2)';
        ctx.lineWidth = 1 / scale;

        const startX = Math.floor(-panX / scale / gridSize) * gridSize;
        const startY = Math.floor(-panY / scale / gridSize) * gridSize;
        const endX = startX + (canvas.width / scale) + gridSize;
        const endY = startY + (canvas.height / scale) + gridSize;

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

    function drawPlot(plot) {
        ctx.save();

        // Fill color based on status
        switch (plot.status) {
            case 'sold':
                ctx.fillStyle = 'rgba(239, 68, 68, 0.3)';
                ctx.strokeStyle = '#dc2626';
                break;
            case 'available':
            default:
                ctx.fillStyle = 'rgba(16, 185, 129, 0.3)';
                ctx.strokeStyle = '#059669';
                break;
        }

        ctx.lineWidth = 2 / scale;

        // Handle different shape types
        if (plot.type === 'rectangle') {
            drawRectangle(plot);
        } else if (plot.points && plot.points.length >= 1) {
            // For polygons, draw even with 1 point (during drawing)
            drawPolygon(plot.points);
        } else {
            // Not enough points to draw anything
            ctx.restore();
            return;
        }

        // Draw vertices if selected or hovered
        if (selectedPlot === plot || plot === currentShape) {
            if (plot.points) {
                plot.points.forEach((point, index) => {
                    // Different colors for different states
                    if (currentTool === 'vertex') {
                        ctx.fillStyle = '#ef4444'; // Red for vertex editing mode
                        ctx.strokeStyle = '#ffffff';
                        ctx.lineWidth = 2 / scale;
                    } else {
                        ctx.fillStyle = '#3b82f6'; // Blue for normal selection
                        ctx.strokeStyle = '#ffffff';
                        ctx.lineWidth = 1 / scale;
                    }
                    
                    ctx.beginPath();
                    ctx.arc(point.x, point.y, 6 / scale, 0, 2 * Math.PI);
                    ctx.fill();
                    ctx.stroke();
                });
            }
        }

        ctx.restore();
    }

    function drawRectangle(plot) {
        if (!plot.startX || !plot.startY || !plot.endX || !plot.endY) {
            return;
        }

        const x = Math.min(plot.startX, plot.endX);
        const y = Math.min(plot.startY, plot.endY);
        const width = Math.abs(plot.endX - plot.startX);
        const height = Math.abs(plot.endY - plot.startY);

        ctx.beginPath();
        ctx.rect(x, y, width, height);
        ctx.fill();
        ctx.stroke();
    }

    function drawPolygon(points) {
        if (!points || points.length < 1) {
            return;
        }

        if (points.length === 1) {
            // Draw a dot for single point
            ctx.beginPath();
            ctx.arc(points[0].x, points[0].y, 3, 0, 2 * Math.PI);
            ctx.fill();
        } else {
            // Draw polygon with multiple points
            ctx.beginPath();
            ctx.moveTo(points[0].x, points[0].y);

            for (let i = 1; i < points.length; i++) {
                ctx.lineTo(points[i].x, points[i].y);
            }

            // Only close path if we have 3 or more points (complete polygon)
            if (points.length >= 3) {
                ctx.closePath();
            }

            ctx.fill();
            ctx.stroke();
        }
    }

    // Utility functions
    function isPointInPolygon(point, polygon) {
        if (!polygon || polygon.length < 3) return false;

        let inside = false;

        for (let i = 0, j = polygon.length - 1; i < polygon.length; j = i++) {
            if (((polygon[i].y > point.y) !== (polygon[j].y > point.y)) &&
                (point.x < (polygon[j].x - polygon[i].x) * (point.y - polygon[i].y) / (polygon[j].y - polygon[i].y) + polygon[i].x)) {
                inside = !inside;
            }
        }

        return inside;
    }

    // Handle fullscreen change
    $(document).on('fullscreenchange webkitfullscreenchange mozfullscreenchange MSFullscreenChange', function() {
        const isFullscreen = !!(document.fullscreenElement || document.webkitFullscreenElement || document.mozFullScreenElement || document.msFullscreenElement);
        $('#fullscreen-toggle').toggleClass('active', isFullscreen);

        if (isFullscreen) {
            $('.tajmap-plot-editor').addClass('fullscreen');
        } else {
            $('.tajmap-plot-editor').removeClass('fullscreen');
        }
    });

    // Create new plot function
    function createNewPlot() {
        // Clear form
        $('#plot-properties-form')[0].reset();

        // Clear plot ID (indicates new plot)
        $('#plot-id').val('');

        // Clear coordinates
        $('#plot-coordinates').val('');

        // Clear selected plot
        selectedPlot = null;

        // Remove selection highlighting
        $('.plot-list-item').removeClass('selected');
        
        // Hide plot details panel
        hidePlotDetails();

        // Switch to polygon tool
        selectTool('polygon');

        // Update status
        $('#editor-status').text('Ready to create new plot - Draw a polygon');

        // Show instructions
        if (!$('#new-plot-instructions').length) {
            $('.editor-canvas-wrapper').prepend(`
                <div id="new-plot-instructions" style="
                    position: absolute;
                    top: 20px;
                    left: 50%;
                    transform: translateX(-50%);
                    background: rgba(0, 0, 0, 0.8);
                    color: white;
                    padding: 10px 20px;
                    border-radius: 5px;
                    font-size: 14px;
                    z-index: 1000;
                    pointer-events: none;
                ">
                    Click on the canvas to start drawing a polygon. Double-click to finish.
                </div>
            `);

            // Auto-hide instructions after 5 seconds
            setTimeout(() => {
                $('#new-plot-instructions').fadeOut();
            }, 5000);
        } else {
            $('#new-plot-instructions').show();
        }

        // Create a temporary placeholder for the new plot in the list
        const tempPlot = {
            id: null,
            plot_name: 'New Plot',
            status: 'available',
            points: []
        };

        // Add to plots array temporarily
        plots.push(tempPlot);
        selectedPlot = tempPlot;

        // Update plot list to show the new plot placeholder
        updatePlotList();
    }

    // Make functions globally available
    window.initializePlotEditor = initializePlotEditor;
    window.createNewPlot = createNewPlot;

})(jQuery);
