<?php

class UXI_Element {

	public $id;
	public $layout_section;
	public $element_type;
	public $parent_id;
	public $atts = array();

	public function __construct($dom, $domNode, $layout_section, $element_type, $parent_id = false) {
		$this->id = bin2hex(random_bytes(8));
		$this->layout_section = $layout_section;
		$this->element_type = $element_type;
		$this->parent_id = $parent_id;
		$this->doAttributes($domNode);
		$this->doSizes();
		$this->doContent($dom, $domNode);
	}

	function doAttributes($domNode) {
		if ($domNode->hasAttributes()) {
			foreach($domNode->attributes as $attr) {
				switch($attr->name) {
					case 'class':
						$this->atts[$attr->name] = explode(' ', $attr->value);
						break;
					default:
						$this->atts[$attr->name] = $attr->value;
						break;
				}
			}
		}
	}

	function doSizes() {
		switch($this->element_type) {
			case 'column':
			case 'column_nested':
				break;
			default:
				return;
		}

		$sizes = array();
		foreach($this->atts['class'] as $class) {
			if (strpos($class, 'grid-') < 0){
				continue;
			}
			$grid_size = str_replace('grid-', '', $class);
			$size_params = explode('-', $grid_size);

			$viewport = $size_params[0];
			$column_width = (intval($size_params[1]) / 12) * 100;
			$column_width_2_decimal_places = bcdiv($column_width, 1, 2);
			$column_width_float = floatval($column_width_2_decimal_places);

			$sizes[$viewport] = $column_width_float;
		}

		$this->sizes = $sizes;
	}

	function doContent($dom, $domNode) {
		switch($this->element_type) {
			case 'widget':
			case 'widget_nested':
				break;
			default:
				return;
		}

		require_once(plugin_dir_path(__FILE__) . 'class-uxi-parse-query.php');

		$widget_query = new UXI_Parse_Query('//*[@class="content"]/*');
		
		$this->html = $widget_query->query_html($dom);
	}
}