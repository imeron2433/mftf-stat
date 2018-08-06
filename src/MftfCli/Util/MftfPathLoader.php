<?php
/**
 * Created by PhpStorm.
 * User: imeron
 * Date: 6/25/18
 * Time: 3:41 PM
 */

namespace MftfCli\Util;


use Magento\FunctionalTestingFramework\Config\MftfApplicationConfig;

class MftfPathLoader
{
    const RELATIVE_BOOTSTRAP_PATH = '/dev/tests/acceptance/tests/_bootstrap.php';
    const RELATIVE_AUTOLOAD_PATH = '/vendor/autoload.php';

    private $magento2cePath = null;

    public function __construct($magento2cePath)
    {
        if (!$magento2cePath) {
            throw new \Exception("Could not find Magento2 at path ${$magento2cePath}");
        }

        $this->magento2cePath = $magento2cePath;
    }

    public function loadMftfPath()
    {
        $devTestAutoloadExists = file_exists( $this->magento2cePath . self::RELATIVE_BOOTSTRAP_PATH);
        $rootAutoloadExists = file_exists($this->magento2cePath . self::RELATIVE_AUTOLOAD_PATH);

        if (!$devTestAutoloadExists && !$rootAutoloadExists) {
            throw new \Exception("magento dependencies must be installed to run this application.");
        }

        $mftfAutoLoad =  $this->magento2cePath . self::RELATIVE_AUTOLOAD_PATH;

        if ($devTestAutoloadExists) {
            $mftfAutoLoad = $this->magento2cePath . self::RELATIVE_BOOTSTRAP_PATH;

        }

        require_once $mftfAutoLoad;
        MftfApplicationConfig::create(true, MftfApplicationConfig::GENERATION_PHASE, false, false);
    }

}