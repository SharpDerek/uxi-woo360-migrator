<?php

require_once(plugin_dir_path(__FILE__) . 'class-uxi-module-button.php');
require_once(plugin_dir_path(__FILE__) . 'class-uxi-style-map.php');

class UXI_Module_Jumbotron extends UXI_Module_Button {

	function is_carousel() {
		$carousel_query = '//*[contains(@class, "has-carousel")]';

		return $this->xpath->query($carousel_query)->length > 0;
	}

	function get_subheading() {
		$subheading_query = '//*[contains(@class, "jumbotron-subheading-line")]/text()';

		$this->subheading = "";

		$subheadings = array();

		foreach($this->xpath->query($subheading_query) as $subheading) {
			$subheadings[] = $subheading->textContent;
		}

		if ($subheadings) {
			$this->subheading = "<p>" . implode("\n", $subheadings) . "</p>";
		}
	}

	function get_body() {
		$body_query = '//*[contains(@class, "jumbotron-paragraph")]';

		$this->body = "";

		foreach($this->xpath->query($body_query) as $body) {
			$this->body = "<p>" . $this->inner_html($body) . "</p>";
			break;
		}
	}

	function get_slides() {
		$slide_text_query = '//*[contains(@class, "carousel")]//*[contains(@class, "item")]/text()';

		$slides = array();

		foreach($this->xpath->query($slide_text_query) as $slide_text) {
			$slide_settings = array(
				'label' => $slide_text->textContent,
				'content_layout' => 'text',
				'title' => $slide_text->textContent,
				'text' => $this->subheading . "\n" . $this->body,
				'text_width' => 100,
				'link' => $this->get_link(),
				'text_margin_top' => 0,
				'text_margin_bottom' => 0,
				'text_margin_left' => 0,
				'text_margin_right' => 0,
				'text_padding_top' => 0,
				'text_padding_bottom' => 0,
				'text_padding_left' => 0,
				'text_padding_right' => 0
			);

			$slide_settings = $this->add_button_settings($slide_settings);

			$slide_settings = array_merge($slide_settings, $this->get_background_settings());

			$slides[] = (object) $slide_settings;
		}

		return $slides;
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
		$schema = array();

		if ($this->is_carousel()) {
			$schema = array(
				'bg_layout' => array(
					'rule' => 'background-image',
					'value_if_exists' => 'photo'
				),
				'bg_photo' => array(
					'rule' => 'background-image',
					'att' => 'id'
				),
				'bg_photo_src' => array(
					'rule' => 'background-image',
					'att' => 'url',
					'dep' => 'bg_photo'
				)
			);
		} else {
			$schema = array(
				'image_type' => array(
					'rule' => 'background-image',
					'value_if_exists' => 'photo'
				),
				'photo' => array(
					'rule' => 'background-image',
					'att' => 'id'
				),
				'photo_src' => array(
					'rule' => 'background-image',
					'att' => 'url',
					'dep' => 'photo'
				)
			);
		}

		$bg_map = new UXI_Style_Map($this->element['styles'], $schema);

		$settings = $bg_map->map;

		return $settings;
	}

	function build_modules() {
		$this->get_button_settings();
		$this->get_subheading();
		$this->get_body();

		if ($this->is_carousel()) {
			$settings = array(
				'slides' => $this->get_slides(),
			);

			$this->modules[] = $this->add_module('content-slider', $settings);

		} else {
			$this->get_header_settings('//*[contains(@class, "jumbotron-heading-inner")]');

			$settings = array(
				'title' => $this->header_settings['heading'],
				'title_tag' => 'h2',
				'text' => $this->subheading . "\n" . $this->body,
				'link' => $this->get_link(),
				'photo_position' => 'bg_image'
			);

			$settings = array_merge($settings, $this->get_background_settings());

			$settings = $this->add_button_settings($settings);

			$this->modules[] = $this->add_module('callout', $settings);

		}
		$this->save_settings();
		return $this->modules;
	}

}