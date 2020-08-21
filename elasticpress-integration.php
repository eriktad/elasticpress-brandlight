<?php
/**
 * Plugin Name: ElasticPress Integration
 * Description: A fast and flexible search and query engine for WordPress.
 * Version:     3.4.3
 * Author:      Brandlight
 * License:     GPLv2 or later
 * Text Domain: elasticpress
 * Domain Path: /lang/
 *
 * @package  elasticpress
 */

add_filter(
	'ep_autosuggest_options',
	function ( $options ) {
		$options[ 'link_pattern_callback' ] = 'bl_ep_autosuggest_link_pattern';

		return $options;
	}
);

add_action(
	'wp_enqueue_scripts',
	static function () {
		wp_enqueue_script(
			'x1-elasticpress-autosuggest',
			plugin_dir_url( __FILE__ ) . 'assets/js/search.js',
			[],
			random_int( 1, 900 )
		);

		wp_enqueue_style(
			'x1-elasticpress-autosuggest',
			plugin_dir_url( __FILE__ ) . 'assets/css/search.css',
			[],
			random_int( 1, 900 )
		);
	},
	PHP_INT_MAX
);


add_filter( 'ep_prepared_post_meta', function ( $metas ) {
	$white_list = [
		'uid_barcodes',
		'uid_gtins',
		'uid_mfns',
		'uid_nav_item_ids',
		'uid_tc_last_update',
		'uid_vendor_item_ids',
		'uid_viart_ids',
		'grade',
		'_thumbnail_id',
		'_product_attributes',
		'_wpb_vc_js_status',
		'_swatch_type',
		'total_sales',
		'_downloadable',
		'_virtual',
		'_regular_price',
		'_sale_price',
		'_tax_status',
		'_tax_class',
		'_purchase_note',
		'_featured',
		'_weight',
		'_length',
		'_width',
		'_height',
		'_visibility',
		'_sku',
		'_sale_price_dates_from',
		'_sale_price_dates_to',
		'_price',
		'_sold_individually',
		'_manage_stock',
		'_backorders',
		'_stock',
		'_upsell_ids',
		'_crosssell_ids',
		'_stock_status',
		'_product_version',
		'_product_tabs',
		'_override_tab_layout',
		'_suggested_price',
		'_min_price',
		'_customer_user',
		'_variable_billing',
		'_wc_average_rating',
		'_product_image_gallery',
		'_bj_lazy_load_skip_post',
		'_min_variation_price',
		'_max_variation_price',
		'_min_price_variation_id',
		'_max_price_variation_id',
		'_min_variation_regular_price',
		'_max_variation_regular_price',
		'_min_regular_price_variation_id',
		'_max_regular_price_variation_id',
		'_min_variation_sale_price',
		'_max_variation_sale_price',
		'_min_sale_price_variation_id',
		'_max_sale_price_variation_id',
		'_default_attributes',
		'_swatch_type_options',
		'_order_key',
		'_billing_company',
		'_billing_address_1',
		'_billing_address_2',
		'_billing_city',
		'_billing_postcode',
		'_billing_country',
		'_billing_state',
		'_billing_email',
		'_billing_phone',
		'_shipping_address_1',
		'_shipping_address_2',
		'_shipping_city',
		'_shipping_postcode',
		'_shipping_country',
		'_shipping_state',
		'_billing_last_name',
		'_billing_first_name',
		'_shipping_first_name',
		'_shipping_last_name',
	];
	foreach ( $metas as $key => $data ) {
		if ( ! in_array( $key, $white_list, true ) ) {
			unset( $metas[ $key ] );
		}
//		else {
//			if ( $key == '_thumbnail_id' && ! empty( $data ) ) {
//				$image_id                          = $data[ 0 ];
//				$images                            = [];
//				$images[ 'thumbnail' ]             = wp_get_attachment_image_url( $image_id, 'thumbnail' );
//				$images[ 'woocommerce_thumbnail' ] = wp_get_attachment_image_url( $image_id, 'woocommerce_thumbnail' );
//				$images[ 'ftc-450x450-c' ]         = wp_get_attachment_image_url( $image_id, 'ftc-450x450-c' );
//				if ( $images[ 'thumbnail' ] && $images[ 'woocommerce_thumbnail' ] && $images[ 'ftc-450x450-c' ] ) {
//					$metas[ 'images' ] = $images;
//				}
//			}
//		}
	}

	return $metas;
},
	PHP_INT_MAX,
	1
);

add_filter(
	'ep_total_field_limit',
	function () {
		return 200000;
	}
);

add_filter(
	'ep_max_result_window',
	function () {
		return 3000000;
	}
);


add_filter(
	'ep_post_mapping',
	function ( $mapping ) {
		$mapping[ 'settings' ][ 'index.max_docvalue_fields_search' ] = 300;

		return $mapping;
	}
);
