<?php

class UXI_Module_Image extends UXI_Module {

	function get_url() {
		$href_query = '//a/@href';

		$this->url = false;

		foreach($this->xpath->query($href_query) as $href) {
			$this->url = $href->value;
			break;
		}
	}

	function get_image_settings() {
		$image_src_query = '//img/@src';

		$this->image_settings = array();

		foreach($this->xpath->query($image_src_query) as $src) {
			$image_src = UXI_Common::media_url_replace($src->value);

			$attachment_id = UXI_Common::get_attachment_id_by_url($image_src);

			$image_src = wp_get_attachment_url($attachment_id);

			$this->image_settings['photo'] = $attachment_id;
			$this->image_settings['photo_src'] = $image_src;
			break;
		}
	}

	function build_modules() {
		$this->get_image_settings();
		$this->get_url();
		$this->get_header_settings();

		if ($this->header_settings) {
			$header_module = $this->add_module('heading', $this->header_settings);

			$this->modules[] = $header_module;
		}

		if ($this->image_settings) {

			if ($this->url) {
				$this->image_settings['link_type'] = 'url';
				$this->image_settings['link_url'] = $this->url;
			}

			$image_module = $this->add_module('photo', $this->image_settings);
			$image_module->get_data();

			$this->modules[] = $image_module;
		}

		$this->save_settings();
		return $this->modules;
	}
}