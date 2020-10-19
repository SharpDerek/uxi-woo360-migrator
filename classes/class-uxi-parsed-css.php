<?php

class UXI_Parsed_CSS {

	public $contents = array();

	public function __construct($css) {
		$cssParser = new Sabberworm\CSS\Parser($css);
		$parsed_css = $cssParser->parse();

		//var_dump($parsed_css);

		foreach($parsed_css->getContents() as $contents) {
			switch(get_class($contents)) {
				case 'Sabberworm\CSS\RuleSet\AtRuleSet':
					$this->contents[] = $this->getAtRuleSet($contents);
					break;
				case 'Sabberworm\CSS\RuleSet\DeclarationBlock':
					$this->contents[] = $this->getDeclarationBlock($contents);
					break;
				case 'Sabberworm\CSS\CSSList\AtRuleBlockList':
					$this->contents = array_merge($this->contents, $this->getAtRuleBlockList($contents));
					break;
			}
		}
	}

	function getAtRuleSet($atRuleSet) {
		$rules = array();

		foreach($atRuleSet->getRules() as $rule) {
			$rules[$rule->getRule()] = $this->getRuleValue($rule->getValue());
		}

		return array(
			'selectors' => array(
				'@' . $atRuleSet->atRuleName()
			),
			'rules' => $rules
		);
	}

	function getDeclarationBlock($declarationBlock, $mediaQuery = false) {
		$selectors = array();
		$rules = array();
		foreach($declarationBlock->getSelectors() as $selector) {
			$thisSelector = $selector->getSelector();
			$selectors[] = $thisSelector;
		}
		foreach($declarationBlock->getRules() as $rule) {
			$rules[$rule->getRule()] = $this->getRuleValue($rule->getValue());
		}
		return array(
			'selectors' => $selectors,
			'mediaQuery' => $mediaQuery,
			'rules' => $rules
		);
	}

	function getAtRuleBlockList($atRuleBlockList) {
		$mediaQuery = '@' . $atRuleBlockList->atRuleName() . ' ' . $atRuleBlockList->atRuleArgs();
		$declarationBlocks = array();

		foreach($atRuleBlockList->getContents() as $declarationBlock) {
			$declarationBlocks[] = $this->getDeclarationBlock($declarationBlock, $mediaQuery);
		}

		return $declarationBlocks;
	}

	function getRuleValue($ruleValue) {
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
			$components[] = $this->getRuleValue($component);
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
			$arguments[] = $this->getRuleValue($argument);
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