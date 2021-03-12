<?php

require_once(plugin_dir_path(__FILE__) . 'class-uxi-files-handler.php');

class UXI_Style_Map {

	private static $files = array(
		'uxi-site-css-parsed.json',
		'uxi-site-custom-css-parsed.json'
	);

	public static $mq_large = array(992, 1500);
	public static $mq_medium = array(768, 991);
	public static $mq_small = array(0, 767);

	public $map = array();

	function __construct($styles, $style_schema) {
		if ($styles) {
			foreach($styles as $style) {
				$rules = $style['rules'];
				$query = $style['mediaQuery'];
				foreach($rules as $rule_name => $rule) {
					foreach($style_schema as $schema_item_name => $schema_item) {
						if ($schema_item['rule'] !== $rule_name) {
							continue;
						}
						if (array_key_exists('dep', $schema_item)) {
							if (!array_key_exists($schema_item['dep'], $this->map)) {
								continue;
							}
						}
						if (array_key_exists('range', $schema_item) && $query) {
							$min = $schema_item['range'][0];
							$max = $schema_item['range'][1];
							if (!self::query_in_range($min, $max, $query)) {
								continue;
							}
						}

						$value = self::format_rule_value($rule, $schema_item['att']);

						if (array_key_exists('compare', $schema_item)) {
							$matches = false;
							foreach($schema_item['compare'] as $compare) {
								if (array_key_exists($compare, $this->map)) {
									if ($this->map['compare'] === $value) {
										$matches = true;
										break;
									}
								}
							}
							if ($matches) {
								continue;
							}
						}
						if (array_key_exists('value_if_exists', $schema_item)) {
							$this->map[$schema_item_name] = $schema_item['value_if_exists'];
						} else {
							$this->map[$schema_item_name] = $value;
						}
					}
				}
			}
		}
	}

	public static function get_styles_from_selectors($selectors) {
		if (!$selectors) {
			return;
		}

		if (!is_array($selectors)) { // Force selectors into array if individual
			$selectors = array($selectors);
		}

		$styles = array();
		foreach(self::$files as $file) {
			$contents = json_decode(UXI_Files_Handler::get_file($file), true);
			foreach($contents as $block) {
				$applied_block_selectors = array_intersect($block['selectors'], $selectors);
				if ($applied_block_selectors) {
					$styles[] = $block;
				}
			}
		}
		return $styles;
	}

	public static function parse_media_query($query) {

		$query_regex = '/(\w+-width).+?([\d\.]+)/';

		preg_match_all($query_regex, $query, $match_sets, PREG_SET_ORDER);

		return $match_sets;
	}

	public static function query_in_range($min, $max, $query) {
		if (is_nan($min) || is_nan($max) || !$query) {
			return true;
		}

		$match_sets = self::parse_media_query($query);

		foreach($match_sets as $matches) {
			$operator = $matches[1];
			$amount = $matches[2];

			switch($operator) {
				case 'min-width':
					$min = $amount;
					break;
				case 'max-width':
					$max = $amount;
					break;
			}
		}

		return $amount >= $min && $amount <= $max;
	}

	public static function format_rule_value($rule, $att = false) {
		switch($rule['type']) {
			case 'color':
				return self::format_color_value($rule['value']);
			case 'size':
				return self::format_size_value($rule['value'], $att);
			case 'string':
				return $rule['value'];
			case 'url':
				return self::format_url_value($rule['value'], $att);
			case 'list':
				return self::format_list_value($rule['value'], $att);
		}
	}

	public static function format_color_value($value) {
		if (!$value) {
			return;
		}

		if (!is_array($value)) { // Hex
			return str_replace('#', '', $value);
		} else { // RGB || RGBA
			extract($value);

			if (isset($a)) {
				return "rgba({$r},{$g},{$b},{$a})";
			}

			$r = dechex($r);
			if (strlen($r)<2) {
				$r = '0'.$r;
			}

			$g = dechex($g);
			if (strlen($g)<2) {
				$g = '0'.$g;
			}

			$b = dechex($b);
			if (strlen($b)<2) {
				$b = '0'.$b;
			}

		    return "{$r}{$g}{$b}";
		}
	}

	public static function format_url_value($value, $att) {
		if (!$value) {
			return;
		}

		$url = UXI_Common::media_url_replace($value);
		$id = UXI_Common::get_attachment_id_by_url($url);

		switch($att) {
			default:
			case 'url':
				return $url;
			case 'id':
				return $id;
			case 'all':
				return array(
					'url' => $url,
					'id' => $id
				);
		}
	}

	public static function format_size_value($value, $att) {
		if (!$value) {
			return;
		}

		if ($att == 'all') {
			return $value;
		} else if ($att && array_key_exists($att, $value)) {
			return $rule['value'][$att];
		}
		return $rule['value']['size'];
	}

	public static function format_list_value($value, $att) {
		if (!$value) {
			return;
		}

		switch($att) {
			case 'border':
				return self::format_border_list_value($value);
			case 'margin':
				return self::format_margin_list_value($value);
			case 'position':
				return self::format_position_list_value($value);
			case 'standard':
				return self::format_standard_list_value($value);
		}
	}

	public static function format_border_list_value($value) {
		$value_items = array();

		for($i = 0; $i < count($value); $i++) {
			switch($i) {
				case 0:
					$value_items['width'] = self::format_size_value($value[$i], 'all');
					break;
				case 1:
					$value_items['style'] = self::format_rule_value($value[$i]);
					break;
				case 2:
					$value_items['color'] = self::format_color_value($value[$i]);
					break;
			}
		}
		return $value_items;
	}

	public static function format_margin_list_value($value) {
		$value_items = array();

		switch(count($value)) {
			case 2:
				for($i = 0; $i < count($value); $i++) {
					switch($i) {
						case 0:
							$value_items['top'] =
							$value_items['bottom'] =
							self::format_size_value($value[$i], 'all');
							break;
						case 1:
							$value_items['right'] =
							$value_items['left'] =
							self::format_rule_value($value[$i]);
							break;
					}
				}
			case 3:
				for($i = 0; $i < count($value); $i++) {
					switch($i) {
						case 0:
							$value_items['top'] =
							self::format_size_value($value[$i], 'all');
							break;
						case 1:
							$value_items['right'] =
							$value_items['left'] =
							self::format_rule_value($value[$i]);
							break;
						case 2:
							$value_items['bottom'] =
							self::format_color_value($value[$i]);
							break;
					}
				}
				break;
			case 4:
				for($i = 0; $i < count($value); $i++) {
					switch($i) {
						case 0:
							$value_items['top'] =
							self::format_size_value($value[$i], 'all');
							break;
						case 1:
							$value_items['right'] =
							self::format_rule_value($value[$i]);
							break;
						case 2:
							$value_items['bottom'] =
							self::format_color_value($value[$i]);
							break;
						case 3:
							$value_items['left'] =
							self::format_color_value($value[$i]);
							break;
					}
				}
				break;
		}
		return $value_items;
	}

	public static function format_position_list_value($value) {
		$value_items = array();

		for($i = 0; $i < count($value); $i++) {
			switch($i) {
				case 0:
					$value_items['x'] = self::format_size_value($value[$i], 'all');
					break;
				case 1:
					$value_items['y'] = self::format_rule_value($value[$i]);
					break;
			}
		}
		return $value_items;
	}

	public static function format_standard_list_value($value) {
		$value_items = array();

		for($i = 0; $i < count($value); $i++) {
			$value_items[] = self::format_rule_value($value[$i], 'all');
		}
		return $value_items;
	}
}