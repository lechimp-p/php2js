<?php

use Lechimp\PHP_JS\JS_Tests;

class MyScript implements Lechimp\PHP_JS\JS\Script {
    public function execute() {
        $tests = $this->buildTests();
        $result = true;

        foreach ($tests as $test) {
            $r = $test->perform();
            echo $test->name().": ".($r ? "ok" : "fail");
            $result = $result && $r;
        }

        if (!$result) {
            exit(1);
        }
    }

    protected function buildTests() {
        return [
            new JS_Tests\SmokeTest()
        ];
    }
}
