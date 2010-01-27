<?php
require_once 'PHPUnit/Framework.php';

//make cvs testing work
chdir(dirname(__FILE__) . '/../');
require_once 'DTA.php';

class DTATest extends PHPUnit_Framework_TestCase
{
    protected $backupGlobals = FALSE;
    protected $fixture;

    protected function setUp()
    {
        // Create the Array fixture.
        $this->fixture = new DTA(DTA_CREDIT);
        $DTA_test_account = array(
            'name' => "Senders Name",
            'bank_code' => "16050000",
            'account_number' => "3503007767",
        );
        $this->fixture->setAccountFileSender($DTA_test_account);
    }

    public function testInstantiate()
    {
        $this->assertEquals("DTA", get_class($this->fixture));
    }

    public function testInstantiateShortBankCode()
    {
        $dtaus = new DTA(DTA_CREDIT);
        $DTA_test_account = array(
             'name' => "Senders Name",
             'additional_name' => '',
             'bank_code' => "1605000",
             'account_number' => "3503007767",
         );

        $this->assertTrue($dtaus->setAccountFileSender($DTA_test_account));
    }

    public function testInstantiateNoBankCode()
    {
        $dtaus = new DTA(DTA_CREDIT);
        $DTA_test_account = array(
             'name' => "Senders Name",
             'additional_name' => '',
             'bank_code' => "",
             'account_number' => "3503007767",
         );

        $this->assertFalse($dtaus->setAccountFileSender($DTA_test_account));
    }

    public function testInstantiateLongBankCode()
    {
        $dtaus = new DTA(DTA_CREDIT);
        $DTA_test_account = array(
             'name' => "Senders Name",
             'additional_name' => '',
             'bank_code' => "160500000",
             'account_number' => "3503007767",
         );

        $this->assertFalse($dtaus->setAccountFileSender($DTA_test_account));
    }

    public function testInstantiateNoAccountNumber()
    {
        $dtaus = new DTA(DTA_CREDIT);
        $DTA_test_account = array(
             'name' => "Senders Name",
             'additional_name' => '',
             'bank_code' => "16050000",
             'account_number' => "",
         );

        $this->assertFalse($dtaus->setAccountFileSender($DTA_test_account));
    }


    public function testInstantiateLongAccountNumber()
    {
        $dtaus = new DTA(DTA_CREDIT);
        $DTA_test_account = array(
             'name' => "Senders Name",
             'additional_name' => '',
             'bank_code' => "16050000",
             'account_number' => "35030077671",
         );

        $this->assertFalse($dtaus->setAccountFileSender($DTA_test_account));
    }

    public function testInstantiateLetterInAccountNumber()
    {
        $dtaus = new DTA(DTA_CREDIT);
        $DTA_test_account = array(
             'name' => "Senders Name",
             'additional_name' => '',
             'bank_code' => "16050000",
             'account_number' => "3503007A67",
         );

        $this->assertFalse($dtaus->setAccountFileSender($DTA_test_account));
    }

    public function testInstantiateWithIntegerAccountNumberSmall()
    {
        // small := leq PHP_INT_MAX (on 32-bit with 10 digits)
        $dtaus = new DTA(DTA_CREDIT);
        $DTA_test_account = array(
             'name' => "Senders Name",
             'additional_name' => '',
             'bank_code' => "1605000",
             'account_number' => PHP_INT_MAX-1,
         );

        $this->assertTrue($dtaus->setAccountFileSender($DTA_test_account));
    }

    public function testInstantiateWithIntegerAccountNumberBig()
    {
        $dtaus = new DTA(DTA_CREDIT);
        $DTA_test_account = array(
             'name' => "Senders Name",
             'additional_name' => '',
             'bank_code' => "1605000",
             'account_number' => 3503007767,
         );

        $this->assertTrue($dtaus->setAccountFileSender($DTA_test_account));
    }

