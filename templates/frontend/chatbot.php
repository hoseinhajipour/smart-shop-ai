<?php
/**
 * Chatbot widget template.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use SmartShopAI\Core\Settings;

$float_settings = Settings::get_float_button_settings();
$appearance     = Settings::get_chatbot_appearance();
$position       = in_array( $float_settings['position'], array( 'left', 'right' ), true ) ? $float_settings['position'] : 'right';
$animation      = sanitize_html_class( $float_settings['animation'] );
?>
<div id="ssai-chatbot-root" class="ssai-chatbot-root ssai-position-<?php echo esc_attr( $position ); ?>" dir="ltr" aria-live="polite">
	<button id="ssai-chat-toggle" class="ssai-chat-toggle ssai-anim-<?php echo esc_attr( $animation ); ?>" aria-label="Open chat" aria-expanded="false">
		<span class="ssai-toggle-icon" aria-hidden="true"></span>
		<span class="ssai-toggle-ring" aria-hidden="true"></span>
	</button>

	<div id="ssai-chat-window" class="ssai-chat-window" hidden role="dialog" aria-label="Chat with shopping assistant">
		<div class="ssai-chat-header">
			<div class="ssai-chat-header-info">
				<div class="ssai-chat-avatar-wrap">
					<span class="ssai-chat-avatar" id="ssai-chat-avatar"><?php echo esc_html( $appearance['avatar_emoji'] ); ?></span>
					<span class="ssai-avatar-pulse" aria-hidden="true"></span>
				</div>
				<div>
					<strong id="ssai-chat-title"><?php echo esc_html( $appearance['title'] ); ?></strong>
					<span class="ssai-chat-status" id="ssai-chat-status">Online</span>
				</div>
			</div>
			<button id="ssai-chat-close" class="ssai-chat-close" aria-label="Close">&times;</button>
		</div>

		<div id="ssai-chat-messages" class="ssai-chat-messages"></div>

		<div id="ssai-quick-actions" class="ssai-quick-actions"></div>

		<div class="ssai-chat-input-area">
			<div class="ssai-input-wrapper">
				<input type="text" id="ssai-chat-input" class="ssai-chat-input" placeholder="Type your question..." autocomplete="off" />
				<span class="ssai-input-focus-ring" aria-hidden="true"></span>
			</div>
			<button id="ssai-chat-send" class="ssai-chat-send" aria-label="Send">
				<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
					<path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"></path>
				</svg>
			</button>
		</div>
	</div>
</div>
