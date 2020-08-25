<?php

class UXI_Parse_Query {
	public function __construct($query, $query_callback = false) {
		$this->query = $query;
		if ($query_callback === false) {
			$query_callback = function($item) { return $item; };
		}
		$this->query_callback = $query_callback;
	}

	public function run_query($dom) {
		$xpath = new DOMXpath($dom);
		$return_query = array();
		foreach($xpath->query($this->query) as $query_element) {
			$return_query = array_merge($return_query, call_user_func($this->query_callback, $query_element));
		}
		return $return_query;
	}

	public function query_html($dom) {
		$xpath = new DOMXpath($dom);
		$return_html = "";
		foreach($xpath->query($this->query) as $query_element) {
			$html = $dom->saveHTML($query_element);
			$return_html .= call_user_func($this->query_callback, $html);
		}
		return $return_html;
	}
}