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
			'welcome'       => self::get( 'chatbot_welcome', '' ),
			'quick_actions' => self::get( 'quick_actions', array() ),
		);
	}
}
