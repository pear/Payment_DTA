<?php

require_once dirname(__FILE__) . '/helper.inc';
require_once 'DTABase.php';

/*
 * This file contains very few test cases -- just enough to test the few
 * parts/branches in DTABase that are not covered by DTA and DTAZV tests.
 *
 * In order to test protected methods we use this dummy class TestDTABase.
 */
class TestDTABase extends DTABase
{
    function getStr($input, &$offset, $length, $liberal = false)
    {
        return parent::getStr($input, $offset, $length, $liberal);
    }
    function getNum($input, &$offset, $length)
    {
        return parent::getNum($input, $offset, $length);
    }

    function setAccountFileSender($account)
    {
        return null;
    }
    function addExchange($account_receiver, $amount, $purposes, $account_sender = array())
    {
        return null;
    }
    function getFileContent()
    {
        return null;
    }
}

class DTABaseTest extends PHPUnit_Framework_TestCase
{
    protected $backupGlobals = FALSE;
    protected $fixture;

    protected function setUp()
    {
        // Create the Array fixture.
        $this->fixture = new TestDTABase();
    }

    public function testGetNumTooShort()
    {
        $input = "12345";
        $offset = 3;
        $length = 4;
        $rc = $this->fixture->getNum($input, $offset, $length);
        $this->assertEquals("45", $rc);
    }

    public function testGetNumOffsetTooBig()
    {
        $this->setExpectedException('Payment_DTA_Exception');
        $input = "12345";
        $offset = 6;
        $length = 4;
        $rc = $this->fixture->getNum($input, $offset, $length);
    }

    public function testGetStrTooShort()
    {
        $input = "ABCDE";
        $offset = 3;
        $length = 4;
        $rc = $this->fixture->getStr($input, $offset, $length);
        $this->assertEquals("DE", $rc);
    }

    public function testGetStrOffsetTooBig()
    {
        $this->setExpectedException('Payment_DTA_Exception');
        $input = "ABCDE";
        $offset = 6;
        $length = 4;
        $rc = $this->fixture->getStr($input, $offset, $length);
    }

    public function testGetStrInvalid()
    {
        $this->setExpectedException('Payment_DTA_Exception');
        $input = "ABCDe";
        $offset = 3;
        $length = 4;
        $rc = $this->fixture->getStr($input, $offset, $length);
    }

}
