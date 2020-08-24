<?php

namespace Brandlight\ElasticPress;


use Brandlight\ElasticPress\mappings\Mappings;

class Bootstrap {
	public $hooks;
	public $mappings;

	private function __construct() {
		$this->hooks = new Hooks( $this );
		$this->mappings = new Mappings( $this );
	}

	private static $instance;

	public static function getInstance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
