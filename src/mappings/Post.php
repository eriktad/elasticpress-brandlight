<?php


namespace Brandlight\ElasticPress\mappings;

use NovemBit\CCA\wp\PluginComponent;

class Post extends PluginComponent {

	public function main( ?array $params = [] ): void {
		add_filter( 'ep_post_mapping', [ $this, 'changePostMapping' ], 10, 1 );
	}

	public function changePostMapping( $mapping ) {
		$mapping[ 'settings' ][ 'index.max_docvalue_fields_search' ]                          = 200;
		$mapping[ 'settings' ][ 'index.number_of_shards' ]                                    = 3;
		$mapping[ 'settings' ][ 'index.mapping.total_fields.limit' ]                          = 15000;
		$mapping[ 'settings' ][ 'index.max_result_window' ]                                   = 2000000;
		$mapping[ 'mappings' ][ 'properties' ][ 'hierarchical_taxonomies' ]                   = [ 'type' => 'object' ];
		$mapping[ 'mappings' ][ 'dynamic_templates' ][][ 'template_hierarchical_taxonomies' ] = [
			'path_match' => 'hierarchical_taxonomies.*',
			'mapping'    => [
				'type'       => 'object',
				'path'       => 'full',
				'properties' => [
					'name'             => [
						'type'   => 'text',
						'fields' => [
							'raw'      => [
								'type' => 'keyword',
							],
							'sortable' => [
								'type'       => 'keyword',
								'normalizer' => 'lowerasciinormalizer',
							],
						],
					],
					'term_id'          => [
						'type' => 'long',
					],
					'term_taxonomy_id' => [
						'type' => 'long',
					],
					'parent'           => [
						'type' => 'long',
					],
					"mapping"          => [
						'type' => 'keyword',
					],
					'slug'             => [
						'type' => 'keyword',
					],
					'term_order'       => [
						'type' => 'long',
					],
				],
			],
		];

		unset( $mapping[ 'mappings' ][ 'dynamic_templates' ][ 'template_meta_types' ][ 'mapping' ][ 'properties' ][ 'date' ] );
		unset( $mapping[ 'mappings' ][ 'dynamic_templates' ][ 'template_meta_types' ][ 'mapping' ][ 'properties' ][ 'datetime' ] );
		unset( $mapping[ 'mappings' ][ 'dynamic_templates' ][ 'template_meta_types' ][ 'mapping' ][ 'properties' ][ 'time' ] );

		return $mapping;
	}
}
