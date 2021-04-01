<?php

class UXI_Module_Google_Map extends UXI_Module {

	function get_address() {
		$iframe_srcquery = '//iframe/@src';

		foreach($this->xpath->query($iframe_srcquery) as $iframe_src) {
			$src = $iframe_src->value;
			parse_str(parse_url($src, PHP_URL_QUERY), $params);
			if (array_key_exists('q', $params)) {
				$address = urldecode($params['q']);
				return $address;
			}
			break;
		}
		return;
	}

	function build_modules() {
		$settings = array(
			'address' => $this->get_address(),
		);

		$this->modules[] = $this->add_module('map', $settings);
		$this->save_settings();
		return $this->modules;
	}

}