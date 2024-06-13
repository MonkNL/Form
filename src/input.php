<?php

if (!class_exists('softError')) {
class softError extends Exception { }
}

if (!class_exists('hardError')) {
class hardError extends Exception { }
}

class Input {
	private $element, $tagName, $method;
	private $isValid = true, $error = [], $validatedOn = [];
	public $attributes = [];

	private $regex = [
		'time' => '/^(?<hours>[0-1]?[0-9]|2[0-3]):(?<minutes>[0-5][0-9])$/',
		'week' => '/^(?<year>[0-9]{4})-W(?<week>[0-4]?[0-9]|5[0-3])$/',
		'month' => '/^(?<year>[0-9]{4})-(?<month>0?[0-9]|1[0-2])$/',
		'datetime-local' => '/^(?<year>[0-9]{4})-(?<month>0?[0-9]|1[0-2])-(?<day>[0-2]?[0-9]|3[0-2])T[0-9]{2}:[0-9]{2}$/',
	];

	private $format = [
		'time' => 'H:i',
		'week' => 'Y-WW',
		'month' => 'Y-m',
		'datetime-local' => 'Y-m-dTh:i',
	];

	/**
	 * Constructor for Input class.
	 * @param object $element - HTML element object.
	 * @param string $method - HTTP method ('get' or 'post').
	 * @return void
	 */
	function __construct(object $element, string $method = 'get') {
		$this->element = $element;
		$this->tagName = $element->tagName;
		$this->setMethod($method);
		$this->getAttributes();
	}

	/**
	 * Set the HTTP method for the input.
	 * @param string $method - HTTP method ('get' or 'post').
	 * @return void
	 */
	function setMethod($method) {
		$this->method = mb_strtolower($method);
	}

	/**
	 * Check if the input is an array type.
	 * @return bool - True if it's an array, false otherwise.
	 */
	function isArray() {
		if (preg_match('/(\[([0-9a-zA-Z]*)\])/', $this->getAttribute('name')) == false) {
			return false;
		}
		return true;
	}

	/**
	 * Get the name attribute of the input.
	 * @return string|null - The name attribute if it's not an array, null otherwise.
	 */
	function getName() {
		if (!$this->isArray()) {
			return $this->getAttribute('name');
		}
	}

	/**
	 * Get the tag name of the HTML element.
	 * @return string - The tag name.
	 */
	function getTagName() {
		return $this->tagName;
	}

	/**
	 * Get attributes of the HTML element.
	 * @return array|false - Array of attributes if available, false if none.
	 */
	function getAttributes() {
		if (!$this->element->hasAttributes()) {
			return false;
		}
		if (!empty($this->attributes)) {
			return $this->attributes;
		}
		foreach ($this->element->attributes as $attribute) {
			$name = mb_strtolower($attribute->nodeName);
			$value = mb_strtolower($ttribute->nodeValue);
			$this->attributes[$name] = $value;
		}
		return $this->attributes;
	}

	/**
	 * Get the value of a specific attribute.
	 * @param string $name - Attribute name.
	 * @return string|false - Attribute value if exists, false otherwise.
	 */
	function getAttribute($name) {
		$name = mb_strtolower($name);
		return $this->attributes[$name] ?? false;
	}

	/**
	 * Get the type attribute of the input.
	 * @return string|false - Type attribute if exists, false otherwise.
	 */
	function getType() {
		return $this->attributes['type'] ?? false;
	}

	/**
	 * Get the value of the input based on the HTTP method.
	 * @return mixed|null - Input value or null if it doesn't exist.
	 */
	function getValue() {
		$method = ($this->method == 'post') ? $_POST : $_GET;
		$method = ($this->getType() == 'file') ? $_FILES : $method;
		if (!$this->isArray()) {
			if (!isset($method[$this->getName()])) {
				return null;
			}
			return $method[$this->getName()];
		}
	}

	/**
	 * Get errors for the input.
	 * @param int|null $key - Specific key for array inputs.
	 * @return array|null - Array of errors or null if none.
	 */
	public function error(int $key = null): array|null {
		if (!$this->isArray() && !empty($this->error)) {
			return $this->error;
		}
		if ($this->isArray() && !empty($this->error[$key])) {
			return $this->error[$key];
		}
		return null;
	}

	/**
	 * Set error for the input.
	 * @param Exception $e - Exception object.
	 * @param int|null $key - Specific key for array inputs.
	 * @return void
	 */
	private function setError($e, $key = null): void {
		if (!$this->isArray()) {
			$this->error[] = $e->getMessage();
			return;
		}
		if (!isset($this->error[$key])) {
			$this->error[$key] = [];
		}
		$this->error[$key][] = $e->getMessage();
	}

