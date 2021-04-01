<?php

class UXI_Module_Categories extends UXI_Module {

	function build_modules() {
		$header_query = '//h2/text()';
		$header_text = '';

		foreach($this->xpath->query($header_query) as $header) {
			$header_text = $header->value;
			break;
		}

		$widget_settings = array(
			'title' => $header_text,
			'count' => false,
			'hierarchical' => false,
			'dropdown' => false
		);

		$settings = array(
			'widget' => 'WP_Widget_Categories',
			'widget-categories' => (object) $widget_settings,
			'id' => $this->element['atts']['id'],
			'class' => UXI_Common::class_concat($this->element['atts']['class'])
		);

		$this->modules[] = $this->add_module('widget', $settings);
		$this->save_settings();
		return $this->modules;
	}

}