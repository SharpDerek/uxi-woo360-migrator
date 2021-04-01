<?php

class UXI_Module_HTML extends UXI_Module {

	function build_modules() {

		$settings = array(
			'html' => $this->content
		);

		$this->modules[] = $this->add_module('html', $settings);

		$this->save_settings();

		return $this->modules;
	}

}