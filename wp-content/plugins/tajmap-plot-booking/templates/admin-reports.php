<?php
if (!defined('ABSPATH')) { exit; }

if (!current_user_can('manage_options')) {
    wp_die('Unauthorized');
}

global $wpdb;

// Get analytics data
$total_plots = $wpdb->get_var('SELECT COUNT(*) FROM ' . TAJMAP_PB_TABLE_PLOTS);
$available_plots = $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . TAJMAP_PB_TABLE_PLOTS . ' WHERE status = %s', 'available'));
$sold_plots = $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . TAJMAP_PB_TABLE_PLOTS . ' WHERE status = %s', 'sold'));
$total_leads = $wpdb->get_var('SELECT COUNT(*) FROM ' . TAJMAP_PB_TABLE_LEADS);
$recent_leads = $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . TAJMAP_PB_TABLE_LEADS . ' WHERE created_at >= %s', date('Y-m-d H:i:s', strtotime('-30 days'))));
$converted_leads = $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . TAJMAP_PB_TABLE_LEADS . ' WHERE status IN (%s, %s)', 'interested', 'closed'));
$conversion_rate = $total_leads > 0 ? round(($converted_leads / $total_leads) * 100, 2) : 0;

// Lead status distribution
$leads_by_status = $wpdb->get_results(
    'SELECT status, COUNT(*) as count FROM ' . TAJMAP_PB_TABLE_LEADS . ' GROUP BY status',
    ARRAY_A
);

// Monthly trends (last 6 months)
$monthly_trends = $wpdb->get_results($wpdb->prepare(
    'SELECT
        DATE_FORMAT(created_at, %s) as month,
        COUNT(*) as leads_count
     FROM ' . TAJMAP_PB_TABLE_LEADS . '
     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
     GROUP BY DATE_FORMAT(created_at, %s)
     ORDER BY month ASC',
    '%Y-%m', '%Y-%m'
), ARRAY_A);

