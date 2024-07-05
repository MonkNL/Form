<?php
namespace Forms;

class Select extends Input {
    private $options = [],$dynamicSelect = false;

    function __construct(Form $form = null, string $method = null) {
        parent::__construct($form, $method);
        $this->type = 'select';
    }

    public function addOption(string $value, ?string $text = null): void {
        $this->options[] = ['value' => $value, 'text' => $text];
    }

    public function addOptionArray(array $options): void {
        foreach ($options as $option) {
            $this->addOption($option['value'], $option['text'] ?? null);
        }
    }
	/**
	 * Import an select options into the Input class.
	 * @param DOMNode $element - HTML element object.
	 * @return void
	 */
    private function getOptionsFormObject(\DOMElement $element): void {
        foreach ($element->childNodes as $child) {
            if ($child->nodeName == 'option') {
                $this->addOption($child->getAttribute('value'), $child->nodeValue);
            }
        }
    }

    static function importObject(\DOMElement $element) {
        $select         = new self();
        $select->type 	= $element->tagName;
        $select->getOptionsFormObject($element);
        $select->getAttributesFromObject($element);
        return $select;
    }

    private function validateSelect($value): bool {
        if ($this->dynamicSelect) {
            return true;
        }
        $options = array_column($this->options, 'value');
        if (!in_array($value, $options)) {
            throw new InvalidInput(_("Invalid value"));
            return false;
        }
        return true;
    }
}
