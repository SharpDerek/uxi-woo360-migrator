<?php

final class UXI_Common {
	
	// Runs a basic cURL request
	public static function uxi_curl($url, $request = "GET") {

		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => $url,
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
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

}