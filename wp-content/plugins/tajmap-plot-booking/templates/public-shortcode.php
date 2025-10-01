<?php
if (!defined('ABSPATH')) { exit; }
?>
<div class="tajmap-pb-public">
	<div class="tajmap-pb-map">
		<img id="tajmap-pb-base-image" src="" alt="Map" />
		<svg id="tajmap-pb-svg" xmlns="http://www.w3.org/2000/svg"></svg>
		<div id="tajmap-pb-tooltip" class="tajmap-pb-tooltip" style="display:none;"></div>
	</div>
	<div id="tajmap-pb-modal" class="tajmap-pb-modal" style="display:none;">
		<div class="tajmap-pb-modal-content">
			<span class="tajmap-pb-close">&times;</span>
			<h3>Book This Plot</h3>
			<form id="tajmap-pb-lead-form">
				<input type="hidden" name="plot_id" id="tajmap-pb-selected-plot-id" />
				<p><label>Phone<br><input type="tel" name="phone" id="tajmap-pb-phone" required pattern="^[+0-9\-()\s]{7,20}$" /></label></p>
				<p><label>Email<br><input type="email" name="email" id="tajmap-pb-email" required /></label></p>
				<p><label>Message<br><textarea name="message" id="tajmap-pb-message" rows="4"></textarea></label></p>
				<p><button type="submit" class="tajmap-pb-submit">Submit</button></p>
			</form>
			<div id="tajmap-pb-form-feedback" class="tajmap-pb-feedback" style="display:none;"></div>
		</div>
	</div>
</div>