	/* validator helpers */

	/**
	 * Validate input attributes.
	 * @return void
	 */
	private function validate() {
		$values = (array)$this->getValue();
		if (empty($values)) {
			$this->validateMethods('');
		}
		foreach ($values as $key => $value) {
				$this->validateMethods($value, $key);
		}
	}

	/**
	 * Validate specific attributes for the given value.
	 * @param mixed $value - Input value.
	 * @param int|string $key - Specific key for array inputs.
	 * @return bool - True if validation is successful, false otherwise.
	 */
	private function validateMethods($value, $key = 0) {
			foreach ($this->attributes as $attribute => $attribute_value) {
					if (method_exists($this, 'validate' . $attribute)) {
							try {
									call_user_func([$this, 'validate' . $attribute], $value);
									$this->validatedOn[] = 'validate' . $attribute;
							} catch (softError $e) {
									return true;
							} catch (hardError $e) {
									$this->setError($e, $key);
									$this->isValid = false;
							}
					}
			}
	}

	/**
	 * Validate input attributes and return overall validation status.
	 * @return bool - True if all validations are successful, false otherwise.
	 */
	public function valid() {
			$this->validate();
			return $this->isValid;
	}

	/**
	 * Validate the input using filter_var.
	 * @param mixed $value - Input value.
	 * @param int $filter - Filter to apply.
	 * @return bool - True if validation is successful, false otherwise.
	 */
	private function filterVar($value, $filter) {
			if (empty($element['value'])) {
					throw new softError(__("No value"));
			}
			if (filter_var($element['value'], $filter) === false) {
					throw new hardError(__("Invalid value"));
			}
			return true;
	}

	/* attributes validation */

	/**
	 * Validate the 'accept' attribute for file inputs.
	 * @param mixed $value - Input value.
	 * @return void
	 */
	private function validateAccept($value) {
			if ($this->getType() != 'file') {
					throw new softError(sprintf(_("Input `%s`  doesn't support this attribute: %s"), $this->getType(),"Accept"));
			}
	}

	/**
	 * Validate the 'pattern' attribute for text inputs.
	 * @param mixed $value - Input value.
	 * @return bool - True if validation is successful, false otherwise.
	 */
	private function validatePattern($value) {
			if (!in_array($this->getType(), ['text', 'search', 'url', 'tel', 'email', 'password'])) {
					throw new softError(sprintf(_("Input `%s`  doesn't support this attribute: %s"), $this->getType(),"pattern"));
			}
			if (empty($value)) {
					throw new softError(__("No value"));
			}
			if (empty($this->getAttribute($pattern))) {
					throw new softError(__("No %s attribute value", "pattern"));
			}
			if (filter_var($value, FILTER_VALIDATE_REGEXP, ["options" => ["regexp" => "/^" . $this->getAttribute('pattern') . "$/"]]) === false) {
					throw new hardError(__("Value doesn't match pattern"));
			}
			return true;
	}

	/**
	 * Validate the 'min' attribute for various input types.
	 * @param mixed $value - Input value.
	 * @return bool - True if validation is successful, false otherwise.
	 */
	private function validateMin($value) {
			if (!in_array($this->getType(), ['date', 'number', 'month', 'week', 'datetime-local', 'range', 'time'])) {
					throw new softError(sprintf(_("Input `%s`  doesn't support this attribute: %s"), $this->getType(),"Min"));
					return true;
			}
			if (empty($value)) {
					throw new softError(__("No value"));
					return true;
			}
			if (in_array($this->getType(), ['range', 'number'])) {
					if ($value < $this->getAttribute('min')) {
							throw new hardError(__("Value lower than required minimum"));
							return false;
					}
			} else {
					if (preg_match($this->regex[$this->getType()], $this->getAttribute('min')) == false || strtotime($this->getAttribute('min'))) {
							throw new softError(__("Invalid Attribute value"));
							return true;
					}
					if (preg_match($this->regex[$this->getType()], $value) == false || strtotime($value) == false) {
							throw new hardError(__("Value has an invalid format"));
							return false;
					}
					if (strtotime($value) < strtotime($this->getAttribute('min'))) {
							throw new hardError(__("Value lower than required minimum"));
							return false;
					}
			}
			return true;
	}

	/**
	 * Validate the 'max' attribute for various input types.
	 * @param mixed $value - Input value.
	 * @return void
	 */
	private function validateMax($value) {
			if (!in_array($this->getAttribute('type'), ['date', 'number', 'month', 'week', 'datetime-local', 'range', 'time'])) {
					throw new softError(sprintf(_("Input `%s`  doesn't support this attribute: %s"), $this->getType(),"Max"));
			}
	}

