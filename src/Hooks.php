<?php


namespace Brandlight\ElasticPress;


use Brandlight\ElasticPress\extended\Post;
use Brandlight\ElasticPress\helpers\CustomMetasBuilder;
use Brandlight\ElasticPress\mappings\Metas;
use Brandlight\ElasticPress\helpers\PostTermsHierarchyBuilder;
use ElasticPress\Indexables;
use NovemBit\CCA\wp\PluginComponent;
use NovemBit\i18n\Module;

class Hooks extends PluginComponent {
	use Metas;

	public const REINDEX_TRIGGERED   = 1;
	public const REINDEX_IN_PROGRESS = 2;
	public const REINDEX_SUCCESS     = 3;
	public const REINDEX_FAILED      = 4;

	public function main( ?array $params = [] ): void {
		add_action( 'init', [ $this, 'epiInit' ], 11 );
		add_action( 'ep_post_sync_args', [ $this, 'epiAddHierarchicalTaxonomies' ], 10, 2 );
		add_filter( 'ep_prepared_post_meta', [ $this, 'epiWhiteListMetas' ], 10, 2 );
		add_filter( 'ep_autosuggest_options', [ $this, 'epiAutosuggestOptions' ] );
		add_filter( 'ep_allow_post_content_filtered_index', function () {
			return false;
		} );
		add_action(
			'wp_enqueue_scripts', [ $this, 'epiEnqueueScripts' ],
			10
		);

		add_action( 'toplevel_page_elasticpress', [
			$this,
			'epiAdminFooter',
		], 9 );
		add_filter( 'cron_schedules', [ $this, 'reindexScheduleInterval' ] );
		if ( ! wp_next_scheduled( 'epi_reindex_interval' ) ) {
			wp_schedule_event( time(), 'every_tree_minutes', 'epi_reindex_interval' );
		}
		add_action( 'epi_reindex_interval', [ $this, 'runCLIReindex' ] );
	}

	public function epiInit() {
		$languages         = Module::instance()->localization->getAcceptLanguages();
		$original_language = Module::instance()->localization->getFromLanguage();
		unset( $languages[ array_search( $original_language, $languages ) ] );
		foreach ( $languages as $language ) {
			$indexable_post = new Post( $language );
//			$indexable_term = new Term( $language );

			Indexables::factory()->register( $indexable_post );
//			Indexables::factory()->register( $indexable_term );
		}
	}

	public function epiAddHierarchicalTaxonomies( $post_args, $post_id ) {
		$terms_hierarchy                        = PostTermsHierarchyBuilder::get( $post_id );
		$post_args[ 'hierarchical_taxonomies' ] = $terms_hierarchy;

		return $post_args;
	}

	public function epiWhiteListMetas( $metas, $post ) {
		foreach ( $metas as $key => $data ) {
			if ( ! in_array( $key, self::$white_list_metas, true ) ) {
				unset( $metas[ $key ] );
			} else {
				if ( ! empty( $data ) ) {
					if ( $key == '_thumbnail_id' ) {
						$metas[ 'images' ] = CustomMetasBuilder::generateImagesData( $data[ 0 ] );
					}
					if ( $key == '_stock_status' ) {
						$metas[ '_stock_status' ] =  CustomMetasBuilder::generateStockStatusWithReadableName( $data[ 0 ] );
					}
					if ( $key == 'grade' ) {
						$metas[ $key ][ 0 ] = min( (float) $data[ 0 ], 5.0 );
					}
				}
			}
		}

		if ( $post->post_type == 'product' ) {
			$product = wc_get_product( $post );
			$product_metas = CustomMetasBuilder::generateProductMetas( $product );
			if( $product_metas ){
			    foreach ( $product_metas as $key => $value ){
			        $metas[ $key ] = $value;
                }
            }
		}

		return $metas;
	}



	public function epiAutosuggestOptions( $options ) {
		$options[ 'link_pattern_callback' ] = 'bl_ep_autosuggest_link_pattern';

		return $options;
	}

	public function epiAdminFooter() {
		if ( wp_verify_nonce( $_POST[ Plugin::instance()->getName() ] ?? null, 'reindex' ) ) {
			if ( $this->triggerCronJob() ) {
				echo 'Reindex triggered';
			} else {
				echo 'Reindex already in progress';
			}
		}
		?>
        <form method="post">
			<?php echo wp_nonce_field( 'reindex', Plugin::instance()->getName() ); ?>
            <button type="submit" class="button">
                <a class="dashicons dashicons-update"></a> Trigger reindex
            </button>
        </form>
		<?php
	}

	public function triggerCronJob() {
		$option_name = Plugin::instance()->getName() . '_reindex_in_progress';
		$job_status  = get_option( $option_name );
		if ( $job_status == self::REINDEX_IN_PROGRESS ) {
			return false;
		}

		return update_option( $option_name, self::REINDEX_TRIGGERED );
	}

	public function reindexScheduleInterval( $schedules ) {
		$schedules[ 'every_tree_minutes' ] = [
			'interval' => 180,
			'display'  => esc_html__( 'Every tree minutes' ),
		];

		return $schedules;
	}

	public function runCLIReindex() {
		$option_in_progress = Plugin::instance()->getName() . '_reindex_in_progress';
		$job_status         = get_option( $option_in_progress );
		$wp_cli_exist       = shell_exec( 'command -v wp' ) ? true : false;

		if ( $job_status == self::REINDEX_TRIGGERED && $wp_cli_exist ) {
			update_option( $option_in_progress, self::REINDEX_IN_PROGRESS );
			$executed = exec( 'wp elasticpress put-mapping && wp elasticpress index' );
			update_option( 'asdqwe', $executed );
			if ( $executed != null ) {
				update_option( $option_in_progress, self::REINDEX_SUCCESS );
			} else {
				update_option( $option_in_progress, self::REINDEX_FAILED );
			}
		}
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