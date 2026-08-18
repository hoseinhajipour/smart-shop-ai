<?php
namespace SmartShopAI\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Logs conversation data for debugging and analytics.
 */
class ConversationLogger {

	public static function log( array $data ): int {
		global $wpdb;

		$table = $wpdb->prefix . 'ssai_conversation_logs';

		$wpdb->insert(
			$table,
			array(
				'session_id'      => sanitize_text_field( $data['session_id'] ?? '' ),
				'user_message'    => sanitize_textarea_field( $data['user_message'] ?? '' ),
				'ai_response'     => sanitize_textarea_field( $data['ai_response'] ?? '' ),
				'intent'          => sanitize_text_field( $data['intent'] ?? '' ),
				'extracted_data'  => wp_json_encode( $data['extracted_data'] ?? array() ),
				'search_query'    => sanitize_textarea_field( $data['search_query'] ?? '' ),
				'products_found'  => wp_json_encode( $data['products_found'] ?? array() ),
				'mcp_requests'    => wp_json_encode( self::sanitize_mcp_log( $data['mcp_requests'] ?? array() ) ),
				'response_time_ms' => (int) ( $data['response_time_ms'] ?? 0 ),
				'error_message'   => sanitize_textarea_field( $data['error_message'] ?? '' ),
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s' )
		);

		return (int) $wpdb->insert_id;
	}

	/**
	 * Remove sensitive data from MCP logs.
	 */
	private static function sanitize_mcp_log( array $requests ): array {
		foreach ( $requests as &$req ) {
			unset( $req['api_key'], $req['token'], $req['authorization'] );
		}
		return $requests;
	}

	public static function get_logs( int $limit = 50, int $offset = 0 ): array {
		global $wpdb;

		$table = $wpdb->prefix . 'ssai_conversation_logs';

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d OFFSET %d",
				$limit,
				$offset
			),
			ARRAY_A
		);

		return $results ?: array();
	}

	public static function get_log_count(): int {
		global $wpdb;
		$table = $wpdb->prefix . 'ssai_conversation_logs';
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
	}
}
