<?php

class UXI_Module_Button extends UXI_Module {

	function get_link() {
		$href_query = "//a/@href";

		$link = "";

		foreach($this->xpath->query($href_query) as $href) {
			$link = $href->value;
			break;
		}

		return $link;
	}

	function get_lightbox_settings() {
		$link_query = "//a[@data-fancybox]";
		$lightbox_content_query = '//*[contains(@class, "fancybox-content")]';

		$this->lightbox_settings = array();

		foreach($this->xpath->query($link_query) as $link) {
			$href = $link->getAttribute('href');
			$data_src = $link->getAttribute('data-src');
			$this->lightbox_settings['click_action'] = 'lightbox';

			$url = ($href)
				? $href
				: (
					(strpos($data_src, '#') !== 0)
					? $data_src
					: ""
				);

			$this->lightbox_settings['lightbox_content_type'] = ($url) ? 'video' : 'html';

			if ($url) {
				$this->lightbox_settings['lightbox_video_link'] = $url;
			} else {
				foreach($this->xpath->query($lightbox_content_query) as $lightbox_content) {
					$html = $this->inner_html($lightbox_content);
					$filtered_html = UXI_Common::filter_html($html);
					$this->lightbox_settings['lightbox_content_html'] = $filtered_html;
					break;
				}
			}
			break;
		}
	}

	function get_button_settings() {
		$button_query = "//a[@href and contains(@class, 'button')]";
		$button_icon_wrap_query = ".//a[contains(@class, 'button-has-icon')]//*[contains(@class, 'button-icon ')]";
		$button_text_query = ".//*[contains(@class, 'button-text-wrap')]/*";

		$this->button_settings = array();

		foreach($this->xpath->query($button_query) as $button) {
			$button_classes = $button->getAttribute('class');
			$button_id = $button->getAttribute('id');
			$this->button_settings['class'] = $button_classes;
			$this->button_settings['id'] = $button_id;

			foreach($this->xpath->query($button_icon_wrap_query, $button) as $button_icon_wrap) {
				$icon_wrap_classes = $button_icon_wrap->getAttribute('class');
				if (strpos($icon_wrap_classes, 'is-left') > -1) {
					$this->button_settings['icon_position'] = 'before';
				} else {
					$this->button_settings['icon_position'] = 'after';
				}
				break;
			}

			foreach($this->xpath->query($button_icon_wrap_query . '/*', $button) as $icon) {
				$icon_class = $icon->getAttribute('class');
				$icon_name = UXI_Common::icon_name($icon_class);
				$this->button_settings['icon'] = $icon_name;
				break;
			}
			$this->button_settings['link'] = $this->get_link();

			$button_text = array();
			foreach($this->xpath->query($button_text_query, $button) as $button_text_section) {
				$button_text_class = $button_text_section->getAttribute('class');
				$button_text_wrap = array('', '');

				if (strpos($button_text_class, 'sub-text') > -1) {
					$button_text_wrap = array('<small>', '</small>');
				}

				$button_text[] .= $button_text_wrap[0] . $button_text_section->textContent . $button_text_wrap[1];
			}
			if ($button_text) {
				$this->button_settings['text'] = implode('<br>', $button_text);
			}
			break;
		}
	}

	function build_modules() {
		$this->get_button_settings();
		$this->get_lightbox_settings();

		$settings = array_merge($this->button_settings, $this->lightbox_settings);

		if ($settings) {
			$this->modules[] = $this->add_module('button', $settings);
		}

		$this->save_settings();
		return $this->modules;
	}

}