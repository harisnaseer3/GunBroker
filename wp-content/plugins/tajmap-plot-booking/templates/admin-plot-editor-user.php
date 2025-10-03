<?php
if (!defined('ABSPATH')) { exit; }

// Allow users with read capability to access this page
if (!current_user_can('read')) {
    wp_die('Unauthorized');
}

// Get all plots for the list
global $wpdb;
$plots = $wpdb->get_results('SELECT * FROM ' . TAJMAP_PB_TABLE_PLOTS . ' ORDER BY id ASC', ARRAY_A);

// Get measurement units and currency from settings
$settings = get_option('tajmap_pb_settings', []);
$measurement_units = $settings['measurement_units'] ?? 'sqft';
$default_currency = $settings['default_currency'] ?? 'Rs.';
$unit_labels = [
    'sqft' => 'sq ft',
    'sqm' => 'sq m',
    'acres' => 'acres'
];
$area_unit_label = $unit_labels[$measurement_units] ?? 'sq ft';
?>
<div class="wrap tajmap-plot-editor">
    <div class="editor-header">
        <h1>Plot Viewer</h1>
        <div class="editor-actions">
            <button class="btn secondary" onclick="window.location.href='<?php echo admin_url('admin.php?page=tajmap-plot-management'); ?>'">
                Dashboard
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
            <button class="btn warning" onclick="window.location.href='<?php echo admin_url('admin.php?page=tajmap-plot-editor'); ?>'">
                Advanced Editor
            </button>
        </div>
    </div>

    <div class="editor-container">
        <script type="text/javascript">
            window.plotEditorPlots = <?php echo json_encode($plots); ?>;
            window.isUserView = true;
        </script>

        <!-- View Controls Only -->
        <div class="tool-palette">
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
        </div>

        <!-- Main Canvas Area -->
        <div class="canvas-container">
            <div class="canvas-toolbar">
                <div class="toolbar-left">
                    <span class="canvas-status" id="canvas-status">View Mode</span>
                </div>
                <div class="toolbar-center">
                    <div class="zoom-controls">
                        <span class="zoom-level" id="zoom-level">100%</span>
                    </div>
                </div>
                <div class="toolbar-right">
                    <!-- No admin controls in user view -->
                </div>
            </div>

            <!-- Canvas -->
            <div class="editor-canvas-wrapper">
                <canvas id="plot-editor-canvas" width="1200" height="800"></canvas>

                <!-- Grid Overlay -->
                <div class="grid-overlay" id="grid-overlay" style="display: none;"></div>
            </div>
        </div>
    </div>

    <!-- Existing Plots Sidebar -->
    <div class="plots-sidebar">
        <div class="sidebar-header">
            <h3>Available Plots (<?php echo count($plots); ?>)</h3>
            <button class="sidebar-toggle" id="sidebar-toggle">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15,18 9,12 15,6"></polyline>
                </svg>
            </button>
        </div>
        
        <div class="plots-sort">
            <select id="plots-sort" class="sort-select">
                <option value="newest">Latest First</option>
                <option value="oldest">Oldest First</option>
                <option value="name-asc">Name A-Z</option>
                <option value="name-desc">Name Z-A</option>
                <option value="status">Status</option>
                <option value="sector">Sector</option>
            </select>
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
                            <?php if ($plot['price']): ?>
                                <div class="plot-price"><?php echo esc_html($default_currency . ' ' . number_format($plot['price'])); ?></div>
                            <?php endif; ?>
                            <?php if ($plot['area']): ?>
                                <div class="plot-area"><?php echo esc_html($plot['area'] . ' ' . $area_unit_label); ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="plot-actions">
                            <button class="btn-icon view" title="View Plot Details">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-plots">
                    <p>No plots available yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Status Bar -->
    <div class="status-bar">
        <div class="status-left">
            <span id="editor-status">View mode - Click plots to view details</span>
        </div>
        <div class="status-right">
            <span id="canvas-info">Canvas: 1200x800px</span>
        </div>
    </div>
</div>

<script type="text/javascript">
// Plot editor is automatically initialized when the JavaScript file loads
// No need to call initializePlotEditor() again here
</script>
