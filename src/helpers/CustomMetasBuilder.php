<?php


namespace Brandlight\ElasticPress\helpers;


use Brandlight\ElasticPress\mappings\Metas;
use WC_Product_Variable;

class CustomMetasBuilder {
	use Metas;

	/**
	 * @param $thumbnail_id
	 *
	 * @return array
	 */
	public static function generateImagesData( $thumbnail_id ) {
		$images                = [];
		$thumbnail             = wp_get_attachment_image_src( $thumbnail_id, 'thumbnail' );
		$woocommerce_thumbnail = wp_get_attachment_image_src( $thumbnail_id, 'woocommerce_thumbnail' );
		$ftc_450               = wp_get_attachment_image_src( $thumbnail_id, 'ftc-450x450-c' );
		if ( $thumbnail && $woocommerce_thumbnail && $ftc_450 ) {
			$to_thumbnail[ 'url' ]                = $thumbnail[ 0 ];
			$to_thumbnail[ 'width' ]              = $thumbnail[ 1 ];
			$to_thumbnail[ 'height' ]             = $thumbnail[ 2 ];
			$to_woocommerce_thumbnail[ 'url' ]    = $woocommerce_thumbnail[ 0 ];
			$to_woocommerce_thumbnail[ 'width' ]  = $woocommerce_thumbnail[ 1 ];
			$to_woocommerce_thumbnail[ 'height' ] = $woocommerce_thumbnail[ 2 ];
			$to_ftc_450[ 'url' ]                  = $ftc_450[ 0 ];
			$to_ftc_450[ 'width' ]                = $ftc_450[ 1 ];
			$to_ftc_450[ 'height' ]               = $ftc_450[ 2 ];
			$images[ 'thumbnail' ]                = json_encode( $to_thumbnail );
			$images[ 'woocommerce_thumbnail' ]    = json_encode( $to_woocommerce_thumbnail );
			$images[ 'ftc-450x450-c' ]            = json_encode( $to_ftc_450 );
		}

		return $images;
	}

	/**
	 * @param $meta_key
	 *
	 * @return array
	 */
	public static function generateStockStatusWithReadableName( $meta_key ) {
		foreach ( self::$stock_status as $key => $value ) {
			if ( $meta_key == $key ) {
				return [ 'status' => $meta_key, 'name' => $value ];
			}
		}

		return [ $meta_key ];
	}

	/**
	 * @param \WC_Product $product
	 *
	 * @return array|bool
	 */
	public static function generateProductMetas( \WC_Product $product ) {
		$metas = [];
		if ( $product ) {
			$metas[ 'is_on_sale' ]   = $product->is_on_sale();
			$metas[ 'product_type' ] = (string) $product->get_type();
			$metas[ 'prices' ]       = self::extractPrices( $product );
			$metas[ 'rating' ]       = [
				"count"   => (int) $product->get_rating_count(),
				"average" => (float) $product->get_average_rating(),
			];
			$default_variation       = self::extractDefaultVariation( $product );
			if ( ! empty( $default_variation ) ) {
				$metas[ 'default_variation' ] = $default_variation;
			}

			return $metas;
		}

		return false;
	}

