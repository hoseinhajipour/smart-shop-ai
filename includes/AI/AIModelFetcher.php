<?php
namespace SmartShopAI\AI;

use SmartShopAI\Core\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fetches available models from the configured AI provider.
 */
class AIModelFetcher {

	/**
	 * @param array{provider?:string,endpoint?:string,api_key?:string} $config
	 * @return array{success:bool,models:array<int,array{id:string,label:string}>,error:string}
	 */
	public function fetch( array $config ): array {
		$provider = sanitize_text_field( $config['provider'] ?? '' );
		$endpoint = esc_url_raw( $config['endpoint'] ?? '' );
		$api_key  = sanitize_text_field( $config['api_key'] ?? '' );

		if ( empty( $api_key ) ) {
			$api_key = Settings::get_ai_settings()['api_key'] ?? '';
		}

		if ( empty( $api_key ) ) {
			return array(
				'success' => false,
				'models'  => array(),
				'error'   => 'API key is required to fetch models.',
			);
		}

		if ( 'gemini' === $provider ) {
			return $this->fetch_gemini( $api_key );
		}

		if ( 'anthropic' === $provider ) {
			return $this->fetch_anthropic( $api_key );
		}

		if ( 'replicate' === $provider ) {
			return $this->fetch_replicate( $api_key );
		}

		return $this->fetch_openai_compatible( $provider, $endpoint, $api_key );
	}

