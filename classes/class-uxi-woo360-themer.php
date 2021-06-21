<?php

require_once(plugin_dir_path(__FILE__) . 'class-uxi-files-handler.php');
require_once(plugin_dir_path(__FILE__) . 'class-uxi-woo360-layout.php');

class UXI_Woo360_Themer {

	public $id;
	public $data_layout;
	public $themer;

	public function __construct($args) {
		$defaults = array(
			'id' => 0,
			'title' => '',
			'type' => '',
			'data_layout' => '',
			'locations' => array(),
			'global' => false, 
			'compiled' => false
		);

		$args = array_merge($defaults, $args);

		extract($args);

		if ($title && $type && $data_layout && $locations) {
			$id = $this->create_post($id, $title, $type, $data_layout, $global);

			$this->update_locations($id, $locations);

			$this->do_layout($id, $data_layout, $compiled);

			$this->do_save_post($id);

			$this->data_layout = $data_layout;

			$this->id = $id;
			$this->themer = array(
				'url' => get_edit_post_link($id),
				'title' => $title
			);
		}
	}

	function create_post($id, $title, $type, $data_layout, $global) {
		$id = wp_insert_post(array(
			'ID' => $id,
			'post_title' => $title,
			'post_status' => 'publish',
			'post_type' => 'fl-theme-layout',
			'meta_input' => array(
				'_fl_theme_layout_type' => $type,
			)
		));

		if ($global) {
			update_post_meta($id, '_global', $global);
		}
		if (gettype($data_layout) == 'string') {
			update_post_meta($id, '_data_layout', $data_layout);
		}
		return $id;
	}

	function update_locations($id, $locations) {
		$existing_locations = FLThemeBuilderRulesLocation::get_saved($id);
		$all_locations = array_merge($existing_locations, $locations);

		FLThemeBuilderRulesLocation::update_saved($id, $all_locations);
	}

	function do_save_post($id) {
		do_action('save_post', $id, get_post($id), true);
	}

	function do_layout($id, $data_layout, $compiled) {
		if (gettype($data_layout) == 'string') {
			$compiled = ($compiled) ? $compiled : json_decode(UXI_Files_Handler::get_file('uxi-compiled.json'), true);
			$data_layouts = $compiled['data-layouts'];
			$data_layout_styling = $data_layouts[$data_layout]['elements'];
		} else {	
			$data_layout_styling = $data_layout;
		}
		$woo360_layout = new UXI_Woo360_Layout($id, $data_layout_styling);
	}
}