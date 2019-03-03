<?php

use Lechimp\PHP_JS\JS\Test\ComparisonTest;

class MyScript implements Lechimp\PHP_JS\JS\Script {
    public function execute() {
        $tests = $this->buildTests();
        $result = true;

        foreach ($tests as $test) {
            $r = $test->perform();
            echo $test->name().": ".($r ? "SUCCESS" : "FAIL");
            $result = $result && $r;
        }

        if (!$result) {
            exit(1);
        }
    }

    protected function buildTests() {
        return [
            new ComparisonTest()
        ];
    }
}
