<?php
/**
 * Chatbot settings page.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap ssai-admin-wrap">
	<h1><?php esc_html_e( 'Chatbot Settings', 'smart-shop-ai' ); ?></h1>

	<form id="ssai-chatbot-form" class="ssai-form">
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Enable Chatbot', 'smart-shop-ai' ); ?></th>
				<td>
					<label>
						<input type="checkbox" id="chatbot_enabled" name="enabled" value="1" />
						<?php esc_html_e( 'Show chatbot on frontend', 'smart-shop-ai' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th><label for="chatbot_welcome"><?php esc_html_e( 'Welcome Message', 'smart-shop-ai' ); ?></label></th>
				<td><textarea id="chatbot_welcome" name="welcome" rows="3" class="large-text"></textarea></td>
			</tr>
		</table>
		<p class="submit">
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Settings', 'smart-shop-ai' ); ?></button>
		</p>
	</form>
</div>
