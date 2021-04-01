<?php

class UXI_Module_Loop_Header extends UXI_Module {

	function build_modules() {
		$settings = array(
			'tag' => 'h1',
			'connections' => array(
				'heading' => (object) array(
					'object' => 'post',
					'property' => 'title',
					'field' => 'text'
				)
			)
		);

		$this->modules[] = $this->add_module('heading', $settings);
		$this->save_settings();
		return $this->modules;
	}

}