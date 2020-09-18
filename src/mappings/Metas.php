<?php


namespace Brandlight\ElasticPress\mappings;


trait Metas {

	public static $stock_status = [
		'instock'     => 'In Stock',
		'outofstock'  => 'Out of Stock',
		'onbackorder' => 'On Backorder',
	];

	public static $white_list_metas = [
		'uid_barcodes',
		'uid_gtins',
		'uid_mfns',
		'uid_nav_item_ids',
		'uid_tc_last_update',
		'uid_vendor_item_ids',
		'uid_viart_ids',
		'grade',
		'_thumbnail_id',
		'_product_attributes',
		'_wpb_vc_js_status',
		'_swatch_type',
		'total_sales',
		'_downloadable',
		'_virtual',
		'_tax_status',
		'_tax_class',
		'_purchase_note',
		'_featured',
		'_weight',
		'_length',
		'_width',
		'_height',
		'_visibility',
		'_sku',
		'_sold_individually',
		'_manage_stock',
		'_backorders',
		'_stock',
		'_upsell_ids',
		'_crosssell_ids',
		'_stock_status',
		'_product_version',
		'_product_tabs',
		'_override_tab_layout',
		'_customer_user',
//		'_variable_billing',
		'_product_image_gallery',
		'_bj_lazy_load_skip_post',
		'_default_attributes',
		'_swatch_type_options',
	];
}