    public function testInstantiateWithIntegerBankCode()
    {
        $dtaus = new DTA(DTA_CREDIT);
        $DTA_test_account = array(
             'name' => "Senders Name",
             'additional_name' => '',
             'bank_code' => 1605000,
             'account_number' => "3503007767",
         );

        $this->assertTrue($dtaus->setAccountFileSender($DTA_test_account));
    }

    public function testCountEmpty()
    {
        if (!method_exists($this->fixture, 'count')) {
            $this->markTestSkipped('no count() in v1.2.0');
        } else {
            $this->assertEquals(0, $this->fixture->count());
        }
    }

    public function testCountNonEmpty()
    {
        if (!method_exists($this->fixture, 'count')) {
            $this->markTestSkipped('no count() in v1.2.0');
        } else {
            $this->fixture->addExchange(array(
                    'name' => "A Receivers Name",
                    'bank_code' => "16050000",
                    'account_number' => "3503007767"
                ),
                (float) 1234.56,
                "Test-Verwendungszweck"
            );
            $this->fixture->addExchange(array(
                    'name' => "A Receivers Name",
                    'bank_code' => "16050000",
                    'account_number' => "3503007767"),
                (float) 321.9,
                "Test-Verwendungszweck"
            );

            $this->assertEquals(2, $this->fixture->count());
        }
    }

