<?php

final class UXI_Common {

	public static $uxi_url = "";
	
	// Runs a basic cURL request
	public static function uxi_curl($url = null, $encoding = "", $request = "GET", $postfields = "", $execute = true) {

		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => is_null($url) ? self::$uxi_url : $url,
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => $encoding,
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => $request,
		  CURLOPT_POSTFIELDS => $postfields,
		  CURLOPT_FOLLOWLOCATION => true,
		));

		if (!$execute) {
			return $curl;
		}

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
			return false;
		  //return "cURL Error #:" . $err;
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
		if (gettype($data_layout) !== 'string') {
			return false;
		}
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

	static $global_types = array(
		'header',
		'singular',
		'archive',
		'footer'
	);

	public static function get_global_post($global_type) {
		if (!in_array($global_type, self::$global_types)) {
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

	public static function get_global_data_layout($global_type) {
		if (!in_array($global_type, self::$global_types)) {
			return false;
		}
		return get_post_meta(self::get_global_post($global_type), '_data_layout', true);
	}

	public static function is_themer($post_id) {
		return get_post_type($post_id) == 'fl-theme-layout';
	}

	public static function filter_html($html) {
		$html = self::replace_image_urls($html);
		$html = self::get_gform_shortcode($html);
		return $html;
	}

	public static function get_gform_shortcode($html) {
		$dom = new DOMDocument();
		$encoded_html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
		@$dom->loadHTML($encoded_html, LIBXML_HTML_NODEFDTD);

		$xpath = new DOMXPath($dom);
		$form_query = '//*[contains(@id, "gform_wrapper_")]';

		$extra_garbage_query = '
		//*[contains(@id, "gform_wrapper_")]/following-sibling::iframe[contains(@name, "gform_ajax_frame")]|
		//*[contains(@id, "gform_wrapper_")]/following-sibling::script[contains(text(),"gform")]
		';

		if (!$xpath->query($form_query)->length) {
			return $html;
		}

		foreach($xpath->query($extra_garbage_query) as $garbage) {
			$garbage->parentNode->removeChild($garbage);
		}

		foreach($xpath->query($form_query) as $form_wrapper) {
			$form_id = str_replace('gform_wrapper_', '', $form_wrapper->getAttribute('id'));

			$title_query = './/*[contains(@class, "gform_title")]';
			$description_query = './/*[contains(@class, "gform_description")]';
			$ajax_query = './/form[contains(@target, "gform_ajax_")]';

			$has_title = ($xpath->query($title_query, $form_wrapper)->length)
			? 'true'
			: 'false';

			$has_description = ($xpath->query($description_query, $form_wrapper)->length)
			? 'true' :
			'false';

			$is_ajax = ($xpath->query($ajax_query, $form_wrapper)->length)
			? 'true'
			: 'false';

			$shortcode = $dom->createElement('p', "[gravityform id={$form_id} title={$has_title} description={$has_description} ajax={$is_ajax}]");

			$form_wrapper->parentNode->replaceChild($shortcode, $form_wrapper);
		}

		$new_html = $dom->saveHTML();

		$new_html = trim(
			preg_replace(
				'/[\s\S]+\<body\>|<\/body>[\s\S]+/',
				'',
				$new_html
			)
		);

		return $new_html;
	}

	public static function replace_image_urls($html) {
		return preg_replace(
			'/(?<=src=").+?(?=\/\d+\/\d+\/)/',
			untrailingslashit(wp_upload_dir()['baseurl']),
			$html
		);
	}

	public static function media_url_replace($url) {
		$new_url = preg_replace('/.+?(?=\/\d+\/\d+\/)/', untrailingslashit(wp_upload_dir()['baseurl']), $url);

		return self::remove_image_size($new_url);
	}

	public static function remove_image_size($url) {
		if (preg_match_all('/-\d+x\d+(\..+)/', $url, $matches, PREG_PATTERN_ORDER)) {
			$url = str_replace($matches[0][0], $matches[1][0], $url);
		}
		return $url;
	}

	public static function uxi_urls() {
		$url = trailingslashit(self::$uxi_url);
		return array(
			'https://www.' . $url,
			'http://www.' . $url,
			'https://' . $url,
			'http://' . $url,
			$url
		);
	}

	public static function url_replace($url) {
		return str_replace(self::uxi_urls(), trailingslashit(site_url()), $url);
	}

	public static function get_attachment_id_by_url($origin_url) {
		$origin_url = self::remove_image_size($origin_url);

		// First, see if an image with this URL already exists
		$attachment_id = attachment_url_to_postid($origin_url);
		
		// $args = array(
		// 	'post_type' => 'attachment',
		// 	'post_status' => 'inherit',
		// 	'meta_query' => array(
		// 		array(
		// 			'key' => 'origin_url',
		// 			'value' => $origin_url
		// 		)
		// 	)
		// );
		// $attachment_query = new WP_Query($args);
		// while($attachment_query->have_posts()) {
		// 	$attachment_query->the_post();
		// 	$attachment_id = get_the_ID();
		// 	break;
		// }
		// wp_reset_postdata();

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

	public static function class_concat($class_array) {
		if (!$class_array) {
			return '';
		}
		return trim(implode(" ", $class_array));
	}

	public static function icon_name($name) {
		$icon_name = str_replace('icon-uxis', 'uxi-icon', $name);
		return $icon_name;
	}

	public static function replace_date_with_shortcode($content) {
		$year = date("Y");

		return str_replace($year, '[fl_year]', $content);
	}

	public static function get_migration_status() {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1", 'uxi_migration_status' ) );
 		$value = false;
        if ( is_object( $row ) ) {
            $value = $row->option_value;
        }
		return $value ? $value : null;
	}

	public static function clear_migration_status() {
		delete_option('uxi_migration_status');
	}

	public static function set_migration_status($status) {
		switch($status) {
			case 'running':
			case 'stopping':
			case 'stopped':
			case 'done':
				update_option('uxi_migration_status', $status);
				break;
		}
	}

	public static function get_migration_progress() {
		return get_option('uxi_migration_progress');
	}

	public static function set_migration_progress($message, $current_step = false, $max_steps = false) {
		$current_status = self::get_migration_progress();

		$new_status = array(
			'message' => $message
		);
		if ($current_step !== false) {
			$new_status['current_step'] = $current_step;
		}
		if ($max_steps !== false) {
			$new_status['max_steps'] = $max_steps;
		} else if (array_key_exists('max_steps', $current_status)) {
			$new_status['max_steps'] = $current_status['max_steps'];
		}

		update_option('uxi_migration_progress', $new_status);
	}

	public static function update_migration_progress($message, $increment = 1) {
		$progress = self::get_migration_progress();

		if (array_key_exists('current_step', $progress)) {
			$progress['current_step'] += $increment;
		}

		$progress['message'] = $message;

		update_option('uxi_migration_progress', $progress);
	}

}