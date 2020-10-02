<?php
/**
 * Plugin Name: ElasticPress Brandlight Add-on
 * Description: A fast and flexible search and query engine for WordPress.
 * Version:     1.1.0
 * Author:      Erik | Novembit
 * License:     GPLv2 or later
 * Text Domain: elasticpress
 * Domain Path: /lang/
 *
 * @package  elasticpress
 */

include __DIR__ . '/vendor/autoload.php';

define( 'EP_DASHBOARD_SYNC', false );

\Brandlight\ElasticPress\Plugin::instance( __FILE__ );
