<?php


namespace Brandlight\ElasticPress\helpers;


use Brandlight\ElasticPress\mappings\Metas;

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
	public static function generateProductMetas( \WC_Product $product ){
		$metas = [];
		if ( $product ) {
			$metas[ 'is_on_sale' ]   = $product->is_on_sale();
			$metas[ 'product_type' ] = (string) $product->get_type();
			$price                   = $product->get_price();
			$metas[ 'prices' ]       = [
				"price"                  => $price,
				"regular_price"          => $price,
				"sale_price"             => $price,
				"max_price"              => $price,
				"max_price_ps"           => $price,
				"min_retail_price"       => $price,
				"max_retail_price"       => $price,
				"min_wholesale_price"    => $price,
				"max_wholesale_price"    => $price,
				"min_retail_price_ps"    => $price,
				"max_retail_price_ps"    => $price,
				"min_wholesale_price_ps" => $price,
				"max_wholesale_price_ps" => $price,
			];

			return $metas;
		}

		return false;
	}
}