<?php


namespace Brandlight\ElasticPress\extended;


use Brandlight\ElasticPress\Plugin;
use ElasticPress\Elasticsearch as Elasticsearch;
use NovemBit\i18n\Module;

class Term extends \ElasticPress\Indexable\Term\Term {

	public $language;

	public function __construct( $language ) {

		$this->slug .= '-' . $language;

		$this->language = $language;

		parent::__construct();
	}

	public function bulk_index( $object_ids ) {
		sort( $object_ids );
		$body = '';

		$to_translate = [];
		$translations = [];

		$_fields = [
			'name'   => 'text'
		];

		$cache_key = md5( static::class . implode( ',', $object_ids ) );
		$documents = Plugin::getInstance()->getCachePool()->get( $cache_key );
		if ( null === $documents ) {
			$documents = [];
			foreach ( $object_ids as $object_id ) {
				$document = $this->prepare_document( $object_id );

				foreach ( $_fields as $name => $type ) {
					if ( isset( $document[ $name ] ) ) {
						$to_translate[ $type ][] = $document[ $name ];
					}
				}

				$documents[ $object_id ] = $document;
			}
			Plugin::getInstance()->getCachePool()->set( $cache_key, $documents, 60 * 60 );
		}

		foreach ( $to_translate as $type => $texts ) {
			$translations[ $type ] = Module::instance()
				->translation
				->setLanguages( [ $this->language ] )
				->{$type}
				->translate( $texts );
		}

		foreach ( $documents as $object_id => $document ) {
			$action_args = [
				'index' => [
					'_id' => absint( $object_id ),
				],
			];

			foreach ( $_fields as $name => $type ) {
				if ( isset( $document[ $name ] ) ) {
					$document[ $name ] = $translations[ $type ][ $document[ $name ] ][ $this->language ] ?? $document[ $name ];
				}
			}

			$body .= wp_json_encode( apply_filters( 'ep_bulk_index_action_args', $action_args, $document ) ) . "\n";
			$body .= addcslashes( wp_json_encode( $document ), "\n" );

			$body .= "\n\n";
		}

		return Elasticsearch::factory()->bulk_index( $this->get_index_name(), $this->slug, $body );
	}
}