<?php
if (!defined('ABSPATH')) { exit; }

if (!current_user_can('manage_options')) {
    wp_die('Unauthorized');
}

// Get analytics data
global $wpdb;
$total_plots = $wpdb->get_var('SELECT COUNT(*) FROM ' . TAJMAP_PB_TABLE_PLOTS);
$available_plots = $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . TAJMAP_PB_TABLE_PLOTS . ' WHERE status = %s', 'available'));
$sold_plots = $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . TAJMAP_PB_TABLE_PLOTS . ' WHERE status = %s', 'sold'));
$total_leads = $wpdb->get_var('SELECT COUNT(*) FROM ' . TAJMAP_PB_TABLE_LEADS);
$recent_leads = $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . TAJMAP_PB_TABLE_LEADS . ' WHERE created_at >= %s', date('Y-m-d H:i:s', strtotime('-30 days'))));
$converted_leads = $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . TAJMAP_PB_TABLE_LEADS . ' WHERE status IN (%s, %s)', 'interested', 'closed'));
$conversion_rate = $total_leads > 0 ? round(($converted_leads / $total_leads) * 100, 2) : 0;

// Recent leads for activity timeline
$recent_leads_list = $wpdb->get_results($wpdb->prepare(
    'SELECT l.*, p.plot_name FROM ' . TAJMAP_PB_TABLE_LEADS . ' l
     LEFT JOIN ' . TAJMAP_PB_TABLE_PLOTS . ' p ON p.id = l.plot_id
     ORDER BY l.created_at DESC LIMIT 10'
), ARRAY_A);
?>
<div class="wrap tajmap-admin-dashboard">
    <div class="dashboard-header">
        <h1>Plot Management Dashboard</h1>
        <div class="dashboard-actions">
            <button class="btn primary" onclick="window.location.href='<?php echo admin_url('admin.php?page=tajmap-plot-editor'); ?>'">
                Manage Plots
            </button>
            <button class="btn secondary" onclick="window.location.href='<?php echo admin_url('admin.php?page=tajmap-plot-leads'); ?>'">
                View Leads
            </button>
            <button class="btn success" onclick="window.open('<?php echo $this->get_user_page_url(); ?>', '_blank')" title="Open in new tab">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                    <polyline points="15,3 21,3 21,9"></polyline>
                    <line x1="10" y1="14" x2="21" y2="3"></line>
                </svg>
                View User Page
            </button>
        </div>
    </div>

    <!-- Analytics Overview -->
    <div class="analytics-section">
        <div class="analytics-grid">
            <!-- Total Plots Widget -->
            <div class="analytics-widget">
                <div class="widget-header">
                    <h3>Total Plots</h3>
                    <div class="widget-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                    </div>
                </div>
                <div class="widget-value"><?php echo number_format($total_plots); ?></div>
                <div class="widget-change">
                    <span class="change-label">Total in system</span>
                </div>
            </div>

            <!-- Available Plots Widget -->
            <div class="analytics-widget">
                <div class="widget-header">
                    <h3>Available Plots</h3>
                    <div class="widget-icon available">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 12l2 2 4-4"></path>
                            <path d="M21 12c-1 0-3-1-3-3s2-3 3-3 3 1 3 3-2 3-3 3"></path>
                        </svg>
                    </div>
                </div>
                <div class="widget-value"><?php echo number_format($available_plots); ?></div>
                <div class="widget-change positive">
                    <span class="change-value"><?php echo $total_plots > 0 ? round(($available_plots / $total_plots) * 100, 1) : 0; ?>%</span>
                    <span class="change-label">of total</span>
                </div>
            </div>

            <!-- Sold Plots Widget -->
            <div class="analytics-widget">
                <div class="widget-header">
                    <h3>Sold Plots</h3>
                    <div class="widget-icon sold">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 12l2 2 4-4"></path>
                            <path d="M21 12c-1 0-3-1-3-3s2-3 3-3 3 1 3 3-2 3-3 3"></path>
                        </svg>
                    </div>
                </div>
                <div class="widget-value"><?php echo number_format($sold_plots); ?></div>
                <div class="widget-change">
                    <span class="change-value"><?php echo $total_plots > 0 ? round(($sold_plots / $total_plots) * 100, 1) : 0; ?>%</span>
                    <span class="change-label">of total</span>
                </div>
            </div>

            <!-- Total Leads Widget -->
            <div class="analytics-widget">
                <div class="widget-header">
                    <h3>Total Leads</h3>
                    <div class="widget-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="8.5" cy="7" r="4"></circle>
                        </svg>
                    </div>
                </div>
                <div class="widget-value"><?php echo number_format($total_leads); ?></div>
                <div class="widget-change">
                    <span class="change-value">+<?php echo $recent_leads; ?></span>
                    <span class="change-label">last 30 days</span>
                </div>
            </div>

            <!-- Conversion Rate Widget -->
            <div class="analytics-widget">
                <div class="widget-header">
                    <h3>Conversion Rate</h3>
                    <div class="widget-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="23,6 13.5,15.5 8.5,10.5 1,18"></polyline>
                            <polyline points="17,6 23,6 23,12"></polyline>
                        </svg>
                    </div>
                </div>
                <div class="widget-value"><?php echo $conversion_rate; ?>%</div>
                <div class="widget-change">
                    <span class="change-label"><?php echo $converted_leads; ?> converted</span>
                </div>
            </div>

            <!-- Recent Activity Widget -->
            <div class="analytics-widget">
                <div class="widget-header">
                    <h3>Recent Activity</h3>
                    <div class="widget-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12,6 12,12 16,14"></polyline>
                        </svg>
                    </div>
                </div>
                <div class="widget-content">
                    <div class="activity-list">
                        <?php if ($recent_leads_list): ?>
                            <?php foreach ($recent_leads_list as $lead): ?>
                                <div class="activity-item">
                                    <div class="activity-info">
                                        <strong><?php echo esc_html($lead['plot_name'] ?: 'Unknown Plot'); ?></strong>
                                        <span class="activity-email"><?php echo esc_html($lead['email']); ?></span>
                                    </div>
                                    <div class="activity-time">
                                        <?php echo human_time_diff(strtotime($lead['created_at']), current_time('timestamp')); ?> ago
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="activity-empty">
                                <p>No recent activity</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions-section">
        <h2>Quick Actions</h2>
        <div class="quick-actions-grid">
            <button class="quick-action-btn" onclick="window.location.href='<?php echo admin_url('admin.php?page=tajmap-plot-editor'); ?>'">
                <div class="action-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                        <circle cx="12" cy="10" r="3"></circle>
                    </svg>
                </div>
                <div class="action-info">
                    <h3>Manage Plots</h3>
                    <p>Add, edit, or remove plots from your development</p>
                </div>
                <div class="action-arrow">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9,18 15,12 9,6"></polyline>
                    </svg>
                </div>
            </button>

            <button class="quick-action-btn" onclick="window.location.href='<?php echo admin_url('admin.php?page=tajmap-plot-leads'); ?>'">
                <div class="action-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="8.5" cy="7" r="4"></circle>
                    </svg>
                </div>
                <div class="action-info">
                    <h3>View Leads</h3>
                    <p>Manage customer inquiries and follow-ups</p>
                </div>
                <div class="action-arrow">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9,18 15,12 9,6"></polyline>
                    </svg>
                </div>
            </button>

            <button class="quick-action-btn" onclick="window.open('<?php echo $this->get_user_page_url(); ?>', '_blank')">
                <div class="action-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                        <polyline points="15,3 21,3 21,9"></polyline>
                        <line x1="10" y1="14" x2="21" y2="3"></line>
                    </svg>
                </div>
                <div class="action-info">
                    <h3>View User Page</h3>
                    <p>See how visitors view and interact with your plots</p>
                </div>
                <div class="action-arrow">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9,18 15,12 9,6"></polyline>
                    </svg>
                </div>
            </button>

            <button class="quick-action-btn" onclick="exportAnalytics()">
                <div class="action-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="7,10 12,15 17,10"></polyline>
                        <line x1="12" y1="15" x2="12" y2="3"></line>
                    </svg>
                </div>
                <div class="action-info">
                    <h3>Export Reports</h3>
                    <p>Download analytics and lead data</p>
                </div>
                <div class="action-arrow">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9,18 15,12 9,6"></polyline>
                    </svg>
                </div>
            </button>

            <button class="quick-action-btn" onclick="window.location.href='<?php echo admin_url('admin.php?page=tajmap-plot-management&action=settings'); ?>'">
                <div class="action-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M12 1v6m0 6v6m11-7h-6m-6 0H1"></path>
                    </svg>
                </div>
                <div class="action-info">
                    <h3>Settings</h3>
                    <p>Configure development settings and preferences</p>
                </div>
                <div class="action-arrow">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9,18 15,12 9,6"></polyline>
                    </svg>
                </div>
            </button>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="charts-section">
        <h2>Performance Analytics</h2>
        <div class="charts-grid">
            <div class="chart-widget">
                <h3>Lead Status Distribution</h3>
                <div class="chart-container">
                    <canvas id="leads-status-chart" width="400" height="200"></canvas>
                </div>
            </div>

            <div class="chart-widget">
                <h3>Monthly Lead Trends</h3>
                <div class="chart-container">
                    <canvas id="leads-trend-chart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
