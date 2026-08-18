<?php
/**
 * MCP Settings page.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap ssai-admin-wrap">
	<h1><?php esc_html_e( 'MCP Settings', 'smart-shop-ai' ); ?></h1>

	<form id="ssai-mcp-form" class="ssai-form">
		<table class="form-table">
			<tr>
				<th><label for="mcp_provider"><?php esc_html_e( 'MCP Provider', 'smart-shop-ai' ); ?></label></th>
				<td>
					<select id="mcp_provider" name="provider">
						<option value="woocommerce_direct"><?php esc_html_e( 'WooCommerce Direct (Built-in)', 'smart-shop-ai' ); ?></option>
						<option value="woocommerce_mcp"><?php esc_html_e( 'WooCommerce MCP (Remote)', 'smart-shop-ai' ); ?></option>
					</select>
					<p class="description"><?php esc_html_e( 'Use WooCommerce Direct for built-in search, or connect to an external WooCommerce MCP server.', 'smart-shop-ai' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="mcp_endpoint"><?php esc_html_e( 'MCP Endpoint', 'smart-shop-ai' ); ?></label></th>
				<td><input type="url" id="mcp_endpoint" name="endpoint" class="regular-text" placeholder="https://mcp.example.com/v1" /></td>
			</tr>
			<tr>
				<th><label for="mcp_api_key"><?php esc_html_e( 'API Key / Token', 'smart-shop-ai' ); ?></label></th>
				<td><input type="password" id="mcp_api_key" name="api_key" class="regular-text" /></td>
			</tr>
		</table>

		<p class="submit">
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Settings', 'smart-shop-ai' ); ?></button>
			<button type="button" id="ssai-test-mcp" class="button"><?php esc_html_e( 'Test MCP Connection', 'smart-shop-ai' ); ?></button>
		</p>
		<div id="ssai-mcp-test-result" class="ssai-test-result"></div>
	</form>
</div>
