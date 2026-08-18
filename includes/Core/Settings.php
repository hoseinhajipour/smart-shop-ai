<?php
namespace SmartShopAI\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Centralized settings access.
 */
class Settings {

	private const OPTION_PREFIX = 'ssai_';

	/** @var array */
	private static $cache = array();

	public static function get( string $key, $default = null ): mixed {
		if ( isset( self::$cache[ $key ] ) ) {
			return self::$cache[ $key ];
		}

		$value = get_option( self::OPTION_PREFIX . $key, $default );
		self::$cache[ $key ] = $value;
		return $value;
	}

	public static function set( string $key, $value ): bool {
		self::$cache[ $key ] = $value;
		return update_option( self::OPTION_PREFIX . $key, $value );
	}

	public static function get_ai_settings(): array {
		return array(
			'provider'    => self::get( 'ai_provider', 'openai' ),
			'endpoint'    => self::get( 'ai_endpoint', '' ),
			'api_key'     => self::get( 'ai_api_key', '' ),
			'model'       => self::get( 'ai_model', 'gpt-4o-mini' ),
			'temperature' => (float) self::get( 'ai_temperature', 0.7 ),
			'max_tokens'  => (int) self::get( 'ai_max_tokens', 2048 ),
			'timeout'     => (int) self::get( 'ai_timeout', 30 ),
		);
	}

	public static function get_mcp_settings(): array {
		return array(
			'provider' => self::get( 'mcp_provider', 'woocommerce_direct' ),
			'endpoint' => self::get( 'mcp_endpoint', '' ),
			'api_key'  => self::get( 'mcp_api_key', '' ),
		);
	}

	public static function get_system_prompt(): string {
		return (string) self::get( 'system_prompt', '' );
	}

	public static function get_attribute_mapping(): array {
		$mapping = self::get( 'attribute_mapping', array() );
		return is_array( $mapping ) ? $mapping : array();
	}

	public static function get_capabilities(): array {
		$caps = self::get( 'capabilities', array() );
		return is_array( $caps ) ? $caps : array();
	}

	public static function is_capability_enabled( string $cap ): bool {
		$caps = self::get_capabilities();
		return ! empty( $caps[ $cap ] );
	}

	public static function get_chatbot_settings(): array {
		return array(
			'enabled'       => (bool) self::get( 'chatbot_enabled', true ),
			'welcome'       => self::get( 'chatbot_welcome', self::get_default_welcome_message() ),
			'quick_actions' => self::get_quick_actions(),
			'appearance'    => self::get_chatbot_appearance(),
			'float_button'  => self::get_float_button_settings(),
		);
	}

	public static function get_default_welcome_message(): string {
		return 'Hi 👋 What product are you looking for?';
	}

	/**
	 * @return array<int, array{icon:string,label:string,query:string}>
	 */
	public static function get_default_quick_actions(): array {
		return array(
			array( 'icon' => '🚗', 'label' => 'Find wheels', 'query' => 'I need wheels for my car' ),
			array( 'icon' => '🔋', 'label' => 'Find a battery', 'query' => 'I need a battery for my car' ),
			array( 'icon' => '🛞', 'label' => 'Find tires', 'query' => 'I need tires for my car' ),
			array( 'icon' => '🔎', 'label' => 'Search products', 'query' => 'Search products' ),
		);
	}

	/**
	 * @return array<int, array{icon:string,label:string,query:string}>
	 */
	public static function get_quick_actions(): array {
		$actions = self::get( 'quick_actions', array() );
		return is_array( $actions ) && ! empty( $actions ) ? $actions : self::get_default_quick_actions();
	}

	/**
	 * @param mixed $actions
	 * @return array<int, array{icon:string,label:string,query:string}>
	 */
	public static function sanitize_quick_actions( $actions ): array {
		if ( ! is_array( $actions ) ) {
			return array();
		}

		$sanitized = array();
		foreach ( $actions as $action ) {
			if ( ! is_array( $action ) ) {
				continue;
			}

			$label = sanitize_text_field( $action['label'] ?? '' );
			$query = sanitize_text_field( $action['query'] ?? '' );
			$icon  = sanitize_text_field( $action['icon'] ?? '' );

			if ( '' === $label && '' === $query ) {
				continue;
			}

			$sanitized[] = array(
				'icon'  => mb_substr( $icon, 0, 4 ),
				'label' => $label,
				'query' => $query,
			);
		}

		return $sanitized;
	}

