<?php


namespace Brandlight\ElasticPress\helpers;


use Brandlight\ElasticPress\Plugin;

class PostTermsHierarchyBuilder {

	private static $tax_objects = [];

	/**
	 * @param array  $terms       Terms array
	 * @param string $levelPrefix Prefix to use
	 *
	 * @return array
	 */
	private static function getTermsHierarchy( array $terms, $levelPrefix ) {
		$newTerms = [];
		foreach ( $terms as $term ) {
			$newTerms[ $term->term_id ] = $term;
		}

		foreach ( $terms as $term ) {
			if ( $term->parent ) {
				$ancestors = get_ancestors( $term->term_id, $term->taxonomy, 'taxonomy' );
				foreach ( $ancestors as $depth => $ancestor ) {
					if ( ! isset( $newTerms[ $ancestor ] ) ) {
						$newTerms[ $ancestor ]        = get_term( $ancestor, $term->taxonomy );
						$newTerms[ $ancestor ]->depth = $depth;
					}
				}
				$newTerms[ $term->term_id ]->depth = count( $ancestors );
			} else {
				$newTerms[ $term->term_id ]->depth = 0;
			}
		}

		$data = [];
		foreach ( $newTerms as $term ) {
			$levelKey = $levelPrefix . $term->depth;
			if ( ! isset( $data[ $levelKey ] ) ) {
				$data[ $levelKey ] = [];
			}
			$data[ $levelKey ][] = [
				"term_id"          => $term->term_id,
				"slug"             => $term->slug,
				"name"             => $term->name,
				"mapping"          => $term->slug . "{#}" . $term->name . "{#}" . ( $term->parent ? $newTerms[ $term->parent ]->slug : '' ),
				"parent"           => $term->parent,
				"term_taxonomy_id" => $term->term_taxonomy_id,
				"term_order"       => $term->term_order,
			];
		}

		return $data;
	}

	/**
	 * Adds taxonomy data to post object for indexing
	 *
	 * @param int|string|WP_Post $post        The post instance or ID
	 * @param string             $levelPrefix Optional: Prefix to use
	 *
	 * @return array
	 */
	public static function get( $post, $levelPrefix = 'level' ) {
		$cache_key = '';
		if ( gettype( $post ) == 'object' ) {
			$cache_key = md5( static::class . $post->ID );
		} else {
			$cache_key = md5( static::class . $post );
		}
		$taxonomies_hierarchy = Plugin::instance()->getCachePool()->get( $cache_key );
		if ( null == $taxonomies_hierarchy ) {
			$post = get_post( $post );

			// Push all taxonomies by default, including custom ones.
			if ( ! isset( self::$tax_objects[ $post->post_type ] ) ) {
				self::$tax_objects[ $post->post_type ] = get_object_taxonomies( $post->post_type, 'objects' );
			}

			$taxonomies_hierarchy = [];
			foreach ( self::$tax_objects[ $post->post_type ] as $taxonomy ) {
				if ( ! $taxonomy->hierarchical ) {
					continue;
				}

				$terms = wp_get_object_terms( $post->ID, $taxonomy->name );
				if ( ! is_wp_error( $terms ) ) {
					$taxonomies_hierarchy[ $taxonomy->name ] = self::getTermsHierarchy( $terms, $levelPrefix );
				}
			}

			Plugin::instance()->getCachePool()->set( $cache_key, $taxonomies_hierarchy, 60 * 60 );
		}

		return $taxonomies_hierarchy;
	}
}