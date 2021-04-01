<?php

class UXI_Module_Loop extends UXI_Module {

	function get_content() {
		$content_query = "//*[contains(@class, 'editor-content')]";

		$content_text = "";

		foreach($this->xpath->query($content_query) as $content) {
			$content_html = $this->inner_html($content);
			break;
		}

		$this->content_settings = array(
			'text' => $content_html
		);
	}

	function build_modules() {
		if (UXI_Common::is_themer($this->post_id)) {
			$settings = array(
				'connections' => array(
					'text' => (object) array(
						'object' => 'post',
						'property' => 'content',
						'field' => 'editor',
					)
				)
			);

			$this->modules[] = $this->add_module('rich-text', $settings);
		} else {
			$this->get_content();
			$this->get_header_settings("//h1[contains(@id, 'main-title')]");

			$this->modules[] = $this->add_module('heading', $this->header_settings);

			$this->modules[] = $this->add_module('rich-text', $this->content_settings);

		}
		$this->save_settings();
		return $this->modules;
	}

}