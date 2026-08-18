<?php
namespace SmartShopAI\Recommendation;

use SmartShopAI\Fitment\FitmentHelper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ranks products by match score against user intent.
 */
class ProductRanker {

	/**
	 * Score weights for ranking criteria.
	 */
	private const WEIGHTS = array(
		'pcd'          => 35,
		'vehicle'      => 25,
		'size'         => 20,
		'et'           => 15,
		'color'        => 10,
		'brand'        => 10,
		'style'        => 10,
		'width'        => 10,
		'diameter'     => 10,
		'product_type' => 5,
		'in_stock'     => 5,
	);

	public function rank( array $products, array $intent ): array {
		$scored = array();

		foreach ( $products as $product ) {
			$score = $this->calculate_score( $product, $intent );
			$product['match_score'] = $score;
			$product['match_label'] = $this->score_label( $score );
			$scored[] = $product;
		}

		usort( $scored, function ( $a, $b ) {
			return $b['match_score'] <=> $a['match_score'];
		} );

		return $scored;
	}

	public function calculate_score( array $product, array $intent ): int {
		$score    = 0;
		$attrs    = $intent['attributes'] ?? array();
		$prod_attrs = $product['attributes'] ?? array();
		$prod_text  = mb_strtolower(
			$product['name'] . ' ' .
			implode( ' ', $prod_attrs ) . ' ' .
			( $product['short_description'] ?? '' )
		);

		// PCD / bolt pattern match (highest priority for vehicle fitment).
		if ( ! empty( $attrs['pcd'] ) ) {
			if ( FitmentHelper::text_contains_pcd( $prod_text, $attrs['pcd'] ) ) {
				$score += self::WEIGHTS['pcd'];
			} else {
				foreach ( $prod_attrs as $val ) {
					if ( FitmentHelper::text_contains_pcd( (string) $val, $attrs['pcd'] ) ) {
						$score += self::WEIGHTS['pcd'];
						break;
					}
				}
			}
		}

		// Vehicle match.
		if ( ! empty( $intent['vehicle'] ) ) {
			$vehicle_lower = mb_strtolower( $intent['vehicle'] );
			if ( mb_strpos( $prod_text, $vehicle_lower ) !== false ) {
				$score += self::WEIGHTS['vehicle'];
			} else {
				foreach ( $prod_attrs as $val ) {
					if ( mb_strpos( mb_strtolower( $val ), $vehicle_lower ) !== false ) {
						$score += self::WEIGHTS['vehicle'];
						break;
					}
				}
			}
		}

		// Size match.
		if ( ! empty( $attrs['size'] ) ) {
			$size = $attrs['size'];
			if (
				mb_strpos( $prod_text, (string) $size ) !== false ||
				mb_strpos( $prod_text, $size . ' inch' ) !== false ||
				mb_strpos( $prod_text, $size . ' اینچ' ) !== false
			) {
				$score += self::WEIGHTS['size'];
			}
		}

		// Color match.
		if ( ! empty( $attrs['color'] ) ) {
			$color = mb_strtolower( $attrs['color'] );
			$color_aliases = array(
				'black' => array( 'مشکی', 'black', 'سیاه' ),
				'white' => array( 'سفید', 'white' ),
				'silver'=> array( 'نقره', 'silver', 'نقره‌ای' ),
			);

			$found = false;
			if ( mb_strpos( $prod_text, $color ) !== false ) {
				$found = true;
			} else {
				foreach ( $color_aliases as $aliases ) {
					if ( in_array( $color, $aliases, true ) ) {
						foreach ( $aliases as $alias ) {
							if ( mb_strpos( $prod_text, $alias ) !== false ) {
								$found = true;
								break;
							}
						}
					}
				}
			}
			if ( $found ) {
				$score += self::WEIGHTS['color'];
			}
		}

		// ET / offset match.
		if ( ! empty( $attrs['et'] ) ) {
			$et = FitmentHelper::normalize_et( (string) $attrs['et'] );
			if (
				mb_strpos( $prod_text, 'et' . $et ) !== false ||
				mb_strpos( $prod_text, 'et ' . $et ) !== false ||
				mb_strpos( $prod_text, 'offset ' . $et ) !== false ||
				preg_match( '/\b' . preg_quote( $et, '/' ) . '\s*mm\b/i', $prod_text )
			) {
				$score += self::WEIGHTS['et'];
			}
		}

		// Width match.
		if ( ! empty( $attrs['width'] ) ) {
			$width = $attrs['width'];
			if ( mb_strpos( $prod_text, (string) $width ) !== false ) {
				$score += self::WEIGHTS['width'];
			}
		}

		// Diameter match.
		if ( ! empty( $attrs['diameter'] ) ) {
			$diameter = $attrs['diameter'];
			if ( mb_strpos( $prod_text, (string) $diameter ) !== false ) {
				$score += self::WEIGHTS['diameter'];
			}
		}

		// Brand match (attributes, name, or product category).
		if ( ! empty( $attrs['brand'] ) ) {
			$brand = mb_strtolower( $attrs['brand'] );
			$found = mb_strpos( $prod_text, $brand ) !== false;

			if ( ! $found && ! empty( $product['categories'] ) ) {
				foreach ( (array) $product['categories'] as $category ) {
					if ( mb_strpos( mb_strtolower( (string) $category ), $brand ) !== false ) {
						$found = true;
						break;
					}
				}
			}

			if ( $found ) {
				$score += self::WEIGHTS['brand'];
			}
		}

		// Style/model match.
		if ( ! empty( $attrs['style'] ) ) {
			$style = mb_strtolower( $attrs['style'] );
			if ( mb_strpos( $prod_text, $style ) !== false ) {
				$score += self::WEIGHTS['style'];
			}
		}

		// Product type match.
		if ( ! empty( $intent['product_type'] ) ) {
			$type_keywords = array(
				'wheel'   => array( 'رینگ', 'ring', 'wheel' ),
				'tire'    => array( 'لاستیک', 'tire' ),
				'battery' => array( 'باتری', 'battery' ),
			);
			$type = $intent['product_type'];
			if ( isset( $type_keywords[ $type ] ) ) {
				foreach ( $type_keywords[ $type ] as $kw ) {
					if ( mb_strpos( $prod_text, $kw ) !== false ) {
						$score += self::WEIGHTS['product_type'];
						break;
					}
				}
			}
		}

		// Stock bonus.
		if ( ! empty( $product['in_stock'] ) ) {
			$score += self::WEIGHTS['in_stock'];
		}

		return min( 100, $score );
	}

	private function score_label( int $score ): string {
		if ( $score >= 80 ) {
			return 'Best match';
		}
		if ( $score >= 60 ) {
			return 'Good match';
		}
		if ( $score >= 40 ) {
			return 'Fair match';
		}
		return 'Low match';
	}
}
