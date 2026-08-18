<?php
/**
 * Admin Dashboard page.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap ssai-admin-wrap">
	<h1><?php esc_html_e( 'Smart Shop AI — Dashboard', 'smart-shop-ai' ); ?></h1>

	<div class="ssai-dashboard-grid">
		<div class="ssai-card">
			<h2><?php esc_html_e( 'Welcome', 'smart-shop-ai' ); ?></h2>
			<p><?php esc_html_e( 'AI-powered shopping assistant for your WooCommerce store. Configure AI provider, MCP connection, and attribute mapping to get started.', 'smart-shop-ai' ); ?></p>
		</div>

		<div class="ssai-card">
			<h2><?php esc_html_e( 'Quick Setup', 'smart-shop-ai' ); ?></h2>
			<ol class="ssai-setup-steps">
				<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=ssai-ai-provider' ) ); ?>"><?php esc_html_e( 'Configure AI Provider', 'smart-shop-ai' ); ?></a></li>
				<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=ssai-mcp' ) ); ?>"><?php esc_html_e( 'Configure MCP Settings', 'smart-shop-ai' ); ?></a></li>
				<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=ssai-attributes' ) ); ?>"><?php esc_html_e( 'Map Product Attributes', 'smart-shop-ai' ); ?></a></li>
				<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=ssai-rules' ) ); ?>"><?php esc_html_e( 'Review AI Rules', 'smart-shop-ai' ); ?></a></li>
				<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=ssai-diagnostics' ) ); ?>"><?php esc_html_e( 'Run Diagnostics', 'smart-shop-ai' ); ?></a></li>
			</ol>
		</div>

		<div class="ssai-card" id="ssai-status-card">
			<h2><?php esc_html_e( 'System Status', 'smart-shop-ai' ); ?></h2>
			<div id="ssai-status-checks" class="ssai-status-checks">
				<p><?php esc_html_e( 'Loading...', 'smart-shop-ai' ); ?></p>
			</div>
		</div>
	</div>
</div>
