<?php


namespace Brandlight\ElasticPress\extended;


class Post extends \ElasticPress\Indexable\Post\Post {


	public $language;

	public function __construct( $language ) {

		$this->slug .= '-' . $language;

		$this->language = $language;

		parent::__construct();
	}

	public function prepare_document( $post_id ) {

		$args = parent::prepare_document( $post_id );

		return apply_filters( 'ep_post_sync_args_multilingual_post_prepare_meta', $args, $this->language, $post_id );
	}
}