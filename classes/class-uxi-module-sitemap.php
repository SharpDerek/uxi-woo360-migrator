<?php

class UXI_Module_Sitemap extends UXI_Module {

	function get_pages() {
		$header_query = '//h2[text()="Pages"]';

		if ($this->xpath->query($header_query)->length <= 0) {
			return;
		}

		$settings = array(
			'widget' => 'WP_Widget_Pages',
			'widget-pages' => (object) array(
				'title' => 'Pages'
			)
		);
		$this->modules[] = $this->add_module('widget', $settings);
	}

	function get_categories() {
		$header_query = '//h2[text()="Categories"]';

		if ($this->xpath->query($header_query)->length <= 0) {
			return;
		}

		$settings = array(
			'widget' => 'WP_Widget_Categories',
			'widget-categories' => (object) array(
				'title' => 'Pages',
				'count' => false,
				'hierarchical' => false,
				'dropdown' => false
			)
		);
		$this->modules[] = $this->add_module('widget', $settings);
	}

	function get_tags() {
		$header_query = '//h2[text()="Tags"]';

		if ($this->xpath->query($header_query)->length <= 0) {
			return;
		}

		$settings = array(
			'widget' => 'WP_Widget_Tag_Cloud',
			'widget-tag_cloud' => (object) array(
				'title' => 'Tags',
			)
		);
		$this->modules[] = $this->add_module('widget', $settings);
	}

	function build_modules() {
		$this->get_pages();
		$this->get_categories();
		$this->get_tags();

		$this->save_settings();
		return $this->modules;
	}

}