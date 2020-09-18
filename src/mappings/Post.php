<?php


namespace Brandlight\ElasticPress\mappings;

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

		return $mapping;
	}
}
