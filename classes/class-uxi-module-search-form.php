<?php

class UXI_Module_Search_Form extends UXI_Module {

	function get_search_form() {
		$input_query = '//input';
		$button_icon_query = '//button[@type="submit"]/*[contains(@class, "icon-")]';

		$this->search_form_settings = array();

		foreach($this->xpath->query($input_query) as $input) {
			$placeholder = $input->getAttribute("placeholder");
			if ($placeholder) {
				$this->search_form_settings['placeholder'] = $placeholder;
			}
			break;
		}

		foreach($this->xpath->query($button_icon_query) as $button_icon) {
			$classes = $button_icon->getAttribute('class');
			$icon_name = UXI_Common::icon_name($classes);

			$this->search_form_settings['icon'] = $icon_name;
			break;
		}
	}

	function build_modules() {
		$this->get_search_form();

		$settings = array();

		if ($this->search_form_settings) {
			$settings = array_merge($settings, $this->search_form_settings);
		}
		
		$this->modules[] = $this->add_module('toggle-search-form', $settings);

		$this->save_settings();
		return $this->modules;
	}

}