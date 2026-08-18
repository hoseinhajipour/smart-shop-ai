<?php
/**
 * AI Provider settings page.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap ssai-admin-wrap">
	<h1><?php esc_html_e( 'AI Provider Settings', 'smart-shop-ai' ); ?></h1>

	<form id="ssai-ai-form" class="ssai-form">
		<table class="form-table">
			<tr>
				<th><label for="provider"><?php esc_html_e( 'Provider', 'smart-shop-ai' ); ?></label></th>
				<td>
					<select id="provider" name="provider">
						<option value="openai">OpenAI</option>
						<option value="anthropic">Anthropic</option>
						<option value="gemini">Gemini</option>
						<option value="openai_compatible">OpenAI Compatible API</option>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="endpoint"><?php esc_html_e( 'API Endpoint', 'smart-shop-ai' ); ?></label></th>
				<td><input type="url" id="endpoint" name="endpoint" class="regular-text" placeholder="https://api.openai.com/v1/chat/completions" /></td>
			</tr>
			<tr>
				<th><label for="api_key"><?php esc_html_e( 'API Key', 'smart-shop-ai' ); ?></label></th>
				<td><input type="password" id="api_key" name="api_key" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="model"><?php esc_html_e( 'Model', 'smart-shop-ai' ); ?></label></th>
				<td><input type="text" id="model" name="model" class="regular-text" placeholder="gpt-4o-mini" /></td>
			</tr>
			<tr>
				<th><label for="temperature"><?php esc_html_e( 'Temperature', 'smart-shop-ai' ); ?></label></th>
				<td><input type="number" id="temperature" name="temperature" min="0" max="2" step="0.1" value="0.7" /></td>
			</tr>
			<tr>
				<th><label for="max_tokens"><?php esc_html_e( 'Max Tokens', 'smart-shop-ai' ); ?></label></th>
				<td><input type="number" id="max_tokens" name="max_tokens" min="100" max="32000" value="2048" /></td>
			</tr>
			<tr>
				<th><label for="timeout"><?php esc_html_e( 'Timeout (seconds)', 'smart-shop-ai' ); ?></label></th>
				<td><input type="number" id="timeout" name="timeout" min="5" max="120" value="30" /></td>
			</tr>
		</table>

		<p class="submit">
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Settings', 'smart-shop-ai' ); ?></button>
			<button type="button" id="ssai-test-ai" class="button"><?php esc_html_e( 'Test Connection', 'smart-shop-ai' ); ?></button>
		</p>
		<div id="ssai-ai-test-result" class="ssai-test-result"></div>
	</form>
</div>