	public static function get_chatbot_appearance(): array {
		return array(
			'title'              => self::get( 'chatbot_title', 'Shopping Assistant' ),
			'avatar_emoji'       => self::get( 'chatbot_avatar_emoji', '🤖' ),
			'primary_color'      => self::get( 'chatbot_primary_color', '#4f46e5' ),
			'secondary_color'    => self::get( 'chatbot_secondary_color', '#7c3aed' ),
			'user_bubble_color'  => self::get( 'chatbot_user_bubble_color', '#4f46e5' ),
			'bot_bubble_color'   => self::get( 'chatbot_bot_bubble_color', '#ffffff' ),
			'background_color'   => self::get( 'chatbot_background_color', '#f8f9fb' ),
			'border_radius'      => (int) self::get( 'chatbot_border_radius', 16 ),
			'font_size'          => (int) self::get( 'chatbot_font_size', 14 ),
		);
	}

	public static function get_float_button_settings(): array {
		return array(
			'position'    => self::get( 'float_button_position', 'right' ),
			'icon'        => self::get( 'float_button_icon', 'chat' ),
			'animation'   => self::get( 'float_button_animation', 'pulse' ),
			'offset_x'    => (int) self::get( 'float_button_offset_x', 24 ),
			'offset_y'    => (int) self::get( 'float_button_offset_y', 24 ),
			'size'        => (int) self::get( 'float_button_size', 56 ),
		);
	}

	/**
	 * Preset AI provider configurations.
	 */
	public static function get_ai_provider_presets(): array {
		return array(
			'openai' => array(
				'label'    => 'OpenAI',
				'endpoint' => 'https://api.openai.com/v1/chat/completions',
				'model'    => 'gpt-4o-mini',
			),
			'anthropic' => array(
				'label'    => 'Anthropic (Claude)',
				'endpoint' => 'https://api.anthropic.com/v1/messages',
				'model'    => 'claude-3-5-sonnet-20241022',
			),
			'gemini' => array(
				'label'    => 'Google Gemini',
				'endpoint' => 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent',
				'model'    => 'gemini-2.0-flash',
			),
			'openrouter' => array(
				'label'    => 'OpenRouter',
				'endpoint' => 'https://openrouter.ai/api/v1/chat/completions',
				'model'    => 'openai/gpt-4o-mini',
			),
			'groq' => array(
				'label'    => 'Groq',
				'endpoint' => 'https://api.groq.com/openai/v1/chat/completions',
				'model'    => 'llama-3.3-70b-versatile',
			),
			'together' => array(
				'label'    => 'Together AI',
				'endpoint' => 'https://api.together.xyz/v1/chat/completions',
				'model'    => 'meta-llama/Llama-3.3-70B-Instruct-Turbo',
			),
			'replicate' => array(
				'label'    => 'Replicate',
				'endpoint' => 'https://api.replicate.com/v1/predictions',
				'model'    => 'openai/gpt-5.1',
			),
			'custom' => array(
				'label'    => 'Custom Endpoint',
				'endpoint' => '',
				'model'    => '',
			),
		);
	}

	/**
	 * Popular Replicate official language models.
	 *
	 * @return array<string, string>
	 */
	public static function get_replicate_model_presets(): array {
		return array(
			'openai/gpt-5.1'                   => 'OpenAI GPT-5.1',
			'openai/gpt-4o'                     => 'OpenAI GPT-4o',
			'openai/gpt-4o-mini'                => 'OpenAI GPT-4o Mini',
			'meta/meta-llama-3-70b-instruct'    => 'Meta Llama 3 70B Instruct',
			'meta/meta-llama-3-8b-instruct'     => 'Meta Llama 3 8B Instruct',
			'anthropic/claude-3.5-sonnet'       => 'Anthropic Claude 3.5 Sonnet',
			'anthropic/claude-3.7-sonnet'       => 'Anthropic Claude 3.7 Sonnet',
		);
	}

	/**
	 * Popular custom endpoint URLs for selection.
	 */
	public static function get_custom_endpoint_presets(): array {
		return array(
			'https://api.openai.com/v1/chat/completions'           => 'OpenAI API',
			'https://openrouter.ai/api/v1/chat/completions'        => 'OpenRouter',
			'https://api.groq.com/openai/v1/chat/completions'    => 'Groq',
			'https://api.together.xyz/v1/chat/completions'         => 'Together AI',
			'https://api.deepseek.com/v1/chat/completions'       => 'DeepSeek',
			'https://api.mistral.ai/v1/chat/completions'           => 'Mistral AI',
			'https://api.perplexity.ai/chat/completions'           => 'Perplexity',
			'https://dashscope.aliyuncs.com/compatible-mode/v1/chat/completions' => 'Alibaba DashScope',
		);
	}
}
