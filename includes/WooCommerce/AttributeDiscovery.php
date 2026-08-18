<?php
namespace SmartShopAI\WooCommerce;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Discovers WooCommerce global attributes dynamically.
 */
class AttributeDiscovery {

	/**
	 * Get all global product attributes.
	 */
	public function get_all_attributes(): array {
		if ( ! function_exists( 'wc_get_attribute_taxonomies' ) ) {
			return array();
		}

		$taxonomies = wc_get_attribute_taxonomies();
		$attributes = array();

		foreach ( $taxonomies as $tax ) {
			$taxonomy = wc_attribute_taxonomy_name( $tax->attribute_name );
			$terms    = get_terms( array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
			) );

			$term_list = array();
			if ( ! is_wp_error( $terms ) ) {
				foreach ( $terms as $term ) {
					$term_list[] = array(
						'id'   => $term->term_id,
						'name' => $term->name,
						'slug' => $term->slug,
					);
				}
			}

			$attributes[] = array(
				'id'         => (int) $tax->attribute_id,
				'name'       => $tax->attribute_name,
				'label'      => $tax->attribute_label,
				'type'       => $tax->attribute_type,
				'taxonomy'   => $taxonomy,
				'terms'      => $term_list,
				'term_count' => count( $term_list ),
			);
		}

		return $attributes;
	}

	/**
	 * Get attribute mapping suggestions based on labels.
	 */
	public function get_mapping_suggestions(): array {
		$attributes = $this->get_all_attributes();
		$suggestions  = array();

		$known_mappings = array(
			'vehicle'     => array( 'vehicle', 'خودرو', 'car', 'مدل خودرو' ),
			'brand'       => array( 'brand', 'برند' ),
			'wheel_size'  => array( 'wheel-size', 'wheel size', 'سایز رینگ', 'سایز' ),
			'color'       => array( 'color', 'رنگ' ),
			'pcd'         => array( 'pcd' ),
			'et'          => array( 'et', 'offset' ),
			'material'    => array( 'material', 'جنس' ),
			'width'       => array( 'width', 'عرض' ),
			'diameter'    => array( 'diameter', 'قطر' ),
		);

		foreach ( $known_mappings as $key => $patterns ) {
			foreach ( $attributes as $attr ) {
				$label_lower = mb_strtolower( $attr['label'] );
				$name_lower  = mb_strtolower( $attr['name'] );

				foreach ( $patterns as $pattern ) {
					if (
						mb_strpos( $label_lower, mb_strtolower( $pattern ) ) !== false ||
						mb_strpos( $name_lower, mb_strtolower( $pattern ) ) !== false
					) {
						$suggestions[ $key ] = $attr['taxonomy'];
						break;
					}
				}
				if ( isset( $suggestions[ $key ] ) ) {
					break;
				}
			}
		}

		return $suggestions;
	}
}
