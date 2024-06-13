<?php


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
}