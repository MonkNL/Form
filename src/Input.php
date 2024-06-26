<?php
namespace Forms;

// Exception class for invalid input
class InvalidInput extends \Exception { }

// Exception class for invalid code alerts
class InvalidCodeAlert extends \Exception { }

class Input {
	private $type, $method = null,$form = null,$options = [],$dynamicSelect = false;
	private $isValid = true, $error = [],$codeAlerts = [], $validatedOn = [];
	public $attributes = [];

	// Regular expressions for various input types
	private $regex = [
		'time' => '/^(?<hours>[0-1]?[0-9]|2[0-3]):(?<minutes>[0-5][0-9])$/',
		'week' => '/^(?<year>[0-9]{4})-W(?<week>[0-4]?[0-9]|5[0-3])$/',
		'month' => '/^(?<year>[0-9]{4})-(?<month>0?[0-9]|1[0-2])$/',
		'datetime-local' => '/^(?<year>[0-9]{4})-(?<month>0?[0-9]|1[0-2])-(?<day>[0-2]?[0-9]|3[0-2])T[0-9]{2}:[0-9]{2}$/',
	];

	// Formats for various input types
	private $format = [
		'time' => 'H:i',
		'week' => 'Y-WW',
		'month' => 'Y-m',
		'datetime-local' => 'Y-m-dTh:i',
	];

	/**
	 * Constructor for Input class.
	 * @param string $method - HTTP method ('get' or 'post').
	 * @return void
	 */
	function __construct(Form $form = null,string $method = null): object  {
		if(!is_null($method)){
			$this->setMethod($form);
		}
		if(!is_null($form)){
			$this->setForm($form);
		}
		return $this;
	}
	function setForm(Form $form){
		$this->form = $form;
	}
	/**
	 * Import an object into the Input class.
	 * @param DOMNode $element - HTML element object.
	 * @return Input - An instance of the Input class.
	 */
	static function importObject(DOMNode $element) {
		$input 			= new self();
		$input->type 	= $element->tagName;
		if($input->type  == 'select'){
			$input->getOptionsFormObject($element);
        }
		$input->getAttributesFromObject($element);
		return $input;
	}
	/**
	 * Import an select options into the Input class.
	 * @param DOMNode $element - HTML element object.
	 * @return void
	 */
	private function getOptionsFormObject(DOMNode $element):void{
        foreach ($element->childNodes as $child) {
        	if ($child->nodeName == 'option') {
            	$this->addOption($child->getAttribute('value'),$child->nodeValue);
            }
        }
	}

	public function addOption(string $value,?string $text = null):void{
		$this->options = ['value'=>$value,'text'=>$text];
	}
	public function addOptionArray(array $options) :void{
		foreach($options as $option){
			$this->addOption($option['value'],$option['text']??null);
		}
	}
	/**
	 * Set attributes for the input.
	 * @param array $attributes - Array of attributes.
	 * @return void
	 */
	public function setAttributes(array $attributes):void {
		$this->attributes = array_change_key_case($attributes, CASE_LOWER);
	}

	/**
	 * Set the HTTP method for the input.
	 * @param string $method - HTTP method ('get' or 'post').
	 * @return void
	 */
	public function setMethod(string $method):void {
		$this->method = mb_strtolower($method);
	}

	/**
	 * Check if the input is an array type.
	 * @return bool - True if it's an array, false otherwise.
	 */
	private function isArray() {
		if (preg_match('/(\[([0-9a-zA-Z]*)\])/', $this->getAttribute('name')) == false) {
			return false;
		}
		return true;
	}

	/**
	 * Get the name attribute of the input.
	 * @return string|null - The name attribute if it's not an array, null otherwise.
	 */
	public function getName() {
		if (!$this->isArray()) {
			return $this->getAttribute('name');
		}
	}

	/**
	 * Get the tag name of the HTML element.
	 * @return string - The tag name.
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * Get attributes of the HTML element.
	 * @return array|false - Array of attributes if available, false if none.
	 */
	private function getAttributesFromObject(DOMNode $element) {
		if (!$element->hasAttributes()) {
			return false;
		}
		if (!empty($this->attributes)) {
			return $this->attributes;
		}
		foreach ($element->attributes as $attribute) {
			$name = mb_strtolower($attribute->nodeName);
			$value = $attribute->nodeValue;
			$this->attributes[$name] = $value;
		}
		return $this->attributes;
	}

