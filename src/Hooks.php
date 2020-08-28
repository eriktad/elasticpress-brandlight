<?php


namespace Brandlight\ElasticPress;


use Brandlight\ElasticPress\extended\Post;
use Brandlight\ElasticPress\extended\Term;
use ElasticPress\Indexables;
use NovemBit\i18n\Module;

class Hooks {

	/**
	 * @var Bootstrap
	 */
	public $parent;

	public $white_list_metas = [
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
	];

	public function __construct( $parent ) {
		$this->parent = $parent;
		add_action( 'init', [ $this, 'epiInit' ], 11 );
		add_filter( 'ep_prepared_post_meta', [ $this, 'epiWhiteListMetas' ], 10, 2 );
		add_filter( 'ep_autosuggest_options', [ $this, 'epiAutosuggestOptions' ] );
		add_action(
			'wp_enqueue_scripts', [ $this, 'epiEnqueueScripts' ],
			10
		);
	}

	public function epiInit() {
		$languages         = Module::instance()->localization->getAcceptLanguages();
		$original_language = Module::instance()->localization->getFromLanguage();
		unset( $languages[ array_search( $original_language, $languages ) ] );
		foreach ( $languages as $language ) {
			$indexable_post = new Post( $language );
			$indexable_term = new Term( $language );

			Indexables::factory()->register( $indexable_post );
			Indexables::factory()->register( $indexable_term );
		}
	}

	public function epiWhiteListMetas( $metas, $post ) {
		foreach ( $metas as $key => $data ) {
			if ( ! in_array( $key, $this->white_list_metas, true ) ) {
				unset( $metas[ $key ] );
			} else {
				if ( $key == '_thumbnail_id' && ! empty( $data ) ) {
					$image_id              = $data[ 0 ];
					$images                = [];
					$thumbnail             = wp_get_attachment_image_src( $image_id, 'thumbnail' );
					$woocommerce_thumbnail = wp_get_attachment_image_src( $image_id, 'woocommerce_thumbnail' );
					$ftc_450               = wp_get_attachment_image_src( $image_id, 'ftc-450x450-c' );
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

						$metas[ 'images' ] = $images;
					}
				}
			}
		}

		if ( $post->post_type == 'product' ) {
			$product = wc_get_product( $post );
			if ( $product ) {
				$metas[ 'is_on_sale' ]   = $product->is_on_sale();
				$metas[ 'product_type' ] = (string) $product->get_type();
			}
		}

		return $metas;
	}

	public function epiAutosuggestOptions( $options ) {
		$options[ 'link_pattern_callback' ] = 'bl_ep_autosuggest_link_pattern';

		return $options;
	}

	public function epiEnqueueScripts() {
		wp_enqueue_script(
			'epi-elasticpress-autosuggest',
			plugin_dir_url( __DIR__ ) . 'assets/js/search.js',
			[],
			random_int( 1, 900 )
		);

		wp_enqueue_style(
			'epi-elasticpress-autosuggest',
			plugin_dir_url( __DIR__ ) . 'assets/css/search.css',
			[],
			random_int( 1, 900 )
		);
	}
}