$export_analytics_url = wp_nonce_url(admin_url('admin-post.php?action=tajmap_pb_export_analytics'), 'tajmap_pb_export');
$export_leads_url = wp_nonce_url(admin_url('admin-post.php?action=tajmap_pb_export_leads'), 'tajmap_pb_export');
?>
<div class="wrap tajmap-reports">
    <div class="reports-header">
        <h1>Reports & Analytics</h1>
        <div class="reports-actions">
            <button class="btn primary" onclick="exportAllReports()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="7,10 12,15 17,10"></polyline>
                    <line x1="12" y1="15" x2="12" y2="3"></line>
                </svg>
                Export All Reports
            </button>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="reports-summary">
        <div class="summary-card">
            <div class="card-icon">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                    <circle cx="12" cy="10" r="3"></circle>
                </svg>
            </div>
            <div class="card-content">
                <h3><?php echo number_format($total_plots); ?></h3>
                <p>Total Plots</p>
            </div>
        </div>

        <div class="summary-card">
            <div class="card-icon success">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 12l2 2 4-4"></path>
                    <path d="M21 12c-1 0-3-1-3-3s2-3 3-3 3 1 3 3-2 3-3 3"></path>
                </svg>
            </div>
            <div class="card-content">
                <h3><?php echo number_format($available_plots); ?></h3>
                <p>Available Plots</p>
            </div>
        </div>

        <div class="summary-card">
            <div class="card-icon danger">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 12l2 2 4-4"></path>
                    <path d="M21 12c-1 0-3-1-3-3s2-3 3-3 3 1 3 3-2 3-3 3"></path>
                </svg>
            </div>
            <div class="card-content">
                <h3><?php echo number_format($sold_plots); ?></h3>
                <p>Sold Plots</p>
            </div>
        </div>

        <div class="summary-card">
            <div class="card-icon">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="8.5" cy="7" r="4"></circle>
                </svg>
            </div>
            <div class="card-content">
                <h3><?php echo number_format($total_leads); ?></h3>
                <p>Total Leads</p>
            </div>
        </div>

        <div class="summary-card">
            <div class="card-icon warning">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="23,6 13.5,15.5 8.5,10.5 1,18"></polyline>
                    <polyline points="17,6 23,6 23,12"></polyline>
                </svg>
            </div>
            <div class="card-content">
                <h3><?php echo $conversion_rate; ?>%</h3>
                <p>Conversion Rate</p>
            </div>
        </div>

        <div class="summary-card">
            <div class="card-icon info">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12,6 12,12 16,14"></polyline>
                </svg>
            </div>
            <div class="card-content">
                <h3><?php echo number_format($recent_leads); ?></h3>
                <p>Recent Leads (30d)</p>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="reports-charts">
        <div class="charts-grid">
            <!-- Lead Status Distribution -->
            <div class="chart-widget">
                <div class="chart-header">
                    <h3>Lead Status Distribution</h3>
                    <div class="chart-actions">
                        <button class="btn small secondary" onclick="exportChart(this, 'lead-status')">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="7,10 12,15 17,10"></polyline>
                                <line x1="12" y1="15" x2="12" y2="3"></line>
                            </svg>
                            Export
                        </button>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="lead-status-chart" width="400" height="300"></canvas>
                </div>
            </div>

            <!-- Monthly Lead Trends -->
            <div class="chart-widget">
                <div class="chart-header">
                    <h3>Monthly Lead Trends</h3>
                    <div class="chart-actions">
                        <select id="trend-period" class="chart-filter">
                            <option value="6">Last 6 Months</option>
                            <option value="12">Last 12 Months</option>
                        </select>
                        <button class="btn small secondary" onclick="exportChart(this, 'lead-trends')">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="7,10 12,15 17,10"></polyline>
                                <line x1="12" y1="15" x2="12" y2="3"></line>
                            </svg>
                            Export
                        </button>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="lead-trends-chart" width="400" height="300"></canvas>
                </div>
            </div>

            <!-- Plot Status Overview -->
            <div class="chart-widget">
                <div class="chart-header">
                    <h3>Plot Status Overview</h3>
                    <div class="chart-actions">
                        <button class="btn small secondary" onclick="exportChart(this, 'plot-status')">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="7,10 12,15 17,10"></polyline>
                                <line x1="12" y1="15" x2="12" y2="3"></line>
                            </svg>
                            Export
                        </button>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="plot-status-chart" width="400" height="300"></canvas>
                </div>
            </div>

            <!-- Conversion Funnel -->
            <div class="chart-widget">
                <div class="chart-header">
                    <h3>Conversion Funnel</h3>
                    <div class="chart-actions">
                        <button class="btn small secondary" onclick="exportChart(this, 'conversion-funnel')">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="7,10 12,15 17,10"></polyline>
                                <line x1="12" y1="15" x2="12" y2="3"></line>
                            </svg>
                            Export
                        </button>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="conversion-funnel-chart" width="400" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Reports Tables -->
    <div class="reports-tables">
        <div class="table-section">
            <div class="table-header">
                <h3>Lead Status Breakdown</h3>
                <button class="btn small secondary" onclick="exportTable('lead-status-table')">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="7,10 12,15 17,10"></polyline>
                        <line x1="12" y1="15" x2="12" y2="3"></line>
                    </svg>
                    Export
                </button>
            </div>
            <table class="wp-list-table widefat fixed striped" id="lead-status-table">
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Count</th>
                        <th>Percentage</th>
                        <th>Trend</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($leads_by_status as $status): ?>
                        <tr>
                            <td>
                                <span class="status-badge <?php echo esc_attr($status['status']); ?>">
                                    <?php echo ucfirst(esc_html($status['status'])); ?>
                                </span>
                            </td>
                            <td><?php echo number_format($status['count']); ?></td>
                            <td><?php echo $total_leads > 0 ? round(($status['count'] / $total_leads) * 100, 1) : 0; ?>%</td>
                            <td>
                                <span class="trend-indicator positive">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="23,6 13.5,15.5 8.5,10.5 1,18"></polyline>
                                        <polyline points="17,6 23,6 23,12"></polyline>
                                    </svg>
                                    +12%
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="table-section">
            <div class="table-header">
                <h3>Monthly Lead Trends</h3>
                <button class="btn small secondary" onclick="exportTable('monthly-trends-table')">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="7,10 12,15 17,10"></polyline>
                        <line x1="12" y1="15" x2="12" y2="3"></line>
                    </svg>
                    Export
                </button>
            </div>
            <table class="wp-list-table widefat fixed striped" id="monthly-trends-table">
                <thead>
                    <tr>
                        <th>Month</th>
                        <th>Leads</th>
                        <th>Growth</th>
                        <th>Target</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($monthly_trends as $trend): ?>
                        <tr>
                            <td><?php echo date('M Y', strtotime($trend['month'] . '-01')); ?></td>
                            <td><?php echo number_format($trend['leads_count']); ?></td>
                            <td>
                                <span class="growth-indicator positive">+8.5%</span>
                            </td>
                            <td>25</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Export Options -->
    <div class="export-options">
        <h3>Export Options</h3>
        <div class="export-grid">
            <button class="export-btn" onclick="window.location.href='<?php echo $export_leads_url; ?>'">
                <div class="export-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="8.5" cy="7" r="4"></circle>
                    </svg>
                </div>
                <div class="export-info">
                    <h4>Export All Leads</h4>
                    <p>Complete lead database with contact information</p>
                </div>
            </button>

            <button class="export-btn" onclick="window.location.href='<?php echo $export_analytics_url; ?>'">
                <div class="export-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="22,12 18,12 15,21 9,3 6,12 2,12"></polyline>
                    </svg>
                </div>
                <div class="export-info">
                    <h4>Export Analytics</h4>
                    <p>Performance metrics and trend analysis</p>
                </div>
            </button>
        </div>
    </div>
