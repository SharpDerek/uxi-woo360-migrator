<?php

class UXI_Module_Video extends UXI_Module {

	function get_video_settings() {
		$video_iframe_query = '//iframe';

		$this->video_settings = array();

		foreach($this->xpath->query($video_iframe_query) as $video_iframe) {
			$this->video_settings['video_type'] = 'embed';
			$this->video_settings['embed_code'] = $video_iframe->ownerDocument->saveHTML($video_iframe);
			break;
		}
	}

	function build_modules() {
		$this->get_video_settings();
		$this->get_header_settings();

		if ($this->header_settings) {
			$header_module = $this->add_module('heading', $this->header_settings);

			$this->modules[] = $header_module;
		}

		if ($this->video_settings) {

			$this->modules[] = $this->add_module('video', $this->video_settings);
		}

		$this->save_settings();
		return $this->modules;
	}
}