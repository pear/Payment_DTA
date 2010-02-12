<?php
require_once 'PHPUnit/Framework.php';

//make cvs testing work
chdir(dirname(__FILE__) . '/../');
require_once 'DTA.php';

// to prevent E_STRICT errors from strftime()
date_default_timezone_set('Europe/Berlin');

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
        // this test is only for 32-bit systems where some (10-digit)
        // account numbers are representable with integers but others are not
        if (PHP_INT_MAX != 2147483647) {
            $this->markTestSkipped('unexpected PHP_INT_MAX -- no 32bit system');
        } else {
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
            $this->assertSame(0, $this->fixture->count());
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

            $this->assertSame(2, $this->fixture->count());
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
        $this->assertSame(0, $this->fixture->count());
    }

    public function testMaxAmount()
    {
        /*
         * this test was written for 32bit systems.
         * on 64bit systems the value PHP_INT_MAX/100 is too big for a float,
         * i.e. introduces a considerable rounding error (yielding a cent
         * amount > PHP_INT_MAX). Thus the 64bit-case is only a workaround
         * to test some smaller value.
         */

        if (PHP_INT_MAX === 2147483647) { // 32bit
            $this->fixture->addExchange(array(
                    'name' => "A Receivers Name",
                    'bank_code' => "16050000",
                    'account_number' => "3503007767"
                ),
                (float) PHP_INT_MAX/100,
                "Ein Test-Verwendungszweck"
            );
            $this->assertSame(1, $this->fixture->count());
        } elseif (PHP_INT_MAX === 9223372036854775807) { // 64bit
            $this->fixture->addExchange(array(
                    'name' => "A Receivers Name",
                    'bank_code' => "16050000",
                    'account_number' => "3503007767"
                ),
                (float) (PHP_INT_MAX-1000)/100,
                "Ein Test-Verwendungszweck"
            );
            $this->assertSame(1, $this->fixture->count());
        } else {
            $this->markTestSkipped('unexpected PHP_INT_MAX -- no 32bit/64bit system?');
        }
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
        $this->assertSame(0, $this->fixture->count());
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
            $this->assertSame(10, $this->fixture->count());
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
            $this->assertSame(9, $this->fixture->count());
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
        $this->assertSame("AE AE OEUE SS", $result);
    }

    public function testMakeValidStringExtended()
    {
        if (!method_exists($this->fixture, 'count')) {
            $this->markTestSkipped('v1.2.0 had fewer char replacements');
        } else {
            $result = $this->fixture->makeValidString("ä Äáöøüß");
            $this->assertSame("AE AEAOEOUESS", $result);
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
        $this->assertSame(512, strlen($this->fixture->getFileContent()));
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
        $this->assertSame(512, strlen($this->fixture->getFileContent()));
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
        $this->assertSame(512, strlen($this->fixture->getFileContent()));
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
        $this->assertSame(512, strlen($this->fixture->getFileContent()));
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

        $this->assertSame(256, strlen($this->fixture->getFileContent()));
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

        $this->assertSame(256, strlen($this->fixture->getFileContent()));
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
        $this->assertSame(0, $this->fixture->count());
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
        $this->assertSame(128*(1+2+1), strlen($this->fixture->getFileContent()));
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
        $this->assertSame(128*(1+(2*2)+1), strlen($this->fixture->getFileContent()));
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
        $this->assertSame(128*(1+2+1), strlen($this->fixture->getFileContent()));
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
        $this->assertSame(128*(1+2+1), strlen($this->fixture->getFileContent()));
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
        $this->assertSame(128*(1+2+1), strlen($this->fixture->getFileContent()));
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
        $this->assertSame(128*(1+2+1), strlen($this->fixture->getFileContent()));
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
        $this->assertSame(128*(1+3+1), strlen($this->fixture->getFileContent()));
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
        $this->assertSame(128*(1+3+1), strlen($this->fixture->getFileContent()));
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
        $this->assertSame(128*(1+4+1), strlen($this->fixture->getFileContent()));
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
        $this->assertSame(128*(1+4+1), strlen($this->fixture->getFileContent()));
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
        $this->assertSame(128*(1+5+1), strlen($this->fixture->getFileContent()));
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
        $this->assertSame(128*(1+5+1), strlen($this->fixture->getFileContent()));
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
        $this->assertSame(128*(1+6+1), strlen($this->fixture->getFileContent()));
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
        $this->assertSame(0, $this->fixture->count());
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
            $this->assertSame(768, strlen($file_content));
        } else {
            $this->fail();
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
        $this->assertSame($expected, $this->fixture->getFileContent());
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
        $this->assertEquals("SENDERS NAME", $meta["sender_name"]);
        $this->assertEquals("16050000", $meta["sender_bank_code"]);
        $this->assertEquals("3503007767", $meta["sender_account"]);
        $this->assertEquals(1234.56, $meta["sum_amounts"]);
        $this->assertEquals(16050000, $meta["sum_bankcodes"]);
        $this->assertEquals(3503007767, $meta["sum_accounts"]);
        $this->assertEquals("1", $meta["count"]);
        $this->assertEquals(strftime("%d%m%y", time()),
            strftime("%d%m%y", $meta["date"]));

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
        $this->assertEquals("SENDERS NAME", $meta["sender_name"]);
        $this->assertEquals("16050000", $meta["sender_bank_code"]);
        $this->assertEquals("3503007767", $meta["sender_account"]);
        $this->assertEquals(3*1234.56, $meta["sum_amounts"]);
        $this->assertEquals(3*16050000, $meta["sum_bankcodes"]);
        $this->assertEquals(3*3503007767, $meta["sum_accounts"]);
        $this->assertEquals("3", $meta["count"]);
        $this->assertEquals(strftime("%d%m%y", time()),
            strftime("%d%m%y", $meta["date"]));
    }

    public function testIteratorEmpty()
    {
        foreach ($this->fixture as $key => $value) {
            $this->fail();
        }
    }

    public function testIteratorElements()
    {

        $this->fixture->addExchange(array(
            'name' => "A Receivers Name",
            'bank_code' => "16050000",
            'account_number' => "3503007767"
            ),
            (float) 1234.56,
            "Test-Verwendungszweck1"
        );
        $this->fixture->addExchange(array(
            'name' => "A Receivers Name",
            'bank_code' => "16050000",
            'account_number' => "3503007767"),
            (float) 321.9,
            "Test-Verwendungszweck2"
        );

        foreach ($this->fixture as $key => $value) {
            // from setUp()
            $this->assertSame(strtoupper("Senders Name"), $value['sender_name']);
            $this->assertSame("16050000", $value['sender_bank_code']);
            $this->assertSame("3503007767", $value['sender_account_number']);

            // same values in addExchange() above
            $this->assertSame(strtoupper("A Receivers Name"), $value['receiver_name']);
            $this->assertSame("16050000", $value['receiver_bank_code']);
            $this->assertSame("3503007767", $value['receiver_account_number']);
            $this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY,
                $value['purposes']);

            // different values in addExchange() above
            if ($key === 0) {
                $this->assertEquals(123456, $value['amount']);
                $this->assertEquals(strtoupper("Test-Verwendungszweck1"),
                    $value['purposes'][0]);
            } elseif ($key === 1) {
                $this->assertEquals(32190, $value['amount']);
                $this->assertEquals(strtoupper("Test-Verwendungszweck2"),
                    $value['purposes'][0]);
            } else {
                $this->fail();
            }
        }
    }


    public function testParserBasicCredit()
    {
        $teststring = // same as in testContent()
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
        $this->assertEquals("SENDERS NAME", $meta["sender_name"]);
        $this->assertEquals("16050000", $meta["sender_bank_code"]);
        $this->assertEquals("3503007767", $meta["sender_account"]);
        $this->assertEquals("1556.46", $meta["sum_amounts"]);
        $this->assertEquals(2*33334444, $meta["sum_bankcodes"]);
        $this->assertEquals(2*13579000, $meta["sum_accounts"]);
        $this->assertEquals("2", $meta["count"]);
        $this->assertEquals(strftime("%d%m%y", time()),
            strftime("%d%m%y", $meta["date"]));
    }

    public function testParserBasicCreditBankFile()
    {
        $teststring = // same as in testContent() but with type GB
            '0128AGB1605000000000000SENDERS NAME               '.strftime("%d%m%y", time()).'    3503'.
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
        $this->assertEquals("SENDERS NAME", $meta["sender_name"]);
        $this->assertEquals("16050000", $meta["sender_bank_code"]);
        $this->assertEquals("3503007767", $meta["sender_account"]);
        $this->assertEquals("1556.46", $meta["sum_amounts"]);
        $this->assertEquals(2*33334444, $meta["sum_bankcodes"]);
        $this->assertEquals(2*13579000, $meta["sum_accounts"]);
        $this->assertEquals("2", $meta["count"]);
        $this->assertEquals(strftime("%d%m%y", time()),
            strftime("%d%m%y", $meta["date"]));
    }

    public function testParserBasicDebit()
    {
        $teststring = // same as in testContent() but type LK
            '0128ALK1605000000000000SENDERS NAME               '.strftime("%d%m%y", time()).'    3503'.
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
        $this->assertEquals("SENDERS NAME", $meta["sender_name"]);
        $this->assertEquals("16050000", $meta["sender_bank_code"]);
        $this->assertEquals("3503007767", $meta["sender_account"]);
        $this->assertEquals("1556.46", $meta["sum_amounts"]);
        $this->assertEquals(2*33334444, $meta["sum_bankcodes"]);
        $this->assertEquals(2*13579000, $meta["sum_accounts"]);
        $this->assertEquals("2", $meta["count"]);
        $this->assertEquals(strftime("%d%m%y", time()),
            strftime("%d%m%y", $meta["date"]));
    }

    public function testParserBasicDebitBankFile()
    {
        $teststring = // same as in testContent() but type LB
            '0128ALB1605000000000000SENDERS NAME               '.strftime("%d%m%y", time()).'    3503'.
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
        $this->assertEquals("SENDERS NAME", $meta["sender_name"]);
        $this->assertEquals("16050000", $meta["sender_bank_code"]);
        $this->assertEquals("3503007767", $meta["sender_account"]);
        $this->assertEquals("1556.46", $meta["sum_amounts"]);
        $this->assertEquals(2*33334444, $meta["sum_bankcodes"]);
        $this->assertEquals(2*13579000, $meta["sum_accounts"]);
        $this->assertEquals("2", $meta["count"]);
        $this->assertEquals(strftime("%d%m%y", time()),
            strftime("%d%m%y", $meta["date"]));
    }

    public function testParserBasicInvalidType()
    {
        $teststring =
        // 64 chars per line; same as in testContent() but invalid char @ offset 5
            '0128AXK1605000000000000SENDERS NAME               '.strftime("%d%m%y", time()).'    3503'.
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
        $this->setExpectedException('Payment_DTA_FatalParseException');
        $dta = new DTA($teststring);
    }

    public function testParserWrongLength()
    {
        $teststring = // same as in testContent() but 1 byte longer
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
            '0000000155646                                                    ';
        $this->setExpectedException('Payment_DTA_FatalParseException');
        $dta = new DTA($teststring);
    }

    public function testParserWrongCType()
    {
        $teststring = // same as in testContent() but 2nd C record has an X instead
            '0128AGK1605000000000000SENDERS NAME               '.strftime("%d%m%y", time()).'    3503'.
            '0077670000000000                                               1'.
            '0187C16050000333344440013579000000000000000051000 00000000000160'.
            '50000350300776700000123456   FRANZ MUELLER                      '.
            'SENDERS NAME               TEST-VERWENDUNGSZWECK      1  00     '.
            '                                                                '.
            '0187X16050000333344440013579000000000000000051000 00000000000160'.
            '50000350300776700000032190   FRANZ MUELLER                      '.
            'SENDERS NAME               TEST-VERWENDUNGSZWECK      1  00     '.
            '                                                                '.
            '0128E     000000200000000000000000000002715800000000000066668888'.
            '0000000155646                                                   ';
        $this->setExpectedException('Payment_DTA_ParseException');
        $dta = new DTA($teststring);
        $this->assertSame(1, $dta->count());
    }
    public function testParserWrongCLength()
    {
        $teststring = // same as in testContent() but 2nd C record has length 188
            '0128AGK1605000000000000SENDERS NAME               '.strftime("%d%m%y", time()).'    3503'.
            '0077670000000000                                               1'.
            '0187C16050000333344440013579000000000000000051000 00000000000160'.
            '50000350300776700000123456   FRANZ MUELLER                      '.
            'SENDERS NAME               TEST-VERWENDUNGSZWECK      1  00     '.
            '                                                                '.
            '0188C16050000333344440013579000000000000000051000 00000000000160'.
            '50000350300776700000032190   FRANZ MUELLER                      '.
            'SENDERS NAME               TEST-VERWENDUNGSZWECK      1  00     '.
            '                                                                '.
            '0128E     000000200000000000000000000002715800000000000066668888'.
            '0000000155646                                                   ';
        $this->setExpectedException('Payment_DTA_ParseException');
        $dta = new DTA($teststring);
        $this->assertSame(1, $dta->count());
    }
    public function testParserWrongCheckCount()
    {
        $teststring = // same as in testContent() but E record indicates 3 C records
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
            '0128E     000000300000000000000000000002715800000000000066668888'.
            '0000000155646                                                   ';
        $this->setExpectedException('Payment_DTA_ChecksumException');
        $dta = new DTA($teststring);
    }
    public function testParserWrongCheckAccounts()
    {
        $teststring = // same as in testContent() but E record has wrong account sum
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
            '0128E     000000200000000000000000000002715900000000000066668888'.
            '0000000155646                                                   ';
        $this->setExpectedException('Payment_DTA_ChecksumException');
        $dta = new DTA($teststring);
    }
    public function testParserWrongCheckBLZs()
    {
        $teststring = // same as in testContent() but E record has wrong bank code sum
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
            '0128E     000000200000000000000000000002715800000000000066668887'.
            '0000000155646                                                   ';
        $this->setExpectedException('Payment_DTA_ChecksumException');
        $dta = new DTA($teststring);
    }

    public function testParserWrongCheckAmounts()
    {
        $teststring = // same as in testContent() but E record has wrong amount sum
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
            '0000000055646                                                   ';
        $this->setExpectedException('Payment_DTA_ChecksumException');
        $dta = new DTA($teststring);
    }

    public function testParserCExtensions_0()
    {
        // no extensions
        $DTA_test_account = array(
             'name' => "Senders Name",
             'bank_code' => "16050000",
             'account_number' => "350300767",
         );
        $this->fixture->setAccountFileSender($DTA_test_account);
        $this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "16050000",
                'account_number' => "3503007767"
            ),
            (float) 1234.56,
            array("Verwendungszweck Zeile 1")
        );
        $test = new DTA($this->fixture->getFileContent());
        $this->assertSame(1, $test->count());
    }

    public function testParserCExtensions_1()
    {
        $DTA_test_account = array(
             'name' => "Senders Name",
             'bank_code' => "16050000",
             'account_number' => "350300767",
         );
        $this->fixture->setAccountFileSender($DTA_test_account);
        $this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "16050000",
                'account_number' => "3503007767"
            ),
            (float) 1234.56,
            array("Verwendungszweck Zeile 1",
                  "Verwendungszweck Zeile 2")
        );
        $test = new DTA($this->fixture->getFileContent());
        $this->assertSame(1, $test->count());
    }

    public function testParserCExtensions_2()
    {
        $DTA_test_account = array(
             'name' => "Senders Name",
             'bank_code' => "16050000",
             'account_number' => "350300767",
         );
        $this->fixture->setAccountFileSender($DTA_test_account);
        $this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "16050000",
                'account_number' => "3503007767"
            ),
            (float) 1234.56,
            array("Verwendungszweck Zeile 1",
                  "Verwendungszweck Zeile 2",
                  "Verwendungszweck Zeile 3")
        );
        $test = new DTA($this->fixture->getFileContent());
        $this->assertSame(1, $test->count());
    }

    public function testParserCExtensions_3()
    {
        $DTA_test_account = array(
             'name' => "Senders Name",
             'bank_code' => "16050000",
             'account_number' => "350300767",
         );
        $this->fixture->setAccountFileSender($DTA_test_account);
        $this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "16050000",
                'account_number' => "3503007767"
            ),
            (float) 1234.56,
            array("Verwendungszweck Zeile 1",
                  "Verwendungszweck Zeile 2",
                  "Verwendungszweck Zeile 3",
                  "Verwendungszweck Zeile 4")
        );
        $test = new DTA($this->fixture->getFileContent());
        $this->assertSame(1, $test->count());
    }

    public function testParserCExtensions_4()
    {
        $DTA_test_account = array(
             'name' => "Senders Name",
             'bank_code' => "16050000",
             'account_number' => "350300767",
         );
        $this->fixture->setAccountFileSender($DTA_test_account);
        $this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "16050000",
                'account_number' => "3503007767"
            ),
            (float) 1234.56,
            array("Verwendungszweck Zeile 1",
                  "Verwendungszweck Zeile 2",
                  "Verwendungszweck Zeile 3",
                  "Verwendungszweck Zeile 4",
                  "Verwendungszweck Zeile 5")
        );
        $test = new DTA($this->fixture->getFileContent());
        $this->assertSame(1, $test->count());
    }

    public function testParserCExtensions_5()
    {
        $DTA_test_account = array(
             'name' => "Senders Name",
             'bank_code' => "16050000",
             'account_number' => "350300767",
         );
        $this->fixture->setAccountFileSender($DTA_test_account);
        $this->fixture->addExchange(array(
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
                  "Verwendungszweck Zeile 6")
        );
        $test = new DTA($this->fixture->getFileContent());
        $this->assertSame(1, $test->count());
    }

    public function testParserCExtensions_6()
    {
        $DTA_test_account = array(
             'name' => "Senders Name",
             'bank_code' => "16050000",
             'account_number' => "350300767",
         );
        $this->fixture->setAccountFileSender($DTA_test_account);
        $this->fixture->addExchange(array(
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
                  "Verwendungszweck Zeile 7")
        );
        $test = new DTA($this->fixture->getFileContent());
        $this->assertSame(1, $test->count());
    }

    public function testParserCExtensions_7()
    {
        $DTA_test_account = array(
             'name' => "Senders Name",
             'bank_code' => "16050000",
             'account_number' => "350300767",
         );
        $this->fixture->setAccountFileSender($DTA_test_account);
        $this->fixture->addExchange(array(
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
                  "Verwendungszweck Zeile 8")
        );
        $test = new DTA($this->fixture->getFileContent());
        $this->assertSame(1, $test->count());
    }

    public function testParserCExtensions_8()
    {
        $DTA_test_account = array(
             'name' => "Senders Name",
             'bank_code' => "16050000",
             'account_number' => "350300767",
         );
        $this->fixture->setAccountFileSender($DTA_test_account);
        $this->fixture->addExchange(array(
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
                  "Verwendungszweck Zeile 9")
        );
        $test = new DTA($this->fixture->getFileContent());
        $this->assertSame(1, $test->count());
    }

    public function testParserCExtensions_9()
    {
        $DTA_test_account = array(
             'name' => "Senders Name",
             'bank_code' => "16050000",
             'account_number' => "350300767",
         );
        $this->fixture->setAccountFileSender($DTA_test_account);
        $this->fixture->addExchange(array(
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
                  "Verwendungszweck Zeile 10")
        );
        $test = new DTA($this->fixture->getFileContent());
        $this->assertSame(1, $test->count());
    }

    public function testParserCExtensions_10()
    {
        $DTA_test_account = array(
             'name' => "Senders Name",
             'bank_code' => "16050000",
             'account_number' => "350300767",
         );
        $this->fixture->setAccountFileSender($DTA_test_account);
        $this->fixture->addExchange(array(
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
                  "Verwendungszweck Zeile 11")
        );
        $test = new DTA($this->fixture->getFileContent());
        $this->assertSame(1, $test->count());
    }

    public function testParserCExtensions_11()
    {
        $DTA_test_account = array(
             'name' => "Senders Name",
             'bank_code' => "16050000",
             'account_number' => "350300767",
         );
        $this->fixture->setAccountFileSender($DTA_test_account);
        $this->fixture->addExchange(array(
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
                  "Verwendungszweck Zeile 12")
        );
        $test = new DTA($this->fixture->getFileContent());
        $this->assertSame(1, $test->count());
    }

    public function testParserCExtensions_12()
    {
        $DTA_test_account = array(
             'name' => "Senders Name",
             'bank_code' => "16050000",
             'account_number' => "350300767",
         );
        $this->fixture->setAccountFileSender($DTA_test_account);
        $this->fixture->addExchange(array(
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
                  "Verwendungszweck Zeile 13")
        );
        $test = new DTA($this->fixture->getFileContent());
        $this->assertSame(1, $test->count());
    }

    public function testParserCExtensions_13()
    {
        $DTA_test_account = array(
             'name' => "Senders Name",
             'bank_code' => "16050000",
             'account_number' => "350300767",
         );
        $this->fixture->setAccountFileSender($DTA_test_account);
        $this->fixture->addExchange(array(
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
                  "Verwendungszweck Zeile 14")
        );
        $test = new DTA($this->fixture->getFileContent());
        $this->assertSame(1, $test->count());
    }

    public function testParserCExtensions_14()
    {
        $DTA_test_account = array(
             'name' => "Senders Name",
             'additional_name' => "Additional Senders Name",
             'bank_code' => "16050000",
             'account_number' => "350300767",
         );
        $this->fixture->setAccountFileSender($DTA_test_account);
        $this->fixture->addExchange(array(
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
                  "Verwendungszweck Zeile 14")
        );
        $test = new DTA($this->fixture->getFileContent());
        $this->assertSame(1, $test->count());
    }

    public function testParserCExtensions_15()
    {
        $DTA_test_account = array(
             'name' => "Senders Name",
             'additional_name' => "Additional Senders Name",
             'bank_code' => "16050000",
             'account_number' => "350300767",
         );
        $this->fixture->setAccountFileSender($DTA_test_account);
        $this->fixture->addExchange(array(
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
        );
        $test = new DTA($this->fixture->getFileContent());
        $this->assertSame(1, $test->count());
    }

    public function testParserCExtensions_16_Fail()
    {
        $teststring1 = // created with testParserCExtensions_15()
            '0128AGK1605000000000000SENDERS NAME               020210    0350'.
            '3007670000000000                                               1'.
            '0622C16050000160500003503007767000000000000051000 00000000000160'.
            '50000035030076700000123456   A RECEIVERS NAME                   '.
            'SENDERS NAME               VERWENDUNGSZWECK ZEILE 1   1  1501ADD'.
            'ITIONAL RECEIVERS NAME  02VERWENDUNGSZWECK ZEILE 2              '.
            '02VERWENDUNGSZWECK ZEILE 3   02VERWENDUNGSZWECK ZEILE 4   02VERW'.
            'ENDUNGSZWECK ZEILE 5   02VERWENDUNGSZWECK ZEILE 6               '.
            '02VERWENDUNGSZWECK ZEILE 7   02VERWENDUNGSZWECK ZEILE 8   02VERW'.
            'ENDUNGSZWECK ZEILE 9   02VERWENDUNGSZWECK ZEILE 10              '.
            '02VERWENDUNGSZWECK ZEILE 11  02VERWENDUNGSZWECK ZEILE 12  02VERW'.
            'ENDUNGSZWECK ZEILE 13  02VERWENDUNGSZWECK ZEILE 14              '.
            '03ADDITIONAL SENDERS NAME                                       '.
            '                                                                '.
            '0128E     000000100000000000000000000350300776700000000016050000'.
            '0000000123456                                                   ';

        $teststring = // invalid with 16 extensions in C record
            '0128AGK1605000000000000SENDERS NAME               020210    0350'.
            '3007670000000000                                               1'.
            '0622C16050000160500003503007767000000000000051000 00000000000160'.
            '50000035030076700000123456   A RECEIVERS NAME                   '.
            'SENDERS NAME               VERWENDUNGSZWECK ZEILE 1   1  1601ADD'.
            'ITIONAL RECEIVERS NAME  02VERWENDUNGSZWECK ZEILE 2              '.
            '02VERWENDUNGSZWECK ZEILE 3   02VERWENDUNGSZWECK ZEILE 4   02VERW'.
            'ENDUNGSZWECK ZEILE 5   02VERWENDUNGSZWECK ZEILE 6               '.
            '02VERWENDUNGSZWECK ZEILE 7   02VERWENDUNGSZWECK ZEILE 8   02VERW'.
            'ENDUNGSZWECK ZEILE 9   02VERWENDUNGSZWECK ZEILE 10              '.
            '02VERWENDUNGSZWECK ZEILE 11  02VERWENDUNGSZWECK ZEILE 12  02VERW'.
            'ENDUNGSZWECK ZEILE 13  02VERWENDUNGSZWECK ZEILE 14              '.
            '03ADDITIONAL SENDERS NAME    02VERWENDUNGSZWECK ZEILE 15        '.
            '                                                                '.
            '0128E     000000100000000000000000000350300776700000000016050000'.
            '0000000123456                                                   ';
        $this->setExpectedException('Payment_DTA_ParseException');
        $dta = new DTA($teststring);
    }

    public function testParserCExtensions_purpose_Fail()
    {
        $teststring = // 14 purpose extensions
            '0128AGK1605000000000000SENDERS NAME               020210    0350'.
            '3007670000000000                                               1'.
            '0622C16050000160500003503007767000000000000051000 00000000000160'.
            '50000035030076700000123456   A RECEIVERS NAME                   '.
            'SENDERS NAME               VERWENDUNGSZWECK ZEILE 1   1  1501ADD'.
            'ITIONAL RECEIVERS NAME  02VERWENDUNGSZWECK ZEILE 2              '.
            '02VERWENDUNGSZWECK ZEILE 3   02VERWENDUNGSZWECK ZEILE 4   02VERW'.
            'ENDUNGSZWECK ZEILE 5   02VERWENDUNGSZWECK ZEILE 6               '.
            '02VERWENDUNGSZWECK ZEILE 7   02VERWENDUNGSZWECK ZEILE 8   02VERW'.
            'ENDUNGSZWECK ZEILE 9   02VERWENDUNGSZWECK ZEILE 10              '.
            '02VERWENDUNGSZWECK ZEILE 11  02VERWENDUNGSZWECK ZEILE 12  02VERW'.
            'ENDUNGSZWECK ZEILE 13  02VERWENDUNGSZWECK ZEILE 14              '.
            '02VERWENDUNGSZWECK ZEILE 15                                     '.
            '                                                                '.
            '0128E     000000100000000000000000000350300776700000000016050000'.
            '0000000123456                                                   ';
        $this->setExpectedException('Payment_DTA_ParseException');
        $dta = new DTA($teststring);
    }

    public function testParserCExtensions_sender_Fail()
    {
        $teststring = // 2 add. sender name extensions
            '0128AGK1605000000000000SENDERS NAME               020210    0350'.
            '3007670000000000                                               1'.
            '0622C16050000160500003503007767000000000000051000 00000000000160'.
            '50000035030076700000123456   A RECEIVERS NAME                   '.
            'SENDERS NAME               VERWENDUNGSZWECK ZEILE 1   1  1501ADD'.
            'ITIONAL RECEIVERS NAME  02VERWENDUNGSZWECK ZEILE 2              '.
            '02VERWENDUNGSZWECK ZEILE 3   02VERWENDUNGSZWECK ZEILE 4   02VERW'.
            'ENDUNGSZWECK ZEILE 5   02VERWENDUNGSZWECK ZEILE 6               '.
            '02VERWENDUNGSZWECK ZEILE 7   02VERWENDUNGSZWECK ZEILE 8   02VERW'.
            'ENDUNGSZWECK ZEILE 9   02VERWENDUNGSZWECK ZEILE 10              '.
            '02VERWENDUNGSZWECK ZEILE 11  02VERWENDUNGSZWECK ZEILE 12  02VERW'.
            'ENDUNGSZWECK ZEILE 13  03ADDITIONAL SENDERS NAME1               '.
            '03ADDITIONAL SENDERS NAME2                                      '.
            '                                                                '.
            '0128E     000000100000000000000000000350300776700000000016050000'.
            '0000000123456                                                   ';
        $this->setExpectedException('Payment_DTA_ParseException');
        $dta = new DTA($teststring);
    }

    public function testParserCExtensions_receiver_Fail()
    {
        $teststring = // 2 add. receiver name extensions
            '0128AGK1605000000000000SENDERS NAME               020210    0350'.
            '3007670000000000                                               1'.
            '0622C16050000160500003503007767000000000000051000 00000000000160'.
            '50000035030076700000123456   A RECEIVERS NAME                   '.
            'SENDERS NAME               VERWENDUNGSZWECK ZEILE 1   1  1501ADD'.
            'ITIONAL RECEIVERS NAME1 02VERWENDUNGSZWECK ZEILE 2              '.
            '02VERWENDUNGSZWECK ZEILE 3   02VERWENDUNGSZWECK ZEILE 4   02VERW'.
            'ENDUNGSZWECK ZEILE 5   02VERWENDUNGSZWECK ZEILE 6               '.
            '02VERWENDUNGSZWECK ZEILE 7   02VERWENDUNGSZWECK ZEILE 8   02VERW'.
            'ENDUNGSZWECK ZEILE 9   02VERWENDUNGSZWECK ZEILE 10              '.
            '02VERWENDUNGSZWECK ZEILE 11  02VERWENDUNGSZWECK ZEILE 12  02VERW'.
            'ENDUNGSZWECK ZEILE 13  02VERWENDUNGSZWECK ZEILE 14              '.
            '01ADDITIONAL RECEIVERS NAME2                                    '.
            '                                                                '.
            '0128E     000000100000000000000000000350300776700000000016050000'.
            '0000000123456                                                   ';
        $this->setExpectedException('Payment_DTA_ParseException');
        $dta = new DTA($teststring);
    }

    public function testParserInvalidRecordLengthC()
    {
        $teststring = // C record length: 620 instead of 622
            '0128AGK1605000000000000SENDERS NAME               020210    0350'.
            '3007670000000000                                               1'.
            '0620C16050000160500003503007767000000000000051000 00000000000160'.
            '50000035030076700000123456   A RECEIVERS NAME                   '.
            'SENDERS NAME               VERWENDUNGSZWECK ZEILE 1   1  1501ADD'.
            'ITIONAL RECEIVERS NAME  02VERWENDUNGSZWECK ZEILE 2              '.
            '02VERWENDUNGSZWECK ZEILE 3   02VERWENDUNGSZWECK ZEILE 4   02VERW'.
            'ENDUNGSZWECK ZEILE 5   02VERWENDUNGSZWECK ZEILE 6               '.
            '02VERWENDUNGSZWECK ZEILE 7   02VERWENDUNGSZWECK ZEILE 8   02VERW'.
            'ENDUNGSZWECK ZEILE 9   02VERWENDUNGSZWECK ZEILE 10              '.
            '02VERWENDUNGSZWECK ZEILE 11  02VERWENDUNGSZWECK ZEILE 12  02VERW'.
            'ENDUNGSZWECK ZEILE 13  02VERWENDUNGSZWECK ZEILE 14              '.
            '03ADDITIONAL SENDERS NAME                                       '.
            '                                                                '.
            '0128E     000000100000000000000000000350300776700000000016050000'.
            '0000000123456                                                   ';
        $this->setExpectedException('Payment_DTA_ParseException');
        $dta = new DTA($teststring);
        $this->assertSame(0, $dta->count());
    }

    public function testParserInvalidRecordLengthE()
    {
        $teststring = // E record length: 129 instead of 128
            '0128AGK1605000000000000SENDERS NAME               020210    0350'.
            '3007670000000000                                               1'.
            '0622C16050000160500003503007767000000000000051000 00000000000160'.
            '50000035030076700000123456   A RECEIVERS NAME                   '.
            'SENDERS NAME               VERWENDUNGSZWECK ZEILE 1   1  1501ADD'.
            'ITIONAL RECEIVERS NAME  02VERWENDUNGSZWECK ZEILE 2              '.
            '02VERWENDUNGSZWECK ZEILE 3   02VERWENDUNGSZWECK ZEILE 4   02VERW'.
            'ENDUNGSZWECK ZEILE 5   02VERWENDUNGSZWECK ZEILE 6               '.
            '02VERWENDUNGSZWECK ZEILE 7   02VERWENDUNGSZWECK ZEILE 8   02VERW'.
            'ENDUNGSZWECK ZEILE 9   02VERWENDUNGSZWECK ZEILE 10              '.
            '02VERWENDUNGSZWECK ZEILE 11  02VERWENDUNGSZWECK ZEILE 12  02VERW'.
            'ENDUNGSZWECK ZEILE 13  02VERWENDUNGSZWECK ZEILE 14              '.
            '03ADDITIONAL SENDERS NAME                                       '.
            '                                                                '.
            '0129E     000000100000000000000000000350300776700000000016050000'.
            '0000000123456                                                   ';
        $this->setExpectedException('Payment_DTA_ParseException');
        $dta = new DTA($teststring);
        $this->assertSame(1, $dta->count());
    }

    public function testParserInvalidRecordLengthNonNumeric()
    {
        $teststring = // E record length: 12a instead of 128
            '0128AGK1605000000000000SENDERS NAME               020210    0350'.
            '3007670000000000                                               1'.
            '0622C16050000160500003503007767000000000000051000 00000000000160'.
            '50000035030076700000123456   A RECEIVERS NAME                   '.
            'SENDERS NAME               VERWENDUNGSZWECK ZEILE 1   1  1501ADD'.
            'ITIONAL RECEIVERS NAME  02VERWENDUNGSZWECK ZEILE 2              '.
            '02VERWENDUNGSZWECK ZEILE 3   02VERWENDUNGSZWECK ZEILE 4   02VERW'.
            'ENDUNGSZWECK ZEILE 5   02VERWENDUNGSZWECK ZEILE 6               '.
            '02VERWENDUNGSZWECK ZEILE 7   02VERWENDUNGSZWECK ZEILE 8   02VERW'.
            'ENDUNGSZWECK ZEILE 9   02VERWENDUNGSZWECK ZEILE 10              '.
            '02VERWENDUNGSZWECK ZEILE 11  02VERWENDUNGSZWECK ZEILE 12  02VERW'.
            'ENDUNGSZWECK ZEILE 13  02VERWENDUNGSZWECK ZEILE 14              '.
            '03ADDITIONAL SENDERS NAME                                       '.
            '                                                                '.
            '012aE     000000100000000000000000000350300776700000000016050000'.
            '0000000123456                                                   ';
        $this->setExpectedException('Payment_DTA_ParseException');
        $dta = new DTA($teststring);
        $this->assertSame(1, $dta->count());
    }

    public function testParserInvalidExtensionType()
    {
        $teststring = // last purpose line with invalid type 04
            '0128AGK1605000000000000SENDERS NAME               020210    0350'.
            '3007670000000000                                               1'.
            '0622C16050000160500003503007767000000000000051000 00000000000160'.
            '50000035030076700000123456   A RECEIVERS NAME                   '.
            'SENDERS NAME               VERWENDUNGSZWECK ZEILE 1   1  1501ADD'.
            'ITIONAL RECEIVERS NAME  02VERWENDUNGSZWECK ZEILE 2              '.
            '02VERWENDUNGSZWECK ZEILE 3   02VERWENDUNGSZWECK ZEILE 4   02VERW'.
            'ENDUNGSZWECK ZEILE 5   02VERWENDUNGSZWECK ZEILE 6               '.
            '02VERWENDUNGSZWECK ZEILE 7   02VERWENDUNGSZWECK ZEILE 8   02VERW'.
            'ENDUNGSZWECK ZEILE 9   02VERWENDUNGSZWECK ZEILE 10              '.
            '02VERWENDUNGSZWECK ZEILE 11  02VERWENDUNGSZWECK ZEILE 12  02VERW'.
            'ENDUNGSZWECK ZEILE 13  04VERWENDUNGSZWECK ZEILE 14              '.
            '03ADDITIONAL SENDERS NAME                                       '.
            '                                                                '.
            '0128E     000000100000000000000000000350300776700000000016050000'.
            '0000000123456                                                   ';
        $this->setExpectedException('Payment_DTA_ParseException');
        $dta = new DTA($teststring);
        $this->assertSame(0, $dta->count());
    }

    public function testParserInvalidERecord()
    {
        // just to maximize phpunit coverage  ;)
        $teststring = // add a 0 after 0128E
            '0128AGK1605000000000000SENDERS NAME               020210    0350'.
            '3007670000000000                                               1'.
            '0622C16050000160500003503007767000000000000051000 00000000000160'.
            '50000035030076700000123456   A RECEIVERS NAME                   '.
            'SENDERS NAME               VERWENDUNGSZWECK ZEILE 1   1  1501ADD'.
            'ITIONAL RECEIVERS NAME  02VERWENDUNGSZWECK ZEILE 2              '.
            '02VERWENDUNGSZWECK ZEILE 3   02VERWENDUNGSZWECK ZEILE 4   02VERW'.
            'ENDUNGSZWECK ZEILE 5   02VERWENDUNGSZWECK ZEILE 6               '.
            '02VERWENDUNGSZWECK ZEILE 7   02VERWENDUNGSZWECK ZEILE 8   02VERW'.
            'ENDUNGSZWECK ZEILE 9   02VERWENDUNGSZWECK ZEILE 10              '.
            '02VERWENDUNGSZWECK ZEILE 11  02VERWENDUNGSZWECK ZEILE 12  02VERW'.
            'ENDUNGSZWECK ZEILE 13  02VERWENDUNGSZWECK ZEILE 14              '.
            '03ADDITIONAL SENDERS NAME                                       '.
            '                                                                '.
            '0128E0    000000100000000000000000000350300776700000000016050000'.
            '0000000123456                                                   ';
        $this->setExpectedException('Payment_DTA_ParseException');
        $dta = new DTA($teststring);
        $this->assertSame(1, $dta->count());
    }

    /*
     * DTA data created by testParserCExtensions_15()
     * useful to build more negative test cases
        $teststring1 =
            '0128AGK1605000000000000SENDERS NAME               020210    0350'.
            '3007670000000000                                               1'.
            '0622C16050000160500003503007767000000000000051000 00000000000160'.
            '50000035030076700000123456   A RECEIVERS NAME                   '.
            'SENDERS NAME               VERWENDUNGSZWECK ZEILE 1   1  1501ADD'.
            'ITIONAL RECEIVERS NAME  02VERWENDUNGSZWECK ZEILE 2              '.
            '02VERWENDUNGSZWECK ZEILE 3   02VERWENDUNGSZWECK ZEILE 4   02VERW'.
            'ENDUNGSZWECK ZEILE 5   02VERWENDUNGSZWECK ZEILE 6               '.
            '02VERWENDUNGSZWECK ZEILE 7   02VERWENDUNGSZWECK ZEILE 8   02VERW'.
            'ENDUNGSZWECK ZEILE 9   02VERWENDUNGSZWECK ZEILE 10              '.
            '02VERWENDUNGSZWECK ZEILE 11  02VERWENDUNGSZWECK ZEILE 12  02VERW'.
            'ENDUNGSZWECK ZEILE 13  02VERWENDUNGSZWECK ZEILE 14              '.
            '03ADDITIONAL SENDERS NAME                                       '.
            '                                                                '.
            '0128E     000000100000000000000000000350300776700000000016050000'.
            '0000000123456                                                   ';
     */
}