	/**
	 * @return array{success:bool,models:array<int,array{id:string,label:string}>,error:string}
	 */
	private function fetch_gemini( string $api_key ): array {
		$url = add_query_arg(
			array(
				'key'    => $api_key,
				'pageSize' => 200,
			),
			'https://generativelanguage.googleapis.com/v1beta/models'
		);

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 30,
				'headers' => array( 'Content-Type' => 'application/json' ),
			)
		);

		$result = $this->parse_http_response( $response );
		if ( ! $result['success'] ) {
			return $result;
		}

		$models = array();
		foreach ( $result['body']['models'] ?? array() as $model ) {
			$name = (string) ( $model['name'] ?? '' );
			if ( '' === $name ) {
				continue;
			}

			$methods = $model['supportedGenerationMethods'] ?? array();
			if ( ! empty( $methods ) && ! in_array( 'generateContent', $methods, true ) ) {
				continue;
			}

			$id = preg_replace( '#^models/#', '', $name );
			if ( '' === $id ) {
				continue;
			}

			$models[] = array(
				'id'    => $id,
				'label' => (string) ( $model['displayName'] ?? $id ),
			);
		}

		return $this->finalize_models( $models, 'No Gemini models found.' );
	}

	/**
	 * @return array{success:bool,models:array<int,array{id:string,label:string}>,error:string}
	 */
	private function fetch_anthropic( string $api_key ): array {
		$response = wp_remote_get(
			'https://api.anthropic.com/v1/models',
			array(
				'timeout' => 30,
				'headers' => array(
					'x-api-key'         => $api_key,
					'anthropic-version' => '2023-06-01',
					'Content-Type'      => 'application/json',
				),
			)
		);

		$result = $this->parse_http_response( $response );
		if ( ! $result['success'] ) {
			return $result;
		}

		$models = array();
		foreach ( $result['body']['data'] ?? array() as $model ) {
			$id = (string) ( $model['id'] ?? '' );
			if ( '' === $id || false === strpos( $id, 'claude' ) ) {
				continue;
			}

			$models[] = array(
				'id'    => $id,
				'label' => (string) ( $model['display_name'] ?? $id ),
			);
		}

		return $this->finalize_models( $models, 'No Claude models found.' );
	}

	/**
	 * @return array{success:bool,models:array<int,array{id:string,label:string}>,error:string}
	 */
	private function fetch_replicate( string $api_key ): array {
		$models = array();
		foreach ( Settings::get_replicate_model_presets() as $id => $label ) {
			$models[] = array(
				'id'    => $id,
				'label' => $label,
			);
		}

		$response = wp_remote_request(
			'https://api.replicate.com/v1/models',
			array(
				'method'  => 'QUERY',
				'timeout' => 30,
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'text/plain',
				),
				'body'    => 'gpt llama claude openai meta anthropic',
			)
		);

		if ( ! is_wp_error( $response ) ) {
			$code = wp_remote_retrieve_response_code( $response );
			$body = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( $code >= 200 && $code < 300 && is_array( $body ) ) {
				foreach ( $body['results'] ?? $body['models'] ?? array() as $model ) {
					$owner = (string) ( $model['owner'] ?? '' );
					$name  = (string) ( $model['name'] ?? '' );
					if ( '' === $owner || '' === $name ) {
						continue;
					}

					$id = $owner . '/' . $name;
					$models[] = array(
						'id'    => $id,
						'label' => (string) ( $model['description'] ?? $name ),
					);
				}
			}
		}

		return $this->finalize_models( $models, 'No Replicate models found.' );
	}

	/**
	 * @return array{success:bool,models:array<int,array{id:string,label:string}>,error:string}
	 */
	private function fetch_openai_compatible( string $provider, string $endpoint, string $api_key ): array {
		$models_url = $this->build_models_url( $provider, $endpoint );
		if ( empty( $models_url ) ) {
			return array(
				'success' => false,
				'models'  => array(),
				'error'   => 'Could not determine models endpoint URL.',
			);
		}

		$headers = array(
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . $api_key,
		);

		if ( 'openrouter' === $provider ) {
			$headers['HTTP-Referer'] = home_url();
			$headers['X-Title']      = get_bloginfo( 'name' );
		}

		$response = wp_remote_get(
			$models_url,
			array(
				'timeout' => 30,
				'headers' => $headers,
			)
		);

		$result = $this->parse_http_response( $response );
		if ( ! $result['success'] ) {
			return $result;
		}

		$items = $result['body']['data'] ?? $result['body']['models'] ?? array();
		$models = array();

		foreach ( $items as $model ) {
			$id = (string) ( $model['id'] ?? '' );
			if ( '' === $id || ! $this->is_chat_model( $id, $provider ) ) {
				continue;
			}

			$label = $id;
			if ( ! empty( $model['name'] ) ) {
				$label = (string) $model['name'];
			} elseif ( ! empty( $model['display_name'] ) ) {
				$label = (string) $model['display_name'];
			}

			$models[] = array(
				'id'    => $id,
				'label' => $label,
			);
		}

		return $this->finalize_models( $models, 'No chat models found for this provider.' );
	}

	private function build_models_url( string $provider, string $endpoint ): string {
		$presets = Settings::get_ai_provider_presets();
		if ( empty( $endpoint ) && ! empty( $presets[ $provider ]['endpoint'] ) ) {
			$endpoint = $presets[ $provider ]['endpoint'];
		}

		if ( empty( $endpoint ) ) {
			return '';
		}

		$endpoint = rtrim( $endpoint, '/' );

		if ( preg_match( '#^(https?://[^/]+)(/v\d+(?:beta)?)(?:/.*)?$#i', $endpoint, $matches ) ) {
			return $matches[1] . $matches[2] . '/models';
		}

		if ( preg_match( '#^(https?://[^/]+)/.+$#i', $endpoint, $matches ) ) {
			return $matches[1] . '/v1/models';
		}

		return $endpoint . '/models';
	}

	private function is_chat_model( string $id, string $provider ): bool {
		if ( 'openrouter' === $provider ) {
			return true;
		}

		$lower = strtolower( $id );
		$blocked = array(
			'embedding',
			'whisper',
			'tts',
			'dall-e',
			'davinci',
			'moderation',
			'realtime',
			'audio',
			'transcribe',
			'search',
			'codex',
		);

		foreach ( $blocked as $needle ) {
			if ( false !== strpos( $lower, $needle ) ) {
				return false;
			}
		}

		$allowed = array( 'gpt', 'o1', 'o3', 'o4', 'chatgpt', 'llama', 'mistral', 'gemini', 'claude', 'qwen', 'deepseek', 'grok', 'sonar', 'command' );
		foreach ( $allowed as $needle ) {
			if ( false !== strpos( $lower, $needle ) ) {
				return true;
			}
		}

		return 'custom' === $provider || 'together' === $provider || 'groq' === $provider;
	}

	/**
	 * @param array<int,array{id:string,label:string}> $models
	 * @return array{success:bool,models:array<int,array{id:string,label:string}>,error:string}
	 */
	private function finalize_models( array $models, string $empty_message ): array {
		$unique = array();
		foreach ( $models as $model ) {
			$unique[ $model['id'] ] = $model;
		}

		$models = array_values( $unique );
		usort(
			$models,
			static function ( array $a, array $b ): int {
				return strcasecmp( $a['label'], $b['label'] );
			}
		);

		if ( empty( $models ) ) {
			return array(
				'success' => false,
				'models'  => array(),
				'error'   => $empty_message,
			);
		}

		return array(
			'success' => true,
			'models'  => $models,
			'error'   => '',
		);
	}

	/**
	 * @param array|\WP_Error $response
	 * @return array{success:bool,body:array,error:string}
	 */
	private function parse_http_response( $response ): array {
		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'body'    => array(),
				'error'   => $response->get_error_message(),
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code < 200 || $code >= 300 ) {
			$error = $body['error']['message'] ?? $body['message'] ?? "HTTP {$code}";
			return array(
				'success' => false,
				'body'    => is_array( $body ) ? $body : array(),
				'error'   => (string) $error,
			);
		}

		return array(
			'success' => true,
			'body'    => is_array( $body ) ? $body : array(),
			'error'   => '',
		);
	}
}
