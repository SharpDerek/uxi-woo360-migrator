<?php

class UXI_Module_RichText extends UXI_Module {

	function build_modules() {
		$settings = array(
			'text' => $this->content
		);

		$this->modules[] = $this->add_module('rich-text', $settings);
		$this->save_settings();
		return $this->modules;
	}

}