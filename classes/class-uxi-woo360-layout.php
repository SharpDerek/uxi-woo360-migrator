<?php
require_once(plugin_dir_path(__FILE__) . 'class-uxi-files-handler.php');
require_once(plugin_dir_path(__FILE__) . 'class-uxi-common.php');
require_once(plugin_dir_path(__FILE__) . 'class-uxi-module.php');
require_once(plugin_dir_path(__FILE__) . 'class-uxi-style-map.php');

class UXI_Woo360_Layout {

	public $post_id;

	public function __construct($post_id, $uxi_layout_styling) {
		$this->post_id = $post_id;
		if ($uxi_layout_styling) {
			FLBuilderModel::set_post_id($this->post_id);
			FLBuilderModel::enable();
			FLBuilderModel::delete_post($this->post_id);
			$element_node_ids = $this->build_post_nodes($uxi_layout_styling);
		}
	}

	function build_post_nodes($uxi_layout_styling) {
		$element_node_ids = array();
		$auto_col = null;
		foreach($uxi_layout_styling as $element_id => $element) {
			if (!is_array($element)) {
				$element_node_ids[$element_id . '_row'] = $this->add_global_row($element);
				continue;
			}
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
					$widgets = $this->add_widget_node($element, $parent_node);
					foreach($widgets as $index => $widget) {
						$element_node_ids[$element_id . '_' . $index] = $widget;
					}
					break;
			}
		}

		$this->update_post_node_settings($element_node_ids, $uxi_layout_styling);

