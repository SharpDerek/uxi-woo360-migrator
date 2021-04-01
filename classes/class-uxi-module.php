<?php

require_once(plugin_dir_path(__FILE__) . 'class-uxi-common.php');

class UXI_Module {

	protected $element;
	protected $parent_node;
	public $modules = array();
	protected $content;
	protected $xpath;
	
	function __construct($element, $parent_node, $post_id) {
		$this->post_id = $post_id;
		$this->element = $element;
		$this->parent_node = $parent_node;
		$this->content = UXI_Common::filter_html($this->element['html']);
		$this->xpath = new DOMXPath($this->load_dom());
		$this->modules = $this->build_modules();
	}

	function build_modules() {

	}

	function save_settings() {
		foreach($this->modules as $index => $module) {
			$settings = $this->modules[$index]->settings;
			$this->save_module_settings($module, $settings);
		}
	}

	function save_module_settings($module, $settings) {
		FLBuilderModel::save_settings($module->node, $settings);
	}

	function add_module($type, $settings) {
		return FLBuilderModel::add_module($type, (object)$settings, $this->parent_node);
	}

	function load_dom($html = false) {
		$dom = new DOMDocument();
		$encoded_html = mb_convert_encoding(($html ? $html : $this->element['html']), 'HTML-ENTITIES', 'UTF-8');
		@$dom->loadHTML($encoded_html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
		return $dom;
	}

	function inner_html($element) {
		$outer_html = $element->ownerDocument->saveHTML($element);
		$dom = $this->load_dom($outer_html);

		$inner_html = "";
		$xpath = new DOMXPath($dom);

		foreach($xpath->query("/*/*") as $child_element) {
			$inner_html .= $child_element->ownerDocument->saveHTML($child_element);
		}
		return $inner_html;
	}

	// Lots of modules have markup for a header, so this function handles getting the data for them.
	function get_header_settings($header_query = '//h2') {
		$this->header_settings = array();

		foreach($this->xpath->query($header_query) as $header) {

			for($i = 0; $i < $header->childNodes->length; $i++) {
				$child_node = $header->childNodes->item($i);
				if ($child_node->nodeName != "#text") {
					$header->removeChild($child_node);
				}
			}
			$classes = $header->getAttribute('class');
			$tag = $header->tagName;
			$this->header_settings['tag'] = $tag;
			$this->header_settings['class'] = trim(str_replace($tag, '', $classes));
			$this->header_settings['heading'] = $header->textContent;
			break;
		}
	}
}