	/**
	 * Get the value of a specific attribute.
	 * @param string $name - Attribute name.
	 * @return string|false - Attribute value if exists, false otherwise.
	 */
	public function getAttribute($name) {
		$name = mb_strtolower($name);
		return $this->attributes[$name] ?? false;
	}

	/**
	 * Get the type attribute of the input.
	 * @return string|false - Type attribute if exists, false otherwise.
	 */
	public function getInputType() {
		$type = $this->getAttribute('type');
		if(is_string($type)){
			return mb_strtolower($type);
		}
		return  $type;
	}

	/**
	 * Get the value of the input based on the HTTP method.
	 * @return mixed|null - Input value or null if it doesn't exist.
	 */
	public function getValue() {
		if(is_null($this->method) && is_null($this->form)){
			$this->setCodeAlert(new Exception(_("No form or method set")));
			$method = $_GET;
		}
		if(!is_null($this->method)){
			$method = ($this->method == 'post') 		? $_POST : $_GET;
		}
		if(!($this->form instanceof Form) || !is_null($this->form->getmethod())){
			$method = ($this->form->getmethod() == 'post') 		? $_POST : $_GET;
		}
		$method = ($this->getInputType() == 'file') ? $_FILES : $method;
		if (!$this->isArray()) {
			if (!isset($method[$this->getName()])) {
			return null;
			}
			return $method[$this->getName()];
		}

		return $method[$this->getName()];
	
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

	/**
	 * Set error for the invalid code.
	 * @param Exception $e - Exception object.
	 * @param int|null $key - Specific key for array inputs.
	 * @return void
	 */
	private function setCodeAlert($e, $key = null): void {
		error_log($e);
		if (!$this->isArray()) {
			$this->codeAlerts[] = $e->getMessage();
			return;
		}
		if (!isset($this->codeAlerts[$key])) {
			$this->codeAlerts[$key] = [];
		}
		$this->codeAlerts[$key][] = $e->getMessage();
	}

	/* validator helpers */

	/**
	 * Validate input attributes.
	 * @return void
	 */
	private function validate():boolean {
		$values = (array)$this->getValue();
		if (empty($values)) {
			$this->validateAttributes('');
		}
		foreach ($values as $key => $value) {
			$this->validateAttributes($value, $key);
			$this->validateTagType($value);
			
		}
		return $this->isValid;
	}
	private function validateTagType($value,$key =0):void{
		if(method_exists($this, 'validate' . $this->getType())) {
			try {
				call_user_func([$this, 'validate' . $this->getType()], $value);
				$this->validatedOn[] = 'validate' . $this->getType();
			} catch (InvalidInput $e) {
				$this->setError($e, $key);
				$this->isValid = false;
			} catch (InvalidCodeAlert $e) {
				$this->setCodeAlert($e, $key);
			} 
		}
	}
	/**
	 * Validate specific attributes for the given value.
	 * @param mixed $value - Input value.
	 * @param int|string $key - Specific key for array inputs.
	 * @return bool - True if validation is successful, false otherwise.
	 */
	private function validateAttributes($value, $key = 0):void {
		foreach ($this->attributes as $attribute => $attribute_value) {
			if (method_exists($this, 'validate' . $attribute)) {
				try {
					call_user_func([$this, 'validate' . $attribute], $value);
					$this->validatedOn[] = 'validate' . $attribute;
				} catch (InvalidInput $e) {
					$this->setError($e, $key);
					$this->isValid = false;
				} catch (InvalidCodeAlert $e) {
					$this->setCodeAlert($e, $key);
				
				} 
			}
		}
	}

	/**
	 * Validate input attributes and return overall validation status.
	 * @return bool - True if all validations are successful, false otherwise.
	 */
	public function valid():boolean{
		$this->validate();
		return $this->isValid;
	}

	/**
	 * Validate the input using filter_var.
	 * @param mixed $value - Input value.
	 * @param int $filter - Filter to apply.
	 * @return bool - True if validation is successful, false otherwise.
	 */
	private function filterVar($value, $filter):boolean{
		if (empty($element['value'])) {
			throw new InvalidCodeAlert(_("No value"));
		}
		if (filter_var($element['value'], $filter) === false) {
			throw new InvalidInput(_("Invalid value"));
			return false;
		}
		return true;
	}
	/* tagType validation */
	private function validateSelect($value):boolean{
		if($this->dynamicSelect){
			return true;
		}
		$options = array_column('value',$this->options);
		if(!in_array($value,$options)){
			throw new InvalidInput(_("Invalid value"));
			return false;
		}
		return true; 
	}
	/* attributes validation */

	/**
	 * Validate the 'accept' attribute for file inputs.
	 * @param mixed $value - Input value.
	 * @return void
	 */
	private function validateAccept($value):boolean {
		if ($this->getInputType() != 'file') {
			throw new InvalidCodeAlert(sprintf(_("Input `%s`  doesn't support this attribute: %s"), $this->getInputType(),"Accept"));
		}
		$fileInfo 		= finfo_open(FILEINFO_MIME_TYPE);
		$detectedType 	= finfo_file( $fileInfo, $value['tmp_name']);
		$acceptedTypes	= $this->getAttribute('accept'); 
		$accepted 		= false;
		foreach (explode(',',$acceptedTypes ) as $type) {
			$type = trim($type); // remove any whitespace
			if (
				$type == $detectedType || 
				$type === strstr($detectedType, '/', true) . '/*' ||
				$type == '.' . pathinfo($value['name'], PATHINFO_EXTENSION)
			){
				$accepted= true;
			}

		}
		if(!$accepted){
			throw new InvalidInputsprintf(sprintf(_("The file type `%s` is not supported. Only these types are supported: %s"), $detected_type, $this->getAttribute('accept')));
			return false;	
		}
		
		return true;
		
	}

	/**
	 * Validate the 'pattern' attribute for text inputs.
	 * @param mixed $value - Input value.
	 * @return bool - True if validation is successful, false otherwise.
	 */
	private function validatePattern($value):boolean{
		if (!in_array($this->getInputType(), ['text', 'search', 'url', 'tel', 'email', 'password'])) {
			throw new InvalidCodeAlert(sprintf(_("Input `%s`  doesn't support this attribute: %s"), $this->getInputType(),"pattern"));
		}
		if (empty($value)) {
			throw new InvalidCodeAlert(_("No value"));
		}
		if (empty($this->getAttribute($pattern))) {
			throw new InvalidCodeAlert(_("No %s attribute value", "pattern"));
		}
		if (filter_var($value, FILTER_VALIDATE_REGEXP, ["options" => ["regexp" => "/^" . $this->getAttribute('pattern') . "$/"]]) === false) {
			throw new InvalidInput(_("Value doesn't match pattern"));
			return false;
		}
		return true;
	}

	/**
	 * Validate the 'min' attribute for various input types.
	 * @param mixed $value - Input value.
	 * @return bool - True if validation is successful, false otherwise.
	 */
	private function validateMin($value):boolean{
		if (!in_array($this->getInputType(), ['date', 'number', 'month', 'week', 'datetime-local', 'range', 'time'])) {
			throw new InvalidCodeAlert(sprintf(_("Input `%s`  doesn't support this attribute: %s"), $this->getInputType(),"Min"));
			return true;
		}
		if (empty($value)) {
			throw new InvalidCodeAlert(_("No value"));
			return true;
		}
		if (in_array($this->getInputType(), ['range', 'number'])) {
			if ($value < $this->getAttribute('min')) {
				$sValue = date($this->format[$this->getInputType()], strtotime($value));
				$sAttr = date($this->format[$this->getInputType()], strtotime($this->getAttribute('min')));
				throw new InvalidInput(sprintf(_("Value `%s` lower than required minimum: %s"),$sValue,$sAttr));
				return false;
			}
			return true;
		} 
		//if (in_array($this->getInputType(), ['date', 'month', 'week', 'datetime-local', 'time']){
		if (preg_match($this->regex[$this->getInputType()], $this->getAttribute('min')) == false || strtotime($this->getAttribute('min'))) {
			throw new InvalidCodeAlert(_("Invalid Attribute value"));
			return true;
		}
		if (preg_match($this->regex[$this->getInputType()], $value) == false || strtotime($value) == false) {
			throw new InvalidInput(_("Value has an invalid format"));
			return false;
		}
		if (strtotime($value) < strtotime($this->getAttribute('min'))) {
			$sValue = date($this->format[$this->getInputType()], strtotime($value));
			$sAttr = date($this->format[$this->getInputType()], strtotime($this->getAttribute('min')));
			throw new InvalidInput(sprintf(_("Value `%s` lower than required minimum: %s"),$sValue,$sAttr));
			return false;
		}
		//}
		return true;
	}

	/**
	 * Validate the 'max' attribute for various input types.
	 * @param mixed $value - Input value.
	 * @return void
	 */
	private function validateMax($value):boolean{
		if (!in_array($this->getInputType(), ['date', 'number', 'month', 'week', 'datetime-local', 'range', 'time'])) {
			throw new InvalidCodeAlert(sprintf(_("Input `%s`  doesn't support this attribute: %s"), $this->getInputType(),"Max"));
		}
		if (!in_array($this->getInputType(), ['date', 'number', 'month', 'week', 'datetime-local', 'range', 'time'])) {
			throw new InvalidCodeAlert(sprintf(_("Input `%s`  doesn't support this attribute: %s"), $this->getInputType(),"Min"));
			return true;
		}
		if (empty($value)) {
			throw new InvalidCodeAlert(_("No value"));
			return true;
		}
		if (in_array($this->getInputType(), ['range', 'number'])) {
			if ($value > $this->getAttribute('max')) {
				$sValue = date($this->format[$this->getInputType()], strtotime($value));
				$sAttr = date($this->format[$this->getInputType()], strtotime($this->getAttribute('max')));
				throw new InvalidInput(sprintf(_("Value `%s` higher than maximum of `%s`"),$sValue,$sAttr));
				return false;
			}
			return true;
		} 
		//if (in_array($this->getInputType(), ['date', 'month', 'week', 'datetime-local', 'time']){
		if (preg_match($this->regex[$this->getInputType()], $this->getAttribute('max')) == false || strtotime($this->getAttribute('min'))) {
			throw new InvalidCodeAlert(_("Invalid Attribute value"));
			return true;
		}
		if (preg_match($this->regex[$this->getInputType()], $value) == false || strtotime($value) == false) {
			throw new InvalidInput(_("Value has an invalid format"));
			return false;
		}
		if (strtotime($value) > strtotime($this->getAttribute('min'))) {
			$sValue = date($this->format[$this->getInputType()], strtotime($value));
			$sAttr = date($this->format[$this->getInputType()], strtotime($this->getAttribute('max')));
			throw new InvalidInput(sprintf(_("Value `%s` higher than maximum of `%s`"),$sValue,$sAttr));
			return false;
		}
		//}
		return true;
	}

	/**
	 * Validate the 'required' attribute for specific input types.
	 * @param mixed $value - Input value.
	 * @return void
	 */
	private function validateRequired($value):boolean{
		if ($this->type != 'textarea' && $this->type != 'select' && !in_array($this->getInputType(), ['text', 'search', 'url', 'tel', 'email', 'password', 'checkbox'])) {
			throw new InvalidCodeAlert(sprintf(_("Input `%s`  doesn't support this attribute: %s"), $this->getInputType(),"Required"));
			return true;
		}
		if (empty($value)) {
			throw new InvalidInput(_("Input is required"));
			return false;
		}
		return true;
	}
	/**
	 * Validate the 'required' attribute for specific input types.
	 * @param mixed $value - Input value.
	 * @return void
	 */
	private function validateStep($value):boolean{
	
		#date	An integer number of days
		#month	An integer number of months
		#week	An integer number of weeks
		#datetime-local, time	An integer number of seconds
		#range, number	An integer
		if(!in_array($this->getInputType(),['date','month', 'week','datetime-local','range'])){
			throw new InvalidCodeAlert(sprintf(_("Input `%s`  doesn't support this attribute: %s"), $this->getInputType(),"Step"));
		}
	}
	/**
	 * Validate the 'required' attribute for specific input types.
	 * @param mixed $value - Input value.
	 * @return void
	 */
	private function validateMinlength($value):boolean{
		
		#text, search, url, tel, email, password; also on the <textarea> element
		if($this->type != 'textarea' && !in_array($this->getInputType(),['text', 'search', 'url', 'tel', 'email', 'password'])){
			throw new InvalidCodeAlert(sprintf(_("Input `%s`  doesn't support this attribute: %s"), $this->getInputType(),"Minlength"));
			return true;
		}
		if(empty($value)){
			throw new InvalidCodeAlert(_("No value"));
			return true;
		}
		if(filter_var($this->getAttribute('minlength'), FILTER_VALIDATE_INT) === false){
			throw new InvalidCodeAlert(_("Invalid attribute value"));
			return true;
		}
		if(mb_strlen($value) < $this->getAttribute('minlength')){
			throw new InvalidInput(_('Value is shorter than minimum required length'));
			return false;
		}
	}
	/**
	 * Validate the 'required' attribute for specific input types.
	 * @param mixed $value - Input value.
	 * @return void
	 */
	private function validateMaxlength($value):boolean{
	
		#text, search, url, tel, email, password; also on the <textarea> element
		if($this->type != 'textarea' && !in_array($this->getInputType(),['text', 'search', 'url', 'tel', 'email', 'password'])){
			throw new InvalidCodeAlert(sprintf(_("Input `%s`  doesn't support this attribute: %s"), $this->getInputType(),"Maxlength"));
		}
		if(empty($value)){
			throw new InvalidCodeAlert(_("No value"));
		}
		if(filter_var($this->getAttribute('maxlength'), FILTER_VALIDATE_INT) === false){
			return true;
		}
		if(mb_strlen($value) > $this->getAttribute('maxlength')){
			throw new InvalidInput(_('Value is longer than the maximum permitted length'));
			return false;
		}
	}
	private $sizeToByte = function($size) {
		$unit = preg_replace('/[^bkmgtpezy]/i', '', $size); // Remove the non-unit characters from the size.
		$size = preg_replace('/[^0-9\.]/', '', $size); // Remove the non-numeric characters from the size.
		if ($unit) {
			// Find the position of the unit in the string
			$pos = strpos('bkmgtpezy', strtolower($unit[0]));
			if ($pos !== false) {
				// Convert the size to bytes
				$size = $size * pow(1024, $pos + 1);
			}
		}
		return $size;
	};

	private $readableSize = function($bytes) {
		$units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
		for ($i = 0; $bytes > 1024; $i++) {
			$bytes /= 1024;
		}
		return round($bytes, 2) . ' ' . $units[$i];
	};
	private function validateFile($value):boolean{
		if(!is_array($value) || key_exists('error',$value)){
			throw new InvalidInput(_('unexcepeted value'));	
			return false;
		}
		$sizeToByte 
		$upload_max_filesize 	= $this->sizeToBytes(ini_get('upload_max_filesize'));
		$post_max_size 			= $this->sizeToBytes(ini_get('post_max_size'))
		$uploadMaxSize 			= $this->readableSize(min($upload_max_filesize,$post_max_size));
		
		switch($value['error']){
			case UPLOAD_ERR_OK:
				return true;
				break;
			case UPLOAD_ERR_INI_SIZE:
			case UPLOAD_ERR_PARTIAL:
				$uploadedFileSize 	= $this->readableSize($_FILES['uploadedFile']['size']);
				throw new InvalidInput(sprintf(_('File size of %s exceeds max size of %s'),$uploadedFileSize, $uploadMaxSize));	
				return false;
				break;
			case UPLOAD_ERR_NO_FILE: 	
				throw new InvalidInput(_('No file was uploaded'));			
				return false;
				break;
			case UPLOAD_ERR_NO_TMP_DIR:	
				throw new InvalidInput(_('No tmp dir to write to'));
				return false;
				break;
			case UPLOAD_ERR_CANT_WRITE:	
				throw new InvalidInput(_('Error writing to disk'));
				return false;
				break;
			default:
				return true;
		}
	}
	function validateDate($value):boolean{
		if (!preg_match($this->regex[$this->getInputType()], $value)) {
			throw new InvalidInput(_('Date is not valid according to regex'));
			return false;
		}
		$date = DateTime::createFromFormat($this->format[$this->getInputType()], $value);
		$errors = DateTime::getLastErrors();
		if (!empty($errors['warning_count'])) {
			throw new InvalidInput(_('Date is not valid according to format'));
			return false;
		}
		// Date is valid
		return true;
	}
	/**
	 * Validate the 'required' attribute for specific input types.
	 * @param mixed $value - Input value.
	 * @return void
	 */
	private function validateType($value):boolean{
		switch($this->getInputType()){
			case 'email':
				return $this->filterVar($value,FILTER_VALIDATE_EMAIL);
				break;
			case 'url':
				return $this->filterVar($value, FILTER_VALIDATE_URL);
				break;
			case 'number':
				return $this->filterVar($value, FILTER_VALIDATE_INT);
				break;
			case 'file':
				return $this->validateFile($value,FILTER_VALIDATE_EMAIL);
				break;
			case 'time':
			case 'week':
			case 'month':
			case 'datetime-local':
				return $this->validateDate($value);
				break;
			default:
			return true;
		}
	}
}
