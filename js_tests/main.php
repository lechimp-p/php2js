<?php

use Lechimp\PHP2JS\JS_Tests;

class TestRunner implements Lechimp\PHP2JS\JS\Script {
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

    public function buildTests() {
        return [
            new JS_Tests\SmokeTest(),
            new JS_Tests\NotIssetTest(),
            new JS_Tests\UseLocalVariablesTest(),
            new JS_Tests\Array_\IndexFetchTest(),
            new JS_Tests\Array_\ForeachWithoutKeyTest(),
            new JS_Tests\Array_\ForeachWithKeyTest(),
            new JS_Tests\Array_\ForeachWithStringKeyTest(),
            new JS_Tests\Class_\ExtendsTest(),
            new JS_Tests\Class_\ExtendsOverwritesTest(),
            new JS_Tests\Class_\ExtendsGivesAccessToProtectedTest(),
            new JS_Tests\Class_\AccessParentTest(),
            new JS_Tests\Class_\ConstantTest(),
            new JS_Tests\Class_\ConstantOnSelfTest(),
            new JS_Tests\Closure\VariableCaptureTest(),
            new JS_Tests\Closure\VariableCaptureUsageOnlyTest(),
            new JS_Tests\Closure\UseVariableTest(),
            new JS_Tests\Closure\UseReferencedVariableTest()
        ];
    }
}
