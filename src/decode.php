<?php

class decodeForm{
	public $elements 	= [];
	public $method 		= 'get';
	private $form 		= [];

	private $isValid  = true;
	function __construct($html){
		$this->form  = new Form();
		$this->decode($html);
	}

	function encode(){
		$elements = $this->form->getElements();
		foreach($this->elements as $element){
			$attr = implode(" ",array_map(fn($k, $v) => "{$k}='$v'",array_keys($element['attributes']),array_values($element['attributes'])));
			echo "<{$element['tag']} $attr>";
		}
	}
	
}