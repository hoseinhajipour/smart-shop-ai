<?php
/**
 * Diagnostics page.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap ssai-admin-wrap">
	<h1><?php esc_html_e( 'Diagnostics', 'smart-shop-ai' ); ?></h1>

	<div id="ssai-diagnostics-checks" class="ssai-diagnostics-checks">
		<p><?php esc_html_e( 'Loading diagnostics...', 'smart-shop-ai' ); ?></p>
	</div>

	<h2><?php esc_html_e( 'Test Query', 'smart-shop-ai' ); ?></h2>
	<div class="ssai-test-query-area">
		<input type="text" id="ssai-test-query" class="regular-text" value="برای پژو 206 رینگ 16 میخوام" style="width: 100%; max-width: 500px;" />
		<button type="button" id="ssai-run-test" class="button button-primary"><?php esc_html_e( 'Run Test', 'smart-shop-ai' ); ?></button>
	</div>

	<div id="ssai-test-results" class="ssai-test-results"></div>
</div>