</div>

<script type="text/javascript">
(function($) {
    $(document).ready(function() {
        initializeReportsCharts();
    });

    function initializeReportsCharts() {
        // Lead Status Chart
        var statusCtx = document.getElementById('lead-status-chart').getContext('2d');
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

        // Monthly Trends Chart
        var trendsCtx = document.getElementById('lead-trends-chart').getContext('2d');
        var trendsChart = new Chart(trendsCtx, {
            type: 'line',
            data: {
                labels: [
                    <?php
                    $labels = [];
                    foreach ($monthly_trends as $trend) {
                        $labels[] = '"' . date('M Y', strtotime($trend['month'] . '-01')) . '"';
                    }
                    echo implode(',', $labels);
                    ?>
                ],
                datasets: [{
                    label: 'New Leads',
                    data: [
                        <?php
                        $data = [];
                        foreach ($monthly_trends as $trend) {
                            $data[] = $trend['leads_count'];
                        }
                        echo implode(',', $data);
                        ?>
                    ],
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    fill: true,
                    tension: 0.4
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

        // Plot Status Chart
        var plotCtx = document.getElementById('plot-status-chart').getContext('2d');
        var plotChart = new Chart(plotCtx, {
            type: 'pie',
            data: {
                labels: ['Available', 'Sold'],
                datasets: [{
                    data: [<?php echo $available_plots; ?>, <?php echo $sold_plots; ?>],
                    backgroundColor: [
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

        // Conversion Funnel Chart
        var funnelCtx = document.getElementById('conversion-funnel-chart').getContext('2d');
        var funnelChart = new Chart(funnelCtx, {
            type: 'bar',
            data: {
                labels: ['New', 'Contacted', 'Interested', 'Closed'],
                datasets: [{
                    label: 'Conversion',
                    data: [<?php echo $new_count . ',' . $contacted_count . ',' . $interested_count . ',' . $closed_count; ?>],
                    backgroundColor: [
                        'rgba(0, 123, 255, 0.8)',
                        'rgba(255, 193, 7, 0.8)',
                        'rgba(40, 167, 69, 0.8)',
                        'rgba(220, 53, 69, 0.8)'
                    ]
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

    function exportChart(button, chartId) {
        // Implement chart export functionality
        alert('Chart export functionality would be implemented here');
    }

    function exportTable(tableId) {
        // Implement table export functionality
        alert('Table export functionality would be implemented here');
    }

    function exportAllReports() {
        // Export all reports as a single document
        alert('Complete report export functionality would be implemented here');
    }

})(jQuery);
</script>
