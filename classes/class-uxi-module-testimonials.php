<?php

class UXI_Module_Testimonials extends UXI_Module {

	function build_modules() {
		$settings = array(
			'post_width' => '',
			'post_type' => 'mad360_testimonial',
			'show_author' => false,
			'show_date' => false,
		);

		$this->modules[] = $this->add_module('post-grid', $settings);
		$this->save_settings();
		return $this->modules;
	}

}