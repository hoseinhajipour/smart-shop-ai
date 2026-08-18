<?php
namespace SmartShopAI\AI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Parses and normalizes intent data from AI extraction.
 */
class IntentParser {

	/**
	 * Known vehicle aliases for normalization.
	 */
	private const VEHICLE_ALIASES = array(
		'206'           => 'Peugeot 206',
		'پژو 206'       => 'Peugeot 206',
		'peugeot 206'   => 'Peugeot 206',
		'405'           => 'Peugeot 405',
		'پژو 405'       => 'Peugeot 405',
		'peugeot 405'   => 'Peugeot 405',
		'سمند'          => 'Samand',
		'samand'        => 'Samand',
		'سمند lx'       => 'Samand LX',
		'samand lx'     => 'Samand LX',
		'پراید'         => 'Pride',
		'pride'         => 'Pride',
		'تیبا'          => 'Tiba',
		'tiba'          => 'Tiba',
		'دنا'           => 'Dena',
		'dena'          => 'Dena',
		'bmw'           => 'BMW',
		'مرسدس'         => 'Mercedes',
		'mercedes'      => 'Mercedes',
		'تویوتا'        => 'Toyota',
		'toyota'        => 'Toyota',
		'هیوندای'       => 'Hyundai',
		'hyundai'       => 'Hyundai',
		'کیا'           => 'Kia',
		'kia'           => 'Kia',
	);

	/**
	 * Product type keywords.
	 */
	private const PRODUCT_TYPES = array(
		'wheel'   => array( 'رینگ', 'ring', 'wheel', 'رینگ اسپرت' ),
		'tire'    => array( 'لاستیک', 'tire', 'tyre' ),
		'battery' => array( 'باتری', 'battery' ),
		'parts'   => array( 'قطعه', 'قطعات', 'part', 'parts', 'لوازم' ),
	);

	public function parse( array $raw_intent ): array {
		$parsed = array(
			'intent'           => $raw_intent['intent'] ?? 'smart_search',
			'product_type'     => $this->normalize_product_type( $raw_intent['product_type'] ?? null ),
			'vehicle'          => $this->normalize_vehicle( $raw_intent['vehicle'] ?? null ),
			'vehicle_brand'    => $raw_intent['vehicle_brand'] ?? null,
			'vehicle_model'    => $raw_intent['vehicle_model'] ?? null,
			'attributes'       => $this->normalize_attributes( $raw_intent['attributes'] ?? array() ),
			'search_text'      => $raw_intent['search_text'] ?? null,
			'needs_followup'   => (bool) ( $raw_intent['needs_followup'] ?? false ),
			'followup_question'=> $raw_intent['followup_question'] ?? null,
			'confidence'       => (float) ( $raw_intent['confidence'] ?? 0.5 ),
		);

		// Fallback keyword detection if AI missed it.
		if ( empty( $parsed['product_type'] ) && ! empty( $raw_intent['original_message'] ) ) {
			$parsed['product_type'] = $this->detect_product_type_from_text( $raw_intent['original_message'] );
		}

		if ( empty( $parsed['vehicle'] ) && ! empty( $raw_intent['original_message'] ) ) {
			$parsed['vehicle'] = $this->detect_vehicle_from_text( $raw_intent['original_message'] );
		}

		return $parsed;
	}

	public function normalize_vehicle( ?string $vehicle ): ?string {
		if ( empty( $vehicle ) ) {
			return null;
		}

		$key = mb_strtolower( trim( $vehicle ) );
		if ( isset( self::VEHICLE_ALIASES[ $key ] ) ) {
			return self::VEHICLE_ALIASES[ $key ];
		}

		return $vehicle;
	}

	public function normalize_product_type( ?string $type ): ?string {
		if ( empty( $type ) ) {
			return null;
		}

		$key = mb_strtolower( trim( $type ) );
		$valid = array( 'wheel', 'tire', 'battery', 'parts', 'other' );

		if ( in_array( $key, $valid, true ) ) {
			return $key;
		}

		return null;
	}

	public function detect_product_type_from_text( string $text ): ?string {
		$lower = mb_strtolower( $text );
		foreach ( self::PRODUCT_TYPES as $type => $keywords ) {
			foreach ( $keywords as $keyword ) {
				if ( mb_strpos( $lower, mb_strtolower( $keyword ) ) !== false ) {
					return $type;
				}
			}
		}
		return null;
	}

	public function detect_vehicle_from_text( string $text ): ?string {
		$lower = mb_strtolower( $text );
		foreach ( self::VEHICLE_ALIASES as $alias => $normalized ) {
			if ( mb_strpos( $lower, mb_strtolower( $alias ) ) !== false ) {
				return $normalized;
			}
		}
		return null;
	}

	private function normalize_attributes( array $attrs ): array {
		$normalized = array();

		$map = array(
			'size'      => 'size',
			'color'     => 'color',
			'brand'     => 'brand',
			'style'     => 'style',
			'price_max' => 'price_max',
			'price_min' => 'price_min',
			'sort_by'   => 'sort_by',
		);

		foreach ( $map as $key => $field ) {
			if ( ! empty( $attrs[ $field ] ) && $attrs[ $field ] !== 'null' ) {
				$normalized[ $field ] = $attrs[ $field ];
			}
		}

		// Normalize color aliases.
		if ( ! empty( $normalized['color'] ) ) {
			$color_map = array(
				'مشکی'  => 'black',
				'سفید'  => 'white',
				'نقره‌ای' => 'silver',
				'خاکستری' => 'gray',
				'black' => 'black',
				'white' => 'white',
			);
			$c = mb_strtolower( $normalized['color'] );
			if ( isset( $color_map[ $c ] ) ) {
				$normalized['color'] = $color_map[ $c ];
			}
		}

		return $normalized;
	}

	/**
	 * Merge intent from conversation context.
	 */
	public function merge_context( array $current, array $previous ): array {
		$merged = $current;

		foreach ( array( 'product_type', 'vehicle', 'vehicle_brand', 'vehicle_model', 'search_text' ) as $field ) {
			if ( empty( $merged[ $field ] ) && ! empty( $previous[ $field ] ) ) {
				$merged[ $field ] = $previous[ $field ];
			}
		}

		if ( ! empty( $previous['attributes'] ) ) {
			foreach ( $previous['attributes'] as $key => $value ) {
				if ( empty( $merged['attributes'][ $key ] ) ) {
					$merged['attributes'][ $key ] = $value;
				}
			}
		}

		// Re-evaluate followup need.
		if ( $merged['product_type'] === 'wheel' && empty( $merged['attributes']['size'] ) && empty( $merged['vehicle'] ) ) {
			$merged['needs_followup'] = true;
			$merged['followup_question'] = $merged['followup_question'] ?: 'Which vehicle and wheel size are you looking for?';
		} elseif ( $merged['product_type'] === 'wheel' && ! empty( $merged['vehicle'] ) && empty( $merged['attributes']['size'] ) ) {
			$merged['needs_followup'] = true;
			$merged['followup_question'] = $merged['followup_question'] ?: 'What wheel size do you need? (e.g. 15, 16, 17)';
		}

		return $merged;
	}
}
