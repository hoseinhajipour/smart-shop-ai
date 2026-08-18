<?php
namespace SmartShopAI\REST;

use SmartShopAI\AI\AIService;
use SmartShopAI\AI\IntentParser;
use SmartShopAI\Search\SearchRouter;
use SmartShopAI\Core\ConversationLogger;
use SmartShopAI\Core\Settings;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Chat REST API endpoint.
 */
class ChatController {

	private const NAMESPACE = 'smart-shop-ai/v1';

	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/chat',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_chat' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'message'    => array( 'required' => true, 'type' => 'string' ),
					'session_id' => array( 'type' => 'string', 'default' => '' ),
					'history'    => array( 'type' => 'array', 'default' => array() ),
					'context'    => array( 'type' => 'object', 'default' => array() ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/chat/config',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_chat_config' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	public function handle_chat( WP_REST_Request $request ): WP_REST_Response {
		$start_time = microtime( true );

		$message    = sanitize_textarea_field( $request->get_param( 'message' ) );
		$session_id = sanitize_text_field( $request->get_param( 'session_id' ) );
		$history    = $request->get_param( 'history' ) ?: array();
		$context    = $request->get_param( 'context' ) ?: array();

		if ( empty( $message ) ) {
			return new WP_REST_Response( array( 'error' => 'Message is required.' ), 400 );
		}

		$ai_service    = new AIService();
		$intent_parser = new IntentParser();
		$search_router = new SearchRouter();

		// Step 1: Extract intent.
		$extract_result = $ai_service->extract_intent( $message, $history );

		if ( ! $extract_result['success'] ) {
			// Fallback to keyword-based parsing.
			$raw_intent = array(
				'intent'           => 'smart_search',
				'original_message' => $message,
			);
		} else {
			$raw_intent = $extract_result['data'];
			$raw_intent['original_message'] = $message;
		}

		$intent = $intent_parser->parse( $raw_intent );

		// Merge with conversation context.
		if ( ! empty( $context['intent'] ) ) {
			$intent = $intent_parser->merge_context( $intent, $context['intent'] );
		}

		$response_data = array(
			'message'      => '',
			'intent'       => $intent,
			'products'     => array(),
			'needs_followup' => false,
			'followup_question' => null,
			'session_id'   => $session_id ?: wp_generate_uuid4(),
		);

		// Step 2: Check if followup needed.
		if ( $intent['needs_followup'] && Settings::is_capability_enabled( 'ask_followup_questions' ) ) {
			$response_data['needs_followup']     = true;
			$response_data['followup_question']  = $intent['followup_question'];
			$response_data['message']            = $intent['followup_question'];
			$response_data['context']            = array( 'intent' => $intent );

			$this->log_conversation( $session_id, $message, $response_data['message'], $intent, '', array(), array(), $start_time );
			return new WP_REST_Response( $response_data, 200 );
		}

		// Step 3: Search products.
		$search_result = $search_router->search( $intent );
		$products      = $search_result['products'] ?? array();
		$search_query  = $search_result['search_query'] ?? '';
		$mcp_log       = $search_result['mcp_log'] ?? array();

		$response_data['products'] = $products;
		$response_data['search_query'] = $search_query;

		// Step 4: Generate AI response.
		if ( ! empty( $products ) ) {
			$ai_response = $ai_service->generate_response( $message, $intent, $products, $history );
			$response_data['message'] = $ai_response['success']
				? $ai_response['content']
				: $this->fallback_response( $products, $intent );
		} else {
			$response_data['message'] = 'Sorry, no products matched your request. Please share more details, such as vehicle model and size.';
		}

		$response_data['context'] = array( 'intent' => $intent );

		$this->log_conversation(
			$session_id,
			$message,
			$response_data['message'],
			$intent,
			$search_query,
			$products,
			$mcp_log,
			$start_time
		);

		return new WP_REST_Response( $response_data, 200 );
	}

	public function get_chat_config( WP_REST_Request $request ): WP_REST_Response {
		$settings = Settings::get_chatbot_settings();

		return new WP_REST_Response( array(
			'enabled'       => $settings['enabled'],
			'welcome'       => $settings['welcome'],
			'quick_actions' => $settings['quick_actions'],
			'rest_url'      => rest_url( self::NAMESPACE ),
			'nonce'         => wp_create_nonce( 'wp_rest' ),
		), 200 );
	}

	private function fallback_response( array $products, array $intent ): string {
		$count = count( $products );
		$type  = $intent['product_type'] ?? 'product';

		$type_labels = array(
			'wheel'   => 'wheel',
			'tire'    => 'tire',
			'battery' => 'battery',
			'parts'   => 'part',
		);

		$label = $type_labels[ $type ] ?? 'product';
		return sprintf( 'I found %d matching %s(s). Here are the results:', $count, $label );
	}

	private function log_conversation(
		string $session_id,
		string $user_message,
		string $ai_response,
		array $intent,
		string $search_query,
		array $products,
		array $mcp_log,
		float $start_time
	): void {
		$elapsed = round( ( microtime( true ) - $start_time ) * 1000 );

		ConversationLogger::log( array(
			'session_id'      => $session_id,
			'user_message'    => $user_message,
			'ai_response'     => $ai_response,
			'intent'          => $intent['intent'] ?? '',
			'extracted_data'  => $intent,
			'search_query'    => $search_query,
			'products_found'  => array_map( function ( $p ) {
				return array( 'id' => $p['id'], 'name' => $p['name'], 'score' => $p['match_score'] ?? 0 );
			}, $products ),
			'mcp_requests'    => $mcp_log,
			'response_time_ms'=> $elapsed,
		) );
	}
}
