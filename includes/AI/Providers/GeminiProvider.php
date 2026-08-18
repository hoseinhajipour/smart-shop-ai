<?php
namespace SmartShopAI\AI\Providers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Google Gemini API provider.
 */
class GeminiProvider extends AbstractAIProvider {

	public function chat( array $messages, array $options = array() ): array {
		$contents = array();
		foreach ( $messages as $msg ) {
			if ( $msg['role'] === 'system' ) {
				continue;
			}
			$role = $msg['role'] === 'assistant' ? 'model' : 'user';
			$contents[] = array(
				'role'  => $role,
				'parts' => array( array( 'text' => $msg['content'] ) ),
			);
		}

		$model = $options['model'] ?? $this->model;
		$url   = $this->endpoint ?: "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

		$body = array(
			'contents'         => $contents,
			'generationConfig' => array(
				'temperature'     => $options['temperature'] ?? $this->temperature,
				'maxOutputTokens' => $options['max_tokens'] ?? $this->max_tokens,
			),
		);

		$response = wp_remote_post(
			add_query_arg( 'key', $this->api_key, $url ),
			array(
				'timeout' => $this->timeout,
				'headers' => array( 'Content-Type' => 'application/json' ),
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
			$error = $body['error']['message'] ?? "HTTP {$code}";
			return array(
				'success' => false,
				'content' => '',
				'error'   => $error,
				'raw'     => $body ?: array(),
			);
		}

		return array(
			'success' => true,
			'content' => $this->extract_content( $body ),
			'error'   => '',
			'raw'     => $body,
		);
	}

	protected function extract_content( array $body ): string {
		return $body['candidates'][0]['content']['parts'][0]['text'] ?? '';
	}
}
