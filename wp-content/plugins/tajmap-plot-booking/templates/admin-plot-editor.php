<?php
if (!defined('ABSPATH')) { exit; }

if (!current_user_can('manage_options')) {
    wp_die('Unauthorized');
}

// Get all plots for the list
global $wpdb;
$plots = $wpdb->get_results('SELECT * FROM ' . TAJMAP_PB_TABLE_PLOTS . ' ORDER BY id ASC', ARRAY_A);
?>
<div class="wrap tajmap-plot-editor">
    <div class="editor-header">
        <h1>Advanced Plot Editor</h1>
        <div class="editor-actions">
            <button class="btn secondary" onclick="window.location.href='<?php echo admin_url('admin.php?page=tajmap-plot-management'); ?>'">
                Dashboard
            </button>
            <button class="btn success" onclick="createNewPlot()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                New Plot
            </button>
            <button class="btn primary" id="fullscreen-toggle">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15,3 21,3 21,9"></polyline>
                    <polyline points="9,21 15,21 15,15"></polyline>
                    <line x1="21" y1="3" x2="14" y2="10"></line>
                    <line x1="3" y1="21" x2="10" y2="14"></line>
                </svg>
                Fullscreen
            </button>
        </div>
    </div>

    <div class="editor-container">
        <script type="text/javascript">
            window.plotEditorPlots = <?php echo json_encode($plots); ?>;
        </script>
        <!-- Tool Palette -->
        <div class="tool-palette">
            <div class="palette-section">
                <h3>Drawing Tools</h3>
                <div class="tool-group">
                    <button class="tool-btn active" id="select-tool" data-tool="select">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 3l7.07 16.97 2.51-7.39 7.39-2.51L3 3z"></path>
                        </svg>
                        Select
                    </button>
                    <button class="tool-btn" id="polygon-tool" data-tool="polygon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="12,2 22,8.5 22,15.5 12,22 2,15.5 2,8.5 12,2"></polygon>
                        </svg>
                        Polygon
                    </button>
                    <button class="tool-btn" id="rectangle-tool" data-tool="rectangle">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        </svg>
                        Rectangle
                    </button>
                </div>
            </div>

            <div class="palette-section">
                <h3>Edit Tools</h3>
                <div class="tool-group">
                    <button class="tool-btn" id="vertex-tool" data-tool="vertex">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        Edit Vertices
                    </button>
                    <button class="tool-btn" id="move-tool" data-tool="move">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="5,9 12,16 19,9"></polyline>
                        </svg>
                        Move Shape
                    </button>
                </div>
            </div>

            <div class="palette-section">
                <h3>View Controls</h3>
                <div class="tool-group">
                    <button class="tool-btn" id="zoom-in">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            <line x1="11" y1="8" x2="11" y2="14"></line>
                            <line x1="8" y1="11" x2="14" y2="11"></line>
                        </svg>
                        Zoom In
                    </button>
                    <button class="tool-btn" id="zoom-out">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            <line x1="8" y1="11" x2="14" y2="11"></line>
                        </svg>
                        Zoom Out
                    </button>
                    <button class="tool-btn" id="fit-view">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        </svg>
                        Fit to View
                    </button>
                </div>
            </div>

            <div class="palette-section">
                <h3>Actions</h3>
                <div class="tool-group">
                    <button class="tool-btn" id="undo-btn">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9,14 4,9 9,4"></polyline>
                            <path d="M20,20v-7a4,4 0 0,0-4-4H4"></path>
                        </svg>
                        Undo
                    </button>
                    <button class="tool-btn" id="clear-btn">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3,6 5,6 21,6"></polyline>
                            <path d="M19,6v14a2,2 0 0,1-2,2H7a2,2 0 0,1-2-2V6m3,0V4a2,2 0 0,1,2-2h4a2,2 0 0,1,2,2v2"></path>
                        </svg>
                        Clear All
                    </button>
                </div>
            </div>
        </div>

        <!-- Main Canvas Area -->
        <div class="canvas-container">
            <div class="canvas-toolbar">
                <div class="toolbar-left">
                    <span class="canvas-status" id="canvas-status">Ready</span>
                </div>
                <div class="toolbar-center">
                    <div class="zoom-controls">
                        <span class="zoom-level" id="zoom-level">100%</span>
                    </div>
                </div>
                <div class="toolbar-right">
                    <button class="btn small" id="snap-grid-toggle">Snap to Grid</button>
                    <button class="btn small" id="show-grid-toggle">Show Grid</button>
                </div>
            </div>

            <!-- Canvas -->
            <div class="editor-canvas-wrapper">
                <canvas id="plot-editor-canvas" width="1200" height="800"></canvas>

                <!-- Grid Overlay -->
                <div class="grid-overlay" id="grid-overlay" style="display: none;"></div>
            </div>

            <!-- Measurement Display -->
            <div class="measurement-display" id="measurement-display" style="display: none;">
                <div class="measurement-item">
                    <label>Area:</label>
                    <span id="area-value">0 sq ft</span>
                </div>
                <div class="measurement-item">
                    <label>Perimeter:</label>
                    <span id="perimeter-value">0 ft</span>
                </div>
            </div>
        </div>

        <!-- Properties Panel -->
        <div class="properties-panel">
            <div class="panel-header">
                <h3>Plot Properties</h3>
                <button class="panel-collapse" id="panel-collapse">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9,18 15,12 9,6"></polyline>
                    </svg>
                </button>
            </div>

            <div class="panel-content">
                <form id="plot-properties-form">
                    <input type="hidden" id="plot-id" name="id" value="">

                    <div class="form-section">
                        <h4>Basic Information</h4>
                        <div class="form-group">
                            <label for="plot-name">Plot Name *</label>
                            <input type="text" id="plot-name" name="plot_name" required>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="plot-sector">Sector</label>
                                <input type="text" id="plot-sector" name="sector">
                            </div>
                            <div class="form-group">
                                <label for="plot-block">Block</label>
                                <input type="text" id="plot-block" name="block">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="plot-street">Street</label>
                            <input type="text" id="plot-street" name="street">
                        </div>
                    </div>

                    <div class="form-section">
                        <h4>Status & Pricing</h4>
                        <div class="form-group">
                            <label for="plot-status">Status</label>
                            <select id="plot-status" name="status">
                                <option value="available">Available</option>
                                <option value="sold">Sold</option>
                            </select>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="plot-price">Price (₹)</label>
                                <input type="number" id="plot-price" name="price" step="1000">
                            </div>
                            <div class="form-group">
                                <label for="plot-area">Area (sq ft)</label>
                                <input type="number" id="plot-area" name="area" step="1">
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h4>Media</h4>
                        <div class="form-group">
                            <label>Base Map Image</label>
                            <div class="image-upload-area">
                                <button type="button" id="upload-base-image" class="btn secondary small">
                                    Upload/Select Image
                                </button>
                                <input type="hidden" id="base-image-id" name="base_image_id" value="">
                                <div id="base-image-preview" class="image-preview"></div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Plot Preview Image</label>
                            <div class="image-upload-area">
                                <button type="button" id="upload-plot-image" class="btn secondary small">
                                    Upload Preview Image
                                </button>
                                <input type="hidden" id="plot-image-id" name="plot_image_id" value="">
                                <div id="plot-image-preview" class="image-preview"></div>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h4>Coordinates</h4>
                        <div class="form-group">
                            <label>Polygon Coordinates</label>
                            <textarea id="plot-coordinates" name="coordinates" rows="8" readonly></textarea>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="button" id="save-plot" class="btn primary">Save Plot</button>
                        <button type="button" id="delete-plot" class="btn danger">Delete Plot</button>
                        <button type="button" id="duplicate-plot" class="btn secondary">Duplicate</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Existing Plots Sidebar -->
    <div class="plots-sidebar">
        <div class="sidebar-header">
            <h3>Existing Plots (<?php echo count($plots); ?>)</h3>
            <button class="sidebar-toggle" id="sidebar-toggle">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15,18 9,12 15,6"></polyline>
                </svg>
            </button>
        </div>

        <div class="plots-list" id="plots-list">
            <?php if ($plots): ?>
                <?php foreach ($plots as $plot): ?>
                    <div class="plot-list-item" data-id="<?php echo $plot['id']; ?>">
                        <div class="plot-info">
                            <h4><?php echo esc_html($plot['plot_name']); ?></h4>
                            <div class="plot-meta">
                                <span class="plot-status <?php echo esc_attr($plot['status']); ?>">
                                    <?php echo ucfirst($plot['status']); ?>
                                </span>
                                <?php if ($plot['sector']): ?>
                                    <span class="plot-sector"><?php echo esc_html($plot['sector']); ?></span>
                                <?php endif; ?>
                                <?php if ($plot['block']): ?>
                                    <span class="plot-block"><?php echo esc_html($plot['block']); ?></span>
                                <?php endif; ?>
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
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-plots">
                    <p>No plots created yet.</p>
                    <button class="btn primary" onclick="selectTool('polygon')">Create First Plot</button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Status Bar -->
    <div class="status-bar">
        <div class="status-left">
            <span id="editor-status">Ready to edit</span>
        </div>
        <div class="status-right">
            <span id="canvas-info">Canvas: 1200x800px</span>
        </div>
    </div>
</div>

<script type="text/javascript">
(function($) {
    $(document).ready(function() {
        // Load the plot editor JavaScript
        if (typeof initializePlotEditor === 'function') {
            initializePlotEditor();
        } else {
            console.error('Plot editor JavaScript not loaded');
        }
    });
})(jQuery);
</script>
