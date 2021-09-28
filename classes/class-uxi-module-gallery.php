<?php

class UXI_Module_Gallery extends UXI_Module {

	function get_gallery_settings() {
		$gallery_item_query = '//figure[contains(@class, "gallery-item")]';
		$image_src_query = ".//img/@src";
		$caption_query = ".//figcaption";

		$this->gallery_settings = array(
			'layout' => 'grid'
		);
		$gallery_images = array();
		$show_captions = false;

		foreach($this->xpath->query($gallery_item_query) as $gallery_item) {
			
			$attachment_id = 0;

			foreach($this->xpath->query($image_src_query, $gallery_item) as $src) {
				$image_src = UXI_Common::media_url_replace($src->value);

				$attachment_id = UXI_Common::get_attachment_id_by_url($image_src);
				break;
			}

			foreach($this->xpath->query($caption_query, $gallery_item) as $caption) {
				$caption_classes = $caption->getAttribute('class');
				if (strpos($caption_classes, 'sr-only') > -1) {
					if ($caption->textContent) {
						$show_captions = "hover";
					}
				} else if (strpos($caption_classes, 'gallery-caption') > -1) {
					$show_captions = "below";
				}
				$image_meta = wp_get_attachment_metadata($attachment_id);

				if ($image_meta) {
					$image_meta['image_meta']['caption'] = $caption->textcontent;

					wp_update_attachment_metadata($attachment_id, $image_meta);
				}
				break;
			}

			$gallery_images[] = $attachment_id;
		}

		if ($gallery_images) {
			$this->gallery_settings['photos'] = $gallery_images;
			$this->gallery_settings['show_captions'] = $show_captions;
		}
	}

	function build_modules() {
		$this->get_gallery_settings();
		$this->get_header_settings();

		if ($this->header_settings) {
			$header_module = $this->add_module('heading', $this->header_settings);

			$this->modules[] = $header_module;
		}

		if ($this->gallery_settings) {
			$gallery_module = $this->add_module('gallery', $this->gallery_settings);

			$this->modules[] = $gallery_module;
		}

		$this->save_settings();
		return $this->modules;
	}
}