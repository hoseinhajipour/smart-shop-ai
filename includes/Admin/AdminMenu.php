<?php
namespace SmartShopAI\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers admin menu and pages.
 */
class AdminMenu {

	public function register_menu(): void {
		add_menu_page(
			__( 'Smart Shop AI', 'smart-shop-ai' ),
			__( 'Smart Shop AI', 'smart-shop-ai' ),
			'manage_options',
			'smart-shop-ai',
			array( $this, 'render_dashboard' ),
			'dashicons-format-chat',
			58
		);

		$pages = array(
			'smart-shop-ai'           => array( 'Dashboard', 'render_dashboard' ),
			'ssai-chatbot'            => array( 'Chatbot', 'render_chatbot' ),
			'ssai-ai-provider'        => array( 'AI Provider', 'render_ai_provider' ),
			'ssai-mcp'                => array( 'MCP Settings', 'render_mcp' ),
			'ssai-attributes'         => array( 'Product Search', 'render_attributes' ),
			'ssai-rules'              => array( 'AI Rules', 'render_rules' ),
			'ssai-prompt'             => array( 'System Prompt', 'render_prompt' ),
			'ssai-capabilities'       => array( 'Capabilities', 'render_capabilities' ),
			'ssai-logs'               => array( 'Conversation Logs', 'render_logs' ),
			'ssai-diagnostics'        => array( 'Diagnostics', 'render_diagnostics' ),
		);

		foreach ( $pages as $slug => $info ) {
			if ( $slug === 'smart-shop-ai' ) {
				continue;
			}
			add_submenu_page(
				'smart-shop-ai',
				__( $info[0], 'smart-shop-ai' ),
				__( $info[0], 'smart-shop-ai' ),
				'manage_options',
				$slug,
				array( $this, $info[1] )
			);
		}
	}

	public function enqueue_assets( string $hook ): void {
		if ( strpos( $hook, 'smart-shop-ai' ) === false && strpos( $hook, 'ssai-' ) === false ) {
			return;
		}

		wp_enqueue_style(
			'ssai-admin',
			SSAI_PLUGIN_URL . 'admin/css/admin.css',
			array(),
			SSAI_VERSION
		);

		wp_enqueue_script(
			'ssai-admin',
			SSAI_PLUGIN_URL . 'admin/js/admin.js',
			array( 'wp-api-fetch' ),
			SSAI_VERSION,
			true
		);

		wp_localize_script( 'ssai-admin', 'ssaiAdmin', array(
			'restUrl' => rest_url( 'smart-shop-ai/v1' ),
			'nonce'   => wp_create_nonce( 'wp_rest' ),
		) );
	}

	public function render_dashboard(): void {
		$this->render_page( 'dashboard' );
	}

	public function render_chatbot(): void {
		$this->render_page( 'chatbot' );
	}

	public function render_ai_provider(): void {
		$this->render_page( 'ai-provider' );
	}

	public function render_mcp(): void {
		$this->render_page( 'mcp' );
	}

	public function render_attributes(): void {
		$this->render_page( 'attributes' );
	}

	public function render_rules(): void {
		$this->render_page( 'rules' );
	}

	public function render_prompt(): void {
		$this->render_page( 'prompt' );
	}

	public function render_capabilities(): void {
		$this->render_page( 'capabilities' );
	}

	public function render_logs(): void {
		$this->render_page( 'logs' );
	}

	public function render_diagnostics(): void {
		$this->render_page( 'diagnostics' );
	}

	private function render_page( string $page ): void {
		$template = SSAI_PLUGIN_DIR . "templates/admin/{$page}.php";
		if ( file_exists( $template ) ) {
			include $template;
		} else {
			echo '<div class="wrap"><h1>Smart Shop AI</h1><p>Page template not found.</p></div>';
		}
	}
}
