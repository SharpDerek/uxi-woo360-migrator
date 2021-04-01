<?php

class UXI_Module_Icon_Group extends UXI_Module {

	function get_icons() {
		$icon_wrap_query = '//ul[contains(@class, "-icons")]/li';

		$icons = array();

		foreach($this->xpath->query($icon_wrap_query) as $icon_wrap) {
			$wrap_classes = $icon_wrap->getAttribute('class');
			$wrap_class_array = UXI_Common::class_split($wrap_classes);

			$link_query = './/a';
			$icon_query = './/*[contains(@class, "icon-")]';

			$icon_settings = array();

			foreach($this->xpath->query($link_query, $icon_wrap) as $link) {
				$url = $link->getAttribute('href');
				$target = $link->getAttribute('target');
				$icon_settings['link'] = $url;
				$icon_settings['link_target'] = $target ? $target : '_self';
				break;
			}

			foreach($this->xpath->query($icon_query, $icon_wrap) as $icon) {
				$classes = $icon->getAttribute('class');
				$icon_settings['icon'] = UXI_Common::icon_name($classes);
				break;
			}
			$icons[] = (object) $icon_settings;
		}
		return $icons;
	}

	function build_modules() {

		$icons = $this->get_icons();

		if ($icons) {
			$settings = array(
				'icons' => $this->get_icons()
			);

			$this->modules[] = $this->add_module('icon-group', $settings);
		}

		$this->save_settings();
		return $this->modules;
	}

}