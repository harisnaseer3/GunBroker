<?php
if (!defined('ABSPATH')) { exit; }

if (!current_user_can('manage_options')) {
    wp_die('Unauthorized');
}

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : get_current_user_id();
$user = get_userdata($user_id);

if (!$user) {
    wp_die('User not found');
}
?>

<div class="wrap">
    <h1><?php echo esc_html($user->display_name); ?> - Performance Report</h1>
    <a href="<?php echo admin_url('admin.php?page=tajmap-admin-users'); ?>" class="page-title-action">← Back to Admin Users</a>
    <hr class="wp-header-end">

    <div class="performance-container">
        <!-- Time Period Filter -->
        <div class="filter-bar">
            <select id="time-period">
                <option value="7">Last 7 Days</option>
                <option value="30" selected>Last 30 Days</option>
                <option value="90">Last 90 Days</option>
                <option value="365">Last Year</option>
                <option value="all">All Time</option>
            </select>
            <button class="button button-primary" id="refresh-report">Refresh</button>
        </div>

        <!-- Performance Stats -->
        <div class="stats-grid">
            <div class="performance-card">
                <h3>Contacted Leads</h3>
                <p class="performance-number" id="contacted-count">0</p>
                <p class="performance-subtitle">Leads marked as contacted</p>
            </div>

            <div class="performance-card">
                <h3>Interested Leads</h3>
                <p class="performance-number" id="interested-count">0</p>
                <p class="performance-subtitle">Leads showing interest</p>
            </div>

            <div class="performance-card">
                <h3>Closed Leads</h3>
                <p class="performance-number" id="closed-count">0</p>
                <p class="performance-subtitle">Successfully closed deals</p>
            </div>

            <div class="performance-card">
                <h3>Conversion Rate</h3>
                <p class="performance-number" id="conversion-rate">0%</p>
                <p class="performance-subtitle">Closed / Total</p>
            </div>
        </div>

        <!-- Activity Chart -->
        <div class="chart-container">
            <h2>Lead Activity Over Time</h2>
            <canvas id="activity-chart"></canvas>
        </div>

        <!-- Recent Activity -->
        <div class="recent-activity">
            <h2>Recent Lead Activities</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Lead</th>
                        <th>Plot</th>
                        <th>Action</th>
                        <th>Status Change</th>
                    </tr>
                </thead>
                <tbody id="activity-list">
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 20px;">
                            <div class="spinner is-active" style="float: none; margin: 0 auto;"></div>
                            <p>Loading activity...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.performance-container {
    margin-top: 20px;
}

.filter-bar {
    background: white;
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    gap: 10px;
    align-items: center;
}

.filter-bar select {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.performance-card {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
}

.performance-card h3 {
    margin: 0 0 10px 0;
    font-size: 14px;
    color: #666;
    font-weight: 600;
    text-transform: uppercase;
}

.performance-number {
    margin: 0;
    font-size: 36px;
    font-weight: 700;
    color: #1f2937;
}

.performance-subtitle {
    margin: 5px 0 0 0;
    font-size: 12px;
    color: #999;
}

.chart-container {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 30px;
}

.chart-container h2 {
    margin-top: 0;
}

.recent-activity {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
}

.recent-activity h2 {
    margin-top: 0;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
jQuery(document).ready(function($) {
    const userId = <?php echo $user_id; ?>;
    let activityChart = null;

    function loadPerformanceData(days = 30) {
        $.post(ajaxurl, {
            action: 'tajmap_pb_get_admin_performance',
            nonce: '<?php echo wp_create_nonce('tajmap_pb_admin'); ?>',
            user_id: userId,
            days: days
        }, function(response) {
            if (response.success) {
                updateStats(response.data.stats);
                updateChart(response.data.chart_data);
                updateActivity(response.data.activities);
            }
        });
    }

    function updateStats(stats) {
        $('#contacted-count').text(stats.contacted || 0);
        $('#interested-count').text(stats.interested || 0);
        $('#closed-count').text(stats.closed || 0);
        
        const conversionRate = stats.total > 0 ? ((stats.closed / stats.total) * 100).toFixed(1) : 0;
        $('#conversion-rate').text(conversionRate + '%');
    }

    function updateChart(data) {
        const ctx = document.getElementById('activity-chart').getContext('2d');
        
        if (activityChart) {
            activityChart.destroy();
        }

        activityChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [
                    {
                        label: 'Contacted',
                        data: data.contacted,
                        borderColor: '#fbbf24',
                        backgroundColor: 'rgba(251, 191, 36, 0.1)',
                        tension: 0.4
                    },
                    {
                        label: 'Interested',
                        data: data.interested,
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4
                    },
                    {
                        label: 'Closed',
                        data: data.closed,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }

    function updateActivity(activities) {
        if (activities.length === 0) {
            $('#activity-list').html('<tr><td colspan="5" style="text-align: center; padding: 20px;">No recent activity</td></tr>');
            return;
        }

        let html = '';
        activities.forEach(function(activity) {
            html += `
                <tr>
                    <td>${activity.date}</td>
                    <td>${activity.lead_name || activity.lead_email}</td>
                    <td>${activity.plot_name || 'N/A'}</td>
                    <td>${activity.action}</td>
                    <td><span class="status-badge ${activity.new_status}">${activity.new_status}</span></td>
                </tr>
            `;
        });

        $('#activity-list').html(html);
    }

    $('#time-period, #refresh-report').on('change click', function() {
        const days = $('#time-period').val();
        loadPerformanceData(days === 'all' ? 9999 : parseInt(days));
    });

    // Load initial data
    loadPerformanceData(30);
});
</script>


