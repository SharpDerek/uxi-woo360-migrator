<?php

require_once(plugin_dir_path(__FILE__) . 'class-uxi-common.php');

class UXI_Module {

	protected $element;
	protected $parent_node;
	public $modules = array();
	protected $content;
	protected $module_settings = array();
	
	function __construct($element, $parent_node) {
		$this->element = $element;
		$this->parent_node = $parent_node;
		$this->content = UXI_Common::filter_html($this->element['html']);
		$this->modules = $this->build_modules();
	}

	function build_modules() {

	}

	function save_settings() {
		foreach($this->modules as $index => $module) {
			$settings = $this->module_settings[$index];
			$this->save_module_settings($module, $settings);
		}
	}

	function save_module_settings($module, $settings) {
		FLBuilderModel::save_settings($module->node, $settings);
	}

	function add_module($type, $settings) {
		return FLBuilderModel::add_module($type, (object)$settings, $this->parent_node);
	}

	function xpath($html) {
		$dom = new DOMDocument();
		$encoded_html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
		@$dom->loadHTML($encoded_html);

		$xpath = new DOMXPath($dom);
		return $xpath;
	}
}