	/**
	 * @param $post
	 *
	 * @return array
	 */
	public static function generateUids( $post ) {
		$post                           = get_post( $post );
		$product                        = wc_get_product( $post );
		$metas[ 'sku' ]                 = $product->get_sku();
		$metas[ 'uid_vendor_item_ids' ] = preg_split( '/, ?/', get_post_meta( $post->ID, 'uid_vendor_item_ids', true ) );
		$metas[ 'uid_gtins' ]           = preg_split( '/, ?/', get_post_meta( $post->ID, 'uid_gtins', true ) );
		$metas[ 'uid_barcodes' ]        = preg_split( '/, ?/', get_post_meta( $post->ID, 'uid_barcodes', true ) );
		$metas[ 'uid_asins' ]           = preg_split( '/, ?/', get_post_meta( $post->ID, 'uid_asins', true ) );
		$metas[ 'uid_viart_ids' ]       = preg_split( '/, ?/', get_post_meta( $post->ID, 'uid_viart_ids', true ) );
		$metas[ 'uid_mfns' ]            = preg_split( '/, ?/', get_post_meta( $post->ID, 'uid_mfns', true ) );
		$metas[ 'uid_nav_item_ids' ]    = preg_split( '/, ?/', get_post_meta( $post->ID, 'uid_nav_item_ids', true ) );

		$to_return = array_values( array_filter( array_unique( array_merge(
			$metas[ 'uid_vendor_item_ids' ],
			$metas[ 'uid_gtins' ],
			$metas[ 'uid_barcodes' ],
			$metas[ 'uid_asins' ],
			$metas[ 'uid_viart_ids' ],
			$metas[ 'uid_mfns' ],
			$metas[ 'uid_nav_item_ids' ],
			[ $metas[ 'sku' ] ]
		) ) ) );

		return $to_return;
	}

	/**
	 * @param $product
	 *
	 * @return array
	 */
	public static function extractPrices( $product ) {
		// Extract prices.
		if ( $product instanceof WC_Product_Variable ) {
			$prices = $product->get_variation_prices( true );

			$price         = $sale_price = current( $prices[ 'price' ] );
			$regular_price = current( $prices[ 'regular_price' ] );
			$pricing_mode  = 'fs';
			$max_price     = $prices[ 'advanced_pricing_data' ][ $pricing_mode ][ 'max' ][ 'customer' ][ 'price' ] ?? end( $prices[ 'price' ] );

			$retail_role      = brandlight_get_retail_pricing_default_role();
			$min_retail_price = $prices[ 'advanced_pricing_data' ][ $pricing_mode ][ 'min' ][ $retail_role ][ 'price' ] ?? $price;
			$max_retail_price = $prices[ 'advanced_pricing_data' ][ $pricing_mode ][ 'max' ][ $retail_role ][ 'price' ] ?? $max_price;

			$wholesale_role      = brandlight_get_wholesale_pricing_default_role();
			$min_wholesale_price = $prices[ 'advanced_pricing_data' ][ $pricing_mode ][ 'min' ][ $wholesale_role ][ 'price' ] ?? $price;
			$max_wholesale_price = $prices[ 'advanced_pricing_data' ][ $pricing_mode ][ 'max' ][ $wholesale_role ][ 'price' ] ?? $max_price;


		} else {
			$price         = wc_get_price_to_display( $product );
			$regular_price = wc_get_price_to_display( $product, [ 'price' => $product->get_regular_price() ] );

			$sale_price          = $price;
			$min_retail_price    = $price;
			$max_retail_price    = $price;
			$min_wholesale_price = $price;
			$max_wholesale_price = $price;
			$max_price           = $price;
		}

		$to_return = [
			"price"               => $price,
			"regular_price"       => $regular_price,
			"sale_price"          => $sale_price,
			"max_price"           => $max_price,
			"min_retail_price"    => $min_retail_price,
			"max_retail_price"    => $max_retail_price,
			"min_wholesale_price" => $min_wholesale_price,
			"max_wholesale_price" => $max_wholesale_price,
		];

		if ( class_exists( 'Brandlight_Feature_Postage_And_Packaging' ) ) {
			$to_return = array_merge( $to_return, \Brandlight_Feature_Postage_And_Packaging::generateProductPSdata( $product ) );
		}

		return $to_return;
	}

	/**
	 * @param $product
	 *
	 * @return array
	 */
	public static function extractDefaultVariation( $product ) {

		$attributes = [];

		if ( $product instanceof WC_Product_Variable ) {

			if ( $retail_variation_id = brandlight_get_default_product_variation( $product, 'retail' ) ) {
				$attributes[ 'retail' ] = $retail_variation_id;
			}

			if ( $wholesale_variation_id = brandlight_get_default_product_variation( $product, 'wholesale' ) ) {
				$attributes[ 'wholesale' ] = $wholesale_variation_id;
			}
		}

		return $attributes;

	}
}