<?php
namespace SmartShopAI\AI\Providers;

use SmartShopAI\AI\AIProviderInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Base class for HTTP-based AI providers.
 */
abstract class AbstractAIProvider implements AIProviderInterface {

	protected string $endpoint;
	protected string $api_key;
	protected string $model;
	protected float $temperature;
	protected int $max_tokens;
	protected int $timeout;

	public function __construct( array $settings ) {
		$this->endpoint    = $settings['endpoint'] ?? '';
		$this->api_key     = $settings['api_key'] ?? '';
		$this->model       = $settings['model'] ?? '';
		$this->temperature = (float) ( $settings['temperature'] ?? 0.7 );
		$this->max_tokens  = (int) ( $settings['max_tokens'] ?? 2048 );
		$this->timeout     = (int) ( $settings['timeout'] ?? 30 );
	}

	public function test_connection(): array {
		$result = $this->chat(
			array(
				array( 'role' => 'user', 'content' => 'ping' ),
			),
			array( 'max_tokens' => 10 )
		);

		if ( $result['success'] ) {
			return array( 'success' => true, 'message' => 'Connection successful.' );
		}

		return array( 'success' => false, 'message' => $result['error'] );
	}

	/**
	 * Make HTTP request to AI endpoint.
	 */
	protected function request( array $body, array $headers = array() ): array {
		if ( empty( $this->endpoint ) || empty( $this->api_key ) ) {
			return array(
				'success' => false,
				'content' => '',
				'error'   => 'AI endpoint or API key not configured.',
				'raw'     => array(),
			);
		}

		$default_headers = array(
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . $this->api_key,
		);

		$response = wp_remote_post(
			$this->endpoint,
			array(
				'timeout' => $this->timeout,
				'headers' => array_merge( $default_headers, $headers ),
				'body'    => wp_json_encode( $body ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'content' => '',
				'error'   => $response->get_error_message(),
				'raw'     => array(),
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code < 200 || $code >= 300 ) {
			$error = $body['error']['message'] ?? $body['message'] ?? "HTTP {$code}";
			return array(
				'success' => false,
				'content' => '',
				'error'   => $error,
				'raw'     => $body ?: array(),
			);
		}

		$content = $this->extract_content( $body );

		return array(
			'success' => true,
			'content' => $content,
			'error'   => '',
			'raw'     => $body,
		);
	}

	/**
	 * Extract text content from provider response.
	 */
	abstract protected function extract_content( array $body ): string;
}
