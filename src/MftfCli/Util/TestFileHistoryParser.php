<?php
/**
 * Created by PhpStorm.
 * User: imeron
 * Date: 6/28/18
 * Time: 2:26 PM
 */

class TestFileHistoryParser
{
    public function __construct()
    {

    }

    /**
     * @param \Magento\FunctionalTestingFramework\Test\Objects\TestObject $testObject
     */
    public function generateTestHistoryMap($testObject)
    {
        // get test filename
        $filename = $testObject->getFilename();

        // parse in raw git history
        $gitLogCommand = "git log -p -- {$filename}";
        $gitLogProcess = new \Symfony\Component\Process\Process($gitLogCommand);
        $gitLogProcess->setWorkingDirectory(TESTS_BP);
        $gitLogProcess->run();
        $result = $gitLogProcess->getOutput();

        // separate by git history header
        $re = '/(commit .*\n)/m';
        $gitHistory = preg_split($re, $result);

        // search separated entries for skip notation as an add
        echo 'here';
    }


    private function reMapHistoryArray($gitEntries)
    {
        $histEntries = [];
        foreach ($gitEntries as $gitEntry) {
            //
        }
    }

}

putenv("MAGENTO_BASE_URL=http://magento2.vagrant102/");

$_ENV["MAGENTO_BASE_URL"] = "http://magento2.vagrant102/";
$_ENV["MAGENTO_ADMIN_USERNAME"] = "admin";
$_ENV["MAGENTO_ADMIN_PASSWORD"] = "123123q";
require_once "/Users/imeron/qa/magento2ce/vendor/autoload.php";

$alt = \Magento\FunctionalTestingFramework\Test\Handlers\TestObjectHandler::getInstance()->getObject('AdminUnassignProductAttributeFromAttributeSetTest');

$test = new TestFileHistoryParser();
$test->generateTestHistoryMap($alt);