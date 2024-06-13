<?php

class decodeForm{
	public $elements = [];
	public $method 	= 'get';
	private $isValid  = true;
	function __construct($html){
		$this->decode($html);
	}
	function decode($html){
		$dom = new DOMDocument();
		libxml_use_internal_errors(true);
		$dom->loadHTML($html);
		libxml_use_internal_errors(false);
		$xpath 			= new DOMXpath($dom);
		$elements		= $xpath->query('//form | //label | //input | //select | //textarea | //button');
		$form 			= [];
		foreach ($elements as $element) {
			if($element->tagName != 'form'){
				$form[] = new input($element,$this->method);
			  	continue;
			}
			if($element->getAttribute('method') != ""){
				$method = mb_strtolower($element->getAttribute('method'));
				$this->method = $method;
				continue;
			}

		}
		$this->elements = $form; 
	}
	function encode(){
		foreach($this->elements as $element){
			$attr = implode(" ",array_map(fn($k, $v) => "{$k}='$v'",array_keys($element['attributes']),array_values($element['attributes'])));
			echo "<{$element['tag']} $attr>";
		}
	}
	function validate(){
		foreach($this->elements as $element){
			if($element->getTageName() == 'label' || $element->getTageName() == 'form'){
				continue;
			}
			echo "<b>{$element->getName()}</b><br/>";
			echo "Value:".print_r($element->getValue(),true)."<br/>";
			echo "Attributes:".print_r($element->getAttributes(),true)."<br/>";
			echo "Valid: ".($element->valid()?"Valid":"Invalid")."<br/>";
			echo "Error: ".print_r($element->error(),true)."<br/>";
			echo "<br/>";
			if($element->valid() == false){
				$this->isValid = false;
			}
		}
	}
	function valid(){
		$this->validate();
		return $this->isValid;
	}

}