<?php
namespace SmartShopAI\AI;

use SmartShopAI\Fitment\FitmentHelper;

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

	/**
	 * Known wheel/rim brand names (not vehicles).
	 */
	private const WHEEL_BRANDS = array(
		'archer', 'kmc', 'rays', 'bbs', 'enkei', 'oz racing', 'oz', 'work',
		'vossen', 'rotiform', 'fuel', 'american racing', 'method', 'black rhino',
		'trailite',
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
			'original_message' => $raw_intent['original_message'] ?? null,
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
			'width'     => 'width',
			'diameter'  => 'diameter',
			'pcd'       => 'pcd',
			'et'        => 'et',
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

		if ( ! empty( $normalized['pcd'] ) ) {
			$normalized['pcd'] = FitmentHelper::normalize_pcd( (string) $normalized['pcd'] );
		}

		if ( ! empty( $normalized['et'] ) ) {
			$normalized['et'] = FitmentHelper::normalize_et( (string) $normalized['et'] );
		}

		if ( ! empty( $normalized['width'] ) ) {
			$normalized['width'] = FitmentHelper::normalize_width( (string) $normalized['width'] );
		}

		if ( ! empty( $normalized['diameter'] ) ) {
			$normalized['diameter'] = FitmentHelper::normalize_diameter( (string) $normalized['diameter'] );
		}

		// Parse combined rim size into diameter + width.
		if ( ! empty( $normalized['size'] ) && mb_strpos( (string) $normalized['size'], 'x' ) !== false ) {
			$parsed = FitmentHelper::parse_rim_size( (string) $normalized['size'] );
			if ( ! empty( $parsed['diameter'] ) ) {
				$normalized['size']     = $parsed['diameter'];
				$normalized['diameter'] = $parsed['diameter'];
			}
			if ( ! empty( $parsed['width'] ) ) {
				$normalized['width'] = $parsed['width'];
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

		return $this->finalize_intent( $merged );
	}

	/**
	 * Apply vehicle fitment data from the site vehicle selector.
	 */
	public function apply_fitment( array $intent, array $fitment ): array {
		if ( empty( $fitment ) ) {
			return $intent;
		}

		$fitment_attrs = FitmentHelper::resolve_attributes( $fitment );

		if ( ! empty( $fitment_attrs ) ) {
			$intent['attributes'] = array_merge( $fitment_attrs, $intent['attributes'] ?? array() );
		}

		if ( empty( $intent['vehicle'] ) ) {
			$vehicle_label = FitmentHelper::build_vehicle_label( $fitment );
			if ( $vehicle_label ) {
				$intent['vehicle'] = $vehicle_label;
			}
		}

		if ( empty( $intent['vehicle_brand'] ) && ! empty( $fitment['make'] ) ) {
			$intent['vehicle_brand'] = $fitment['make'];
		}

		if ( empty( $intent['vehicle_model'] ) && ! empty( $fitment['model'] ) ) {
			$intent['vehicle_model'] = $fitment['model'];
		}

		if ( empty( $intent['product_type'] ) && ! empty( $fitment['product_type'] ) ) {
			$intent['product_type'] = $this->normalize_product_type( $fitment['product_type'] );
		}

		$intent['fitment'] = $fitment;

		return $this->finalize_intent( $intent );
	}

	/**
	 * Post-process intent: fix misidentifications, build search text, adjust followup.
	 */
	public function finalize_intent( array $intent ): array {
		if ( empty( $intent['product_type'] ) ) {
			$detected = $this->detect_wheel_product_type( $intent );
			if ( $detected ) {
				$intent['product_type'] = $detected;
			}
		}

		$intent = $this->correct_wheel_vehicle_confusion( $intent );

		if ( empty( $intent['search_text'] ) ) {
			$built = $this->build_search_text( $intent );
			if ( $built ) {
				$intent['search_text'] = $built;
			}
		}

		$intent = $this->adjust_wheel_followup( $intent );
		$intent = $this->adjust_brand_followup( $intent );
		$intent = $this->normalize_search_text( $intent );
		$intent = $this->infer_brand_from_message( $intent );

		return $intent;
	}

	/**
	 * Extract wheel brand from message when AI omitted it (e.g. "TRAILITE این محصول رو دارین؟").
	 */
	private function infer_brand_from_message( array $intent ): array {
		$attrs = $intent['attributes'] ?? array();
		if ( ! empty( $attrs['brand'] ) ) {
			return $intent;
		}

		$message = $intent['original_message'] ?? '';
		if ( '' === $message ) {
			return $intent;
		}

		if ( preg_match( '/\b([A-Z][A-Z0-9]{2,})\b/u', $message, $matches ) ) {
			$intent['attributes']['brand'] = $matches[1];
			if ( empty( $intent['search_text'] ) ) {
				$intent['search_text'] = $matches[1];
			}
			$intent['needs_followup'] = false;
		}

		return $intent;
	}

	/**
	 * When brand is known, search immediately — don't ask followup questions.
	 */
	private function adjust_brand_followup( array $intent ): array {
		$attrs = $intent['attributes'] ?? array();

		if ( ! empty( $attrs['brand'] ) ) {
			$intent['needs_followup'] = false;
		}

		$message = $intent['original_message'] ?? '';
		if ( $message && $this->is_availability_question( $message ) ) {
			if ( ! empty( $attrs['brand'] ) || ! empty( $intent['search_text'] ) ) {
				$intent['needs_followup'] = false;
			}
		}

		// Infer product type from message when brand is set.
		if ( ! empty( $attrs['brand'] ) && empty( $intent['product_type'] ) && $message ) {
			$detected = $this->detect_product_type_from_text( $message );
			if ( $detected ) {
				$intent['product_type'] = $detected;
			}
		}

		return $intent;
	}

	private function is_availability_question( string $text ): bool {
		$lower    = mb_strtolower( $text );
		$patterns = array(
			'دارین', 'دارید', 'داریم', 'موجود', 'have you', 'do you have',
			'in stock', 'این محصول', 'this product', 'available',
		);

		foreach ( $patterns as $pattern ) {
			if ( mb_strpos( $lower, $pattern ) !== false ) {
				return true;
			}
		}

		return false;
	}

	private function normalize_search_text( array $intent ): array {
		if ( ! empty( $intent['search_text'] ) ) {
			$text = $intent['search_text'];
			// Common Persian typo.
			$text = str_replace( 'رینک', 'رینگ', $text );
			$intent['search_text'] = $text;
		}

		if ( ! empty( $intent['original_message'] ) ) {
			$msg = str_replace( 'رینک', 'رینگ', $intent['original_message'] );
			if ( empty( $intent['product_type'] ) ) {
				$detected = $this->detect_product_type_from_text( $msg );
				if ( $detected ) {
					$intent['product_type'] = $detected;
				}
			}
		}

		return $intent;
	}

	/**
	 * Build a text search query from structured intent fields.
	 */
	public function build_search_text( array $intent ): ?string {
		if ( ! empty( $intent['search_text'] ) ) {
			return $intent['search_text'];
		}

		$parts  = array();
		$attrs  = $intent['attributes'] ?? array();

		if ( ! empty( $attrs['brand'] ) ) {
			$parts[] = $attrs['brand'];
		}
		if ( ! empty( $attrs['style'] ) ) {
			$parts[] = $attrs['style'];
		}
		if ( ! empty( $attrs['size'] ) ) {
			$parts[] = $attrs['size'];
		}
		if ( ! empty( $attrs['pcd'] ) ) {
			$parts[] = $attrs['pcd'];
		}
		if ( ! empty( $attrs['et'] ) ) {
			$parts[] = 'ET' . $attrs['et'];
		}
		if ( ! empty( $attrs['width'] ) && ! empty( $attrs['diameter'] ) ) {
			$parts[] = $attrs['diameter'] . 'x' . $attrs['width'];
		}
		if ( ! empty( $intent['vehicle'] ) ) {
			$parts[] = $intent['vehicle'];
		}

		if ( empty( $parts ) && ! empty( $intent['product_type'] ) ) {
			$type_labels = array(
				'wheel'   => 'wheel',
				'tire'    => 'tire',
				'battery' => 'battery',
				'parts'   => 'parts',
			);
			$type = $intent['product_type'];
			if ( isset( $type_labels[ $type ] ) ) {
				$parts[] = $type_labels[ $type ];
			}
		}

		return ! empty( $parts ) ? implode( ' ', $parts ) : null;
	}

	/**
	 * Prevent wheel brand/model names from being treated as vehicle names.
	 */
	private function correct_wheel_vehicle_confusion( array $intent ): array {
		if ( ( $intent['product_type'] ?? '' ) !== 'wheel' ) {
			return $intent;
		}

		$attrs = $intent['attributes'] ?? array();

		// Parse wheel model strings from the current message context.
		if ( ! empty( $intent['vehicle'] ) ) {
			$vehicle_key = mb_strtolower( trim( $intent['vehicle'] ) );

			if ( ! isset( self::VEHICLE_ALIASES[ $vehicle_key ] ) && ! $this->is_known_vehicle( $intent['vehicle'] ) ) {
				$parsed = $this->parse_wheel_model_string( $intent['vehicle'] );

				if ( ! empty( $parsed['brand'] ) && empty( $attrs['brand'] ) ) {
					$attrs['brand'] = $parsed['brand'];
				}
				if ( ! empty( $parsed['style'] ) && empty( $attrs['style'] ) ) {
					$attrs['style'] = $parsed['style'];
				}

				$intent['vehicle']        = null;
				$intent['vehicle_brand']  = null;
				$intent['vehicle_model']  = null;
			}
		}

		$intent['attributes'] = $attrs;
		return $intent;
	}

	private function is_known_vehicle( string $vehicle ): bool {
		$lower = mb_strtolower( trim( $vehicle ) );
		foreach ( self::VEHICLE_ALIASES as $alias => $normalized ) {
			if ( $lower === mb_strtolower( $alias ) || $lower === mb_strtolower( $normalized ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Parse strings like "KMC T9" into brand + style.
	 */
	private function parse_wheel_model_string( string $text ): array {
		$text = trim( $text );

		if ( preg_match( '/^(kmc)\s*t[-\s]?(\w+)$/iu', $text, $matches ) ) {
			return array(
				'brand' => strtoupper( $matches[1] ),
				'style' => 'T' . strtoupper( $matches[2] ),
			);
		}

		$lower = mb_strtolower( $text );
		foreach ( self::WHEEL_BRANDS as $brand ) {
			if ( mb_strpos( $lower, $brand ) !== false ) {
				$style = trim( preg_replace( '/\b' . preg_quote( $brand, '/' ) . '\b/iu', '', $text ) );
				return array(
					'brand' => strtoupper( $brand ),
					'style' => $style ?: null,
				);
			}
		}

		return array( 'style' => $text );
	}

	private function detect_wheel_product_type( array $intent ): ?string {
		$text = trim(
			( $intent['search_text'] ?? '' ) . ' ' .
			( $intent['vehicle'] ?? '' ) . ' ' .
			( $intent['attributes']['brand'] ?? '' ) . ' ' .
			( $intent['attributes']['style'] ?? '' )
		);

		if ( '' === $text ) {
			return null;
		}

		$lower = mb_strtolower( $text );
		foreach ( self::WHEEL_BRANDS as $brand ) {
			if ( mb_strpos( $lower, $brand ) !== false ) {
				return 'wheel';
			}
		}

		if ( preg_match( '/\bkmc\s*t[-\s]?\w+/i', $text ) ) {
			return 'wheel';
		}

		return null;
	}

	private function adjust_wheel_followup( array $intent ): array {
		if ( ( $intent['product_type'] ?? '' ) !== 'wheel' ) {
			return $intent;
		}

		$attrs     = $intent['attributes'] ?? array();
		$has_brand = ! empty( $attrs['brand'] );
		$has_style = ! empty( $attrs['style'] );
		$has_size  = ! empty( $attrs['size'] );
		$has_text  = ! empty( $intent['search_text'] );

		// Enough info to search without asking for vehicle.
		if ( $has_text || ( $has_brand && ( $has_size || $has_style ) ) || ( $has_size && $has_style ) || ! empty( $attrs['pcd'] ) ) {
			$intent['needs_followup'] = false;
			return $intent;
		}

		if ( empty( $attrs['size'] ) && empty( $intent['vehicle'] ) ) {
			$intent['needs_followup']     = true;
			$intent['followup_question']  = $intent['followup_question'] ?: 'Which vehicle and wheel size are you looking for?';
		} elseif ( ! empty( $intent['vehicle'] ) && empty( $attrs['size'] ) ) {
			$intent['needs_followup']     = true;
			$intent['followup_question']  = $intent['followup_question'] ?: 'What wheel size do you need? (e.g. 15, 16, 17)';
		}

		return $intent;
	}
}
