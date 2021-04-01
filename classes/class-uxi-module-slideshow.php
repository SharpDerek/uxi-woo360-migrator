<?php

class UXI_Module_Slideshow extends UXI_Module {

	function get_slideshow_settings() {
		$slideshow_item_src_query = '//*[contains(@class, "item")]/img/@src';

		$this->slideshow_settings = array();
		$slideshow_images = array();

		foreach($this->xpath->query($slideshow_item_src_query) as $slideshow_item_src) {
			$image_src = UXI_Common::media_url_replace($slideshow_item_src->value);

			$attachment_id = UXI_Common::get_attachment_id_by_url($image_src);

			$image_src = wp_get_attachment_url($attachment_id);

			$slideshow_images[] = $attachment_id;
		}

		if ($slideshow_images) {
			$this->slideshow_settings['photos'] = $slideshow_images;
		}
	}

	function build_modules() {
		$this->get_slideshow_settings();

		if ($this->slideshow_settings) {

			$slideshow_module = $this->add_module('slideshow', $this->slideshow_settings);

			$this->modules[] = $slideshow_module;
		}

		$this->save_settings();
		return $this->modules;
	}
}