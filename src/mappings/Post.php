<?php


namespace Brandlight\ElasticPress\mappings;


class Post {

	/**
	 * @var Mappings
	 */
	public $parent;
	public function __construct( $parent ) {
		$this->parent = $parent;
		add_filter( 'ep_post_mapping', [ $this, 'changePostMapping' ], 10, 1 );
	}

	public function changePostMapping( $mapping ) {
		$mapping[ 'settings' ][ 'index.max_docvalue_fields_search' ] = 300;
		$mapping[ 'settings' ][ 'index.mapping.total_fields.limit' ] = 200000;
		$mapping[ 'settings' ][ 'index.max_result_window' ]          = 3000000;

		return $mapping;
	}
}
