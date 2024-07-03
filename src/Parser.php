<?php
namespace Forms;

class Parser{
    private $elements = [];
    private $html = '';
    function __construct($elements) {
        $this->elements = $elements;
        $this->parseForm();
    }

    private function parseForm(){
        foreach($this->elements as $element){
            switch(get_class($element)){
                case 'Input':
                    $this->parseInput($elements);
                    break;
                default:
                    die('Unknown element type class');
            }
        }
    }
    private function parseInput(Input $element){
        $element->getType();
        $attributes     = $element->getAttributes();
        $attributesHTML = implode(' ',array_map(function($k, $v) { return $k . '="' . $v . '"'; }, array_keys($array), $array);
        $this->addHTML("<${$element} ${$attributesHTML} />");

    }
    private function addHTML(string $html){
        $this->html .= $html;
    }
    

}

?>