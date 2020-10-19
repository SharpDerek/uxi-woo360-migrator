<?php

require_once(plugin_dir_path(__FILE__) . 'class-uxi-files-handler.php');

class UXI_Element_Styles {

	public $rules = array();

	public function __construct($uxi_element) {
		$parsed_css = UXI_Files_Handler::get_file('uxi-site-custom-css-parsed.json');
		if (is_null($parsed_css)) {
			return;
		}

		$this->rules = $this->get_rules($uxi_element, $parsed_css);
	}

	function get_rules($uxi_element, $parsed_css) {

		$rules = array();

		$parsed_css = json_decode($parsed_css, true);

		$id = (array_key_exists('id', $uxi_element->atts)) ? '#' . $uxi_element->atts['id'] : false;
		$classes = array();
		if (array_key_exists('class', $uxi_element->atts)) {
			foreach($uxi_element->atts['class'] as $class) {
				$classes[] = '.' . $class;
			}
		}

		$this->id = $id;
		$this->classes = $classes;

		foreach($parsed_css as $ruleset) {
			$ruleset_relevant = false;
			foreach($ruleset['selectors'] as $selector) {
				if ($id) {
					if (preg_match($this->regex($id), $selector) === 1) {
						$ruleset_relevant = true;
						break;
					}
				}
				foreach($classes as $class) {
					if (preg_match($this->regex($class), $selector) === 1) {
						$ruleset_relevant = true;
						break;
					}
				}
				if ($ruleset_relevant) {
					break;
				}
			}

			if ($ruleset_relevant) {
				$rules[] = $ruleset;
			} else {
				continue;
			}
		}

		return $rules;

	}

	function regex($input) {
		return sprintf('/(\%s)([\s]|$)/', $input);
	}
}