<?php
namespace SmartShopAI\Fitment;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Normalizes and resolves vehicle fitment specs (PCD, offset, rim size).
 */
class FitmentHelper {

	/**
	 * Convert fitment data from vehicle selector into intent attributes.
	 *
	 * Expected input shapes:
	 * - { pcd: "6X139.7", bolt_pattern: "6X139.7", offset: "35mm", rim_size: "20x8" }
	 * - { make, model, year, trim, pcd, rim_sizes: [{ size, offset, bolt_pattern }] }
	 */
	public static function resolve_attributes( array $fitment ): array {
		$attrs = array();

		$pcd = $fitment['pcd'] ?? $fitment['bolt_pattern'] ?? $fitment['boltPattern'] ?? null;
		if ( $pcd ) {
			$attrs['pcd'] = self::normalize_pcd( (string) $pcd );
		}

		$et = $fitment['et'] ?? $fitment['offset'] ?? null;
		if ( $et ) {
			$attrs['et'] = self::normalize_et( (string) $et );
		}

		$rim_size = $fitment['rim_size'] ?? $fitment['rimSize'] ?? $fitment['size'] ?? null;
		if ( $rim_size ) {
			$parsed = self::parse_rim_size( (string) $rim_size );
			if ( ! empty( $parsed['diameter'] ) ) {
				$attrs['size']    = $parsed['diameter'];
				$attrs['diameter'] = $parsed['diameter'];
			}
			if ( ! empty( $parsed['width'] ) ) {
				$attrs['width'] = $parsed['width'];
			}
		}

		if ( ! empty( $fitment['width'] ) ) {
			$attrs['width'] = self::normalize_width( (string) $fitment['width'] );
		}

		if ( ! empty( $fitment['diameter'] ) ) {
			$attrs['diameter'] = self::normalize_diameter( (string) $fitment['diameter'] );
			if ( empty( $attrs['size'] ) ) {
				$attrs['size'] = $attrs['diameter'];
			}
		}

		// Multiple rim size options from vehicle fitment table.
		if ( ! empty( $fitment['rim_sizes'] ) && is_array( $fitment['rim_sizes'] ) ) {
			$attrs['rim_options'] = array();
			foreach ( $fitment['rim_sizes'] as $option ) {
				if ( ! is_array( $option ) ) {
					continue;
				}
				$entry = array();
				$size  = $option['rim_size'] ?? $option['size'] ?? $option['rimSize'] ?? null;
				if ( $size ) {
					$parsed = self::parse_rim_size( (string) $size );
					$entry  = array_merge( $entry, array_filter( $parsed ) );
				}
				$bolt = $option['bolt_pattern'] ?? $option['pcd'] ?? $option['boltPattern'] ?? null;
				if ( $bolt ) {
					$entry['pcd'] = self::normalize_pcd( (string) $bolt );
				}
				$offset = $option['offset'] ?? $option['et'] ?? null;
				if ( $offset ) {
					$entry['et'] = self::normalize_et( (string) $offset );
				}
				if ( ! empty( $entry ) ) {
					$attrs['rim_options'][] = $entry;
				}
			}

			// Use first rim option to fill missing primary attributes.
			if ( ! empty( $attrs['rim_options'][0] ) ) {
				$first = $attrs['rim_options'][0];
				foreach ( array( 'pcd', 'et', 'diameter', 'width', 'size' ) as $key ) {
					if ( empty( $attrs[ $key ] ) && ! empty( $first[ $key ] ) ) {
						$attrs[ $key ] = $first[ $key ];
					}
				}
			}
		}

		return array_filter( $attrs );
	}

	/**
	 * Build vehicle label from fitment selector data.
	 */
	public static function build_vehicle_label( array $fitment ): ?string {
		$parts = array_filter( array(
			$fitment['make'] ?? $fitment['vehicle_brand'] ?? null,
			$fitment['model'] ?? $fitment['vehicle_model'] ?? null,
			$fitment['year'] ?? null,
			$fitment['trim'] ?? null,
		) );

		return ! empty( $parts ) ? implode( ' ', $parts ) : null;
	}

	/**
	 * Normalize bolt pattern / PCD to comparable form: 6x139.7
	 */
	public static function normalize_pcd( string $pcd ): string {
		$pcd = mb_strtolower( trim( $pcd ) );
		$pcd = str_replace( array( '×', 'x', ' ' ), 'x', $pcd );
		$pcd = preg_replace( '/[^0-9x.]/', '', $pcd );

		if ( preg_match( '/^(\d+)x([\d.]+)$/', $pcd, $matches ) ) {
			return $matches[1] . 'x' . rtrim( rtrim( $matches[2], '0' ), '.' );
		}

		return $pcd;
	}

	/**
	 * Normalize offset / ET to numeric string.
	 */
	public static function normalize_et( string $et ): string {
		$et = mb_strtolower( trim( $et ) );
		$et = preg_replace( '/^et\s*/', '', $et );
		$et = preg_replace( '/[^0-9.\-]/', '', $et );
		return $et;
	}

	/**
	 * Parse rim size like "20x8" or "20x8.5".
	 */
	public static function parse_rim_size( string $size ): array {
		$size = mb_strtolower( trim( $size ) );
		if ( preg_match( '/^(\d+(?:\.\d+)?)\s*x\s*(\d+(?:\.\d+)?)/', $size, $matches ) ) {
			return array(
				'diameter' => self::normalize_diameter( $matches[1] ),
				'width'    => self::normalize_width( $matches[2] ),
				'size'     => self::normalize_diameter( $matches[1] ),
			);
		}

		if ( preg_match( '/^\d+(?:\.\d+)?$/', $size ) ) {
			return array( 'diameter' => $size, 'size' => $size );
		}

		return array();
	}

	public static function normalize_diameter( string $value ): string {
		return preg_replace( '/[^0-9.]/', '', trim( $value ) );
	}

	public static function normalize_width( string $value ): string {
		return preg_replace( '/[^0-9.]/', '', trim( $value ) );
	}

	/**
	 * Check if two PCD values match (handles 6x139.7 vs 6X139.7 etc).
	 */
	public static function pcd_matches( string $a, string $b ): bool {
		return self::normalize_pcd( $a ) === self::normalize_pcd( $b );
	}

	/**
	 * Check if product text/attribute contains the given PCD.
	 */
	public static function text_contains_pcd( string $haystack, string $pcd ): bool {
		$normalized = self::normalize_pcd( $pcd );
		$lower      = mb_strtolower( $haystack );

		$variants = array(
			$normalized,
			str_replace( 'x', '×', $normalized ),
			strtoupper( $normalized ),
			strtoupper( str_replace( 'x', 'X', $normalized ) ),
		);

		foreach ( array_unique( $variants ) as $variant ) {
			if ( mb_strpos( $lower, mb_strtolower( $variant ) ) !== false ) {
				return true;
			}
		}

		// Match bolt count and diameter separately: "6x139.7" in "6X139.7".
		if ( preg_match( '/^(\d+)x([\d.]+)$/', $normalized, $parts ) ) {
			$pattern = '/\b' . preg_quote( $parts[1], '/' ) . '\s*[x×]\s*' . preg_quote( $parts[2], '/' ) . '\b/i';
			return (bool) preg_match( $pattern, $haystack );
		}

		return false;
	}
}
