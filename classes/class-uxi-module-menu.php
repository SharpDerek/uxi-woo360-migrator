<?php

class UXI_Module_Menu extends UXI_Module {

	protected $menu_query = '//ul[contains(@class,"nav")]';

	function get_menu_settings() {
		$this->menu_settings = array();

		foreach($this->xpath->query($this->menu_query) as $menu) {
			$menu_classes = $menu->getAttribute('class');

			if (strpos($menu_classes, 'nav-stacked') > -1) {
				$this->menu_settings['menu_layout'] = 'vertical';
			} else if (strpos($menu_classes, 'nav-horizontal') > -1) {
				$this->menu_settings['menu_layout'] = 'horizontal';
			}

			if (strpos($menu_classes, 'nav-right') > -1) {
				$this->menu_settings['menu_align'] = 'right';
			} else if (
				strpos($menu_classes, 'nav-center') > -1 ||
				strpos($menu_classes, 'nav-justified') > -1
			) {
				$this->menu_settings['menu_align'] = 'center';
			} else {
				$this->menu_settings['menu_align'] = 'left';
			}
			break;
		}
	}

	function get_all_wordpress_menus() {
		$menus = array();
		$menu_args = array(
			'hide_empty' => false
		);
		$menu_terms = get_terms('nav_menu', $menu_args);
		foreach($menu_terms as $menu) {
			$menu_atts = array();
			foreach(wp_get_nav_menu_items($menu->slug) as $menu_item) {
				$menu_atts[] = array(
					'url' => $menu_item->url,
					'title' => html_entity_decode($menu_item->title)
				);
			}
			$menus[$menu->slug] = $menu_atts;
		}
		return $menus;
	}

	function get_menu_name() {
		$menu_item_query = $this->menu_query . '//li/a';

		$menus = $this->get_all_wordpress_menus();

		// Try to match the URL and Title of each menu item to an existing menu
		foreach($menus as $menu_name => $menu) {
			$matches = 0;
			$index = 0;
			foreach($this->xpath->query($menu_item_query) as $menu_item) {
				$menu_item_url = trailingslashit(
					UXI_Common::url_replace(
						$menu_item->getAttribute('href')
					)
				);
				$menu_item_title = $menu_item->textContent;

				$menu_url = trailingslashit($menu[$index]['url']);
				$menu_title = $menu[$index]['title'];

				if ($menu_url === $menu_item_url && $menu_title === $menu_item_title) {
					$matches++;
				} else {
					break;
				}

				$index++;
			}
			// If we have the same number of matches as we do menu items, we've found our match
			if ($matches == count($menu)) {
				return $menu_name;
			}
		}
		// If not, just return the first menu's slug
		return array_keys($menus)[0];
	}

	function build_modules() {
		$this->get_menu_settings();
		$this->get_header_settings('//nav/h2');

		$menu_settings = array_merge(
			$this->menu_settings,
			array(
				'menu' => $this->get_menu_name()
			)
		);

		if ($this->header_settings) {
			if (strpos($header_settings['class'], 'sr-only') < 0) {
				$this->modules[] = $this->add_module('heading', $this->header_settings);
			}
			$menu_settings['mobile_title'] = $this->header_settings['heading'];
		}

		$this->modules[] = $this->add_module('menu', $menu_settings);
		$this->save_settings();
		return $this->modules;
	}

}