(function($) {
    // Export analytics function
    window.exportAnalytics = function() {
        window.location.href = '<?php echo wp_nonce_url(admin_url('admin-post.php?action=tajmap_pb_export_analytics'), 'tajmap_pb_export'); ?>';
    };

    // Initialize charts when page loads
    $(document).ready(function() {
        initializeCharts();
    });

    function initializeCharts() {
        // Lead Status Distribution Chart
        var statusCtx = document.getElementById('leads-status-chart').getContext('2d');
        var statusChart = new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['New', 'Contacted', 'Interested', 'Closed'],
                datasets: [{
                    data: [
                        <?php
                        $new_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM " . TAJMAP_PB_TABLE_LEADS . " WHERE status = %s", 'new'));
                        $contacted_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM " . TAJMAP_PB_TABLE_LEADS . " WHERE status = %s", 'contacted'));
                        $interested_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM " . TAJMAP_PB_TABLE_LEADS . " WHERE status = %s", 'interested'));
                        $closed_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM " . TAJMAP_PB_TABLE_LEADS . " WHERE status = %s", 'closed'));
                        echo $new_count . ',' . $contacted_count . ',' . $interested_count . ',' . $closed_count;
                        ?>
                    ],
                    backgroundColor: [
                        '#007bff',
                        '#ffc107',
                        '#28a745',
                        '#dc3545'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: {
                    position: 'bottom'
                }
            }
        });

        // Monthly Lead Trends Chart (simplified - showing last 6 months)
        var trendCtx = document.getElementById('leads-trend-chart').getContext('2d');
        var trendChart = new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'New Leads',
                    data: [12, 19, 15, 25, 22, 18],
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero: true
                        }
                    }]
                }
            }
        });
    }
})(jQuery);
</script>
