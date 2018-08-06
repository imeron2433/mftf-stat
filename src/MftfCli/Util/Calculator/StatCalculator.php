<?php
/**
 * Created by PhpStorm.
 * User: imeron
 * Date: 6/21/18
 * Time: 8:52 AM
 */

namespace MftfCli\Util\Calculator;


use MftfCli\Util\MftfPathLoader;
use MftfCli\Util\ModuleNameExtractor;

abstract class StatCalculator
{
    abstract protected function aggregateStats();
    abstract protected function getTableHeaders();


    public function __construct($magento2cePath)
    {
        $pathloader = new MftfPathLoader($magento2cePath);
        $pathloader->loadMftfPath();
        $this->moduleNameExtractor = new ModuleNameExtractor();
    }

    protected function returnMedianTestSteps($testStepArray)
    {
        $stepArrayCount = count($testStepArray);

        if ($stepArrayCount === 1) {
            return $testStepArray[0];
        }

        arsort($testStepArray);
        if ($stepArrayCount % 2 == 0) {
            $middleValue = $stepArrayCount / 2;
            return $testStepArray[$middleValue];
        }

        $middleValue = ($stepArrayCount - 1) / 2;
        $middleValue1 =  $middleValue + 1;

        return ($testStepArray[$middleValue] + $testStepArray[$middleValue1]) / 2;
    }

    protected function extractModuleName($path)
    {
        return $this->moduleNameExtractor->extractModuleName($path);
    }
}