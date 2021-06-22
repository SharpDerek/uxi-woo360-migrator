<?php

function uxi_deposit_plugins(WP_REST_Request $request){
	if (!check_ajax_referer('wp_rest', '_wpnonce') ){
		return "Invalid nonce";
	}

	$plugin = $request['plugin'];
	$action = $request['action'];

	switch($plugin) {
		case 'uxi-resources':
			switch($action) {
				case 'install':
					uxi_install_resources_plugin();
					return "Depositing UXi Resources Plugin";
				case 'activate':
					uxi_activate_resources_plugin();
					return "Activating UXi Resources Plugin";
			}
	}

}