<?php
/**
 * Conversation Logs page.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap ssai-admin-wrap">
	<h1><?php esc_html_e( 'Conversation Logs', 'smart-shop-ai' ); ?></h1>

	<div id="ssai-logs-toolbar" class="ssai-logs-toolbar" style="display:none;">
		<div class="ssai-logs-toolbar-left">
			<label class="ssai-logs-select-all">
				<input type="checkbox" id="ssai-logs-select-all" />
				<?php esc_html_e( 'Select all', 'smart-shop-ai' ); ?>
			</label>
			<span id="ssai-logs-count" class="ssai-logs-count"></span>
		</div>
		<div class="ssai-logs-toolbar-right">
			<button type="button" id="ssai-logs-export" class="button">
				<?php esc_html_e( 'Export CSV', 'smart-shop-ai' ); ?>
			</button>
			<button type="button" id="ssai-logs-delete-selected" class="button" disabled>
				<?php esc_html_e( 'Delete Selected', 'smart-shop-ai' ); ?>
			</button>
		</div>
	</div>

	<div id="ssai-logs-container">
		<p><?php esc_html_e( 'Loading logs...', 'smart-shop-ai' ); ?></p>
	</div>
</div>
