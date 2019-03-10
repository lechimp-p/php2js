<?php

use Lechimp\PHP_JS\JS_Tests;

class TestRunner implements Lechimp\PHP_JS\JS\Script {
    public function execute() {
        $tests = $this->buildTests();
        $result = true;

        echo "\n";

        foreach ($tests as $test) {
            $r = $test->perform();
            echo $test->name().": ".($r ? "ok" : "fail")."\n";
            $result = $result && $r;
        }

        echo "\n";

        if (!$result) {
            echo "Some Tests failed.\n";
            exit(1);
        }

        echo "Tests ok!\n";
    }

    protected function buildTests() {
        return [
            new JS_Tests\SmokeTest()
        ];
    }
}
