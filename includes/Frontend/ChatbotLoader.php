<?php
namespace SmartShopAI\Frontend;

use SmartShopAI\Core\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loads chatbot frontend assets and renders widget.
 */
class ChatbotLoader {

	public function enqueue_assets(): void {
		$settings = Settings::get_chatbot_settings();
		if ( ! $settings['enabled'] ) {
			return;
		}

		wp_enqueue_style(
			'ssai-chatbot',
			SSAI_PLUGIN_URL . 'frontend/css/chatbot.css',
			array(),
			SSAI_VERSION
		);

		wp_enqueue_script(
			'ssai-chatbot',
			SSAI_PLUGIN_URL . 'frontend/js/chatbot.js',
			array(),
			SSAI_VERSION,
			true
		);

		$appearance   = $settings['appearance'];
		$float_button = $settings['float_button'];

		wp_localize_script( 'ssai-chatbot', 'ssaiChat', array(
			'restUrl'      => rest_url( 'smart-shop-ai/v1' ),
			'nonce'        => wp_create_nonce( 'wp_rest' ),
			'welcome'      => $settings['welcome'],
			'quickActions' => $settings['quick_actions'],
			'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
			'appearance'   => $appearance,
			'floatButton'  => $float_button,
			'support'      => array(
				'enabled' => ! empty( $settings['support']['enabled'] ),
			),
		) );

		// Inject CSS custom properties for theming.
		$css = $this->build_theme_css( $appearance, $float_button );
		wp_add_inline_style( 'ssai-chatbot', $css );
	}

	public function render_chatbot(): void {
		$settings = Settings::get_chatbot_settings();
		if ( ! $settings['enabled'] ) {
			return;
		}

		$template = SSAI_PLUGIN_DIR . 'templates/frontend/chatbot.php';
		if ( file_exists( $template ) ) {
			include $template;
		}
	}

	private function build_theme_css( array $appearance, array $float_button ): string {
		$primary   = sanitize_hex_color( $appearance['primary_color'] ) ?: '#4f46e5';
		$secondary = sanitize_hex_color( $appearance['secondary_color'] ) ?: '#7c3aed';
		$user      = sanitize_hex_color( $appearance['user_bubble_color'] ) ?: $primary;
		$bot       = sanitize_hex_color( $appearance['bot_bubble_color'] ) ?: '#ffffff';
		$bg        = sanitize_hex_color( $appearance['background_color'] ) ?: '#f8f9fb';
		$radius    = max( 0, min( 32, (int) $appearance['border_radius'] ) );
		$font_size = max( 12, min( 18, (int) $appearance['font_size'] ) );
		$position  = in_array( $float_button['position'], array( 'left', 'right' ), true ) ? $float_button['position'] : 'right';
		$offset_x  = max( 0, min( 200, (int) $float_button['offset_x'] ) );
		$offset_y  = max( 0, min( 200, (int) $float_button['offset_y'] ) );
		$size      = max( 44, min( 72, (int) $float_button['size'] ) );
		$animation = sanitize_html_class( $float_button['animation'] );

		$position_css = 'right' === $position
			? "right: {$offset_x}px; left: auto;"
			: "left: {$offset_x}px; right: auto;";

		return ":root {
			--ssai-primary: {$primary};
			--ssai-secondary: {$secondary};
			--ssai-user-bubble: {$user};
			--ssai-bot-bubble: {$bot};
			--ssai-bg: {$bg};
			--ssai-radius: {$radius}px;
			--ssai-font-size: {$font_size}px;
			--ssai-float-size: {$size}px;
			--ssai-offset-x: {$offset_x}px;
			--ssai-offset-y: {$offset_y}px;
		}
		.ssai-chatbot-root {
			bottom: {$offset_y}px;
			{$position_css}
		}
		.ssai-chatbot-root.ssai-position-left .ssai-chat-window {
			left: 0;
			right: auto;
		}
		.ssai-chatbot-root.ssai-position-right .ssai-chat-window {
			right: 0;
			left: auto;
		}
		.ssai-chat-toggle.ssai-anim-{$animation} {
			animation-name: ssai-{$animation};
		}";
	}
}
