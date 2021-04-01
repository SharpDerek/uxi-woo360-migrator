<?php

class UXI_Module_Posts extends UXI_Module {

	function build_modules() {
		$settings = array(
			'post_width' => ''
		);

		$this->modules[] = $this->add_module('post-grid', $settings);
		$this->save_settings();
		return $this->modules;
	}

}