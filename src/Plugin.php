<?php

namespace Brandlight\ElasticPress;


use Brandlight\ElasticPress\mappings\Mappings;
use Cache\Adapter\Filesystem\FilesystemCachePool;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use Psr\SimpleCache\CacheInterface;

/**
 * Class Plugin
 *
 * @package Brandlight\ElasticPress
 *
 * @property Hooks    $hooks
 * @property Mappings $mappings
 */
class Plugin extends \NovemBit\CCA\wp\Plugin {

	private $cache_pool;

	public $components = [
		'mappings' => Mappings::class,
		'hooks'    => Hooks::class,
	];

	public function getCachePool(): CacheInterface {
		return $this->cache_pool;
	}

	protected function main(): void {
		$filesystemAdapter = new Local( sys_get_temp_dir() . '/' . md5( self::class ) );
		$filesystem        = new Filesystem( $filesystemAdapter );
		$this->cache_pool  = new FilesystemCachePool( $filesystem );

	}

	public function onActivate(): void {
		parent::onActivate();
	}

	public function getName(): string {
		return 'elasticpress-brandlight';
	}
}
