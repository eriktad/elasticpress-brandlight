<?php


namespace Brandlight\ElasticPress\extended;


use Brandlight\ElasticPress\Bootstrap;
use ElasticPress\Elasticsearch as Elasticsearch;
use NovemBit\i18n\Module;


class Post extends \ElasticPress\Indexable\Post\Post {


	public $language;

	private $fields_to_translate = [
		'post_title'   => 'text',
		'post_excerpt' => 'text',
		'post_content' => 'html_fragment',
		'permalink'    => 'url',
	];

	public function __construct( $language ) {

		$this->slug .= '-' . $language;

		$this->language = $language;

		parent::__construct();
	}

	private function translate( array $bulk_text ): array {
		$translations = [];

		foreach ( $bulk_text as $type => $texts ) {
			$translations[ $type ] = Module::instance()
				->translation
				->setLanguages( [ $this->language ] )
				->{$type}
				->translate( $texts );
		}

		return $translations;
	}

	private function extract_bulk_text_from_document( array $document, array &$to_translate ): void {
		foreach ( $this->fields_to_translate as $name => $type ) {
			if ( isset( $document[ $name ] ) ) {
				$to_translate[ $type ][] = $document[ $name ];
			}
		}
	}

	private function set_text_translations_to_document( array &$document, array $translations ): void {
		foreach ( $this->fields_to_translate as $name => $type ) {
			if ( isset( $document[ $name ] ) ) {
				$document[ $name ] = $translations[ $type ][ $document[ $name ] ][ $this->language ] ?? $document[ $name ];
			}
		}
	}

	public function bulk_index( $object_ids ) {
		sort( $object_ids );
		$body = '';

		$to_translate = [];
		$use_cache    = count( $object_ids ) > 1;
		$cache_key    = md5( static::class . implode( ',', $object_ids ) );
		$documents    = $use_cache ? Bootstrap::getInstance()->getCachePool()->get( $cache_key ) : null;
		if ( null === $documents ) {
			$documents = [];
			foreach ( $object_ids as $object_id ) {
				$document = $this->prepare_document( $object_id );

				$this->extract_bulk_text_from_document( $document, $to_translate );

				$documents[ $object_id ] = $document;
			}
			if ( $use_cache ) {
				Bootstrap::getInstance()->getCachePool()->set( $cache_key, $documents, 60 * 60 );
			}
		}

		$translations = $this->translate( $to_translate );

		foreach ( $documents as $object_id => $document ) {
			$action_args = [
				'index' => [
					'_id' => absint( $object_id ),
				],
			];

			$this->set_text_translations_to_document( $document, $translations );

			$body .= wp_json_encode( apply_filters( 'ep_bulk_index_action_args', $action_args, $document ) ) . "\n";
			$body .= addcslashes( wp_json_encode( $document ), "\n" );

			$body .= "\n\n";
		}

		return Elasticsearch::factory()->bulk_index( $this->get_index_name(), $this->slug, $body );
	}

	public function index( $object_id, $blocking = false ) {
		$document     = $this->prepare_document( $object_id );
		$to_translate = [];
		$this->extract_bulk_text_from_document( $document, $to_translate );
		$translations = $this->translate( $to_translate );
		$this->set_text_translations_to_document( $document, $translations );

		if ( false === $document ) {
			return false;
		}

		/**
		 * Conditionally kill indexing on a specific object
		 *
		 * @hook   ep_{indexable_slug}_index_kill
		 *
		 * @param  {bool} $kill True to not index
		 * @param  {int} $object_id Id of object to index
		 *
		 * @return {bool}  New kill value
		 * @since  3.0
		 */
		if ( apply_filters( 'ep_' . $this->slug . '_index_kill', false, $object_id ) ) {
			return false;
		}

		/**
		 * Filter document before index
		 *
		 * @hook   ep_pre_index_{indexable_slug}
		 *
		 * @param  {array} $document Document to index
		 *
		 * @return {array} New document
		 * @since  3.0
		 */
		$document = apply_filters( 'ep_pre_index_' . $this->slug, $document );

		$return = Elasticsearch::factory()->index_document( $this->get_index_name(), $this->slug, $document, $blocking );

		/**
		 * Fires after document is indexed
		 *
		 * @hook   ep_after_index_{indexable_slug}
		 *
		 * @param  {array} $document Document to index
		 * @param  {array|boolean} $return ES response on success, false on failure
		 *
		 * @since  3.0
		 */
		do_action( 'ep_after_index_' . $this->slug, $document, $return );

		return $return;
	}
}