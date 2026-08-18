<?php
/**
 * Chatbot settings page.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap ssai-admin-wrap ssai-chatbot-settings">
	<h1><?php esc_html_e( 'Chatbot Settings', 'smart-shop-ai' ); ?></h1>

	<form id="ssai-chatbot-form" class="ssai-form">
		<div class="ssai-settings-grid">
			<div class="ssai-settings-panel">
				<h2><?php esc_html_e( 'General', 'smart-shop-ai' ); ?></h2>
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
					<tr>
						<th><label for="chatbot_title"><?php esc_html_e( 'Chat Title', 'smart-shop-ai' ); ?></label></th>
						<td><input type="text" id="chatbot_title" class="regular-text" value="Shopping Assistant" /></td>
					</tr>
					<tr>
						<th><label for="chatbot_avatar_emoji"><?php esc_html_e( 'AI Character', 'smart-shop-ai' ); ?></label></th>
						<td>
							<div class="ssai-emoji-picker">
								<input type="text" id="chatbot_avatar_emoji" class="small-text" value="🤖" maxlength="4" />
								<div class="ssai-emoji-options">
									<button type="button" class="ssai-emoji-btn" data-emoji="🤖">🤖</button>
									<button type="button" class="ssai-emoji-btn" data-emoji="🛒">🛒</button>
									<button type="button" class="ssai-emoji-btn" data-emoji="✨">✨</button>
									<button type="button" class="ssai-emoji-btn" data-emoji="🎯">🎯</button>
									<button type="button" class="ssai-emoji-btn" data-emoji="💬">💬</button>
									<button type="button" class="ssai-emoji-btn" data-emoji="🧠">🧠</button>
								</div>
							</div>
						</td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'Appearance & Colors', 'smart-shop-ai' ); ?></h2>
				<table class="form-table">
					<tr>
						<th><label for="chatbot_primary_color"><?php esc_html_e( 'Primary Color', 'smart-shop-ai' ); ?></label></th>
						<td><input type="color" id="chatbot_primary_color" value="#4f46e5" /></td>
					</tr>
					<tr>
						<th><label for="chatbot_secondary_color"><?php esc_html_e( 'Secondary Color', 'smart-shop-ai' ); ?></label></th>
						<td><input type="color" id="chatbot_secondary_color" value="#7c3aed" /></td>
					</tr>
					<tr>
						<th><label for="chatbot_user_bubble_color"><?php esc_html_e( 'User Bubble Color', 'smart-shop-ai' ); ?></label></th>
						<td><input type="color" id="chatbot_user_bubble_color" value="#4f46e5" /></td>
					</tr>
					<tr>
						<th><label for="chatbot_bot_bubble_color"><?php esc_html_e( 'Bot Bubble Color', 'smart-shop-ai' ); ?></label></th>
						<td><input type="color" id="chatbot_bot_bubble_color" value="#ffffff" /></td>
					</tr>
					<tr>
						<th><label for="chatbot_background_color"><?php esc_html_e( 'Background Color', 'smart-shop-ai' ); ?></label></th>
						<td><input type="color" id="chatbot_background_color" value="#f8f9fb" /></td>
					</tr>
					<tr>
						<th><label for="chatbot_border_radius"><?php esc_html_e( 'Border Radius (px)', 'smart-shop-ai' ); ?></label></th>
						<td><input type="range" id="chatbot_border_radius" min="0" max="32" value="16" /> <span id="chatbot_border_radius_val">16</span>px</td>
					</tr>
					<tr>
						<th><label for="chatbot_font_size"><?php esc_html_e( 'Font Size (px)', 'smart-shop-ai' ); ?></label></th>
						<td><input type="range" id="chatbot_font_size" min="12" max="18" value="14" /> <span id="chatbot_font_size_val">14</span>px</td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'Floating Button', 'smart-shop-ai' ); ?></h2>
				<table class="form-table">
					<tr>
						<th><label for="float_button_position"><?php esc_html_e( 'Position', 'smart-shop-ai' ); ?></label></th>
						<td>
							<select id="float_button_position">
								<option value="right"><?php esc_html_e( 'Right side', 'smart-shop-ai' ); ?></option>
								<option value="left"><?php esc_html_e( 'Left side', 'smart-shop-ai' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th><label for="float_button_icon"><?php esc_html_e( 'Icon', 'smart-shop-ai' ); ?></label></th>
						<td>
							<select id="float_button_icon">
								<option value="chat"><?php esc_html_e( 'Chat bubble', 'smart-shop-ai' ); ?></option>
								<option value="robot"><?php esc_html_e( 'Robot', 'smart-shop-ai' ); ?></option>
								<option value="help"><?php esc_html_e( 'Help', 'smart-shop-ai' ); ?></option>
								<option value="sparkle"><?php esc_html_e( 'Sparkle / AI', 'smart-shop-ai' ); ?></option>
								<option value="cart"><?php esc_html_e( 'Shopping cart', 'smart-shop-ai' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th><label for="float_button_animation"><?php esc_html_e( 'Animation Effect', 'smart-shop-ai' ); ?></label></th>
						<td>
							<select id="float_button_animation">
								<option value="none"><?php esc_html_e( 'None', 'smart-shop-ai' ); ?></option>
								<option value="pulse"><?php esc_html_e( 'Pulse', 'smart-shop-ai' ); ?></option>
								<option value="bounce"><?php esc_html_e( 'Bounce', 'smart-shop-ai' ); ?></option>
								<option value="wiggle"><?php esc_html_e( 'Wiggle', 'smart-shop-ai' ); ?></option>
								<option value="glow"><?php esc_html_e( 'Glow', 'smart-shop-ai' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th><label for="float_button_size"><?php esc_html_e( 'Button Size (px)', 'smart-shop-ai' ); ?></label></th>
						<td><input type="range" id="float_button_size" min="44" max="72" value="56" /> <span id="float_button_size_val">56</span>px</td>
					</tr>
					<tr>
						<th><label for="float_button_offset_x"><?php esc_html_e( 'Offset X (px)', 'smart-shop-ai' ); ?></label></th>
						<td>
							<input type="number" id="float_button_offset_x" min="0" max="200" value="24" />
							<p class="description"><?php esc_html_e( 'Horizontal distance from screen edge.', 'smart-shop-ai' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><label for="float_button_offset_y"><?php esc_html_e( 'Offset Y (px)', 'smart-shop-ai' ); ?></label></th>
						<td>
							<input type="number" id="float_button_offset_y" min="0" max="200" value="24" />
							<p class="description"><?php esc_html_e( 'Vertical distance from screen bottom.', 'smart-shop-ai' ); ?></p>
						</td>
					</tr>
				</table>
			</div>

			<div class="ssai-preview-panel">
				<h2><?php esc_html_e( 'Live Preview', 'smart-shop-ai' ); ?></h2>
				<div id="ssai-chatbot-preview" class="ssai-chatbot-preview">
					<div class="ssai-preview-float-btn" id="ssai-preview-float-btn">
						<span class="ssai-preview-icon">💬</span>
					</div>
					<div class="ssai-preview-window">
						<div class="ssai-preview-header">
							<span class="ssai-preview-avatar">🤖</span>
							<div>
								<strong class="ssai-preview-title">Shopping Assistant</strong>
								<span class="ssai-preview-status">Online</span>
							</div>
						</div>
						<div class="ssai-preview-messages">
							<div class="ssai-preview-msg bot">Hi 👋 How can I help you today?</div>
							<div class="ssai-preview-msg user">I need wheels for my car</div>
							<div class="ssai-preview-typing">
								<span class="ssai-preview-avatar-sm">🤖</span>
								<span class="ssai-typing-dots"><span></span><span></span><span></span></span>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<p class="submit">
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Settings', 'smart-shop-ai' ); ?></button>
		</p>
	</form>
</div>
