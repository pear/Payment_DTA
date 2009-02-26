<?php
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Payment_DTA_AllTests::main');
}

require_once 'PHPUnit/Framework/TestSuite.php';
require_once 'PHPUnit/TextUI/TestRunner.php';

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

        $suite->addTestSuite('DTATest');
        $suite->addTestSuite('DTAZVTest');

        return $suite;
    }
}

// exec test suite
if (PHPUnit_MAIN_METHOD == 'Payment_DTA_AllTests::main') {
    Payment_DTA_AllTests::main();
}
