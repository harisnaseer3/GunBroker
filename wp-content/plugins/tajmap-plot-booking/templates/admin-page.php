<?php
if (!defined('ABSPATH')) { exit; }
?>
<div class="wrap tajmap-pb-admin">
	<h1>Plot Management</h1>
	<div class="tajmap-pb-controls">
		<button id="tajmap-pb-upload-image" class="button button-primary">Upload/Select Base Map Image</button>
		<input type="hidden" id="tajmap-pb-base-image-id" value="" />
		<span id="tajmap-pb-image-info"></span>
	</div>
	<div class="tajmap-pb-editor">
		<canvas id="tajmap-pb-canvas" width="1024" height="680"></canvas>
		<div class="tajmap-pb-form">
			<h2>Plot Details</h2>
			<input type="hidden" id="tajmap-pb-plot-id" value="" />
			<p><label>Plot Name<br><input type="text" id="tajmap-pb-plot-name" class="regular-text" /></label></p>
			<p><label>Street<br><input type="text" id="tajmap-pb-street" class="regular-text" /></label></p>
			<p><label>Sector<br><input type="text" id="tajmap-pb-sector" class="regular-text" /></label></p>
			<p><label>Block<br><input type="text" id="tajmap-pb-block" class="regular-text" /></label></p>
			<p>
				<label>Status<br>
					<select id="tajmap-pb-status">
						<option value="available">Available</option>
						<option value="sold">Sold</option>
					</select>
				</label>
			</p>
			<p>
				<button id="tajmap-pb-start-poly" class="button">Start Polygon</button>
				<button id="tajmap-pb-complete-poly" class="button">Complete Polygon</button>
				<button id="tajmap-pb-reset-poly" class="button">Reset</button>
			</p>
			<p>
				<button id="tajmap-pb-save-plot" class="button button-primary">Save Plot</button>
				<button id="tajmap-pb-delete-plot" class="button button-secondary">Delete Plot</button>
			</p>
			<p>
				<strong>Existing Plots</strong>
				<ul id="tajmap-pb-plot-list"></ul>
			</p>
		</div>
	</div>
</div>
