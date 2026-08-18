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

		wp_localize_script( 'ssai-chatbot', 'ssaiChat', array(
			'restUrl' => rest_url( 'smart-shop-ai/v1' ),
			'nonce'   => wp_create_nonce( 'wp_rest' ),
			'welcome' => $settings['welcome'],
			'quickActions' => $settings['quick_actions'],
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		) );
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
}
