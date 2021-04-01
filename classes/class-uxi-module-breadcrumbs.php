<?php

class UXI_Module_Breadcrumbs extends UXI_Module {

	function set_breadcrumbs_settings() {
		$current_settings = get_option('wpseo_titles', false);

		if (!$current_settings) {
			return;
		}

		$current_settings['breadcrumbs-enable'] = true;

		$breadcrumb_sep_query = '//*[@data-breadcrumb]/@data-breadcrumb';

		foreach($this->xpath->query($breadcrumb_sep_query) as $sep) {
			$current_settings['breadcrumbs-sep'] = $sep->value;
			break;
		}

		update_option('wpseo_titles', $current_settings);
	}

	function build_modules() {
		$this->set_breadcrumbs_settings();

		$settings = array(
			'text' => '[wpseo_breadcrumb]'
		);

		$this->modules[] = $this->add_module('rich-text', $settings);

		$this->save_settings();
		return $this->modules;
	}

}