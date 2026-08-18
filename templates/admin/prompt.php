<?php
/**
 * System Prompt page.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap ssai-admin-wrap">
	<h1><?php esc_html_e( 'System Prompt', 'smart-shop-ai' ); ?></h1>
	<p><?php esc_html_e( 'Define the global system prompt that guides the AI assistant.', 'smart-shop-ai' ); ?></p>

	<form id="ssai-prompt-form" class="ssai-form">
		<table class="form-table">
			<tr>
				<th><label for="system_prompt"><?php esc_html_e( 'System Prompt', 'smart-shop-ai' ); ?></label></th>
				<td>
					<textarea id="system_prompt" name="prompt" rows="12" class="large-text code"></textarea>
					<p class="description"><?php esc_html_e( 'This prompt is sent to the AI with every conversation. Define the assistant role, behavior, and constraints.', 'smart-shop-ai' ); ?></p>
				</td>
			</tr>
		</table>
		<p class="submit">
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Prompt', 'smart-shop-ai' ); ?></button>
		</p>
	</form>
</div>
