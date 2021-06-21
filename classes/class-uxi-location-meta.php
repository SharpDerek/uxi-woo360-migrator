<?php

require_once(plugin_dir_path(__FILE__) . 'class-uxi-parse-query.php');

class UXI_Location_Meta {

	public $meta = array();

	public function __construct($dom) {
		$this->dom = $dom;

		$this->meta = array_merge(
			$this->get_address_meta(),
			$this->get_phone_meta(),
			$this->get_fax_meta(),
			$this->get_email_meta(),
			$this->get_website_meta(),
			$this->get_hours_meta()
		);
	}

	public function get_hours_meta() {
		$hours_query = new UXI_Parse_Query(
			"//ol[contains(@class, 'company-info-hours')]",
			function($hours_list) {
				return $hours_list->ownerDocument->saveHTML($hours_list);
			},
			true
		);

		$hours = $hours_query->run_query($this->dom);

		if (!$hours) {
			return array();
		}

		return array(
			'uxi_hours_unformatted' => $hours
		);
	}

	public function get_address_meta() {
		$index = 0;

		$address_query = new UXI_Parse_Query(
			"//*[contains(@class, 'company-info-address')]/span/span",
			function($address_piece) use (&$index) {

				$children = $address_piece->childNodes;
				$child_contents = array();
				$has_break = false;

				foreach($children as $child) {
					if (property_exists($child, 'tagName') && $child->tagName == 'br') {
						$has_break = true;
					} else {
						$child_contents[] = trim($child->nodeValue);
					}
				}

				$value = array();

				switch($index) {
					case 0:
						if ($has_break) {
							$value = array(
								'wpsl_address' => $child_contents[0],
								'wpsl_wpsl_address2' => $child_contents[1]
							);
						} else {
							$value = array(
								'wpsl_address' => trim($address_piece->nodeValue)
							);
						}
						break;
					case 1:
						$value = array(
							'wpsl_city' => trim($address_piece->nodeValue)
						);
						break;
					case 2:
						$value = array(
							'wpsl_state' => trim($address_piece->nodeValue)
						);
						break;
					case 3:
						$value = array(
							'wpsl_zip' => trim($address_piece->nodeValue)
						);
						break;
				}

				$index++;
				return $value;
			}
		);

		$address_meta = $address_query->run_query($this->dom);
		if (!is_array($address_meta)) {
			$address_meta = array();
		}
		$address_meta['wpsl_country'] = 'United States';

		return $address_meta;
	}

	public function get_phone_meta() {
		return $this->get_text_meta(
			"//*[contains(@class, 'company-info-tel')]//a/text()",
			'wpsl_phone'
		);
	}

	public function get_fax_meta() {
		return $this->get_text_meta(
			"//*[contains(@class, 'company-info-fax')]//a/text()",
			'wpsl_fax'
		);
	}

	public function get_email_meta() {
		return $this->get_text_meta(
			"//*[contains(@class, 'company-info-email')]//a/text()",
			'wpsl_email'
		);
	}

	public function get_website_meta() {
		return $this->get_value_meta(
			"//*[contains(@class, 'company-info-ext-link')]//a/@href",
			'wpsl_url'
		);
	}

	public function get_text_meta($query, $key) {
		$text_query = new UXI_Parse_Query(
			$query,
			function($text) {
				return $text->textContent;
			},
			true
		);

		$text_meta = array();

		$text_content = $text_query->run_query($this->dom);

		if ($text_content) {
			$text_meta[$key] = $text_content;
		}

		return $text_meta;
	}

	public function get_value_meta($query, $key) {
		$value_query = new UXI_Parse_Query(
			$query,
			function($value) {
				return $value->value;
			},
			true
		);

		$value_meta = array();

		$value = $value_query->run_query($this->dom);

		if ($value) {
			$value_meta[$key] = $value;
		}

		return $value_meta;
	}
}