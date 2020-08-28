<?php

class UXI_Parsed_CSS {

	public $contents = array();

	public function __construct($css) {
		$cssParser = new Sabberworm\CSS\Parser($css);
		$parsed_css = $cssParser->parse();

		foreach($parsed_css->getAllDeclarationBlocks() as $block) {
			$selectors = array();
			//$relevantItems = array();
			$rules = array();
			foreach($block->getSelectors() as $selector) {
				$thisSelector = $selector->getSelector();
				$selectors[] = $thisSelector;
				// foreach(explode(" ", $thisSelector) as $item) {
				// 	if (!in_array($item, $relevantItems)) {
				// 		$relevantItems[] = $item;
				// 	}
				// }
			}
			foreach($block->getRules() as $rule) {
				$rules[$rule->getRule()] = $this->getActualRuleValue($rule->getValue());
			}
			$this->contents[] = array(
				'selectors' => $selectors,
				//'relevantItems' => $relevantItems,
				'rules' => $rules
			);
		}
	}

	function getActualRuleValue($ruleValue) {
		if (is_object($ruleValue)) {
			switch(get_class($ruleValue)) {
				case "Sabberworm\CSS\Value\RuleValueList":
					return array(
						'type' => 'list',
						'value' => $this->getRuleValueList($ruleValue)
					);
				case "Sabberworm\CSS\Value\CSSString":
					return array(
						'type' => 'string',
						'value' => $this->getCSSString($ruleValue)
					);
				case "Sabberworm\CSS\Value\Size":
					return array(
						'type' => 'size',
						'value' => $this->getSize($ruleValue)
					);
				case "Sabberworm\CSS\Value\Color":
					return array(
						'type' => 'color',
						'value' => $this->getColor($ruleValue)
					);
				case "Sabberworm\CSS\Value\CSSFunction":
					return array(
						'type' => 'function',
						'value' => $this->getCSSFunction($ruleValue)
					);
				case "Sabberworm\CSS\Value\URL":
					return array(
						'type' => 'url',
						'value' => $this->getURL($ruleValue)
					);
				default:
					return get_class($ruleValue);
			}
		} else {
			return array (
				'type' => 'string',
				'value' => $ruleValue
			);
		}
	}

	function getRuleValueList($ruleValueList) {
		$components = array();
		foreach($ruleValueList->getListComponents() as $component) {
			$components[] = $this->getActualRuleValue($component);
		}
		return $components;
	}

	function getSize($size, $isColor = false) {
		if ($isColor) {
			return $size->getSize();
		} else {
			return array(
				'size' => $size->getSize(),
				'unit' => $size->getUnit(),
			);
		}
	}

	function getColor($color) {
		$colors = array();
		foreach($color->getColor() as $channel => $colorValue) {
			$colors[$channel] = $this->getSize($colorValue, true);
		}
		return $colors;
	}

	function getCSSFunction($cssFunction) {
		$name = $cssFunction->getName();
		$arguments = array();

		foreach($cssFunction->getArguments() as $argument) {
			$arguments[] = $this->getActualRuleValue($argument);
		}

		return array(
			'name' => $name,
			'arguments' => $arguments
		);
	}

	function getURL($url) {
		return $this->getCSSString($url->getURL());
	}

	function getCSSString($cssString) {
		return $cssString->getString();
	}
}