<?php

require_once(plugin_dir_path(__FILE__) . 'class-uxi-module-button.php');
require_once(plugin_dir_path(__FILE__) . 'class-uxi-style-map.php');

class UXI_Module_Jumbotron extends UXI_Module_Button {

	function is_carousel() {
		$carousel_query = '//*[contains(@class, "has-carousel")]';

		return $this->xpath->query($carousel_query)->length > 0;
	}

	function get_subheadings() {
		$subheading_query = '//*[contains(@class, "jumbotron-subheading-line")]/text()';

		$this->subheadings = array();

		foreach($this->xpath->query($subheading_query) as $subheading) {
			$this->subheadings[] = $subheading->textContent;
		}
	}

	function get_body() {
		$body_query = '//*[contains(@class, "jumbotron-paragraph")]';

		$this->body = "";

		foreach($this->xpath->query($body_query) as $body) {
			$this->body = $this->inner_html($body);
			break;
		}
	}

	function get_slides() {
		$slide_text_query = '//*[contains(@class, "carousel-inner")]//*[contains(@class, "item")]/text()';

		$slides = array();

		foreach($this->xpath->query($slide_text_query) as $slide_text) {
			$slide_settings = array(
				'label' => $slide_text->textContent,
				'content_layout' => 'text',
				'title' => $slide_text->textContent,
				'text_width' => 100,
				'link' => $this->get_link(),
				'text_position' => 'left',
				'text_margin_top' => 0,
				'text_margin_bottom' => 0,
				'text_margin_left' => 0,
				'text_margin_right' => 0,
				'text_padding_top' => 0,
				'text_padding_bottom' => 0,
				'text_padding_left' => 0,
				'text_padding_right' => 0
			);

			$slides[] = (object) $slide_settings;
		}

		return $slides;
	}

	function get_slide_speed() {
		$data_interval_query = '//*[@data-interval]/@data-interval';

		$speed = 5000;

		foreach($this->xpath->query($data_interval_query) as $data_interval) {
			$speed = intval($data_interval->value);
			break;
		}

		return $speed/1000;
	}

	function add_button_settings($settings) {
		if ($this->button_settings) {
			$settings['cta_type'] = 'button';
			$settings['cta_text'] = $this->button_settings['text'];

			if (isset($this->button_settings['icon'])) {
				$settings['btn_icon'] = $this->button_settings['icon'];
				$settings['btn_icon_position'] = $this->button_settings['icon_position'];
			}
		}
		return $settings;
	}

	function get_background_settings() {

		$schema = array(
			'bg_type' => array(
				'rule' => 'background-image',
				'value_if_exists' => 'photo'
			),
			'bg_image' => array(
				'rule' => 'background-image',
				'att' => 'id'
			),
			'bg_image_src' => array(
				'rule' => 'background-image',
				'att' => 'url',
				'dep' => 'bg_image'
			)
		);

		$bg_map = new UXI_Style_Map($this->element['styles'], $schema);

		$settings = $bg_map->map;

		return $settings;
	}

	function update_column_settings() {
		$bg_settings = $this->get_background_settings();

		if ($bg_settings) {
			$parent_node = FLBuilderModel::get_node($this->parent_node);

			FLBuilderModel::save_settings($this->parent_node, array_merge(
				(array) $parent_node->settings,
				$bg_settings
			));
		}

	}

	function build_modules() {
		$this->get_button_settings();
		$this->get_subheadings();
		$this->get_body();

		if ($this->is_carousel()) {
			$settings = array(
				'slides' => $this->get_slides(),
				'height' => 0,
				'dots' => 0,
				'delay' => $this->get_slide_speed(),
				'transition' => 'horizontal'
			);

			$this->modules[] = $this->add_module('content-slider', $settings);

		} else {
			$this->get_header_settings('//*[contains(@class, "jumbotron-heading-inner")]');
			if ($this->header_settings) {
				$header_module = $this->add_module('heading', array_merge(
					$this->header_settings,
					array(
						'tag' => 'h2'
					)
				));
				$this->modules[] = $header_module;
			}
		}

		if ($this->subheadings) {
			foreach($this->subheadings as $subheading) {
				$subheading_module = $this->add_module('heading', array(
					'tag' => 'h4',
					'heading' => $subheading
				));

				$this->modules[] = $subheading_module;
			}
		}

		if ($this->body) {
			$body_module = $this->add_module('rich-text', array(
				'text' => $this->body
			));

			$this->modules[] = $body_module;
		}

		if ($this->button_settings) {
			$button_module = $this->add_module('button', $this->button_settings);

			$this->modules[] = $button_module;
		}

		$this->save_settings();
		$this->update_column_settings();
		return $this->modules;
	}

}