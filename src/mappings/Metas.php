<?php


namespace Brandlight\ElasticPress\mappings;


trait Metas {

	public static $white_list_metas = [
		'grade',
		'_thumbnail_id',
		'_sku',
		'_stock_status',
	];

	public static $stock_status = [
		'instock'     => 'In Stock',
		'outofstock'  => 'Out of Stock',
		'onbackorder' => 'On Backorder',
	];

}