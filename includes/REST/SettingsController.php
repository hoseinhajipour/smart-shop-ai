<?php
namespace SmartShopAI\REST;

use SmartShopAI\Core\Settings;
use SmartShopAI\AI\AIService;
use SmartShopAI\MCP\MCPService;
use SmartShopAI\WooCommerce\AttributeDiscovery;
use SmartShopAI\Rules\RulesManager;
use SmartShopAI\Core\ConversationLogger;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings REST API endpoints.
 */
class SettingsController {

	private const NAMESPACE = 'smart-shop-ai/v1';

	public function register_routes(): void {
		// AI Settings.
		register_rest_route( self::NAMESPACE, '/settings/ai', array(
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_ai_settings' ),
				'permission_callback' => array( $this, 'check_admin' ),
			),
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'update_ai_settings' ),
				'permission_callback' => array( $this, 'check_admin' ),
			),
		) );

		// MCP Settings.
		register_rest_route( self::NAMESPACE, '/settings/mcp', array(
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_mcp_settings' ),
				'permission_callback' => array( $this, 'check_admin' ),
			),
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'update_mcp_settings' ),
				'permission_callback' => array( $this, 'check_admin' ),
			),
		) );

		// System Prompt.
		register_rest_route( self::NAMESPACE, '/settings/prompt', array(
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_system_prompt' ),
				'permission_callback' => array( $this, 'check_admin' ),
			),
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'update_system_prompt' ),
				'permission_callback' => array( $this, 'check_admin' ),
			),
		) );

		// Capabilities.
		register_rest_route( self::NAMESPACE, '/settings/capabilities', array(
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_capabilities' ),
				'permission_callback' => array( $this, 'check_admin' ),
			),
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'update_capabilities' ),
				'permission_callback' => array( $this, 'check_admin' ),
			),
		) );

		// Attribute Mapping.
		register_rest_route( self::NAMESPACE, '/settings/attributes', array(
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_attributes' ),
				'permission_callback' => array( $this, 'check_admin' ),
			),
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'update_attribute_mapping' ),
				'permission_callback' => array( $this, 'check_admin' ),
			),
		) );

		// Chatbot Settings.
		register_rest_route( self::NAMESPACE, '/settings/chatbot', array(
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_chatbot_settings' ),
				'permission_callback' => array( $this, 'check_admin' ),
			),
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'update_chatbot_settings' ),
				'permission_callback' => array( $this, 'check_admin' ),
			),
		) );

		// AI Rules CRUD.
		register_rest_route( self::NAMESPACE, '/rules', array(
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_rules' ),
				'permission_callback' => array( $this, 'check_admin' ),
			),
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'create_rule' ),
				'permission_callback' => array( $this, 'check_admin' ),
			),
		) );

		register_rest_route( self::NAMESPACE, '/rules/(?P<id>\d+)', array(
			array(
				'methods'             => 'PUT',
				'callback'            => array( $this, 'update_rule' ),
				'permission_callback' => array( $this, 'check_admin' ),
			),
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'delete_rule' ),
				'permission_callback' => array( $this, 'check_admin' ),
			),
		) );

		// Conversation Logs.
		register_rest_route( self::NAMESPACE, '/logs', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_logs' ),
			'permission_callback' => array( $this, 'check_admin' ),
		) );

		// Test Connections.
		register_rest_route( self::NAMESPACE, '/test/ai', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'test_ai_connection' ),
			'permission_callback' => array( $this, 'check_admin' ),
		) );

		register_rest_route( self::NAMESPACE, '/test/mcp', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'test_mcp_connection' ),
			'permission_callback' => array( $this, 'check_admin' ),
		) );
	}

	public function check_admin(): bool {
		return current_user_can( 'manage_options' );
	}

	public function get_ai_settings( WP_REST_Request $request ): WP_REST_Response {
		$settings = Settings::get_ai_settings();
		// Mask API key.
		if ( ! empty( $settings['api_key'] ) ) {
			$settings['api_key_masked'] = substr( $settings['api_key'], 0, 4 ) . '...' . substr( $settings['api_key'], -4 );
		}
		return new WP_REST_Response( $settings, 200 );
	}

	public function update_ai_settings( WP_REST_Request $request ): WP_REST_Response {
		$params = $request->get_json_params() ?: $request->get_params();

		$fields = array( 'ai_provider', 'ai_endpoint', 'ai_api_key', 'ai_model', 'ai_temperature', 'ai_max_tokens', 'ai_timeout' );
		foreach ( $fields as $field ) {
			$key = str_replace( 'ai_', '', $field );
			if ( isset( $params[ $key ] ) || isset( $params[ $field ] ) ) {
				$value = $params[ $key ] ?? $params[ $field ];
				Settings::set( $field, $value );
			}
		}

		return new WP_REST_Response( array( 'success' => true ), 200 );
	}

	public function get_mcp_settings( WP_REST_Request $request ): WP_REST_Response {
		return new WP_REST_Response( Settings::get_mcp_settings(), 200 );
	}

	public function update_mcp_settings( WP_REST_Request $request ): WP_REST_Response {
		$params = $request->get_json_params() ?: $request->get_params();

		if ( isset( $params['provider'] ) ) {
			Settings::set( 'mcp_provider', sanitize_text_field( $params['provider'] ) );
		}
		if ( isset( $params['endpoint'] ) ) {
			Settings::set( 'mcp_endpoint', esc_url_raw( $params['endpoint'] ) );
		}
		if ( isset( $params['api_key'] ) ) {
			Settings::set( 'mcp_api_key', sanitize_text_field( $params['api_key'] ) );
		}

		return new WP_REST_Response( array( 'success' => true ), 200 );
	}

	public function get_system_prompt( WP_REST_Request $request ): WP_REST_Response {
		return new WP_REST_Response( array( 'prompt' => Settings::get_system_prompt() ), 200 );
	}

	public function update_system_prompt( WP_REST_Request $request ): WP_REST_Response {
		$params = $request->get_json_params() ?: $request->get_params();
		Settings::set( 'system_prompt', sanitize_textarea_field( $params['prompt'] ?? '' ) );
		return new WP_REST_Response( array( 'success' => true ), 200 );
	}

	public function get_capabilities( WP_REST_Request $request ): WP_REST_Response {
		return new WP_REST_Response( Settings::get_capabilities(), 200 );
	}

	public function update_capabilities( WP_REST_Request $request ): WP_REST_Response {
		$params = $request->get_json_params() ?: $request->get_params();
		Settings::set( 'capabilities', $params );
		return new WP_REST_Response( array( 'success' => true ), 200 );
	}

	public function get_attributes( WP_REST_Request $request ): WP_REST_Response {
		$discovery = new AttributeDiscovery();
		return new WP_REST_Response( array(
			'attributes'  => $discovery->get_all_attributes(),
			'mapping'       => Settings::get_attribute_mapping(),
			'suggestions' => $discovery->get_mapping_suggestions(),
		), 200 );
	}

	public function update_attribute_mapping( WP_REST_Request $request ): WP_REST_Response {
		$params = $request->get_json_params() ?: $request->get_params();
		Settings::set( 'attribute_mapping', $params['mapping'] ?? array() );
		return new WP_REST_Response( array( 'success' => true ), 200 );
	}

	public function get_chatbot_settings( WP_REST_Request $request ): WP_REST_Response {
		return new WP_REST_Response( Settings::get_chatbot_settings(), 200 );
	}

	public function update_chatbot_settings( WP_REST_Request $request ): WP_REST_Response {
		$params = $request->get_json_params() ?: $request->get_params();

		if ( isset( $params['enabled'] ) ) {
			Settings::set( 'chatbot_enabled', (bool) $params['enabled'] );
		}
		if ( isset( $params['welcome'] ) ) {
			Settings::set( 'chatbot_welcome', sanitize_textarea_field( $params['welcome'] ) );
		}
		if ( isset( $params['quick_actions'] ) ) {
			Settings::set( 'quick_actions', $params['quick_actions'] );
		}

		return new WP_REST_Response( array( 'success' => true ), 200 );
	}

	public function get_rules( WP_REST_Request $request ): WP_REST_Response {
		$manager = new RulesManager();
		return new WP_REST_Response( $manager->get_all_rules(), 200 );
	}

	public function create_rule( WP_REST_Request $request ): WP_REST_Response {
		$params  = $request->get_json_params() ?: $request->get_params();
		$manager = new RulesManager();
		$id      = $manager->create_rule( $params );
		return new WP_REST_Response( array( 'id' => $id, 'success' => true ), 201 );
	}

	public function update_rule( WP_REST_Request $request ): WP_REST_Response {
		$id      = (int) $request->get_param( 'id' );
		$params  = $request->get_json_params() ?: $request->get_params();
		$manager = new RulesManager();
		$manager->update_rule( $id, $params );
		return new WP_REST_Response( array( 'success' => true ), 200 );
	}

	public function delete_rule( WP_REST_Request $request ): WP_REST_Response {
		$id      = (int) $request->get_param( 'id' );
		$manager = new RulesManager();
		$manager->delete_rule( $id );
		return new WP_REST_Response( array( 'success' => true ), 200 );
	}

	public function get_logs( WP_REST_Request $request ): WP_REST_Response {
		$limit  = (int) ( $request->get_param( 'limit' ) ?: 50 );
		$offset = (int) ( $request->get_param( 'offset' ) ?: 0 );

		return new WP_REST_Response( array(
			'logs'  => ConversationLogger::get_logs( $limit, $offset ),
			'total' => ConversationLogger::get_log_count(),
		), 200 );
	}

	public function test_ai_connection( WP_REST_Request $request ): WP_REST_Response {
		$ai = new AIService();
		$result = $ai->test_connection();
		return new WP_REST_Response( $result, $result['success'] ? 200 : 500 );
	}

	public function test_mcp_connection( WP_REST_Request $request ): WP_REST_Response {
		$mcp    = new MCPService();
		$result = $mcp->test_connection();
		return new WP_REST_Response( $result, $result['success'] ? 200 : 500 );
	}
}
