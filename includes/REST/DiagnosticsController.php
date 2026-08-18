<?php
namespace SmartShopAI\REST;

use SmartShopAI\AI\AIService;
use SmartShopAI\AI\IntentParser;
use SmartShopAI\MCP\MCPService;
use SmartShopAI\Search\SearchRouter;
use SmartShopAI\WooCommerce\AttributeDiscovery;
use SmartShopAI\WooCommerce\WooCommerceHelper;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Diagnostics REST API endpoint.
 */
class DiagnosticsController {

	private const NAMESPACE = 'smart-shop-ai/v1';

	public function register_routes(): void {
		register_rest_route( self::NAMESPACE, '/diagnostics', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_diagnostics' ),
			'permission_callback' => array( $this, 'check_admin' ),
		) );

		register_rest_route( self::NAMESPACE, '/diagnostics/test', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'run_test_query' ),
			'permission_callback' => array( $this, 'check_admin' ),
		) );
	}

	public function check_admin(): bool {
		return current_user_can( 'manage_options' );
	}

	public function get_diagnostics( WP_REST_Request $request ): WP_REST_Response {
		$ai  = new AIService();
		$mcp = new MCPService();
		$discovery = new AttributeDiscovery();

		$ai_test  = $ai->test_connection();
		$mcp_test = $mcp->test_connection();
		$woo_ok   = WooCommerceHelper::is_woocommerce_active();
		$attrs    = $discovery->get_all_attributes();

		$product_count = 0;
		if ( $woo_ok ) {
			$count = wp_count_posts( 'product' );
			$product_count = isset( $count->publish ) ? (int) $count->publish : 0;
		}

		return new WP_REST_Response( array(
			'checks' => array(
				'ai_connection'  => array( 'status' => $ai_test['success'], 'message' => $ai_test['message'] ),
				'mcp_connection' => array( 'status' => $mcp_test['success'], 'message' => $mcp_test['message'] ),
				'woocommerce'    => array( 'status' => $woo_ok, 'message' => $woo_ok ? "Active ({$product_count} products)" : 'Not active' ),
				'attributes'     => array( 'status' => count( $attrs ) > 0, 'message' => count( $attrs ) . ' attributes found' ),
				'product_search' => array( 'status' => $woo_ok && $product_count > 0, 'message' => $product_count > 0 ? 'Ready' : 'No products' ),
			),
			'attribute_count' => count( $attrs ),
			'product_count'   => $product_count,
		), 200 );
	}

	public function run_test_query( WP_REST_Request $request ): WP_REST_Response {
		$query = sanitize_textarea_field( $request->get_param( 'query' ) ?: 'I need 16 inch wheels for Peugeot 206' );

		$ai_service    = new AIService();
		$intent_parser = new IntentParser();
		$search_router = new SearchRouter();

		// Extract intent.
		$extract_result = $ai_service->extract_intent( $query );
		$raw_intent = $extract_result['success'] ? $extract_result['data'] : array();
		$raw_intent['original_message'] = $query;
		$intent = $intent_parser->parse( $raw_intent );

		// Search.
		$search_result = $search_router->search( $intent );
		$products = $search_result['products'] ?? array();

		// AI response.
		$ai_response = '';
		if ( ! empty( $products ) ) {
			$resp = $ai_service->generate_response( $query, $intent, array_slice( $products, 0, 5 ) );
			$ai_response = $resp['success'] ? $resp['content'] : '';
		}

		return new WP_REST_Response( array(
			'query'              => $query,
			'detected_intent'    => $intent,
			'detected_vehicle'   => $intent['vehicle'],
			'detected_attributes'=> $intent['attributes'],
			'generated_search'   => $search_result['search_query'] ?? '',
			'search_type'        => $search_result['search_type'] ?? '',
			'mcp_log'            => $search_result['mcp_log'] ?? array(),
			'final_products'     => array_slice( $products, 0, 10 ),
			'ai_response'        => $ai_response,
			'extract_raw'        => $extract_result,
		), 200 );
	}
}
