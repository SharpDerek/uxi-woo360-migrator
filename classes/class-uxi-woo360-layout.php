<?php
require_once(plugin_dir_path(__FILE__) . 'class-uxi-files-handler.php');
require_once(plugin_dir_path(__FILE__) . 'class-uxi-common.php');

class UXI_Woo360_Layout {

	public $style;

	public function __construct($post_id, $uxi_layout_styling) {
		if ($uxi_layout_styling) {
			$this->build_post_nodes($post_id, $uxi_layout_styling);
		}
		$this->style = array(
			'url' => get_permalink($post_id),
			'title' => get_the_title($post_id)
		);
	}

	function build_post_nodes($post_id, $uxi_layout_styling) {
		FLBuilderModel::set_post_id($post_id);
		FLBuilderModel::enable();
		FLBuilderModel::delete_post($post_id);
		$element_node_ids = array();
		$auto_col = null;
		foreach($uxi_layout_styling as $element_id => $element) {
			$parent_id = $element['parent_id'];
			switch($element['element_type']) {
				case 'row':
					$row = FLBuilderModel::add_row();
					$element_node_ids[$element_id . '_row'] = $row;

					$auto_col_groups = FLBuilderModel::get_nodes('column-group', $row->node);
					$auto_col_group_node_id = array_keys($auto_col_groups)[0];
					$auto_col_group = $auto_col_groups[$auto_col_group_node_id];

					$element_node_ids[$element_id] = $auto_col_group;

					$auto_cols = FLBuilderModel::get_nodes('column', $auto_col_group->node);
					$auto_col_node_id = array_keys($auto_cols)[0];
					$auto_col = $auto_cols[$auto_col_node_id];
					break;
				case 'row_nested':
					$parent_node = $element_node_ids[$parent_id]->node;
					$row_nested_node = FLBuilderModel::add_col_group($parent_node);
					$element_node_ids[$element_id] = $row_nested_node;

					$auto_cols = FLBuilderModel::get_nodes('column', $row_nested_node->node);
					$auto_col_node_id = array_keys($auto_cols)[0];
					$auto_col = $auto_cols[$auto_col_node_id];
					break;
				case 'column':
				case 'column_nested':
					if (is_null($auto_col)) {
						$parent_node = $element_node_ids[$parent_id]->node;
						$existing_cols = FLBuilderModel::get_nodes('column', $parent_node);
						$column_node = FLBuilderModel::add_col($parent_node, count($existing_cols));
					} else {
						$column_node = $auto_col;
						$auto_col = null;
					}
					$element_node_ids[$element_id] = $column_node;
					break;
				case 'widget':
				case 'widget_nested':
					$parent_node = $element_node_ids[$parent_id]->node;
					$element_node_ids[$element_id] = $this->add_widget_node($element, $parent_node);
					break;
			}
		}

		$this->update_post_node_settings($element_node_ids, $uxi_layout_styling);
	}

	function update_post_node_settings($element_node_ids, $uxi_layout_styling) {
		foreach($uxi_layout_styling as $element_id => $element) {
			$node = $element_node_ids[$element_id];
			switch($element['element_type']) {
				case 'row':
					$row_node = $element_node_ids[$element_id . '_row'];
					FLBuilderModel::save_settings($row_node->node, array(
						'class' => implode(' ', $element['atts']['class'])
					));
				case 'row_nested':
					break;
				case 'column':
				case 'column_nested':
					$column_settings = array(
						'class' => implode(' ', $element['atts']['class']),
						'id' => $element_id
					);

					$element_sizes = $element['sizes'];

					if (array_key_exists('palm', $element_sizes)) {
						$column_settings['size_responsive'] = $element_sizes['palm'];
						$column_settings['size_medium'] 	= $element_sizes['palm'];
						$column_settings['size'] 			= $element_sizes['palm'];
					}

					if (array_key_exists('tab', $element_sizes)) {
						$column_settings['size_medium'] 	= $element_sizes['tab'];
						$column_settings['size'] 			= $element_sizes['tab'];
					}

					if (array_key_exists('desk', $element_sizes)) {
						$column_settings['size'] 			= $element_sizes['desk'];
					}

					FLBuilderModel::save_settings($node->node, $column_settings);
					break;
			}
		}
	}

	function add_widget_node($element, $parent_node) {
		$widget_type = $element['atts']['uxi-widget'];

		$widget_module = null;

		switch($widget_type) {
			// case 'uxi_widget_recent_posts':
			// case 'uxi_widget_archives':
			// case 'widget_categories':
			// case 'uxi_loop':
			// case 'uxi_loop_header':
			// case 'uxi_widget_cta2':
			// case 'uxi_jumbotron2':
			// case 'uxi_widget_button':
			// case 'widget_uxi_navigation':
			// case 'widget_uxi_custom_menu':
			// case 'uxi_google_map':
			// case 'uxi_company_address':
			default:
			case 'uxi_widget_embed':
				$widget_module = $this->add_html_module($element, $parent_node);
				break;
			// case 'uxi_widget_testimonials':
			// case 'uxi_widget_social_2':
			// case 'uxi_payment_icons':
			// case 'uxi_widget_search':
			// case 'uxi_widget_sitemap':
			// case 'uxi_widget_breadcrumbs':
			case 'uxi_gform':
			case 'uxi_widget_wysiwyg_text_area':
				$widget_module = $this->add_rich_text_module($element, $parent_node);
				break;
			// case 'uxi_widget_copyright':
			// case 'uxi_widget_logo':
			case 'widget_uxi_image':
				$widget_module = $this->add_image_module($element, $parent_node);
				break;
			// case 'widget_uxi_gallery':
			// case 'wigdet_uxi_slideshow':
			// case 'uxi_widget_video':
			// case 'uxi_widget_lightbox2':
		}

		return $widget_module;
	}

	function additional_settings($widget_module) {
		$widget_settings = array();
		FLBuilderModel::save_settings($widget_module->node, $widget_settings);

		return $widget_module;
	}

	function add_html_module($element, $parent_node) {
		$widget_settings = array(
			'html' => UXI_Common::filter_html($element['html'])
			//'html' => $element['html']
 		);

 		$html_module = FLBuilderModel::add_module('html', (object)$widget_settings, $parent_node);

 		return $this->additional_settings($html_module);
	}

	function add_rich_text_module($element, $parent_node) {
		$widget_settings = array(
			'text' => UXI_Common::filter_html($element['html'])
			//'text' => $element['html']
 		);

 		$rich_text_module = FLBuilderModel::add_module('rich-text', (object)$widget_settings, $parent_node);

 		return $this->additional_settings($rich_text_module);
	}

	function add_image_module($element, $parent_node) {
		$html = $element['html'];


		$dom = new DOMDocument();
		$encoded_html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
		@$dom->loadHTML($encoded_html);

		$xpath = new DOMXPath($dom);
		$image_query = '//img';
		$image_src = "";

		foreach($xpath->query($image_query) as $image) {
			$src = $image->getAttribute('src');
			$image_src = UXI_Common::media_url_replace($src);
			break;
		}

		$attachment_id = attachment_url_to_postid($image_src);

		$widget_settings = array(
			'html' => "Attachment ID: {$attachment_id}. Image Src: {$image_src}"
		);
		$image_module = FLBuilderModel::add_module('html', (object)$widget_settings, $parent_node);

		return $this->additional_settings($image_module);
	}
}