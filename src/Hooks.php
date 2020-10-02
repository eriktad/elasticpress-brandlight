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

	/**
	 * @param array|null $params
	 */
	public function main( ?array $params = [] ): void {
		add_action( 'init', [ $this, 'addTranslationsToIndexable' ], 11 );
		add_action( 'ep_post_sync_args', [ $this, 'changePostStructureToIndex' ], 10, 2 );
		add_filter( 'ep_prepared_post_meta', [ $this, 'whiteListMetas' ], 10, 2 );
		add_filter( 'ep_allow_post_content_filtered_index', function () {
			return false;
		} );
		add_action( 'toplevel_page_elasticpress', [ $this, 'adminFooter', ], 9 );
		add_filter( 'cron_schedules', [ $this, 'reindexScheduleInterval' ] );
		if ( ! wp_next_scheduled( 'epi_reindex_interval' ) ) {
			wp_schedule_event( time(), 'every_tree_minutes', 'epi_reindex_interval' );
		}
		add_action( 'epi_reindex_interval', [ $this, 'runCLIReindex' ] );
	}

	/**
	 * Create new indices for translations
	 */
	public function addTranslationsToIndexable() {
		$languages         = Module::instance()->localization->getAcceptLanguages();
		$original_language = Module::instance()->localization->getFromLanguage();
		unset( $languages[ array_search( $original_language, $languages ) ] );
		foreach ( $languages as $language ) {
			$indexable_post = new Post( $language );
			Indexables::factory()->register( $indexable_post );
		}
	}

	/**
	 * Create hierarchical taxonomies tree for index
	 *
	 * @param array $post_args
	 * @param       $post_id
	 *
	 * @return mixed
	 */
	public function changePostStructureToIndex( $post_args, $post_id ) {
		$terms_hierarchy = PostTermsHierarchyBuilder::get( $post_id );

		$post_args[ 'hierarchical_taxonomies' ] = $terms_hierarchy;
		$post_args[ 'permalink' ]               = wp_make_link_relative( $post_args[ 'permalink' ] );
		$post_args[ 'post_content' ]            = strip_shortcodes( strip_tags( $post_args[ 'post_content' ] ) );
		$post_args[ 'terms' ]                   = $this->addTermsRelativeUrls( $post_args[ 'terms' ] );

		return $post_args;
	}

	public function addTermsRelativeUrls( $terms ) {
		if ( ! empty( $terms ) ) {
			foreach ( $terms as $tax_name => &$taxonomy ) {
				if ( ! empty( $taxonomy ) ) {
					foreach ( $taxonomy as &$term ) {
						$term[ 'url' ] = wp_make_link_relative( get_term_link( $term[ 'term_id' ], $tax_name ) );
					}
				}
			}
		}

		return $terms;
	}

	/**
	 * Remove unusable metas from index and update some metas structure for our need
	 *
	 * @param $metas
	 * @param $post
	 *
	 * @return mixed
	 */
	public function whiteListMetas( $metas, $post ) {
		foreach ( $metas as $key => $data ) {
			if ( ! in_array( $key, self::$white_list_metas, true ) ) {
				unset( $metas[ $key ] );
			} else {
				if ( ! empty( $data ) ) {
					//generate image urls
					if ( $key == '_thumbnail_id' ) {
						$metas[ 'images' ] = CustomMetasBuilder::generateImagesData( $data[ 0 ] );
					}
					//generate stock status with readable name
					if ( $key == '_stock_status' ) {
						$metas[ '_stock_status' ] = CustomMetasBuilder::generateStockStatusWithReadableName( $data[ 0 ] );
					}
					//set grade default value
					if ( $key == 'grade' ) {
						$metas[ $key ][ 0 ] = min( (float) $data[ 0 ], 5.0 );
					}
				}
			}
		}
		//generate product metas for shop ui
		if ( $post->post_type == 'product' ) {
			$product       = wc_get_product( $post );
			$product_metas = CustomMetasBuilder::generateProductMetas( $product );
			if ( $product_metas ) {
				foreach ( $product_metas as $key => $value ) {
					$metas[ $key ] = $value;
				}
			}

			$uids            = CustomMetasBuilder::generateUids( $post );
			$metas[ 'uids' ] = $uids;
		}


		return $metas;
	}

	/**
	 * Add reindex trigger button
	 */
	public function adminFooter() {
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

	/**
	 * Trigger cron job for reindex
	 *
	 * @return bool
	 */
	public function triggerCronJob() {
		$option_name = Plugin::instance()->getName() . '_reindex_in_progress';
		$job_status  = get_option( $option_name );
		if ( $job_status == self::REINDEX_IN_PROGRESS ) {
			return false;
		}

		return update_option( $option_name, self::REINDEX_TRIGGERED );
	}

	/**
	 * reindex cron interval
	 *
	 * @param $schedules
	 *
	 * @return mixed
	 */
	public function reindexScheduleInterval( $schedules ) {
		$schedules[ 'every_tree_minutes' ] = [
			'interval' => 180,
			'display'  => esc_html__( 'Every tree minutes' ),
		];

		return $schedules;
	}

	/**
	 * wp cron reindex ( run cli commands )
	 */
	public function runCLIReindex() {
		$option_in_progress = Plugin::instance()->getName() . '_reindex_in_progress';
		$job_status         = get_option( $option_in_progress );
		$wp_cli_exist       = shell_exec( 'command -v wp' ) ? true : false;

		if ( $job_status == self::REINDEX_TRIGGERED && $wp_cli_exist ) {
			update_option( $option_in_progress, self::REINDEX_IN_PROGRESS );
			$executed = exec( 'wp elasticpress put-mapping && wp elasticpress index' );
			if ( $executed != null ) {
				update_option( $option_in_progress, self::REINDEX_SUCCESS );
			} else {
				update_option( $option_in_progress, self::REINDEX_FAILED );
			}
		}
	}
}