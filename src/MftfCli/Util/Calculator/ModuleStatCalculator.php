<?php
/**
 * Created by PhpStorm.
 * User: imeron
 * Date: 6/21/18
 * Time: 8:52 AM
 */

namespace MftfCli\Util\Calculator;

use Magento\FunctionalTestingFramework\Test\Handlers\TestObjectHandler;
use Magento\FunctionalTestingFramework\Test\Objects\TestHookObject;
use Magento\FunctionalTestingFramework\Test\Objects\TestObject;

class ModuleStatCalculator extends StatCalculator
{
    public function aggregateStats()
    {
        ob_start(); // suppress any other output
        $tohInstance = TestObjectHandler::getInstance();
        $allTests = $tohInstance->getAllObjects();

        $result = [];

        /** @var TestObject $test */
        foreach ($allTests as $test) {
            $module = $this->extractModuleName($test->getFilename());

            if ($test->isSkipped()) {
                $current_skipped = $result[$module]['skipped_tests'] ?? 0;
                $result[$module]['skipped_tests'] = $current_skipped + 1;
            }

            $current_test_count = $result[$module]['num_tests'] ?? 0;
            $result[$module]['num_tests'] = $current_test_count + 1;

            $test_steps_array = $result[$module]['test_steps'] ?? [];
            $result[$module]['test_steps'] = array_merge($test_steps_array, [$this->getTestActions($test)]);
        }

        ob_end_clean();

        return $this->formatResultsAsTable($result);
    }

    public function getTableHeaders()
    {
        return ['module_name', 'num_tests', 'skipped_tests', 'average_test_steps', 'median_test_steps'];
    }

    private function formatResultsAsTable($stats)
    {
        $cleanReuslts = $this->cleanResults($stats);

        $table = [];
        foreach ($cleanReuslts as $module => $statArray) {
            $table[] = array_merge([$module], array_values($statArray));
        }

        return $table;
    }

    private function cleanResults($result)
    {
        $endResult = [];
        foreach($result as $module => $stats) {
            $endResult[$module]['num_tests'] = $stats['num_tests'];
            $endResult[$module]['skipped_tests'] = $stats['skipped_tests'] ?? 0;
            $averageOfSteps = array_sum($stats['test_steps'])/count($stats['test_steps']);
            $endResult[$module]['average_test_steps'] = ceil($averageOfSteps);
            $endResult[$module]['median_test_steps'] = $this->returnMedianTestSteps($stats['test_steps']);
        }

        ksort($endResult);
        return $endResult;
    }


    /**
     * @param TestObject $test
     */
    private function getTestActions($test)
    {
        $testActions = 0;
        if (!empty($test->getHooks())) {
            /** @var TestHookObject $testHook */
            foreach ($test->getHooks() as $testHook) {
                $testActions+= count($testHook->getActions());
            }
        }

        $testActions += count($test->getOrderedActions());
        return $testActions;
    }
}