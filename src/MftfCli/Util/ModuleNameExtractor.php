<?php
/**
 * Created by PhpStorm.
 * User: imeron
 * Date: 6/25/18
 * Time: 4:27 PM
 */

namespace MftfCli\Util;


class ModuleNameExtractor
{
    public function extractModuleName($path)
    {
        if (empty($path)) {
            return "NO MODULE DETECTED";
        }
        $paths = explode(DIRECTORY_SEPARATOR, $path);
        if (count($paths) < 3) {
            return "NO MODULE DETECTED";
        } elseif ($paths[count($paths)-3] == "Mftf") {
            // app/code/Magento/[Analytics]/Test/Mftf/Test/SomeText.xml
            return $paths[count($paths)-5];
        }
        // dev/tests/acceptance/tests/functional/Magento/FunctionalTest/[Analytics]/Test/SomeText.xml
        return $paths[count($paths)-3];
    }
}