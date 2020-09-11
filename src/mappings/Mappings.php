<?php


namespace Brandlight\ElasticPress\mappings;

use NovemBit\CCA\wp\PluginComponent;

/**
 * Class Mappings
 *
 * @package Brandlight\ElasticPress\mappings
 *
 * @property Post $post
 */
class Mappings extends PluginComponent {

	public $components = [
		'post' => Post::class
	];

	public function main( ?array $params = [] ): void {
		// TODO: Implement main() method.
	}
}