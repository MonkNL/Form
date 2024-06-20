<?php
namespace Forms;

class Form{
    private $method = 'get',$elements = [];
    function __construct(string $name, string $method = 'get') {
        $this->setMethod($method);
	}
    public function setMethod(string $method){
        $this->method = $method;
    }
    public function addInput(object $input){
        $elements = $input;
    }
    public function getElements(){
        return $this->elements;
    }
    public function decodeHTML(string $html){
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_use_internal_errors(false);
        $xpath 			= new \DOMXpath($dom);
        $elements		= $xpath->query('//form | //label | //input | //select | //textarea | //button');
        
        foreach ($elements as $element) {
            if($element->tagName != 'form'){
                $this->addObject(Input::importObject($element,$this->method));
                continue;
            }
            if($element->getAttribute('method') != ""){
                $method = mb_strtolower($element->getAttribute('method'));
                $this->setMethod($method);
                continue;
            }

        }
    }
    private function addObject($element){
       $this->elements[] = $element;
    }

    function validate(){
	    $elements = $this->getElements();
	    foreach($this->elements as $element){
            if(!in_array($element->getType(),['input','select','textarea'])){
                continue;
            }
            if($element->valid()){
                continue;
            }
            $element->getError();
			$this->isValid = false;
		}
		return $this->isValid;
	}
	
	function valid(){
		return $this->validate();
	}

}