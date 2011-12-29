<?php

// Keep tests from running twice when calling this file directly via PHPUnit.
$call_main = false;
if (strpos($_SERVER['argv'][0], 'phpunit') === false) {
    // Called via php, not PHPUnit.  Pass the request to PHPUnit.
    if (!defined('PHPUnit_MAIN_METHOD')) {
        /** The test's main method name */
        define('PHPUnit_MAIN_METHOD', 'Payment_DTA_AllTests::main');
        $call_main = true;
    }
}

require_once dirname(__FILE__) . '/helper.inc';

require_once 'DTABaseTest.php';
require_once 'DTATest.php';
require_once 'DTAZVTest.php';

class Payment_DTA_AllTests
{
    public static function main()
    {

        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Payment_DTA Tests');

        $suite->addTestSuite('DTABaseTest');
        $suite->addTestSuite('DTATest');
        $suite->addTestSuite('DTAZVTest');

        return $suite;
    }
}

// exec test suite
if ($call_main) {
    Payment_DTA_AllTests::main();
}
