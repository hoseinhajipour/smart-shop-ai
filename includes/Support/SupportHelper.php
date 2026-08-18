<?php
namespace SmartShopAI\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Support contact channel helpers.
 */
class SupportHelper {

	public const CHANNEL_TYPES = array(
		'phone'     => array( 'label' => 'Phone', 'icon' => '📞' ),
		'whatsapp'  => array( 'label' => 'WhatsApp', 'icon' => '💬' ),
		'instagram' => array( 'label' => 'Instagram', 'icon' => '📷' ),
		'telegram'  => array( 'label' => 'Telegram', 'icon' => '✈️' ),
		'email'     => array( 'label' => 'Email', 'icon' => '✉️' ),
		'custom'    => array( 'label' => 'Custom Link', 'icon' => '🔗' ),
	);

	/**
	 * Build a clickable URL for a support channel.
	 */
	public static function build_channel_url( string $type, string $value, string $custom_url = '' ): string {
		$value = trim( $value );
		$type  = sanitize_key( $type );

		switch ( $type ) {
			case 'phone':
				$digits = preg_replace( '/[^\d+]/', '', $value );
				return $digits ? 'tel:' . $digits : '';

			case 'whatsapp':
				$digits = preg_replace( '/\D/', '', $value );
				return $digits ? 'https://wa.me/' . $digits : '';

			case 'instagram':
				$handle = ltrim( $value, '@/' );
				$handle = preg_replace( '#^https?://(www\.)?instagram\.com/#i', '', $handle );
				$handle = trim( $handle, '/' );
				return $handle ? 'https://instagram.com/' . rawurlencode( $handle ) : '';

			case 'telegram':
				$handle = ltrim( $value, '@/' );
				$handle = preg_replace( '#^https?://(t\.me|telegram\.me)/#i', '', $handle );
				$handle = trim( $handle, '/' );
				return $handle ? 'https://t.me/' . rawurlencode( $handle ) : '';

			case 'email':
				$email = sanitize_email( $value );
				return $email ? 'mailto:' . $email : '';

			case 'custom':
				$url = $custom_url ?: $value;
				return esc_url_raw( $url );

			default:
				return '';
		}
	}

	/**
	 * Format channels for frontend display.
	 *
	 * @param array $channels Raw channel config.
	 * @return array<int, array{type:string,label:string,value:string,url:string,icon:string}>
	 */
	public static function format_channels_for_display( array $channels ): array {
		$formatted = array();

		foreach ( $channels as $channel ) {
			if ( ! is_array( $channel ) ) {
				continue;
			}

			$type  = sanitize_key( $channel['type'] ?? '' );
			$value = trim( (string) ( $channel['value'] ?? '' ) );
			$label = trim( (string) ( $channel['label'] ?? '' ) );

			if ( '' === $type || '' === $value ) {
				continue;
			}

			if ( ! isset( self::CHANNEL_TYPES[ $type ] ) ) {
				continue;
			}

			$url = self::build_channel_url( $type, $value, (string) ( $channel['url'] ?? '' ) );
			if ( '' === $url ) {
				continue;
			}

			$meta = self::CHANNEL_TYPES[ $type ];

			$formatted[] = array(
				'type'  => $type,
				'label' => $label ?: $meta['label'],
				'value' => $value,
				'url'   => $url,
				'icon'  => $meta['icon'],
			);
		}

		return $formatted;
	}

	public static function get_display_value( string $type, string $value ): string {
		switch ( $type ) {
			case 'phone':
			case 'whatsapp':
				return $value;
			case 'instagram':
			case 'telegram':
				return '@' . ltrim( $value, '@' );
			case 'email':
				return $value;
			default:
				return $value;
		}
	}
}
