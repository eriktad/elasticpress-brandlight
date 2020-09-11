<?php
/**
 * Plugin Name: ElasticPress Integration
 * Description: A fast and flexible search and query engine for WordPress.
 * Version:     3.4.3
 * Author:      Brandlight
 * License:     GPLv2 or later
 * Text Domain: elasticpress
 * Domain Path: /lang/
 *
 * @package  elasticpress
 */

include __DIR__ . '/vendor/autoload.php';

define( 'EP_DASHBOARD_SYNC', false );

\Brandlight\ElasticPress\Plugin::instance( __FILE__ );
