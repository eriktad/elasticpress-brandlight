<?php


namespace Brandlight\ElasticPress\mappings;

use ElasticPress\Elasticsearch;
use NovemBit\CCA\wp\PluginComponent;

class Post extends PluginComponent {

	public function main( ?array $params = [] ): void {
		add_filter( 'ep_post_mapping', [ $this, 'changePostMapping' ], 10, 1 );
	}

	public function changePostMapping( $mapping ) {
		$mapping[ 'settings' ][ 'index.max_docvalue_fields_search' ]        = 200;
		$mapping[ 'settings' ][ 'index.number_of_shards' ]                  = 3;
		$mapping[ 'settings' ][ 'index.mapping.total_fields.limit' ]        = 15000;
		$mapping[ 'settings' ][ 'index.max_result_window' ]                 = 2000000;
		$mapping[ 'mappings' ][ 'properties' ][ 'hierarchical_taxonomies' ] = [ 'type' => 'object' ];
		$text_type                                                          = $mapping[ 'mappings' ][ 'properties' ][ 'post_content' ][ 'type' ];
		foreach ( $mapping[ 'mappings' ][ 'dynamic_templates' ] as &$template ) {
			if ( array_key_first( $template ) == 'template_terms' ) {
				$template[ 'template_terms' ][ 'mapping' ][ 'properties' ][ 'url' ]                           = [ 'type' => 'text' ];
				$template[ 'template_terms' ][ 'mapping' ][ 'properties' ][ 'name' ][ 'fields' ][ 'suggest' ] = [
					'type'            => $text_type,
					'analyzer'        => 'edge_ngram_analyzer',
					'search_analyzer' => 'standard',
				];
			}
		}
		$mapping[ 'mappings' ][ 'properties' ][ 'post_content' ][ 'fields' ][ 'suggest' ] = [
			'type'            => $text_type,
			'analyzer'        => 'edge_ngram_analyzer',
			'search_analyzer' => 'standard',
		];

		return $mapping;
	}
}
