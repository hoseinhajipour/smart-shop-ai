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
	 * Get WooCommerce product categories for mapping (e.g. brand → category).
	 */
	public function get_product_categories(): array {
		$terms = get_terms( array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'ASC',
		) );

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return array();
		}

		$categories = array();
		foreach ( $terms as $term ) {
			$depth = 0;
			$parent = $term->parent;
			while ( $parent ) {
				$depth++;
				$parent_term = get_term( $parent, 'product_cat' );
				$parent      = ( $parent_term && ! is_wp_error( $parent_term ) ) ? $parent_term->parent : 0;
			}

			$prefix = $depth > 0 ? str_repeat( '— ', $depth ) : '';

			$categories[] = array(
				'id'         => (int) $term->term_id,
				'name'       => $term->name,
				'slug'       => $term->slug,
				'label'      => $prefix . $term->name,
				'taxonomy'   => 'product_cat',
				'parent'     => (int) $term->parent,
				'term_count' => (int) $term->count,
			);
		}

		return $categories;
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
			'pcd'         => array( 'pcd', 'bolt pattern', 'bolt-pattern', 'پیچ', 'در پیچ' ),
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

	/**
	 * Suggest product_cat for brand when no global brand attribute exists.
	 */
	public function suggest_brand_category(): ?string {
		$categories = $this->get_product_categories();
		if ( empty( $categories ) ) {
			return null;
		}

		// If store uses categories as brands (common for wheel shops), suggest product_cat.
		$brand_slugs = array( 'archer', 'kmc', 'rays', 'bbs', 'enkei', 'brand', 'brands', 'برند' );
		foreach ( $categories as $cat ) {
			$slug_lower = mb_strtolower( $cat['slug'] );
			$name_lower = mb_strtolower( $cat['name'] );
			foreach ( $brand_slugs as $pattern ) {
				if ( $slug_lower === $pattern || mb_strpos( $name_lower, $pattern ) !== false ) {
					return 'product_cat';
				}
			}
		}

		return null;
	}
}
