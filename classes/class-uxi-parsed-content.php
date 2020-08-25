<?php

require_once(plugin_dir_path(__FILE__) . 'class-uxi-parse-query.php');
require_once(plugin_dir_path(__FILE__) . 'class-uxi-element.php');

class UXI_Parsed_Content {

	public static $layout_sections = array(
		'uxi-header',
		'uxi-main',
		'uxi-footer'
	);

	function childHTML($domNode) {
		return $this->dom->saveHTML($domNode);
	}

	function UXI_Element($query_element) {
		return new UXI_Element($this->dom, $query_element, $this->layout_section, $this->element_type, $this->parent_id);
	}

	public function element_queries() {
		return array(
			'row' => new UXI_Parse_Query(
				'//*[@'.$this->layout_section.']//*[@data-layout]',
				function($query_element) {
					$this_element = $this->UXI_Element($query_element);

					$childHTML = $this->childHTML($query_element);
					$columns = new UXI_Parsed_Content($this->layout_section, 'column', $childHTML, $this->all_content, $this_element->id);
					return array_merge(array($this_element->id => $this_element), $columns->content);
				}
			),
			'row_nested' => new UXI_Parse_Query(
				'//*[@uxi-row]',
				function($query_element) {
					$this_element = $this->UXI_Element($query_element);

					$childHTML = $this->childHTML($query_element);
					$nested_columns = new UXI_Parsed_Content($this->layout_section, 'column_nested', $childHTML, $this->all_content, $this_element->id);

					return array_merge(array($this_element->id => $this_element), $nested_columns->content);
				}
			),
			'column' => new UXI_Parse_Query(
				'//*[@data-layout]/*[@class="container"]/*[@class="container-inner"]/*[@class="row"]/*',
				function($query_element) {
					$this_element = $this->UXI_Element($query_element);

					$childHTML = $this->childHTML($query_element);
					$nested_rows = new UXI_Parsed_Content($this->layout_section, 'row_nested', $childHTML, $this->all_content, $this_element->id);
					$widgets = new UXI_Parsed_Content($this->layout_section, 'widget', $childHTML, $this->all_content, $this_element->id);

					return array_merge(array($this_element->id => $this_element), $nested_rows->content, $widgets->content);
				}
			),
			'column_nested' => new UXI_Parse_Query(
				'//*[@class="row"]/*',
				function($query_element) {
					$this_element = $this->UXI_Element($query_element);

					$childHTML = $this->childHTML($query_element);
					$widgets = new UXI_Parsed_Content($this->layout_section, 'widget_nested', $childHTML, $this->all_content, $this_element->id);

					return array_merge(array($this_element->id => $this_element), $widgets->content);
				}
			),
			'widget' => new UXI_Parse_Query(
				'./*/*/*[@uxi-widget]',
				function($query_element) {
					$this_element = $this->UXI_Element($query_element);

					return array($this_element->id => $this_element);
				}
			),
			'widget_nested' => new UXI_Parse_Query(
				'//*[@uxi-widget]',
				function($query_element) {
					$this_element = $this->UXI_Element($query_element);

					return array($this_element->id => $this_element);
				}
			),
		);
	}

	public function __construct($layout_section, $element_type, $html, $all_content = array(), $parent_id = false) {

		$this->layout_section = $layout_section;
		$this->element_type = $element_type;

		if (!in_array($layout_section, self::$layout_sections) || !array_key_exists($this->element_type, $this->element_queries())) {
			return $all_content;
		}

		$this->all_content = $all_content;
		$this->parent_id = $parent_id;

		$this->dom = new DOMDocument();
		@$this->dom->loadHTML($html);

		$this->content = array_merge($this->all_content, $this->element_queries()[$this->element_type]->run_query($this->dom));
	}
}