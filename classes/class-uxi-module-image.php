<?php

class UXI_Module_Image extends UXI_Module {

	private $attachment_id;
	private $image_src;

	function get_image_data() {
		$xpath = $this->xpath($this->element['html']);
		$image_query = '//img';
		$image_src = "";

		foreach($xpath->query($image_query) as $image) {
			$src = $image->getAttribute('src');
			$image_src = UXI_Common::media_url_replace($src);
			break;
		}

		$this->attachment_id = UXI_Common::get_attachment_id_by_url($image_src);

		$this->image_src = wp_get_attachment_url($this->attachment_id);
	}

	function get_header() {
		$xpath = $this->xpath($this->element['html']);
		$header_query = '//h2';

		$this->header_class = "";
		$this->header_tag = "h1";
		$this->header_text = "";
		$this->has_header = false;

		foreach($xpath->query($header_query) as $header) {
			$this->has_header = true;
			$classes = $header->getAttribute('class');
			$tag_regex = '/h\d/';
			preg_match($tag_regex, $classes, $matches);
			if ($matches) {
				$this->header_tag = $matches[0];
				$this->header_class = trim(str_replace($this->header_tag, '', $classes));
			}
			$this->header_text = $header->textContent;
			break;
		}

		$settings = array(
			'class' => $this->header_class,
			'tag' => $this->header_tag,
			'heading' => $this->header_text,
		);

		$this->header_settings = $settings;
	}

	// function get_header_styles() {
	// 	require_once(plugin_dir_path(__FILE__) . 'class-uxi-style-rules.php');
	// 	$tag_rules = new UXI_Style_Rules(array(
	// 		$this->header_tag,
	// 		'.' . $this->header_tag
	// 	));

	// 	$header_classes = UXI_Common::class_split($this->header_class);
		
	// 	$class_rules = new UXI_Style_Rules($header_classes);

	// 	$margin_bottom = $tag_rules->get_style('margin-bottom');

	// 	$settings = array(
	// 		'margin_top' => 0,
	// 		'margin_left' => 0,
	// 		'margin_right' => 0,
	// 		'margin_bottom' => 0
	// 	);

	// 	if ($margin_bottom) {
	// 		$margin_bottom = $margin_bottom[0];
	// 		$settings['margin_bottom'] = $margin_bottom['value']['size'];
	// 		$settings['margin_unit'] = $margin_bottom['value']['unit'];
	// 	}

	// 	//$margin_medium = $tag_rules->get_style('margin-bottom', '767');

	// 	//var_dump($margin);
	// 	//var_dump($margin_medium);

	// 	return $settings;
	// }

	function build_modules() {
		$this->get_header();
		if ($this->has_header) {
			$header_module = $this->add_module('heading', $this->header_settings);

			$this->modules[] = $header_module;
			$this->settings[] = $this->header_settings;
		}

		$this->get_image_data();
		$settings = array(
			'photo' => $this->attachment_id,
			'photo_src' => $this->image_src,
			'margin_top' => 0,
			'margin_bottom' => 0,
			'margin_right' => 0,
			'margin_left' => 0,
			'align' => 'left'
		);

		$module = $this->add_module('photo', $settings);
		$module->get_data();

		$this->modules[] = $module;
		$this->settings[] = $settings;

		$this->save_settings();
		return $this->modules;
	}

}