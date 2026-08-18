<?php
namespace SmartShopAI\WooCommerce;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WooCommerce product formatting utilities.
 */
class WooCommerceHelper {

	public static function format_product( $product ): ?array {
		if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
			return null;
		}

		$image_id  = $product->get_image_id();
		$image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'medium' ) : wc_placeholder_img_src( 'medium' );

		$attributes = array();
		foreach ( $product->get_attributes() as $attr ) {
			if ( $attr->is_taxonomy() ) {
				$terms = wc_get_product_terms( $product->get_id(), $attr->get_name(), array( 'fields' => 'names' ) );
				$attributes[ wc_attribute_label( $attr->get_name() ) ] = implode( ', ', $terms );
			} else {
				$attributes[ $attr->get_name() ] = implode( ', ', $attr->get_options() );
			}
		}

		return array(
			'id'            => $product->get_id(),
			'name'          => $product->get_name(),
			'slug'          => $product->get_slug(),
			'price'         => $product->get_price(),
			'price_html'    => $product->get_price_html(),
			'regular_price' => $product->get_regular_price(),
			'sale_price'    => $product->get_sale_price(),
			'stock_status'  => $product->get_stock_status(),
			'stock_quantity'=> $product->get_stock_quantity(),
			'in_stock'      => $product->is_in_stock(),
			'sku'           => $product->get_sku(),
			'image'         => $image_url,
			'url'           => $product->get_permalink(),
			'attributes'    => $attributes,
			'categories'    => wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'names' ) ),
			'short_description' => wp_strip_all_tags( $product->get_short_description() ),
		);
	}

	public static function is_woocommerce_active(): bool {
		return class_exists( 'WooCommerce' );
	}
}