    public function testAmountZero()
    {
        $this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "16050000",
                'account_number' => "3503007767"
            ),
            (float) 0.0,
            "Ein Test-Verwendungszweck"
        );
        $this->assertEquals(0, $this->fixture->count());
    }

    public function testMaxAmount()
    {
        $this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "16050000",
                'account_number' => "3503007767"
            ),
            (float) PHP_INT_MAX/100,
            "Ein Test-Verwendungszweck"
        );
        $this->assertEquals(1, $this->fixture->count());
    }

    public function testAmountTooBig()
    {
        $this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "16050000",
                'account_number' => "3503007767"
            ),
            (float) (PHP_INT_MAX/100+1),
            "Ein Test-Verwendungszweck"
        );
        $this->assertEquals(0, $this->fixture->count());
    }

    public function testAmountSumNoOverflow()
    {
        if (PHP_INT_MAX != 2147483647) {
            $this->markTestSkipped('unexpected PHP_INT_MAX -- maybe a 64bit system?');
        } else {
            for($i = 0; $i < 10; $i++) {
                $this->fixture->addExchange(array(
                        'name' => "A Receivers Name",
                        'bank_code' => "16050000",
                        'account_number' => "3503007767"
                    ),
                    2147483.64,
                    "Ein Test-Verwendungszweck"
                );
            }
            $this->assertEquals(10, $this->fixture->count());
        }
    }

    public function testAmountSumOverflow()
    {
        if (PHP_INT_MAX != 2147483647) {
            $this->markTestSkipped('unexpected PHP_INT_MAX -- maybe a 64bit system?');
        } else {
            /* add enough transfers so that the sum of
             * amounts will cause an integer overflow */
            for($i = 0; $i < 10; $i++) {
                $this->fixture->addExchange(array(
                        'name' => "A Receivers Name",
                        'bank_code' => "16050000",
                        'account_number' => "3503007767"
                    ),
                    2147484, // = ceil(PHP_INT_MAX/100/10) and > PHP_INT_MAX/100/10
                    "Ein Test-Verwendungszweck"
                );
            }
            $this->assertEquals(9, $this->fixture->count());
        }
    }

    public function testValidStringTrue()
    {
        $result = $this->fixture->validString(" \$%&*+,-./0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ");
        $this->assertTrue($result);
    }

    public function testValidStringFalse1()
    {
        $result = $this->fixture->validString("ä");
        $this->assertFalse($result);
    }

    public function testValidStringFalse2()
    {
        $result = $this->fixture->validString("ÄÖÜ");
        $this->assertFalse($result);
    }

    public function testMakeValidString()
    {
        $result = $this->fixture->makeValidString("ä Ä~öü§ß");
        $this->assertEquals("AE AE OEUE SS", $result);
    }

    public function testMakeValidStringExtended()
    {
        if (!method_exists($this->fixture, 'count')) {
            $this->markTestSkipped('v1.2.0 had fewer char replacements');
        } else {
            $result = $this->fixture->makeValidString("ä Äáöøüß");
            $this->assertEquals("AE AEAOEOUESS", $result);
        }
    }

    public function testRejectLetterInAccountNumber()
    {
        $result = $this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "16050000",
                'account_number' => "3A5030076B"
            ),
            (float) 1234.56,
            "Kurzer Test-Verwendungszweck"
        );
        $this->assertFalse($result);
    }

    public function testIntegerAccountNumber()
    {
        $result = $this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "16050000",
                'account_number' => 3503007767
            ),
            (float) 1234.56,
            "Kurzer Test-Verwendungszweck"
        );
        $this->assertTrue($result);
    }

    public function testUmlautInRecvName()
    {
        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "Ä Receivers Näme",
                'bank_code' => "16050000",
                'account_number' => "3503007767"
            ),
            (float) 1234.56,
            "Kurzer Test-Verwendungszweck"
        ));
        $this->assertEquals(512, strlen($this->fixture->getFileContent()));
    }

    public function testAdditionalSenderName()
    {
        $DTA_test_account = array(
            'name' => "Senders Name",
            'additional_name' => "some very long additional sender name",
            'bank_code' => "16050000",
            'account_number' => "3503007767",
        );
        $this->assertTrue($this->fixture->setAccountFileSender($DTA_test_account));

        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "16050000",
                'account_number' => "3503007767",
            ),
            (float) 1234.56,
            "Kurzer Test-Verwendungszweck"
        ));
        $this->assertEquals(512, strlen($this->fixture->getFileContent()));
    }

    public function testAdditionalSenderNameWithIntegers()
    {
        $DTA_test_account = array(
            'name' => "Senders Name",
            'additional_name' => "some very long additional sender name",
            'bank_code' => 16050000,
            'account_number' => 3503007767,
        );
        $this->assertTrue($this->fixture->setAccountFileSender($DTA_test_account));

        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => 16050000,
                'account_number' => 3503007767,
            ),
            (float) 1234.56,
            "Kurzer Test-Verwendungszweck"
        ));
        $this->assertEquals(512, strlen($this->fixture->getFileContent()));
    }

    public function testAdditionalRecvName()
    {
        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "16050000",
                'account_number' => "3503007767",
                'additional_name' => "some very long additional receiver name"
            ),
            (float) 1234.56,
            "Kurzer Test-Verwendungszweck"
        ));
        $this->assertEquals(512, strlen($this->fixture->getFileContent()));
    }

    public function testFileLengthRejectLongAccountNumber()
    {
        $this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "16050000",
                'account_number' => "35030077671"
            ),
            (float) 1234.56,
            "Kurzer Test-Verwendungszweck"
        );
        $this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "16050000",
                'account_number' => "35030077671"),
            (float) 321.9,
            "Kurzer Test-Verwendungszweck"
        );

        $this->assertEquals(256, strlen($this->fixture->getFileContent()));
    }

    public function testFileLengthLeadingZerosAccountNumber()
    {
        // this covers Bug #14736
        $this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "16050000",
                'account_number' => "00000000003503007767"
            ),
            (float) 1234.56,
            "Kurzer Test-Verwendungszweck"
        );
        $this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "16050000",
                'account_number' => "000000000003503007767"),
            (float) 321.9,
            "Kurzer Test-Verwendungszweck"
        );

        $this->assertEquals(256, strlen($this->fixture->getFileContent()));
    }

    public function testCountLeadingZerosAccountNumber()
    {
        // this covers Bug #14736
        $this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "16050000",
                'account_number' => "00000000003503007767"
            ),
            (float) 1234.56,
            "Kurzer Test-Verwendungszweck"
        );
        $this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "16050000",
                'account_number' => "000000000003503007767"),
            (float) 321.9,
            "Kurzer Test-Verwendungszweck"
        );
        $this->assertEquals(0, $this->fixture->count());
    }

    /* following tests should check for correct file size, i.e. correct
     * number of C record parts, for different numbers of extensions */
    public function testFileLengthOneTransferNoExt()
    {
        // shortest format with one C record, no extensions
        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "16050000",
                'account_number' => "3503007767"
            ),
            (float) 1234.56,
            "Kurzer Test-Verwendungszweck"
        ));
        $this->assertEquals(128*(1+2+1), strlen($this->fixture->getFileContent()));
    }

    public function testFileLengthTwoTransfersNoExt()
    {
        // two C records of two parts each
        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "16050000",
                'account_number' => "3503007767"
            ),
            (float) 1234.56,
            "Kurzer Test-Verwendungszweck"
        ));
        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "16050000",
                'account_number' => "3503007767"),
            (float) 321.9,
            "Kurzer Test-Verwendungszweck"
        ));
        $this->assertEquals(128*(1+(2*2)+1), strlen($this->fixture->getFileContent()));
    }

    public function testFileLengthOneTransferOneExt()
    {
        // shortest format with one C record, one extension
        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'additional_name' => "Additional Senders Name",
                'bank_code' => "16050000",
                'account_number' => "3503007767"
            ),
            (float) 1234.56,
            "Kurzer Test-Verwendungszweck"
        ));
        $this->assertEquals(128*(1+2+1), strlen($this->fixture->getFileContent()));
    }

    public function testFileLengthOneTransferTwoExt()
    {
        // shortest format with one C record, two extensions
        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'additional_name' => "Additional Senders Name",
                'bank_code' => "16050000",
                'account_number' => "3503007767"
            ),
            (float) 1234.56,
            array("Verwendungszweck Zeile 1",
                  "Verwendungszweck Zeile 2")
        ));
        $this->assertEquals(128*(1+2+1), strlen($this->fixture->getFileContent()));
    }

    public function testFileLengthOneTransferTwoExt2a()
    {
        // check if still valid without purpose extension
        $DTA_test_account = array(
             'name' => "Senders Name",
             'additional_name' => "Additional Senders Name",
             'bank_code' => "16050000",
             'account_number' => "350300767",
         );
        $this->assertTrue($this->fixture->setAccountFileSender($DTA_test_account));

        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'additional_name' => "Additional Receivers Name",
                'bank_code' => "16050000",
                'account_number' => "3503007767"
            ),
            (float) 1234.56,
            array("Verwendungszweck Zeile 1")
        ));
        $this->assertEquals(128*(1+2+1), strlen($this->fixture->getFileContent()));
    }

    public function testFileLengthOneTransferTwoExt2b()
    {
        // check if still valid when giving purpose as string
        $DTA_test_account = array(
             'name' => "Senders Name",
             'additional_name' => "Additional Senders Name",
             'bank_code' => "16050000",
             'account_number' => "350300767",
         );
        $this->assertTrue($this->fixture->setAccountFileSender($DTA_test_account));

        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'additional_name' => "Additional Receivers Name",
                'bank_code' => "16050000",
                'account_number' => "3503007767"
            ),
            (float) 1234.56,
            "Verwendungszweck Zeile 1"
        ));
        $this->assertEquals(128*(1+2+1), strlen($this->fixture->getFileContent()));
    }

    public function testFileLengthOneTransferThreeExt()
    {
        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'additional_name' => "Additional Senders Name",
                'bank_code' => "16050000",
                'account_number' => "3503007767"
            ),
            (float) 1234.56,
            array("Verwendungszweck Zeile 1",
                  "Verwendungszweck Zeile 2",
                  "Verwendungszweck Zeile 3")
        ));
        // C record needs three parts now
        $this->assertEquals(128*(1+3+1), strlen($this->fixture->getFileContent()));
    }

    public function testFileLengthOneTransferSixExt()
    {
        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'additional_name' => "Additional Senders Name",
                'bank_code' => "16050000",
                'account_number' => "3503007767"
            ),
            (float) 1234.56,
            array("Verwendungszweck Zeile 1",
                  "Verwendungszweck Zeile 2",
                  "Verwendungszweck Zeile 3",
                  "Verwendungszweck Zeile 4",
                  "Verwendungszweck Zeile 5",
                  "Verwendungszweck Zeile 6")
        ));
        $this->assertEquals(128*(1+3+1), strlen($this->fixture->getFileContent()));
    }

    public function testFileLengthOneTransferSevenExt()
    {
        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'additional_name' => "Additional Senders Name",
                'bank_code' => "16050000",
                'account_number' => "3503007767"
            ),
            (float) 1234.56,
            array("Verwendungszweck Zeile 1",
                  "Verwendungszweck Zeile 2",
                  "Verwendungszweck Zeile 3",
                  "Verwendungszweck Zeile 4",
                  "Verwendungszweck Zeile 5",
                  "Verwendungszweck Zeile 6",
                  "Verwendungszweck Zeile 7")
        ));
        // C record needs four parts now
        $this->assertEquals(128*(1+4+1), strlen($this->fixture->getFileContent()));
    }

    public function testFileLengthOneTransferTenExt()
    {
        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'additional_name' => "Additional Senders Name",
                'bank_code' => "16050000",
                'account_number' => "3503007767"
            ),
            (float) 1234.56,
            array("Verwendungszweck Zeile 1",
                  "Verwendungszweck Zeile 2",
                  "Verwendungszweck Zeile 3",
                  "Verwendungszweck Zeile 4",
                  "Verwendungszweck Zeile 5",
                  "Verwendungszweck Zeile 6",
                  "Verwendungszweck Zeile 7",
                  "Verwendungszweck Zeile 8",
                  "Verwendungszweck Zeile 9",
                  "Verwendungszweck Zeile 10")
        ));
        $this->assertEquals(128*(1+4+1), strlen($this->fixture->getFileContent()));
    }

    public function testFileLengthOneTransferElevenExt()
    {
        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'additional_name' => "Additional Senders Name",
                'bank_code' => "16050000",
                'account_number' => "3503007767"
            ),
            (float) 1234.56,
            array("Verwendungszweck Zeile 1",
                  "Verwendungszweck Zeile 2",
                  "Verwendungszweck Zeile 3",
                  "Verwendungszweck Zeile 4",
                  "Verwendungszweck Zeile 5",
                  "Verwendungszweck Zeile 6",
                  "Verwendungszweck Zeile 7",
                  "Verwendungszweck Zeile 8",
                  "Verwendungszweck Zeile 9",
                  "Verwendungszweck Zeile 10",
                  "Verwendungszweck Zeile 11")
        ));
        // C record needs five parts now
        $this->assertEquals(128*(1+5+1), strlen($this->fixture->getFileContent()));
    }

    public function testFileLengthOneTransferFourteenExt()
    {
        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'additional_name' => "Additional Receivers Name",
                'bank_code' => "16050000",
                'account_number' => "3503007767"
            ),
            (float) 1234.56,
            array("Verwendungszweck Zeile 1",
                  "Verwendungszweck Zeile 2",
                  "Verwendungszweck Zeile 3",
                  "Verwendungszweck Zeile 4",
                  "Verwendungszweck Zeile 5",
                  "Verwendungszweck Zeile 6",
                  "Verwendungszweck Zeile 7",
                  "Verwendungszweck Zeile 8",
                  "Verwendungszweck Zeile 9",
                  "Verwendungszweck Zeile 10",
                  "Verwendungszweck Zeile 11",
                  "Verwendungszweck Zeile 12",
                  "Verwendungszweck Zeile 13",
                  "Verwendungszweck Zeile 14")
        ));
        $this->assertEquals(128*(1+5+1), strlen($this->fixture->getFileContent()));
    }

    public function testFileLengthOneTransferFifteenExt()
    {
        // add all 15 possible extensions
        $DTA_test_account = array(
             'name' => "Senders Name",
             'additional_name' => "Additional Senders Name",
             'bank_code' => "16050000",
             'account_number' => "350300767",
         );
        $this->assertTrue($this->fixture->setAccountFileSender($DTA_test_account));

        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'additional_name' => "Additional Receivers Name",
                'bank_code' => "16050000",
                'account_number' => "3503007767"
            ),
            (float) 1234.56,
            array("Verwendungszweck Zeile 1",
                  "Verwendungszweck Zeile 2",
                  "Verwendungszweck Zeile 3",
                  "Verwendungszweck Zeile 4",
                  "Verwendungszweck Zeile 5",
                  "Verwendungszweck Zeile 6",
                  "Verwendungszweck Zeile 7",
                  "Verwendungszweck Zeile 8",
                  "Verwendungszweck Zeile 9",
                  "Verwendungszweck Zeile 10",
                  "Verwendungszweck Zeile 11",
                  "Verwendungszweck Zeile 12",
                  "Verwendungszweck Zeile 13",
                  "Verwendungszweck Zeile 14")
        ));
        $this->assertEquals(128*(1+6+1), strlen($this->fixture->getFileContent()));
    }

    public function testPurposeLineLimit()
    {
        // too many purpose lines
        $this->assertFalse($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "16050000",
                'account_number' => "3503007767"
            ),
            (float) 1234.56,
            array("Verwendungszweck Zeile 1",
                  "Verwendungszweck Zeile 2",
                  "Verwendungszweck Zeile 3",
                  "Verwendungszweck Zeile 4",
                  "Verwendungszweck Zeile 5",
                  "Verwendungszweck Zeile 6",
                  "Verwendungszweck Zeile 7",
                  "Verwendungszweck Zeile 8",
                  "Verwendungszweck Zeile 9",
                  "Verwendungszweck Zeile 10",
                  "Verwendungszweck Zeile 11",
                  "Verwendungszweck Zeile 12",
                  "Verwendungszweck Zeile 13",
                  "Verwendungszweck Zeile 14",
                  "Verwendungszweck Zeile 15")
        ));
        $this->assertEquals(0, $this->fixture->count());
    }

    public function testSaveFileTrue()
    {
        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "16050000",
                'account_number' => "3503007767"
            ),
            (float) 1234.56,
            "Kurzer Test-Verwendungszweck"
        ));
        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "16050000",
                'account_number' => "3503007767"),
            (float) 321.9,
            "Kurzer Test-Verwendungszweck"
        ));

        $tmpfname = tempnam(sys_get_temp_dir(), "dtatest");
        if ($this->fixture->saveFile($tmpfname)) {
            $file_content = file_get_contents($tmpfname);
            unlink($tmpfname);
            $this->assertEquals(768, strlen($file_content));
        } else {
            $this->assertTrue(false);
        }
    }

    public function testSaveFileFalse()
    {
        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "16050000",
                'account_number' => "3503007767"
            ),
            (float) 1234.56,
            "Kurzer Test-Verwendungszweck"
        ));

        $tmpfname = "/root/nonexistantdirectory/dtatestfile";
        $this->assertFalse($this->fixture->saveFile($tmpfname));
    }

    public function testContent()
    {
        $this->fixture->addExchange(array(
                "name"           => "Franz Mueller",
                "bank_code"      => 33334444,
                "account_number" => 13579000,
            ),
            (float) 1234.56,
            "Test-Verwendungszweck"
        );
        $this->fixture->addExchange(array(
                "name"           => "Franz Mueller",
                "bank_code"      => 33334444,
                "account_number" => 13579000
            ),
            (float) 321.9,
            "Test-Verwendungszweck"
        );

        $date = strftime("%d%m%y", time());

        $expected = // 64 chars per line:
            '0128AGK1605000000000000SENDERS NAME               '.$date.'    3503'.
            '0077670000000000                                               1'.
            '0187C16050000333344440013579000000000000000051000 00000000000160'.
            '50000350300776700000123456   FRANZ MUELLER                      '.
            'SENDERS NAME               TEST-VERWENDUNGSZWECK      1  00     '.
            '                                                                '.
            '0187C16050000333344440013579000000000000000051000 00000000000160'.
            '50000350300776700000032190   FRANZ MUELLER                      '.
            'SENDERS NAME               TEST-VERWENDUNGSZWECK      1  00     '.
            '                                                                '.
            '0128E     000000200000000000000000000002715800000000000066668888'.
            '0000000155646                                                   ';
        $this->assertEquals($expected, $this->fixture->getFileContent());
    }

    public function testGetMetaData1()
    {
        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "16050000",
                'account_number' => "3503007767"
            ),
            (float) 1234.56,
            "Kurzer Test-Verwendungszweck"
        ));

        $meta = $this->fixture->getMetaData();
        $this->assertTrue($meta["sender_name"]      == "SENDERS NAME");
        $this->assertTrue($meta["sender_bank_code"] == "16050000");
        $this->assertTrue($meta["sender_account"]   == "3503007767");
        $this->assertTrue($meta["sum_amounts"]      == "1234.56");
        $this->assertTrue($meta["sum_bankcodes"]    == "16050000");
        $this->assertTrue($meta["sum_accounts"]     == "3503007767");
        $this->assertTrue($meta["count"]            == "1");
        $this->assertTrue(strftime("%d%m%y", $meta["date"])
                            == strftime("%d%m%y", time()));
    }

    public function testGetMetaData2()
    {
        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "16050000",
                'account_number' => "3503007767"
            ),
            (float) 1234.56,
            "Kurzer Test-Verwendungszweck"
        ));
        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "16050000",
                'account_number' => "3503007767"
            ),
            (float) 1234.56,
            "Kurzer Test-Verwendungszweck"
        ));
        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "16050000",
                'account_number' => "3503007767"
            ),
            (float) 1234.56,
            "Kurzer Test-Verwendungszweck"
        ));

        $meta = $this->fixture->getMetaData();
        $this->assertTrue($meta["sender_name"]      == "SENDERS NAME");
        $this->assertTrue($meta["sender_bank_code"] == "16050000");
        $this->assertTrue($meta["sender_account"]   == "3503007767");
        $this->assertTrue($meta["sum_amounts"]      == 3*1234.56);
        $this->assertTrue($meta["sum_bankcodes"]    == 3*16050000);
        $this->assertTrue($meta["sum_accounts"]     == 3*3503007767);
        $this->assertTrue($meta["count"]            == "3");
        $this->assertTrue(strftime("%d%m%y", $meta["date"])
                            == strftime("%d%m%y", time()));
    }

    public function testParserBasics()
    {
        $teststring = // 64 chars per line; same as in testContent()
            '0128AGK1605000000000000SENDERS NAME               '.strftime("%d%m%y", time()).'    3503'.
            '0077670000000000                                               1'.
            '0187C16050000333344440013579000000000000000051000 00000000000160'.
            '50000350300776700000123456   FRANZ MUELLER                      '.
            'SENDERS NAME               TEST-VERWENDUNGSZWECK      1  00     '.
            '                                                                '.
            '0187C16050000333344440013579000000000000000051000 00000000000160'.
            '50000350300776700000032190   FRANZ MUELLER                      '.
            'SENDERS NAME               TEST-VERWENDUNGSZWECK      1  00     '.
            '                                                                '.
            '0128E     000000200000000000000000000002715800000000000066668888'.
            '0000000155646                                                   ';
        $dta = new DTA($teststring);
        $meta = $dta->getMetaData();
        $this->assertTrue($meta["sender_name"]      == "SENDERS NAME");
        $this->assertTrue($meta["sender_bank_code"] == "16050000");
        $this->assertTrue($meta["sender_account"]   == "3503007767");
        $this->assertTrue($meta["sum_amounts"]      == "1556.46");
        $this->assertTrue($meta["sum_bankcodes"]    == 2*33334444);
        $this->assertTrue($meta["sum_accounts"]     == 2*13579000);
        $this->assertTrue($meta["count"]            == "2");
        $this->assertTrue(strftime("%d%m%y", $meta["date"])
                            == strftime("%d%m%y", time()));

    }

}
