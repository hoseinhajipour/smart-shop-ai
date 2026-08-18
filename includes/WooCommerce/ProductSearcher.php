<?php
namespace SmartShopAI\WooCommerce;

use SmartShopAI\Core\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Searches WooCommerce products using various strategies.
 */
class ProductSearcher {

	private AttributeDiscovery $attribute_discovery;

	public function __construct() {
		$this->attribute_discovery = new AttributeDiscovery();
	}

	/**
	 * General search with multiple params.
	 */
	public function search( array $params ): array {
		$intent       = $params['intent'] ?? 'smart_search';
		$search_text  = $params['search_text'] ?? '';
		$product_type = $params['product_type'] ?? null;
		$vehicle      = $params['vehicle'] ?? null;
		$attributes   = $params['attributes'] ?? array();
		$limit        = (int) ( $params['limit'] ?? 20 );

		if ( $intent === 'product_search' && $search_text ) {
			return $this->text_search( $search_text, $limit );
		}

		$filters = $this->build_attribute_filters( $product_type, $vehicle, $attributes );

		if ( ! empty( $filters ) ) {
			$products = $this->search_by_attributes( $filters, $limit );
			if ( ! empty( $products ) ) {
				return $products;
			}
		}

		// Fallback to text search.
		$fallback_query = $this->build_fallback_query( $product_type, $vehicle, $attributes, $search_text );
		if ( $fallback_query ) {
			return $this->text_search( $fallback_query, $limit );
		}

		return array();
	}

	/**
	 * Text-based product search.
	 */
	public function text_search( string $query, int $limit = 20 ): array {
		$args = array(
			'status'   => 'publish',
			'limit'    => $limit,
			'orderby'  => 'relevance',
			'return'   => 'ids',
		);

		// Use WooCommerce product search.
		$data_store = \WC_Data_Store::load( 'product' );
		$product_ids = $data_store->search_products( $query, $limit, 'relevance', true, false );

		if ( empty( $product_ids ) ) {
			// Fallback to WP search.
			$wp_query = new \WP_Query( array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				's'              => $query,
				'posts_per_page' => $limit,
				'fields'         => 'ids',
			) );
			$product_ids = $wp_query->posts;
		}

		return $this->format_products( $product_ids );
	}

	/**
	 * Search by WooCommerce attribute taxonomies.
	 */
	public function search_by_attributes( array $filters, int $limit = 20 ): array {
		$tax_query = array( 'relation' => 'AND' );

		foreach ( $filters as $taxonomy => $terms ) {
			if ( empty( $terms ) ) {
				continue;
			}

			$term_values = is_array( $terms ) ? $terms : array( $terms );

			$tax_query[] = array(
				'taxonomy' => $taxonomy,
				'field'    => 'slug',
				'terms'    => $this->resolve_term_slugs( $taxonomy, $term_values ),
				'operator' => 'IN',
			);
		}

		if ( count( $tax_query ) <= 1 ) {
			return array();
		}

		$query = new \WP_Query( array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'fields'         => 'ids',
			'tax_query'      => $tax_query,
		) );

		return $this->format_products( $query->posts );
	}

	/**
	 * Build attribute filters from intent data using mapping.
	 */
	private function build_attribute_filters( ?string $product_type, ?string $vehicle, array $attributes ): array {
		$mapping = Settings::get_attribute_mapping();
		$filters = array();

		if ( $vehicle && ! empty( $mapping['vehicle'] ) ) {
			$filters[ $mapping['vehicle'] ] = $this->find_matching_terms( $mapping['vehicle'], $vehicle );
		}

		if ( ! empty( $attributes['size'] ) && ! empty( $mapping['wheel_size'] ) ) {
			$filters[ $mapping['wheel_size'] ] = $this->find_matching_terms( $mapping['wheel_size'], $attributes['size'] );
		}

		if ( ! empty( $attributes['color'] ) && ! empty( $mapping['color'] ) ) {
			$filters[ $mapping['color'] ] = $this->find_matching_terms( $mapping['color'], $attributes['color'] );
		}

		if ( ! empty( $attributes['brand'] ) && ! empty( $mapping['brand'] ) ) {
			$filters[ $mapping['brand'] ] = $this->find_matching_terms( $mapping['brand'], $attributes['brand'] );
		}

		// Product type → category search.
		if ( $product_type ) {
			$cat_slug = $this->product_type_to_category( $product_type );
			if ( $cat_slug ) {
				$filters['product_cat'] = array( $cat_slug );
			}
		}

		return $filters;
	}

	private function find_matching_terms( string $taxonomy, string $value ): array {
		$terms = get_terms( array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
		) );

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return array( sanitize_title( $value ) );
		}

		$value_lower = mb_strtolower( trim( $value ) );
		$matches     = array();

		foreach ( $terms as $term ) {
			$name_lower = mb_strtolower( $term->name );
			$slug_lower = mb_strtolower( $term->slug );

			if (
				$name_lower === $value_lower ||
				$slug_lower === $value_lower ||
				mb_strpos( $name_lower, $value_lower ) !== false ||
				mb_strpos( $value_lower, $name_lower ) !== false
			) {
				$matches[] = $term->slug;
			}
		}

		return $matches ?: array( sanitize_title( $value ) );
	}

	private function resolve_term_slugs( string $taxonomy, array $values ): array {
		$slugs = array();
		foreach ( $values as $value ) {
			if ( is_string( $value ) ) {
				$found = $this->find_matching_terms( $taxonomy, $value );
				$slugs = array_merge( $slugs, $found );
			}
		}
		return array_unique( $slugs );
	}

	private function product_type_to_category( string $type ): ?string {
		$map = array(
			'wheel'   => 'rings',
			'tire'    => 'tires',
			'battery' => 'batteries',
			'parts'   => 'parts',
		);
		return $map[ $type ] ?? null;
	}

	private function build_fallback_query( ?string $product_type, ?string $vehicle, array $attributes, ?string $search_text ): string {
		$parts = array();

		if ( $search_text ) {
			$parts[] = $search_text;
		}

		$type_labels = array(
			'wheel'   => 'wheel',
			'tire'    => 'tire',
			'battery' => 'battery',
			'parts'   => 'parts',
		);

		if ( $product_type && isset( $type_labels[ $product_type ] ) ) {
			$parts[] = $type_labels[ $product_type ];
		}

		if ( $vehicle ) {
			$parts[] = $vehicle;
		}

		if ( ! empty( $attributes['size'] ) ) {
			$parts[] = $attributes['size'];
		}

		if ( ! empty( $attributes['color'] ) ) {
			$parts[] = $attributes['color'];
		}

		return implode( ' ', $parts );
	}

	private function format_products( array $product_ids ): array {
		$products = array();
		foreach ( $product_ids as $id ) {
			$formatted = WooCommerceHelper::format_product( wc_get_product( $id ) );
			if ( $formatted ) {
				$products[] = $formatted;
			}
		}
		return $products;
	}
}
