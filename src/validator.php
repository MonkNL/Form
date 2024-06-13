<?php

if(!class_exists('softError')){	class softError extends Exception { } }
if(!class_exists('hardError')){ class hardError extends Exception { } }

class form{
	public $elements = [];

}


function html2validate($html){

		//echo strtolower($element->getAttribute('type'));
		/*	$inputName = $input->getAttribute('name');
			$inputType = ;
			$inputValue = $_POST[$inputName] ?? ''; // Get the submitted value from $_POST

			// Assuming you have additional attributes in your HTML form
			$inputAttributes = $this->getInputAttributes($input);

			if ($this->validator->validateInput($inputType, $inputValue, $inputAttributes)) {
					echo "$inputName is valid.<br>";
			} else {
					echo "$inputName is invalid.<br>";
			}
			
	}*/
}

$inputs = [
	'button'=>['value'=>'button'],
	'checkbox'=>['required','value'=>'aap'],
	'color'=>[],
	'date'=>[],
	'datetime-local'=>[],
	'email'=>[],
	'file'=>[],
	'hidden'=>[],
	'image'=>[],
	'month'=>[],
	'number'=>['min'=>10],
	'password'=>[],
	'radio'=>[],
	'range'=>[],
	'reset'=>[],
	'search'=>[],
	'tel'=>[],
	'text'=>[],
	'time'=>[],
	'url'=>[],
	'week'=>[],
	'submit'=>['value'=>'submit'],
	];	

$html ="<form action='' method='post' novalidate>";
foreach($inputs as $input => $attributes){
	$attributes = array_map(fn($key,$value) => (is_int($key)?"{$value}":"{$key}='{$value}'"),array_keys($attributes),array_values($attributes));
	$attributes = implode(" ",$attributes);
	$html .= "<label>{$input}: <input name='{$input}' type='{$input}' {$attributes}/></label><br/>";
}
$html .="<select name='select'><option value='1'>option 1</option><option value='2'>option 2</option></select>";
$html .="</form>";

echo $html;
$form = new decodeForm($html);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
$form->validate();
}
