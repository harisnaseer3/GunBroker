<?php
if (!defined('ABSPATH')) { exit; }

if (!current_user_can('manage_options')) { wp_die('Unauthorized'); }

global $wpdb;
$rows = $wpdb->get_results('SELECT l.*, p.plot_name, p.street, p.sector, p.block FROM ' . TAJMAP_PB_TABLE_LEADS . ' l LEFT JOIN ' . TAJMAP_PB_TABLE_PLOTS . ' p ON p.id = l.plot_id ORDER BY l.created_at DESC', ARRAY_A);
$export_url = wp_nonce_url(admin_url('admin-post.php?action=tajmap_pb_export_csv'), 'tajmap_pb_export');
?>
<div class="wrap tajmap-pb-admin">
	<h1>Leads</h1>
	<p><a href="<?php echo esc_url($export_url); ?>" class="button button-primary">Export CSV</a></p>
	<table class="widefat fixed striped">
		<thead>
			<tr>
				<th>ID</th>
				<th>Plot</th>
				<th>Contact</th>
				<th>Message</th>
				<th>Status</th>
				<th>Created</th>
			</tr>
		</thead>
		<tbody>
			<?php if ($rows) : foreach ($rows as $r) : ?>
			<tr>
				<td><?php echo intval($r['id']); ?></td>
				<td><?php echo esc_html(trim(($r['plot_name'] ?: '') . ' ' . ($r['street'] ?: '') . ', ' . ($r['block'] ?: '') . ' Block')); ?></td>
				<td>
					<div>Phone: <?php echo esc_html($r['phone']); ?></div>
					<div>Email: <?php echo esc_html($r['email']); ?></div>
				</td>
				<td><?php echo esc_html($r['message']); ?></td>
				<td>
					<select class="tajmap-pb-lead-status" data-id="<?php echo intval($r['id']); ?>">
						<option value="new" <?php selected($r['status'], 'new'); ?>>New</option>
						<option value="contacted" <?php selected($r['status'], 'contacted'); ?>>Contacted</option>
					</select>
				</td>
				<td><?php echo esc_html($r['created_at']); ?></td>
			</tr>
			<?php endforeach; else: ?>
			<tr><td colspan="6">No leads yet.</td></tr>
			<?php endif; ?>
		</tbody>
	</table>
</div>
<script type="text/javascript">
(function($){
	$(document).on('change','.tajmap-pb-lead-status',function(){
		var id=$(this).data('id'); var status=$(this).val();
		$.post(ajaxurl,{ action:'tajmap_pb_set_lead_status', nonce:'<?php echo esc_js(wp_create_nonce('tajmap_pb_admin')); ?>', id:id, status:status }, function(res){ if(!(res&&res.success)){ alert('Failed to update'); } });
	});
})(jQuery);
</script>
