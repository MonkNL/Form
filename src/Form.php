<?php
namespace Forms;

class Form{
    private $method = 'get', $elements = [], $isValid, $errors = [];

    /**
     * Constructor for Form class.
     * @param string $name - Form name.
     * @param string $method - HTTP method ('get' or 'post').
     * @return void
     */
    function __construct(string $name, string $method = 'get') {
        $this->setMethod($method);
    }

    /**
     * Sets the HTTP method for the form.
     * @param string $method - HTTP method ('get' or 'post').
     * @return void
     */
    public function setMethod(string $method){
        $this->method = $method;
    }

    /**
     * Adds an input element to the form.
     * @param object $input - Input object.
     * @return void
     */
    public function addInput(object $input){
        $this->elements[] = $input;
    }

    /**
     * Returns all elements in the form.
     * @return array
     */
    public function getElements(){
        return $this->elements;
    }

    /**
     * Decodes HTML and adds form elements to the form.
     * @param string $html - HTML string.
     * @return void
     */
    public function decodeHTML(string $html){
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_use_internal_errors(false);
        $xpath = new \DOMXpath($dom);
        $elements = $xpath->query('//form | //label | //input | //select | //textarea | //button');
        foreach ($elements as $element) {
            if($element->tagName != 'form'){
                $input  = Input::importObject($element,$this->method);
                $this->addInput($input);
                continue;
            }
            if($element->getAttribute('method') != ""){
                $method = mb_strtolower($element->getAttribute('method'));
                $this->setMethod($method);
                continue;
            }
        }
    }

    /**
     * Validates all input elements in the form.
     * @return bool
     */
    function validate(){
        $this->isValid = true;
        $elements = $this->getElements();
        foreach($this->elements as $element){
            if(!($element instanceof Input)){
                continue;
            }
            if($element->valid()){
                continue;
            }
            $this->addError($element->getName(),$element->error());
            $this->isValid = false;
        }
        return $this->isValid;
    }

    /**
     * Adds an error message to the form.
     * @param string $name - Name of the input element.
     * @param string $error - Error message.
     * @return void
     */
    function addError($name, $error){
        $this->errors[] = $name.':'.implode(',',$error);
    }

    /**
     * Returns all error messages in the form.
     * @return array
     */
    function getErrors(){
        return $this->errors;
    }

    /**
     * Checks if the form is valid.
     * @return bool
     */
    function valid(){
        return $this->validate();
    }
}
