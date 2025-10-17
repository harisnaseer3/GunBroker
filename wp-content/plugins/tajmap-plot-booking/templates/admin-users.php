<?php
if (!defined('ABSPATH')) { exit; }

if (!current_user_can('manage_options')) {
    wp_die('Unauthorized');
}

// Get all WordPress users with admin capabilities
$wp_users = get_users(['role__in' => ['administrator', 'editor']]);
?>

<div class="wrap">
    <h1 class="wp-heading-inline">Admin User Management</h1>
    <a href="#" class="page-title-action" id="add-admin-user">Add New Admin</a>
    <hr class="wp-header-end">

    <div class="admin-users-container">
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                </div>
                <div class="stat-content">
                    <h3>Total Admins</h3>
                    <p class="stat-number" id="total-admins">0</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="8.5" cy="7" r="4"></circle>
                        <polyline points="17 11 19 13 23 9"></polyline>
                    </svg>
                </div>
                <div class="stat-content">
                    <h3>Active This Month</h3>
                    <p class="stat-number" id="active-admins">0</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 12h-4l-3 9L9 3l-3 9H2"></path>
                    </svg>
                </div>
                <div class="stat-content">
                    <h3>Total Leads Handled</h3>
                    <p class="stat-number" id="total-leads-handled">0</p>
                </div>
            </div>
        </div>

        <div class="users-table-container">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Leads Contacted</th>
                        <th>Leads Interested</th>
                        <th>Leads Closed</th>
                        <th>Total Leads</th>
                        <th>Success Rate</th>
                        <th>Last Active</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="admin-users-list">
                    <tr>
                        <td colspan="10" style="text-align: center; padding: 40px;">
                            <div class="spinner is-active" style="float: none; margin: 0 auto;"></div>
                            <p>Loading admin users...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add/Edit Admin Modal -->
<div id="admin-user-modal" class="tajmap-modal" style="display: none;">
    <div class="tajmap-modal-content">
        <div class="tajmap-modal-header">
            <h2 id="modal-title">Add New Admin</h2>
            <button class="tajmap-modal-close">&times;</button>
        </div>
        <div class="tajmap-modal-body">
            <form id="admin-user-form">
                <input type="hidden" id="user-id" name="user_id">
                
                <div class="form-group">
                    <label for="user-select">Select WordPress User *</label>
                    <select id="user-select" name="wp_user_id" required>
                        <option value="">-- Select User --</option>
                        <?php foreach ($wp_users as $user): ?>
                            <option value="<?php echo esc_attr($user->ID); ?>">
                                <?php echo esc_html($user->display_name . ' (' . $user->user_email . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="user-role">Admin Role *</label>
                    <select id="user-role" name="role" required>
                        <option value="admin">Full Admin</option>
                        <option value="lead_manager">Lead Manager</option>
                        <option value="plot_manager">Plot Manager</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" class="button button-primary">Save Admin</button>
                    <button type="button" class="button" id="cancel-user">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.admin-users-container {
    margin-top: 20px;
}

.stats-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.stat-content h3 {
    margin: 0;
    font-size: 14px;
    color: #666;
    font-weight: 500;
}

.stat-number {
    margin: 5px 0 0 0;
    font-size: 28px;
    font-weight: 700;
    color: #1f2937;
}

.users-table-container {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
}

.success-rate {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 12px;
}

.success-rate.high {
    background: #d1fae5;
    color: #065f46;
}

.success-rate.medium {
    background: #fef3c7;
    color: #92400e;
}

.success-rate.low {
    background: #fee2e2;
    color: #991b1b;
}

/* Modal Styles */
.tajmap-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 100000;
    align-items: center;
    justify-content: center;
}

.tajmap-modal-content {
    background: white;
    border-radius: 8px;
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    overflow: auto;
}

.tajmap-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #ddd;
}

.tajmap-modal-header h2 {
    margin: 0;
}

.tajmap-modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #666;
}

.tajmap-modal-body {
    padding: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.form-group select {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.form-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    margin-top: 20px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Load admin users data
    function loadAdminUsers() {
        $.post(ajaxurl, {
            action: 'tajmap_pb_get_admin_users',
            nonce: '<?php echo wp_create_nonce('tajmap_pb_admin'); ?>'
        }, function(response) {
            if (response.success) {
                renderAdminUsers(response.data.users);
                updateStats(response.data.stats);
            } else {
                $('#admin-users-list').html('<tr><td colspan="10" style="text-align: center; padding: 20px;">Error loading users</td></tr>');
            }
        });
    }

    function renderAdminUsers(users) {
        if (users.length === 0) {
            $('#admin-users-list').html('<tr><td colspan="10" style="text-align: center; padding: 20px;">No admin users found</td></tr>');
            return;
        }

        let html = '';
        users.forEach(function(user) {
            const successRate = user.total_leads > 0 ? ((user.closed_leads / user.total_leads) * 100).toFixed(1) : 0;
            let rateClass = 'low';
            if (successRate >= 50) rateClass = 'high';
            else if (successRate >= 25) rateClass = 'medium';

            html += `
                <tr>
                    <td><strong>${user.display_name}</strong></td>
                    <td>${user.email}</td>
                    <td>${user.role || 'Admin'}</td>
                    <td>${user.contacted_leads || 0}</td>
                    <td>${user.interested_leads || 0}</td>
                    <td>${user.closed_leads || 0}</td>
                    <td><strong>${user.total_leads || 0}</strong></td>
                    <td><span class="success-rate ${rateClass}">${successRate}%</span></td>
                    <td>${user.last_active || 'Never'}</td>
                    <td>
                        <button class="button button-small view-details" data-user-id="${user.ID}">View Details</button>
                    </td>
                </tr>
            `;
        });

        $('#admin-users-list').html(html);
    }

    function updateStats(stats) {
        $('#total-admins').text(stats.total_admins || 0);
        $('#active-admins').text(stats.active_admins || 0);
        $('#total-leads-handled').text(stats.total_leads_handled || 0);
    }

    // Add new admin user
    $('#add-admin-user').on('click', function(e) {
        e.preventDefault();
        $('#modal-title').text('Add New Admin');
        $('#admin-user-form')[0].reset();
        $('#user-id').val('');
        $('#admin-user-modal').css('display', 'flex');
    });

    // Close modal
    $('.tajmap-modal-close, #cancel-user').on('click', function() {
        $('#admin-user-modal').hide();
    });

    // Submit form
    $('#admin-user-form').on('submit', function(e) {
        e.preventDefault();

        const formData = {
            action: 'tajmap_pb_save_admin_user',
            nonce: '<?php echo wp_create_nonce('tajmap_pb_admin'); ?>',
            wp_user_id: $('#user-select').val(),
            role: $('#user-role').val()
        };

        $.post(ajaxurl, formData, function(response) {
            if (response.success) {
                alert('Admin user saved successfully!');
                $('#admin-user-modal').hide();
                loadAdminUsers();
            } else {
                alert('Error: ' + (response.data || 'Unknown error'));
            }
        });
    });

    // View details
    $(document).on('click', '.view-details', function() {
        const userId = $(this).data('user-id');
        window.location.href = '<?php echo admin_url('admin.php?page=tajmap-admin-performance&user_id='); ?>' + userId;
    });

    // Load data on page load
    loadAdminUsers();
});
</script>


