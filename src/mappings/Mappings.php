<?php


namespace Brandlight\ElasticPress\mappings;


use Brandlight\ElasticPress\Bootstrap;

class Mappings {

	/**
	 * @var Bootstrap
	 */
	public $parent;

	public $post;

	public function __construct( $parent ) {
		$this->parent = $parent;
		$this->post = new Post( $this );
	}

}