<?php

class UXI_Module_Copyright extends UXI_Module {

	function build_modules() {
		$settings = array(
			'text' => UXI_Common::replace_date_with_shortcode($this->content)
		);

		$this->modules[] = $this->add_module('rich-text', $settings);
		$this->save_settings();
		return $this->modules;
	}

}