		return $element_node_ids;
	}

	function add_global_row($global_row_data_id) {
		$global_row_data_id = intval($global_row_data_id);

		$global_row = UXI_Common::get_global_row($global_row_data_id);

		if (!$global_row) { // No global row post found, need to create
			$compiled = json_decode(UXI_Files_Handler::get_file('uxi-compiled.json'), true);
			$global_row_styling = $compiled['global_modules'][$global_row_data_id]['elements'];
			$global_row_settings = array(
				"name" => "Global Row #{$global_row_data_id}",
				"global" => true
			);

			$element_node_ids = $this->build_post_nodes($global_row_styling);
			$row_node = $element_node_ids[array_keys($element_node_ids)[0]];

			$global_row_data = FLBuilderModel::save_node_template($row_node->node, $global_row_settings);
			$global_row = $global_row_data['postID'];
			update_post_meta($global_row, '_data_id', $global_row_data_id);
		}

		$template_id = get_post_meta($global_row, '_fl_builder_template_id', true);
		$template_data = FLBuilderModel::get_layout_data( 'published', $global_row );
		$template_data = FLBuilderModel::generate_new_node_ids( $template_data );

		$global_row_node = FLBuilderModel::get_node_template_root( 'row', $template_data );

		return $global_row_node;
	}

	function update_post_node_settings($element_node_ids, $uxi_layout_styling) {
		$compiled = json_decode(UXI_Files_Handler::get_file('uxi-compiled.json'), true);

		foreach($uxi_layout_styling as $element_id => $element) {
			$node = $element_node_ids[$element_id];
			switch($element['element_type']) {
				case 'row':
					$class = implode(' ', $element['atts']['class']);

					$settings = array(
						'class' => $class,
						'width' => 'full',
						'bg_size' => 'auto',
					);

					$row_bg_color_schema = array(
						'bg_type' => array(
							'rule' => 'background-color',
							'value_if_exists' => 'color'
						),
						'bg_color' => array(
							'rule' => 'background-color',
						)
					);

					$row_bg_image_schema = array(
						'bg_type' => array(
							'rule' => 'background-image',
							'value_if_exists' => 'photo' 
						),
						'bg_image' => array(
							'rule' => 'background-image',
							'att' => 'id',
							'range' => UXI_Style_Map::$mq_large,
						),
						'bg_image_src' => array(
							'rule' => 'background-image',
							'att' => 'url',
							'range' => UXI_Style_Map::$mq_large,
							'dep' => 'bg_image'
						),
						'bg_repeat' => array(
							'rule' => 'background-repeat',
							'range' => UXI_Style_Map::$mq_large,
							'dep' => 'bg_image'
						),
						'bg_position' => array(
							'rule' => 'background-position',
							'att' => 'concat',
							'range' => UXI_Style_Map::$mq_large,
							'dep' => 'bg_image'
						),
						'bg_attachment' => array(
							'rule' => 'background-attachment',
							'range' => UXI_Style_Map::$mq_large,
							'dep' => 'bg_image'
						),
						'bg_size' => array(
							'rule' => 'background-size',
							'range' => UXI_Style_Map::$mq_large,
							'dep' => 'bg_image'
						),
						'bg_image_medium' => array(
							'rule' => 'background-image',
							'att' => 'id',
							'range' => UXI_Style_Map::$mq_medium,
							'compare' => array('bg_image')
						),
						'bg_image_medium_src' => array(
							'rule' => 'background-image',
							'att' => 'url',
							'range' => UXI_Style_Map::$mq_medium,
							'compare' => array('bg_image_src'),
							'dep' => 'bg_image_medium'
						),
						'bg_repeat_medium' => array(
							'rule' => 'background-repeat',
							'range' => UXI_Style_Map::$mq_medium,
							'compare' => array('bg_repeat')
						),
						'bg_position_medium' => array(
							'rule' => 'background-position',
							'att' => 'concat',
							'range' => UXI_Style_Map::$mq_medium,
							'compare' => array('bg_position')
						),
						'bg_attachment_medium' => array(
							'rule' => 'background-attachment',
							'range' => UXI_Style_Map::$mq_medium,
							'compare' => array('bg_attachment')
						),
						'bg_size_medium' => array(
							'rule' => 'background-size',
							'range' => UXI_Style_Map::$mq_medium,
							'compare' => array('bg_size')
						),
						'bg_image_responsive' => array(
							'rule' => 'background-image',
							'att' => 'id',
							'range' => UXI_Style_Map::$mq_small,
							'compare' => array('bg_image', 'bg_image_medium'),
						),
						'bg_image_responsive_src' => array(
							'rule' => 'background-image',
							'att' => 'url',
							'range' => UXI_Style_Map::$mq_small,
							'compare' => array('bg_image_src', 'bg_image_medium_src'),
							'dep' => 'bg_image_responsive'
						),
						'bg_repeat_responsive' => array(
							'rule' => 'background-repeat',
							'range' => UXI_Style_Map::$mq_small,
							'compare' => array('bg_repeat', 'bg_repeat_medium')
						),
						'bg_position_responsive' => array(
							'rule' => 'background-position',
							'att' => 'concat',
							'range' => UXI_Style_Map::$mq_small,
							'compare' => array('bg_position', 'bg_position_medium')
						),
						'bg_attachment_responsive' => array(
							'rule' => 'background-attachment',
							'range' => UXI_Style_Map::$mq_small,
							'compare' => array('bg_attachment', 'bg_attachment_medium')
						),
						'bg_size_medium' => array(
							'rule' => 'background-size',
							'range' => UXI_Style_Map::$mq_small,
							'compare' => array('bg_size', 'bg_size_medium')
						),
					);


					$row_bg_color_map = new UXI_Style_Map($element['styles'], $row_bg_color_schema);

					$row_bg_image_map = new UXI_Style_Map($element['styles'], $row_bg_image_schema);

					$row_bg_color_settings = $row_bg_color_map->map;
					$row_bg_image_settings = $row_bg_image_map->map;

					$settings = array_merge($settings, $row_bg_color_settings, $row_bg_image_settings);

					$row_node = $element_node_ids[$element_id . '_row'];
					FLBuilderModel::save_settings(
						$row_node->node,
						$settings
					);
				case 'row_nested':
					break;
				case 'column':
				case 'column_nested':
					$column_settings = array(
						'class' => implode(' ', $element['atts']['class']),
						'id' => $element_id
					);

					$element_sizes = $element['sizes'];

					$column_settings['size_responsive'] = 100;

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

		// Ensure nodes get properly added to the builder for this post
		$data = array();

		foreach($element_node_ids as $node_id => $node) {
			if (property_exists($node, 'template_id')) {
				$data[$node->node] = $node;
			} else {
				$data[$node->node] = FLBuilderModel::get_node($node->node);
			}
		}

		FLBuilderModel::update_layout_data($data, null, $this->post_id);
	}

	function add_widget_node($element, $parent_node) {
		$widget_type = $element['atts']['uxi-widget'];

		$widget_module = null;

		switch($widget_type) {
			case 'uxi_widget_recent_posts':
				require_once(plugin_dir_path(__FILE__) . 'class-uxi-module-posts.php');
				$widget_module = new UXI_Module_Posts($element, $parent_node, $this->post_id);
				break;
			case 'uxi_widget_archives':
				require_once(plugin_dir_path(__FILE__) . 'class-uxi-module-archives.php');
				$widget_module = new UXI_Module_Archives($element, $parent_node, $this->post_id);
				break;
			case 'widget_categories':
				require_once(plugin_dir_path(__FILE__) . 'class-uxi-module-categories.php');
				$widget_module = new UXI_Module_Categories($element, $parent_node, $this->post_id);
				break;
			case 'uxi_loop':
				require_once(plugin_dir_path(__FILE__) . 'class-uxi-module-loop.php');
				$widget_module = new UXI_Module_Loop($element, $parent_node, $this->post_id);
				break;
			case 'uxi_loop_header':
				require_once(plugin_dir_path(__FILE__) . 'class-uxi-module-loop-header.php');
				$widget_module = new UXI_Module_Loop_Header($element, $parent_node, $this->post_id);
				break;
			case 'uxi_widget_cta2':
				require_once(plugin_dir_path(__FILE__) . 'class-uxi-module-cta.php');
				$widget_module = new UXI_Module_CTA($element, $parent_node, $this->post_id);
				break;
			case 'uxi_jumbotron2':
				require_once(plugin_dir_path(__FILE__) . 'class-uxi-module-jumbotron.php');
				$widget_module = new UXI_Module_Jumbotron($element, $parent_node, $this->post_id);
				break;
			case 'uxi_widget_lightbox2':
			case 'uxi_widget_button':
				require_once(plugin_dir_path(__FILE__) . 'class-uxi-module-button.php');
				$widget_module = new UXI_Module_Button($element, $parent_node, $this->post_id);
				break;
			case 'widget_uxi_navigation':
			case 'widget_uxi_custom_menu':
				require_once(plugin_dir_path(__FILE__) . 'class-uxi-module-menu.php');
				$widget_module = new UXI_Module_Menu($element, $parent_node, $this->post_id);
				break;
			case 'uxi_company_address':
				require_once(plugin_dir_path(__FILE__) . 'class-uxi-module-company-info.php');
				$widget_module = new UXI_Module_Company_Info($element, $parent_node, $this->post_id);
				break;
			case 'uxi_google_map':
				require_once(plugin_dir_path(__FILE__) . 'class-uxi-module-google-map.php');
				$widget_module = new UXI_Module_Google_Map($element, $parent_node, $this->post_id);
				break;
			default:
			case 'uxi_widget_embed':
				require_once(plugin_dir_path(__FILE__) . 'class-uxi-module-html.php');
				$widget_module = new UXI_Module_HTML($element, $parent_node, $this->post_id);
				break;
			case 'uxi_widget_testimonials':
				require_once(plugin_dir_path(__FILE__) . 'class-uxi-module-testimonials.php');
				$widget_module = new UXI_Module_Testimonials($element, $parent_node, $this->post_id);
				break;
			case 'uxi_widget_social_2':
			case 'uxi_payment_icons':
				require_once(plugin_dir_path(__FILE__) . 'class-uxi-module-icon-group.php');
				$widget_module = new UXI_Module_Icon_Group($element, $parent_node, $this->post_id);
				break;
			case 'uxi_widget_search':
				require_once(plugin_dir_path(__FILE__) . 'class-uxi-module-search-form.php');
				$widget_module = new UXI_Module_Search_Form($element, $parent_node, $this->post_id);
				break;
			case 'uxi_widget_sitemap':
				require_once(plugin_dir_path(__FILE__) . 'class-uxi-module-sitemap.php');
				$widget_module = new UXI_Module_Sitemap($element, $parent_node, $this->post_id);
				break;
			case 'uxi_widget_breadcrumbs':
				require_once(plugin_dir_path(__FILE__) . 'class-uxi-module-breadcrumbs.php');
				$widget_module = new UXI_Module_Breadcrumbs($element, $parent_node, $this->post_id);
				break;
			case 'uxi_gform':
			case 'uxi_widget_wysiwyg_text_area':
				require_once(plugin_dir_path(__FILE__) . 'class-uxi-module-richtext.php');
				$widget_module = new UXI_Module_RichText($element, $parent_node, $this->post_id);
				break;
			case 'uxi_widget_copyright':
				require_once(plugin_dir_path(__FILE__) . 'class-uxi-module-copyright.php');
				$widget_module = new UXI_Module_Copyright($element, $parent_node, $this->post_id);
				break;
			case 'uxi_widget_logo':
			case 'widget_uxi_image':
				require_once(plugin_dir_path(__FILE__) . 'class-uxi-module-image.php');
				$widget_module = new UXI_Module_Image($element, $parent_node, $this->post_id);
				break;
			case 'widget_uxi_gallery':
				require_once(plugin_dir_path(__FILE__) . 'class-uxi-module-gallery.php');
				$widget_module = new UXI_Module_Gallery($element, $parent_node, $this->post_id);
				break;
			case 'wigdet_uxi_slideshow':
				require_once(plugin_dir_path(__FILE__) . 'class-uxi-module-slideshow.php');
				$widget_module = new UXI_Module_Slideshow($element, $parent_node, $this->post_id);
				break;
			case 'uxi_widget_video':
				require_once(plugin_dir_path(__FILE__) . 'class-uxi-module-video.php');
				$widget_module = new UXI_Module_Video($element, $parent_node, $this->post_id);
				break;
		}

		return $widget_module->modules;
	}
}