<?php


namespace Brandlight\ElasticPress\helpers;


class PostTermsHierarchyBuilder {

	private static $tax_objects = [];

	/**
	 * Generate slugs hierarchy.
	 *
	 * @param string  $slug      Current slug
	 * @param WP_Term $term      The term object
	 * @param array   $terms     Terms map array
	 * @param string  $separator Separator to use
	 *
	 * @return string
	 */
	private static function generateSlugHierarchy( &$slug, $term, $terms, $separator ) {
		if ( $term->parent ) {
			$slug = $terms[ $term->parent ]->slug . $separator . $slug;
			self::generateSlugHierarchy( $slug, $terms[ $term->parent ], $terms, $separator );
		}

		return $slug;
	}

	/**
	 * @param array  $terms     Terms array
	 * @param string $separator Separator to use
	 *
	 * @return array
	 */
	private static function getTermsHierarchy( array $terms, $separator ) {
		$newTerms = [];
		foreach ( $terms as $term ) {
			$newTerms[ $term->term_id ] = $term;
		}

		foreach ( $terms as $term ) {
			if ( $term->parent ) {
				$ancestors = get_ancestors( $term->term_id, $term->taxonomy, 'taxonomy' );
				foreach ( $ancestors as $ancestor_term_id ) {
					if ( ! isset( $newTerms[ $ancestor_term_id ] ) ) {
						$newTerms[ $ancestor_term_id ] = get_term( $ancestor_term_id, $term->taxonomy );
					}
				}
			}
		}

		$hierarchy = [];
		foreach ( $newTerms as $term ) {
			$hierarchy[] = join(
				$separator,
				array_unique(
					explode( $separator, self::generateSlugHierarchy( $term->slug, $term, $newTerms, $separator ) )
				)
			);
		}

		return $hierarchy;
	}

	/**
	 * Adds taxonomy data to post object for indexing
	 *
	 * @param int|string|WP_Post $post      The post instance or ID
	 * @param string             $separator Optional: Separator to use
	 *
	 * @return array
	 */
	public static function get( $post, $separator = '>' ) {
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
				$taxonomies_hierarchy[ $taxonomy->name ] = self::getTermsHierarchy( $terms, $separator );
			}
		}

		return $taxonomies_hierarchy;
	}
}