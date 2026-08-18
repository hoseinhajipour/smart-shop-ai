<?php
namespace SmartShopAI\AI\Providers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * OpenAI API provider.
 */
class OpenAIProvider extends AbstractAIProvider {

	public function chat( array $messages, array $options = array() ): array {
		$body = array(
			'model'       => $options['model'] ?? $this->model,
			'messages'    => $messages,
			'temperature' => $options['temperature'] ?? $this->temperature,
			'max_tokens'  => $options['max_tokens'] ?? $this->max_tokens,
		);

		if ( ! empty( $options['response_format'] ) ) {
			$body['response_format'] = $options['response_format'];
		}

		return $this->request( $body );
	}

	protected function extract_content( array $body ): string {
		return $body['choices'][0]['message']['content'] ?? '';
	}
}
