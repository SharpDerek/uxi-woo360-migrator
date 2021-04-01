<?php

class UXI_Module_CTA extends UXI_Module {

	protected $header_query = "//*[contains(@class, 'cta2-heading') and not(contains(@class, 'cta2-heading-wrap'))]";
	protected $image_wrap_query = "//*[contains(@class, 'cta2-image-wrap')]";

	function get_image() {
		$image_query = '//img';
		$image_src = "";

		$this->image_settings = array();

		foreach($this->xpath->query($image_query) as $image) {
			$src = $image->getAttribute('src');
			$image_src = UXI_Common::media_url_replace($src);
			break;
		}

		if ($image_src) {
			$id = UXI_Common::get_attachment_id_by_url($image_src);
			$this->image_settings['id'] = $id;
			$this->image_settings['src'] = wp_get_attachment_url($id);
		}
	}

	function get_link() {
		$href_query = "//a/@href";

		$link = "";

		foreach($this->xpath->query($href_query) as $href) {
			$link = $href->value;
			break;
		}

		return $link;
	}

	function has_button() {
		$button_query = "//a[@href and contains(@class, 'button')]";

		foreach($this->xpath->query($button_query) as $button) {
			return true;
		}
		return false;
	}

	function get_header() {
		$this->header_settings = array();
		$this->is_separate_header = true;

		foreach($this->xpath->query($this->header_query) as $header) {
			$header_classes = $header->getAttribute('class');
			if (strpos($header_classes, 'is-top') > -1) {
				$this->is_separate_header = false;
				$this->header_settings['align'] = 'flex-start';
			} else if (strpos($header_classes, 'is-bottom') > -1) {
				$this->is_separate_header = false;
				$this->header_settings['align'] = 'flex-end';
			}
			$this->header_settings['tag'] = 'h2';
			$this->header_settings['class'] = $header_classes;
			$this->header_settings['heading'] = $header->textContent;
			$this->header_settings['html'] = $this->inner_html($header);

			break;
		}
	}

	function header_is_first() {
		$elements_query = $this->header_query . '|' . $this->image_wrap_query;

		$index = 0;

		foreach($this->xpath->query($elements_query) as $element) {
			$classes = $element->getAttribute('class');

			if (strpos($classes, 'cta2-heading') > -1 && $index == 0) {
				return true;
			}
			$index++;
		}
		return false;
	}

	function get_paragraph() {
		$p_query = "//*[contains(@class, 'cta2-paragraph')]";

		$this->paragraph_settings = array();

		foreach($this->xpath->query($p_query) as $p) {
			$this->paragraph_settings['text'] = $p->textContent;
			break;
		}
	}

	function build_modules() {
		$this->get_header();
		$this->get_image();
		$this->get_paragraph();

		if ($this->is_separate_header) {
			if ($this->header_is_first()) {
				$this->add_header_module();
				$this->add_photo_module();
			} else {
				$this->add_photo_module();
				$this->add_header_module();
			}
		} else {
			$cta_settings = array();

			if ($this->header_settings) {
				$cta_settings['cb_caption_vertical_align'] = $this->header_settings['align'];
				$cta_settings['cb_caption_editor_field'] = $this->header_settings['html'];
			}
			if ($this->image_settings) {
				$cta_settings['cb_caption_photo'] = $this->image_settings['id'];
				$cta_settings['cb_caption_photo_src'] = $this->image_settings['src'];
			}
			$cta_settings['cb_caption_link_field'] = $this->get_link();
			$this->modules[] = $this->add_module('cb-caption', $cta_settings);
		}

		if ($this->paragraph_settings) {
			$this->modules[] = $this->add_module('rich-text', $this->paragraph_settings);
		}

		if ($this->has_button()) {
			require_once(plugin_dir_path(__FILE__) . 'class-uxi-module-button.php');

			$button = new UXI_Module_Button($this->element, $this->parent_node, $this->post_id);
			$this->modules = array_merge($this->modules, $button->modules);
		}

		$this->save_settings();
		return $this->modules;
	}

	function add_header_module() {
		if (!$this->header_settings) {
			return;
		}
		$header_settings = array(
			'heading' => $this->header_settings['heading'],
			'tag' => $this->header_settings['tag'],
			'class' => $this->header_settings['class']
		);
		if (!$this->button_settings) {
			$header_settings['link'] = $this->get_link();
		}
		$this->modules[] = $this->add_module('heading', $header_settings);
	}

	function add_photo_module() {
		if (!$this->image_settings) {
			return;
		}
		$image_settings = array(
			'photo' => $this->image_settings['id'],
			'photo_src' => $this->image_settings['src']
		);

		if (!$this->button_settings) {
			$image_settings['link_type'] = 'url';
			$image_settings['link_url'] = $this->get_link();
		}
		$photo_module = $this->add_module('photo', $image_settings);
		$photo_module->get_data();

		$this->modules[] = $photo_module;
	}

}