	/**
	 * Validate the 'required' attribute for specific input types.
	 * @param mixed $value - Input value.
	 * @return void
	 */
	private function validateRequired($value) {
			if ($this->tagName != 'textarea' && $this->tagName != 'select' && !in_array($this->getAttribute('type'), ['text', 'search', 'url', 'tel', 'email', 'password', 'checkbox'])) {
					throw new softError(sprintf(_("Input `%s`  doesn't support this attribute: %s"), $this->getType(),"Required"));
					return true;
			}
			if (empty($value)) {
					throw new hardError(__("Input is required"));
					return false;
			}
			return true;
	}
		/**
	 * Validate the 'required' attribute for specific input types.
	 * @param mixed $value - Input value.
	 * @return void
	 */
	private function validateStep($value){
	
		#date	An integer number of days
		#month	An integer number of months
		#week	An integer number of weeks
		#datetime-local, time	An integer number of seconds
		#range, number	An integer
		if(!in_array($this->getAttribute('type'),['date','month', 'week','datetime-local','range'])){
			throw new softError(sprintf(_("Input `%s`  doesn't support this attribute: %s"), $this->getType(),"Step"));
		}
	}
		/**
	 * Validate the 'required' attribute for specific input types.
	 * @param mixed $value - Input value.
	 * @return void
	 */
	private function validateMinlength($value){
		
		#text, search, url, tel, email, password; also on the <textarea> element
		if($this->tagName != 'textarea' && !in_array($this->getAttribute('type'),['text', 'search', 'url', 'tel', 'email', 'password'])){
			throw new softError(sprintf(_("Input `%s`  doesn't support this attribute: %s"), $this->getType(),"Minlength"));
			return true;
		}
		if(empty($value)){
			throw new softError(__("No value"));
			return true;
		}
		if(filter_var($this->getAttribute('minlength'), FILTER_VALIDATE_INT) === false){
			throw new softError(__("Invalid attribute value"));
			return true;
		}
		if(mb_strlen($value) < $this->getAttribute('minlength')){
			throw new hardError(__('Value is shorter than minimum required length'));
			return false;
		}
	}
		/**
	 * Validate the 'required' attribute for specific input types.
	 * @param mixed $value - Input value.
	 * @return void
	 */
	private function validateMaxlength($value){
		
		#text, search, url, tel, email, password; also on the <textarea> element
		if($this->tagName != 'textarea' && !in_array($this->getAttribute('type'),['text', 'search', 'url', 'tel', 'email', 'password'])){
			throw new softError(sprintf(_("Input `%s`  doesn't support this attribute: %s"), $this->getType(),"Maxlength"));
		}
		if(empty($value)){
			throw new softError(__("No value"));
		}
		if(filter_var($this->getAttribute('maxlength'), FILTER_VALIDATE_INT) === false){
			return true;
		}
		if(mb_strlen($value) > $this->getAttribute('maxlength')){
			throw new hardError(__('Value is longer than the maximum permitted length'));
		}
	}
	private function validateFile($value){
		switch($value['error']){
			case UPLOAD_ERR_INI_SIZE: 	
				throw new hardError(__('File exceeds max size in php.ini'));		
				return false;
				break;
			case UPLOAD_ERR_PARTIAL:		
				throw new hardError(__('File exceeds max size in html form'));		
				return false;
				break;
			case UPLOAD_ERR_NO_FILE: 		
				throw new hardError(__('File No file was uploaded'));						
				return false;
				break;
			case UPLOAD_ERR_NO_TMP_DIR:	
				throw new hardError(__('No /tmp dir to write to'));
				return false;
				break;
			case UPLOAD_ERR_CANT_WRITE:	
				throw new hardError(__('File:: Error writing to disk'));
				return false;
				break;
			default:
			return true;
		}
	}
		/**
	 * Validate the 'required' attribute for specific input types.
	 * @param mixed $value - Input value.
	 * @return void
	 */
	private function validateType($value){
		/*
			case UPLOAD_ERR_INI_SIZE: 	return ['File exceeds max size in php.ini'];break;
			case UPLOAD_ERR_PARTIAL:	return ['File exceeds max size in html form'];break;
			case UPLOAD_ERR_NO_FILE: 	return ['File No file was uploaded'];break;
			case UPLOAD_ERR_NO_TMP_DIR:	return ['No /tmp dir to write to'];break;
			case UPLOAD_ERR_CANT_WRITE:	return ['File:: Error writing to disk'];break;
		*/
		switch($this->getAttribute('type')){
			case 'email':
				return $this->filterVar($value,FILTER_VALIDATE_EMAIL);
			break;
			case 'file':
				return $this->validateFile($value,FILTER_VALIDATE_EMAIL);
			break;
			default:
				return true;
		}
	}
}
