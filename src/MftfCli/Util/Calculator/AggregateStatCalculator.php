<?php
/**
 * Created by PhpStorm.
 * User: imeron
 * Date: 6/21/18
 * Time: 10:17 AM
 */

namespace MftfCli\Util\Calculator;


use Magento\FunctionalTestingFramework\Test\Handlers\TestObjectHandler;
use Magento\FunctionalTestingFramework\Test\Objects\TestObject;

class AggregateStatCalculator extends StatCalculator
{

    public function aggregateStats()
    {
        ob_start();
        $result = [];
        $result['num_test'] = 0;
        $result['skipped_tests'] = 0;
        $result['test_duration'] = [];

        $toh = TestObjectHandler::getInstance();
        /** @var TestObject $testObject */
        foreach ($toh->getAllObjects() as $testObject) {
            if ($testObject->isSkipped()) {
                $result['skipped_tests']++;
            }

            $result['num_test']++;
            array_push($result['test_duration'], $testObject->getEstimatedDuration());
        }

        $average_steps = array_sum($result['test_duration'])/count($result['test_duration']);
        $result['average_test_duration'] = ceil($average_steps);
        $result['median_test_duration'] = $this->returnMedianTestSteps($result['test_duration']);

        unset($result['test_duration']);
        ob_end_clean();

        return [$result];
    }

    public function getTableHeaders()
    {
        return ['num_test', 'skipped_test', 'average_test_duration', 'median_test_duration'];
    }
}