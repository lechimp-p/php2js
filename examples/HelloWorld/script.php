<?php

use Lechimp\PHP_JS\JS\API\Document;

class MyScript implements Lechimp\PHP_JS\JS\Script {
    protected $document;

    public function __construct(Document $document) {
        $this->document = $document;
    }

    public function execute() {
        $element = $this->document->getElementById("display");
        $element->setInnerHTML("<h1>Hello World!</h1>");
    }
}
