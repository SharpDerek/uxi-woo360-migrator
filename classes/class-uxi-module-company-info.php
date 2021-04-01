<?php

class UXI_Module_Company_Info extends UXI_Module {

	function get_columns() {
		$columns_query = '//*[@class="grid-tab-6"]';

		$this->has_columns = $this->xpath->query($columns_query)->length > 0;
	}

	function get_section_1_content() {
		$address_query = '//*[@class="company-info-address"]';
		$map_link_query = '//*[@class="company-info-map-link"]';
		$phone_query = '//*[@class="company-info-phone"]';
		$email_query = '//*[@class="company-info-email"]';

		$queries = array(
			$address_query,
			$map_link_query,
			$phone_query,
			$email_query
		);

		$this->section_1_content = $this->get_queries_contents($queries);
	}

	function get_section_2_content() {
		$hours_heading_query = '//*[contains(@class, "company-info-hours-heading")]';
		$hours_query = '//ol[contains(@class, "company-info-hours")]';

		$queries = array(
			$hours_heading_query,
			$hours_query
		);

		$this->section_2_content = $this->get_queries_contents($queries);
	}

	function get_queries_contents($queries = array(), $sep = "\n") {
		$all_items_query = implode(" | ", $queries);

		$content_pieces = array();

		foreach($this->xpath->query($all_items_query) as $item) {
			$content_pieces[] = $item->ownerDocument->saveHTML($item);
		}

		if (!$content_pieces) {
			return "";
		} else {
			return implode($sep, $content_pieces);
		}
	}

	function has_payment_icons() {
		$payment_icons_query = '//ul[contains(@class, "payment-icons")]';

		return $this->xpath->query($payment_icons_query)->length > 0;
	}

	function build_modules() {
		$this->get_columns();
		$this->get_section_1_content();
		$this->get_section_2_content();
		$this->get_header_settings();

		if ($this->header_settings) {
			$header_module = $this->add_module('heading', $this->header_settings);

			$this->modules[] = $header_module;
		}

		if ($this->has_columns) {
			$this->build_columns_module();
		$this->build_payment_icons_module();
		} else {
			if ($this->section_1_content) {
				$section_1_settings = array(
					'text' => $this->section_1_content
				);
				$this->modules[] = $this->add_module('rich-text', $section_1_settings);
			}

			$this->build_payment_icons_module();

			if ($this->section_2_content) {
				$section_2_settings = array(
					'text' => $this->section_2_content
				);
				$this->modules[] = $this->add_module('rich-text', $section_2_settings);
			}
		}

		$this->save_settings();
		return $this->modules;
	}

	function build_columns_module() {
		$columns_text_sections = array(
			$this->section_1_content,
			$this->section_2_content
		);

		$columns_html_sections = array();

		foreach($columns_text_sections as $section) {
			if (!$section) {
				continue;
			}

			$columns_html_sections[] = "<div class=\"col-xs-12 col-md-6\">\n{$section}\n</div>";
		}

		if (!$columns_html_sections) {
			return;
		}

		$columns_html = implode("\n", $columns_html_sections);

		$settings = array(
			'text' => $columns_html
		);

		$this->modules[] = $this->add_module('rich-text', $settings);
	}

	function build_payment_icons_module() {
		if (!$this->has_payment_icons()) {
			return;
		}
		require_once(plugin_dir_path(__FILE__) . 'class-uxi-module-icon-group.php');
		$payment_icons = new UXI_Module_Icon_Group($this->element, $this->parent_node, $this->post_id);
		$this->modules = array_merge($this->modules, $payment_icons->modules);
	}
}