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
						<option value="anthropic">Anthropic (Claude)</option>
						<option value="gemini">Google Gemini</option>
						<option value="openrouter">OpenRouter</option>
						<option value="groq">Groq</option>
						<option value="together">Together AI</option>
						<option value="replicate">Replicate</option>
						<option value="custom"><?php esc_html_e( 'Custom Endpoint', 'smart-shop-ai' ); ?></option>
					</select>
					<p class="description"><?php esc_html_e( 'Select a provider preset or choose Custom for your own endpoint.', 'smart-shop-ai' ); ?></p>
				</td>
			</tr>
			<tr id="ssai-custom-endpoint-preset-row" style="display:none;">
				<th><label for="custom_endpoint_preset"><?php esc_html_e( 'Endpoint Preset', 'smart-shop-ai' ); ?></label></th>
				<td>
					<select id="custom_endpoint_preset">
						<option value=""><?php esc_html_e( '— Select a popular endpoint —', 'smart-shop-ai' ); ?></option>
						<option value="https://api.openai.com/v1/chat/completions">OpenAI API</option>
						<option value="https://openrouter.ai/api/v1/chat/completions">OpenRouter</option>
						<option value="https://api.groq.com/openai/v1/chat/completions">Groq</option>
						<option value="https://api.together.xyz/v1/chat/completions">Together AI</option>
						<option value="https://api.deepseek.com/v1/chat/completions">DeepSeek</option>
						<option value="https://api.mistral.ai/v1/chat/completions">Mistral AI</option>
						<option value="https://api.perplexity.ai/chat/completions">Perplexity</option>
						<option value="https://dashscope.aliyuncs.com/compatible-mode/v1/chat/completions">Alibaba DashScope</option>
						<option value="__custom__"><?php esc_html_e( 'Enter custom URL manually', 'smart-shop-ai' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="endpoint"><?php esc_html_e( 'API Endpoint', 'smart-shop-ai' ); ?></label></th>
				<td>
					<input type="url" id="endpoint" name="endpoint" class="large-text" placeholder="https://api.openai.com/v1/chat/completions" />
					<p class="description" id="ssai-endpoint-hint"></p>
				</td>
			</tr>
			<tr>
				<th><label for="api_key"><?php esc_html_e( 'API Key', 'smart-shop-ai' ); ?></label></th>
				<td>
					<div class="ssai-secret-field">
						<input type="password" id="api_key" name="api_key" class="regular-text" autocomplete="off" />
						<button type="button" id="ssai-toggle-api-key" class="button ssai-toggle-secret" aria-pressed="false" aria-label="<?php esc_attr_e( 'Show API key', 'smart-shop-ai' ); ?>">
							<?php esc_html_e( 'Show', 'smart-shop-ai' ); ?>
						</button>
					</div>
				</td>
			</tr>
			<tr>
				<th><label for="model"><?php esc_html_e( 'Model', 'smart-shop-ai' ); ?></label></th>
				<td>
					<div class="ssai-model-field">
						<select id="model" name="model" class="regular-text">
							<option value=""><?php esc_html_e( '— Select model —', 'smart-shop-ai' ); ?></option>
						</select>
						<input type="text" id="model_custom" class="regular-text" placeholder="<?php esc_attr_e( 'Enter custom model ID', 'smart-shop-ai' ); ?>" style="display:none;" />
						<button type="button" id="ssai-fetch-models" class="button"><?php esc_html_e( 'Fetch Models', 'smart-shop-ai' ); ?></button>
					</div>
					<p class="description" id="ssai-model-hint"></p>
					<p class="description" id="ssai-model-fetch-status"></p>
				</td>
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
