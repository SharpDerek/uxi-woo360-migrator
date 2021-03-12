<?php

final class UXI_Common {

	public static $uxi_url = "";
	
	// Runs a basic cURL request
	public static function uxi_curl($url, $encoding = "", $request = "GET") {

		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => $url,
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => $encoding,
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => $request,
		  CURLOPT_POSTFIELDS => "",
		  CURLOPT_FOLLOWLOCATION => true,
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
		  return "cURL Error #:" . $err;
		} else {
		  return $response;
		}
		return false;
	}

	//Gets the proper permalink for a post, whether it's published or not
	public static function get_post_permalink($post_id) {
		return (get_post_status($post_id) == 'publish') ? get_permalink($post_id) : get_post_permalink($post_id, false, true);
	}

	public static function get_data_layout_post($data_layout, $type) {
		$data_layout_query = new WP_Query(array(
			'post_type' => 'fl-theme-layout',
			'meta_query' => array(
				array(
					'key' => '_data_layout',
					'value' => $data_layout,
					'compare' => 'LIKE'
				),
				array(
					'key' => '_fl_theme_layout_type',
					'value' => $type,
					'compare' => 'LIKE'
				),
				'relation' => 'AND'
			)
		));

		if ($data_layout_query->found_posts > 0) {
			return $data_layout_query->posts[0]->ID;
		}
		return false;
	}

	public static function get_global_post($global_type) {

		switch($global_type) {
			case 'header':
			case 'singular':
			case 'archive':
			case 'footer':
				break;
			default:
				return 0;
		}

		$data_layout_query = new WP_Query(array(
			'post_type' => 'fl-theme-layout',
			'meta_query' => array(
				array(
					'key' => '_global',
					'value' => $global_type,
					'compare' => 'LIKE'
				),
				'relation' => 'AND'
			)
		));

		if ($data_layout_query->found_posts > 0) {
			return $data_layout_query->posts[0]->ID;
		}
		return 0;
	}

	public static function filter_html($html) {
		$html = self::replace_image_urls($html);
		$html = self::get_gform_shortcode($html);

		return $html;
	}

	public static function get_gform_shortcode($html) {
		$dom = new DOMDocument();
		$encoded_html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
		@$dom->loadHTML($encoded_html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

		$xpath = new DOMXPath($dom);
		$form_query = '//*[contains(@id, "gform_wrapper_")]';

		$extra_garbage_query = '
		//*[contains(@id, "gform_wrapper_")]/following-sibling::iframe[contains(@name, "gform_ajax_frame")]|
		//*[contains(@id, "gform_wrapper_")]/following-sibling::script[contains(text(),"gform")]
		';

		foreach($xpath->query($extra_garbage_query) as $garbage) {
			$garbage->parentNode->removeChild($garbage);
		}

		foreach($xpath->query($form_query) as $form_wrapper) {
			$form_html = $dom->saveHTML($form_wrapper);

			$form_dom = new DOMDocument();
			@$form_dom->loadHTML($form_html);
			$form_xpath = new DOMXPath($form_dom);

			$form_id = str_replace('gform_wrapper_', '', $form_wrapper->getAttribute('id'));
			$has_title			= (!!$xpath->query('//*[contains(@class, "gform_title")]')		 ->length) ? 'true' : 'false';
			$has_description	= (!!$xpath->query('//*[contains(@class, "gform_description")]') ->length) ? 'true' : 'false';
			$is_ajax			= (!!$xpath->query('//form[contains(@target, "gform_ajax_")]')	 ->length) ? 'true' : 'false';

			$shortcode = $dom->createElement('p', "[gravityform id={$form_id} title={$has_title} description={$has_description} ajax={$is_ajax}]");

			$form_wrapper->parentNode->replaceChild($shortcode, $form_wrapper);
		}
		

		return $dom->saveHTML();
	}

	public static function replace_image_urls($html) {
		$dom = new DOMDocument();
		$encoded_html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
		@$dom->loadHTML($encoded_html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

		$xpath = new DOMXPath($dom);
		$image_query = '//img';

		foreach($xpath->query($image_query) as $image_element) {
			$img_src = $image_element->getAttribute('src');
			$new_img_src = self::media_url_replace($img_src);
			$image_element->setAttribute('src', $new_img_src);
		}
		return $dom->saveHTML();
	}

	public static function media_url_replace($url) {
		$new_url = preg_replace('/.+?(?=\/\d+\/\d+)/', untrailingslashit(wp_upload_dir()['baseurl']), $url);
		return $new_url;
	}

	public static function get_attachment_id_by_url($origin_url) {
		$attachment_id = 0;

		// First, see if an image created with this function already exists
		$args = array(
			'post_type' => 'attachment',
			'post_status' => 'inherit',
			'meta_query' => array(
				array(
					'key' => 'origin_url',
					'value' => $origin_url
				)
			)
		);
		$attachment_query = new WP_Query($args);
		while($attachment_query->have_posts()) {
			$attachment_query->the_post();
			$attachment_id = get_the_ID();
			break;
		}
		wp_reset_postdata();

		// If we found an image, return its ID
		if ($attachment_id) {
			return $attachment_id;
		}

		$filename = basename($origin_url);
		$file_url = $origin_url;
		$filepath = str_replace(
			trailingslashit(get_site_url()),
			trailingslashit(get_home_path()),
			$origin_url
		);

		require_once( ABSPATH . 'wp-admin/includes/image.php' );

		// Second, if the URL doesn't already belong to the site, upload the image to the uploads folder
		if (strpos($origin_url, get_site_url()) < 0) {
			$upload_dir = wp_upload_dir();
			$filepath = trailingslashit($upload_dir['path']) . $filename;
			$file_url = trailingslashit($upload_dir['url']) . $filename;
			$contents = file_get_contents($origin_url);
			$file = file_put_contents($filepath, $contents);
		}

		$filetype = wp_check_filetype($filename, null);
		$attachment_args = array(
			'post_mime_type' => $filetype['type'],
			'post_title' => $filename,
			'post_content' => '',
			'post_status' => 'inherit'
		);

		$attachment_id = wp_insert_attachment($attachment_args, $filepath);
		$metadata = wp_generate_attachment_metadata($attachment_id, $filepath);
		wp_update_attachment_metadata($attachment_id, $metadata);
		update_post_meta($attachment_id, 'origin_url', $origin_url);
		return $attachment_id;
	}

	public static function toPx($type, $value) {
		$base_font_size = 16;
		switch($type) {
			case 'em':
				$value *= $base_font_size;
				break;
		}
		return $value;
	}

	public static function class_split($class_string) {
		$class_string = preg_replace('/\s+/', ' .', $class_string);

		return explode(' ', $class_string);
	}

}