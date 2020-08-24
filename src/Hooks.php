<?php


namespace Brandlight\ElasticPress;


use Brandlight\ElasticPress\extended\Post;
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
		add_filter( 'ep_post_sync_args_multilingual_post_prepare_meta', [
			$this,
			'epiMultilingualPostPrepareMeta',
		], 10, 3 );
		add_filter( 'ep_prepared_post_meta', [ $this, 'epiWhiteListMetas' ], 10, 1 );
		add_filter( 'ep_autosuggest_options', [ $this, 'epiAutosuggestOptions' ] );
		add_action(
			'wp_enqueue_scripts', [ $this, 'epiEnqueueScripts' ],
			10
		);
	}

	public function epiInit() {
		$languages = Module::instance()->localization->getAcceptLanguages();
		$original_language = Module::instance()->localization->getFromLanguage();
		unset( $languages[ array_search( $original_language,$languages ) ] );
		foreach ( $languages as $language ) {
			$indexable_posts = new Post( $language );

//			$indexable_terms = new \ElasticPress\Indexable\Term\Term();

			Indexables::factory()->register( $indexable_posts );

//			Indexables::factory()->register( $indexable_terms );

		}
	}

	public function epiMultilingualPostPrepareMeta( $args, $language, $post_id ) {
		$to_translate = [];
		$translations = [];

		$_fields = [
			'post_title'            => 'text',
			'post_excerpt'          => 'text',
			'post_content'          => 'html',
			'post_content_filtered' => 'html',
			'permalink'             => 'url',
		];

		foreach ( $_fields as $name => $type ) {
			if ( isset( $args[ $name ] ) ) {
				$to_translate[ $type ] = $args[ $name ];
			}
		}

		foreach ( $to_translate as $type => $texts ) {
			$translations[ $type ] = Module::instance()
				->translation
				->setLanguages( [ $language ] )
				->{$type}
				->translate( [ $texts ] );
		}
		foreach ( $_fields as $name => $type ) {
			if ( isset( $args[ $name ] ) ) {
				$args[ $name ] = $translations[ $type ][ $args[ $name ] ][ $language ] ?? $args[ $name ];
			}
		}

		return $args;
	}

	public function epiWhiteListMetas( $metas ) {
		foreach ( $metas as $key => $data ) {
			if ( ! in_array( $key, $this->white_list_metas, true ) ) {
				unset( $metas[ $key ] );
			} else {
				if ( $key == '_thumbnail_id' && ! empty( $data ) ) {
					$image_id                          = $data[ 0 ];
					$images                            = [];
					$images[ 'thumbnail' ]             = wp_get_attachment_image_url( $image_id, 'thumbnail' );
					$images[ 'woocommerce_thumbnail' ] = wp_get_attachment_image_url( $image_id, 'woocommerce_thumbnail' );
					$images[ 'ftc-450x450-c' ]         = wp_get_attachment_image_url( $image_id, 'ftc-450x450-c' );
					if ( $images[ 'thumbnail' ] && $images[ 'woocommerce_thumbnail' ] && $images[ 'ftc-450x450-c' ] ) {
						$metas[ 'images' ] = $images;
					}
				}
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