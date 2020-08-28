<?php

namespace Brandlight\ElasticPress;


use Brandlight\ElasticPress\mappings\Mappings;
use Cache\Adapter\Filesystem\FilesystemCachePool;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use Psr\SimpleCache\CacheInterface;

class Bootstrap {
	/**
	 * @var Hooks
	 */
	public $hooks;

	/**
	 * @var Mappings
	 */
	public $mappings;

	private $cache_pool;

	private function __construct() {

		$filesystemAdapter = new Local( sys_get_temp_dir() . '/' . md5( self::class ) );
		$filesystem        = new Filesystem( $filesystemAdapter );
		$this->cache_pool  = new FilesystemCachePool( $filesystem );
		$this->hooks       = new Hooks( $this );
		$this->mappings    = new Mappings( $this );
	}

	private static $instance;

	public static function getInstance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function getCachePool(): CacheInterface {
		return $this->cache_pool;
	}
}
