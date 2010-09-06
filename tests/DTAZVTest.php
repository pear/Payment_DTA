<?php
require_once 'PHPUnit/Framework.php';

//make cvs testing work
chdir(dirname(__FILE__) . '/../');
require_once 'DTAZV.php';

class DTAZVTest extends PHPUnit_Framework_TestCase
{
    protected $backupGlobals = FALSE;
    protected $fixture;

    protected function setUp()
    {
        // Create the Array fixture.
        $this->fixture = new DTAZV();
        $DTAZV_account = array(
            'name' => "Senders Name",
            'additional_name' => '',
            'bank_code' => "16050000",
            'account_number' => "3503007767",
        );
        $this->fixture->setAccountFileSender($DTAZV_account);
    }

    public function testInstantiate()
    {
        $this->assertEquals("DTAZV", get_class($this->fixture));
    }

    public function testInstantiateShortBankCode()
    {
        $dtaus = new DTAZV();
        $DTAZV_account = array(
             'name' => "Senders Name",
             'additional_name' => '',
             'bank_code' => "16050",
             'account_number' => "3503007767",
        );

        $this->assertTrue($dtaus->setAccountFileSender($DTAZV_account));
    }

    public function testInstantiateNoBankCode()
    {
        $dtaus = new DTAZV();
        $DTAZV_account = array(
             'name' => "Senders Name",
             'additional_name' => '',
             'bank_code' => "",
             'account_number' => "3503007767",
         );

        $this->assertFalse($dtaus->setAccountFileSender($DTAZV_account));
    }

    public function testInstantiateLongBankCode()
    {
        $dtaus = new DTAZV();
        $DTAZV_account = array(
             'name' => "Senders Name",
             'additional_name' => '',
             'bank_code' => "160500001",
             'account_number' => "3503007767",
         );

        $this->assertFalse($dtaus->setAccountFileSender($DTAZV_account));
    }

    public function testInstantiateNoAccountNumber()
    {
        $dtaus = new DTAZV();
        $DTAZV_account = array(
             'name' => "Senders Name",
             'additional_name' => '',
             'bank_code' => "16050000",
             'account_number' => "",
         );

        $this->assertFalse($dtaus->setAccountFileSender($DTAZV_account));
    }

    public function testInstantiateLongAccountNumber()
    {
        $dtaus = new DTAZV();
        $DTAZV_account = array(
             'name' => "Senders Name",
             'additional_name' => '',
             'bank_code' => "16050000",
             'account_number' => "35030077671",
         );

        $this->assertFalse($dtaus->setAccountFileSender($DTAZV_account));
    }

    public function testInstantiateWithIntegerAccountNumberSmall()
    {
        // this test is only for 32-bit systems where some (10-digit)
        // account numbers are representable with integers but others are not
        if (PHP_INT_MAX != 2147483647) {
            $this->markTestSkipped('unexpected PHP_INT_MAX -- no 32bit system');
        } else {
            // small := leq PHP_INT_MAX (on 32-bit with 10 digits)
            $dtaus = new DTAZV();
            $DTAZV_account = array(
                 'name' => "Senders Name",
                 'additional_name' => 'and some more',
                 'bank_code' => "16050000",
                 'account_number' => PHP_INT_MAX,
            );
            $this->assertTrue($dtaus->setAccountFileSender($DTAZV_account));
        }
    }

    public function testInstantiateWithIntegerAccountNumberBig()
    {
        $dtaus = new DTAZV();
        $DTAZV_account = array(
             'name' => "Senders Name",
             'additional_name' => 'and some more',
             'bank_code' => "16050000",
             'account_number' => 3503007767,
        );

        $this->assertTrue($dtaus->setAccountFileSender($DTAZV_account));
    }

    public function testInstantiateWithIntegerBankCode()
    {
        $dtaus = new DTAZV();
        $DTAZV_account = array(
             'name' => "Senders Name",
             'additional_name' => '',
             'bank_code' => 16050000,
             'account_number' => "3503007767",
        );

        $this->assertTrue($dtaus->setAccountFileSender($DTAZV_account));
    }

    public function testInstantiateLetterInAccount()
    {
        $dtaus = new DTAZV();
        $DTAZV_account = array(
             'name' => "Senders Name",
             'additional_name' => '',
             'bank_code' => "16050000",
             'account_number' => "3503007A67",
         );

        $this->assertFalse($dtaus->setAccountFileSender($DTAZV_account));
    }

    public function testCountEmpty()
    {
        $this->assertSame(0, $this->fixture->count());
        $this->assertSame(256+256, strlen($this->fixture->getFileContent()));
    }

    public function testCountNonEmpty()
    {
        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "MARKDEFF",
                'account_number' => "DE68210501700012345678"
            ),
            (float) 1234.56,
            "Ein ganz lange Test-Verwendungszweck der über 35 Zeichen lang sein soll um umbrochen zu werden"
        ));
        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "RZTIAT22263",
                'account_number' => "DE21700519950000007229"),
            (float) 321.9,
            "Ein ganz lange Test-Verwendungszweck der über 35 Zeichen lang sein soll um umbrochen zu werden"
        ));

        $this->assertSame(2, $this->fixture->count());
        $this->assertSame(256+768+768+256, strlen($this->fixture->getFileContent()));
    }

    public function testInvalidBankCode()
    {
        $this->assertFalse($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "MARKDEF",
                'account_number' => "DE68210501700012345678"
            ),
            (float) 1234.56,
            "Ein ganz lange Test-Verwendungszweck der über 35 Zeichen lang sein soll um umbrochen zu werden"
        ));
        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "RZTIAT22263",
                'account_number' => "DE21700519950000007229"),
            (float) 321.9,
            "Ein ganz lange Test-Verwendungszweck der über 35 Zeichen lang sein soll um umbrochen zu werden"
        ));

        $this->assertSame(1, $this->fixture->count());
        $this->assertSame(256+768+256, strlen($this->fixture->getFileContent()));
    }

    public function testDTAZVMaxAmountPass()
    {
        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "RZTIAT22263",
                'account_number' => "DE21700519950000007229"),
            12500,
            "Ein ganz lange Test-Verwendungszweck der über 35 Zeichen lang sein soll um umbrochen zu werden"
        ));

        $this->assertSame(1, $this->fixture->count());
    }

    public function testDTAZVMaxAmountFail()
    {
        $this->assertFalse($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "RZTIAT22263",
                'account_number' => "DE21700519950000007229"
            ),
            12500.01,
            "Ein ganz lange Test-Verwendungszweck der über 35 Zeichen lang sein soll um umbrochen zu werden"
        ));
        $this->assertSame(0, $this->fixture->count());
    }

    public function testDTAZVLowerMaxAmountPass()
    {
        if (!method_exists($this->fixture, 'setMaxAmount')) {
            $this->markTestSkipped('no method setMaxAmount()');
            return;
        }
        $this->fixture->setMaxAmount(1000);
        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "RZTIAT22263",
                'account_number' => "DE21700519950000007229"),
            1000,
            "Test-Verwendungszweck"
        ));

        $this->assertSame(1, $this->fixture->count());
    }

    public function testDTAZVLowerMaxAmountFail()
    {
        if (!method_exists($this->fixture, 'setMaxAmount')) {
            $this->markTestSkipped('no method setMaxAmount()');
            return;
        }
        $this->fixture->setMaxAmount(1000);
        $this->assertFalse($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "MARKDEF",
                'account_number' => "DE21700519950000007229"
            ),
            1000.01,
            "Test-Verwendungszweck"
        ));
        $this->assertSame(0, $this->fixture->count());
    }

    public function testDTAZVHigherMaxAmountPass()
    {
        if (!method_exists($this->fixture, 'setMaxAmount')) {
            $this->markTestSkipped('no method setMaxAmount()');
            return;
        }
        $this->fixture->setMaxAmount(50000);
        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "RZTIAT22263",
                'account_number' => "DE21700519950000007229"),
            49999,
            "Test-Verwendungszweck"
        ));

        $this->assertSame(1, $this->fixture->count());
    }

    public function testDTAZVHigherMaxAmountFail()
    {
        if (!method_exists($this->fixture, 'setMaxAmount')) {
            $this->markTestSkipped('no method setMaxAmount()');
            return;
        }
        $this->fixture->setMaxAmount(50000);
        $this->assertFalse($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "MARKDEF",
                'account_number' => "DE21700519950000007229"
            ),
            50000.00999,
            "Test-Verwendungszweck"
        ));
        $this->assertSame(0, $this->fixture->count());
    }

    public function testDTAZVDisableMaxAmountPass()
    {
        /*
         * PHP_INT_MAX/100 causes problems on 64bit systems
         * (cf. DTATest.php, testMaxAmount()).
         * Thus use an arbitrary but large value.
         */
        if (!method_exists($this->fixture, 'setMaxAmount')) {
            $this->markTestSkipped('no method setMaxAmount()');
            return;
        }
        $this->fixture->setMaxAmount(0);
        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "RZTIAT22263",
                'account_number' => "DE21700519950000007229"),
            (PHP_INT_MAX-1000)/100 - 1,
            "Test-Verwendungszweck"
        ));

        $this->assertSame(1, $this->fixture->count());
    }

    public function testDTAZVDisableMaxAmountFail()
    {
        if (!method_exists($this->fixture, 'setMaxAmount')) {
            $this->markTestSkipped('no method setMaxAmount()');
            return;
        }
        $this->fixture->setMaxAmount(0);
        $this->assertFalse($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "RZTIAT22263",
                'account_number' => "DE21700519950000007229"
            ),
            PHP_INT_MAX/100 + 1,
            "Test-Verwendungszweck"
        ));
        $this->assertSame(0, $this->fixture->count());
    }


    public function testPurposesArray()
    {
        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "MARKDEFF",
                'account_number' => "DE68210501700012345678"
            ),
            (float) 1234.56,
            array("Ein ganz lange Test-Verwendungszweck",
                "der über 35 Zeichen lang sein soll",
                "um umbrochen zu werden")
        ));

        $this->assertSame(1, $this->fixture->count());
        $this->assertSame(256+768+256, strlen($this->fixture->getFileContent()));
    }

    public function testUmlautInRecvName()
    {
        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "Ä Receivers Näme",
                'bank_code' => "MARKDEFF",
                'account_number' => "DE68210501700012345678"
            ),
            (float) 1234.56,
            array("Ein ganz lange Test-Verwendungszweck",
                "der über 35 Zeichen lang sein soll",
                "um umbrochen zu werden")
        ));

        $this->assertSame(1, $this->fixture->count());
        $this->assertSame(256+768+256, strlen($this->fixture->getFileContent()));
    }

    public function testAdditionalSenderName()
    {
        $DTAZV_account = array(
             'name' => "Senders Name",
             'additional_name' => '',
             'bank_code' => "16050000",
             'account_number' => "3503007767",
        );
        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "MARKDEFF",
                'account_number' => "DE68210501700012345678"
            ),
            (float) 1234.56,
            "Test-Verwendungszweck",
            $DTAZV_account
        ));

        $this->assertSame(1, $this->fixture->count());
        $this->assertSame(256+768+256, strlen($this->fixture->getFileContent()));
    }

    public function testAdditionalSenderNameWithIntegers()
    {
        $DTAZV_test_account = array(
             'name' => "Senders Name",
             'additional_name' => 'and some more',
             'bank_code' => 16050000,
             'account_number' => 3503007767,
        );
        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "MARKDEFF",
                'account_number' => "DE68210501700012345678"
            ),
            (float) 1234.56,
            "Test-Verwendungszweck",
            $DTAZV_test_account
        ));

        $this->assertSame(1, $this->fixture->count());
        $this->assertSame(256+768+256, strlen($this->fixture->getFileContent()));
    }

    public function testValidStringTrue()
    {
        $result = $this->fixture->validString(" \$%&*+,-./0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ");
        $this->assertTrue($result);
    }

    public function testValidStringFalse()
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
        $result = $this->fixture->makeValidString("ä Ä~áöøü§ß");
        $this->assertSame("AE AE AOEOUE SS", $result);
    }

    public function testFileLength()
    {
        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "MARKDEFF",
                'account_number' => "DE68210501700012345678"
            ),
            (float) 1234.56,
            "Ein ganz lange Test-Verwendungszweck der über 35 Zeichen lang sein soll um umbrochen zu werden"
        ));
        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "RZTIAT22263",
                'account_number' => "DE21700519950000007229"),
            (float) 321.9,
            "Ein ganz lange Test-Verwendungszweck der über 35 Zeichen lang sein soll um umbrochen zu werden"
        ));

        $this->assertSame(256+768+768+256, strlen($this->fixture->getFileContent()));
    }

    public function testChecksum()
    {
        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "MARKDEFF",
                'account_number' => "DE68210501700012345678"
            ),
            (float) 1234.56,
            "Test-Verwendungszweck"
        ));
        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "RZTIAT22263",
                'account_number' => "DE21700519950000007229"
            ),
            (float) 1234.56,
            "Test-Verwendungszweck"
        ));

        /* v1.3.0 incorrectly used the intval(sum) as checksum in field Z03,
         * yielding intval(1234.56+1234.56) = 2469
         * The correct calculation is to use the sum of intvals,
         * thus intval(1234.56)+intval(1234.56) = 2468
         */
        $content = $this->fixture->getFileContent();
        $this->assertSame(2468, (int)substr($content, 256+768+768+5, 15));
    }

    public function testGermanBLZ()
    {
        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "16050000",
                'account_number' => "DE21700519950000007229"
            ),
            (float) 1234.56,
            "Ein Test-Verwendungszweck"
        ));
        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "RZTIAT22263",
                'account_number' => "DE21700519950000007229"),
            (float) 321.9,
            "Ein ganz lange Test-Verwendungszweck der über 35 Zeichen lang sein soll um umbrochen zu werden"
        ));

        $this->assertSame(256+768+768+256, strlen($this->fixture->getFileContent()));
    }

    public function testSaveFileTrue()
    {
        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "16050000",
                'account_number' => "DE21700519950000007229"
            ),
            (float) 1234.56,
            "Ein Test-Verwendungszweck"
        ));

        $tmpfname = tempnam(sys_get_temp_dir(), "dtatest");
        if ($this->fixture->saveFile($tmpfname)) {
            $file_content = file_get_contents($tmpfname);
            unlink($tmpfname);
            $this->assertSame(256+768+256, strlen($file_content));
        } else {
            $this->assertTrue(false);
        }
    }

    public function testSaveFileFalse()
    {
        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "16050000",
                'account_number' => "DE21700519950000007229"
            ),
            (float) 1234.56,
            "Ein Test-Verwendungszweck"
        ));

        $tmpfname = "/root/nonexistantdirectory/dtatestfile";
        $this->assertFalse($this->fixture->saveFile($tmpfname));
    }

    public function testContent()
    {
        $this->assertTrue($this->fixture->addExchange(array(
		'name' => "Receivers Name",
		'bank_code' => "RZTIAT22263",
		'account_number' => "DE21700519950000007229"),
	    (float) 123.45,
	    "Test-Verwendungszweck"
        ));
        $this->assertTrue($this->fixture->addExchange(array(
		'name' => "Second Receivers Name",
		'bank_code' => "RZTIAT22263",
		'account_number' => "DE21700519950000007229"),
	    (float) 234.56,
	    "Test2"
        ));
        $dates = strftime("%d%m%y00%d%m%y", time());

        $expected = // 64 chars per line:
            '0256Q160500003503007767SENDERS NAME                             '.
            '                                                                '.
            '                                   '.$dates.'N0000000000    '.
            '                                                                '.
            '0768T16050000EUR350300776700000000000000   0000000000RZTIAT22263'.
            '                                                                '.
            '                                                                '.
            '               DE RECEIVERS NAME                                '.
            '                                                                '.
            '                                                                '.
            '                                    /DE21700519950000007229     '.
            '       EUR00000000000123450TEST-VERWENDUNGSZWECK                '.
            '                                                                '.
            '                                       00000000                 '.
            '        0013                                                    '.
            '          0                                                   00'.
            '0768T16050000EUR350300776700000000000000   0000000000RZTIAT22263'.
            '                                                                '.
            '                                                                '.
            '               DE SECOND RECEIVERS NAME                         '.
            '                                                                '.
            '                                                                '.
            '                                    /DE21700519950000007229     '.
            '       EUR00000000000234560TEST2                                '.
            '                                                                '.
            '                                       00000000                 '.
            '        0013                                                    '.
            '          0                                                   00'.
            '0256Z000000000000357000000000000002                             '.
            '                                                                '.
            '                                                                '.
            '                                                                ';
        $this->assertSame($expected, $this->fixture->getFileContent());
    }

    public function testGetMetaData1()
    {
        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "16050000",
                'account_number' => "DE21700519950000007229"
            ),
            (float) 1234.56,
            "Ein Test-Verwendungszweck"
        ));

        $meta = $this->fixture->getMetaData();
        $this->assertTrue($meta["sender_name"]      == "SENDERS NAME");
        $this->assertTrue($meta["sender_bank_code"] == "16050000");
        $this->assertTrue($meta["sender_account"]   == "3503007767");
        $this->assertTrue($meta["sum_amounts"]      == "1234.56");
        $this->assertTrue($meta["count"]            == "1");
        $this->assertTrue($meta["type"]             == "CREDIT");
        $this->assertTrue(strftime("%d%m%y", $meta["date"])
                            == strftime("%d%m%y", time()));
    }

    public function testGetMetaData2()
    {
        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "16050000",
                'account_number' => "DE21700519950000007229"
            ),
            (float) 1234.56,
            "Ein Test-Verwendungszweck"
        ));
        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "16050000",
                'account_number' => "DE21700519950000007229"
            ),
            (float) 1234.56,
            "Ein Test-Verwendungszweck"
        ));
        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "16050000",
                'account_number' => "DE21700519950000007229"
            ),
            (float) 1234.56,
            "Ein Test-Verwendungszweck"
        ));

        $meta = $this->fixture->getMetaData();
        $this->assertTrue($meta["sender_name"]      == "SENDERS NAME");
        $this->assertTrue($meta["sender_bank_code"] == "16050000");
        $this->assertTrue($meta["sender_account"]   == "3503007767");
        $this->assertTrue($meta["sum_amounts"]      == 3*1234.56);
        $this->assertTrue($meta["count"]            == "3");
        $this->assertTrue($meta["type"]             == "CREDIT");
        $this->assertTrue(strftime("%d%m%y", $meta["date"])
                            == strftime("%d%m%y", time()));
    }

    public function testIteratorEmpty()
    {
        foreach ($this->fixture as $key => $value) {
            $this->fail();
        }
    }

    public function testIteratorElements()
    {
        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "RZTIAT22263",
                'account_number' => "DE21700519950000007229"
            ),
            (float) 1234.56,
            "Test-Verwendungszweck1"
        ));
        $this->assertTrue($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "RZTIAT22263",
                'account_number' => "DE21700519950000007229"
            ),
            (float) 321.9,
            "Test-Verwendungszweck2"
        ));

        foreach ($this->fixture as $key => $value) {
            // from setUp()
            $this->assertSame(strtoupper("Senders Name"), $value['sender_name']);
            $this->assertSame("16050000", $value['sender_bank_code']);
            $this->assertSame("3503007767", $value['sender_account_number']);

            // same values in addExchange() above
            $this->assertSame(strtoupper("A Receivers Name"), $value['receiver_name']);
            $this->assertSame("RZTIAT22263", $value['receiver_bank_code']);
            $this->assertSame("DE21700519950000007229", $value['receiver_account_number']);
            $this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY,
                $value['purposes']);

            // different values in addExchange() above
            if ($key === 0) {
                $this->assertEquals(123456, $value['amount']);
                $this->assertTrue($value['purposes'][0] ===
                    strtoupper("Test-Verwendungszweck1"));
            } elseif ($key === 1) {
                $this->assertEquals(32190, $value['amount']);
                $this->assertTrue($value['purposes'][0] ===
                    strtoupper("Test-Verwendungszweck2"));
            } else {
                $this->fail();
            }
        }
    }

    public function testParserBasic()
    {
        $dates = strftime("%d%m%y00%d%m%y", time());
        $teststring = // same as in testContent()
            '0256Q160500003503007767SENDERS NAME                             '.
            '                                                                '.
            '                                   '.$dates.'N0000000000    '.
            '                                                                '.
            '0768T16050000EUR350300776700000000000000   0000000000RZTIAT22263'.
            '                                                                '.
            '                                                                '.
            '               DE RECEIVERS NAME                                '.
            '                                                                '.
            '                                                                '.
            '                                    /DE21700519950000007229     '.
            '       EUR00000000000123450TEST-VERWENDUNGSZWECK                '.
            '                                                                '.
            '                                       00000000                 '.
            '        0013                                                    '.
            '          0                                                   00'.
            '0768T16050000EUR350300776700000000000000   0000000000RZTIAT22263'.
            '                                                                '.
            '                                                                '.
            '               DE SECOND RECEIVERS NAME                         '.
            '                                                                '.
            '                                                                '.
            '                                    /DE21700519950000007229     '.
            '       EUR00000000000234560TEST2                                '.
            '                                                                '.
            '                                       00000000                 '.
            '        0013                                                    '.
            '          0                                                   00'.
            '0256Z000000000000357000000000000002                             '.
            '                                                                '.
            '                                                                '.
            '                                                                ';
        $dtazv = new DTAZV($teststring);
        $meta = $dtazv->getMetaData();
        $this->assertEquals("SENDERS NAME", $meta["sender_name"]);
        $this->assertEquals("16050000", $meta["sender_bank_code"]);
        $this->assertEquals("3503007767", $meta["sender_account"]);
        $this->assertEquals("358.01", $meta["sum_amounts"]);
        $this->assertEquals("2", $meta["count"]);
        $this->assertEquals("CREDIT", $meta["type"]);
        $this->assertEquals(strftime("%d%m%y", time()),
            strftime("%d%m%y", $meta["date"]));
    }

    public function testParserWrongCheckCount()
    {
        $dates = strftime("%d%m%y00%d%m%y", time());
        $teststring = // same as in testContent() but Z record indicates 3 T records
            '0256Q160500003503007767SENDERS NAME                             '.
            '                                                                '.
            '                                   '.$dates.'N0000000000    '.
            '                                                                '.
            '0768T16050000EUR350300776700000000000000   0000000000RZTIAT22263'.
            '                                                                '.
            '                                                                '.
            '               DE RECEIVERS NAME                                '.
            '                                                                '.
            '                                                                '.
            '                                    /DE21700519950000007229     '.
            '       EUR00000000000123450TEST-VERWENDUNGSZWECK                '.
            '                                                                '.
            '                                       00000000                 '.
            '        0013                                                    '.
            '          0                                                   00'.
            '0768T16050000EUR350300776700000000000000   0000000000RZTIAT22263'.
            '                                                                '.
            '                                                                '.
            '               DE SECOND RECEIVERS NAME                         '.
            '                                                                '.
            '                                                                '.
            '                                    /DE21700519950000007229     '.
            '       EUR00000000000234560TEST2                                '.
            '                                                                '.
            '                                       00000000                 '.
            '        0013                                                    '.
            '          0                                                   00'.
            '0256Z000000000000357000000000000003                             '.
            '                                                                '.
            '                                                                '.
            '                                                                ';
        $dtazv = new DTAZV($teststring);
        $this->assertSame(2, $dtazv->count());
        $errors = $dtazv->getParsingErrors();
        $this->assertEquals('Payment_DTA_ChecksumException',
            get_class(array_pop($errors)));
    }

    public function testParserWrongCheckAmounts()
    {
        $dates = strftime("%d%m%y00%d%m%y", time());
        $teststring = // same as in testContent() but Z record has wrong amount sum
            '0256Q160500003503007767SENDERS NAME                             '.
            '                                                                '.
            '                                   '.$dates.'N0000000000    '.
            '                                                                '.
            '0768T16050000EUR350300776700000000000000   0000000000RZTIAT22263'.
            '                                                                '.
            '                                                                '.
            '               DE RECEIVERS NAME                                '.
            '                                                                '.
            '                                                                '.
            '                                    /DE21700519950000007229     '.
            '       EUR00000000000123450TEST-VERWENDUNGSZWECK                '.
            '                                                                '.
            '                                       00000000                 '.
            '        0013                                                    '.
            '          0                                                   00'.
            '0768T16050000EUR350300776700000000000000   0000000000RZTIAT22263'.
            '                                                                '.
            '                                                                '.
            '               DE SECOND RECEIVERS NAME                         '.
            '                                                                '.
            '                                                                '.
            '                                    /DE21700519950000007229     '.
            '       EUR00000000000234560TEST2                                '.
            '                                                                '.
            '                                       00000000                 '.
            '        0013                                                    '.
            '          0                                                   00'.
            '0256Z000000000000358000000000000002                             '.
            '                                                                '.
            '                                                                '.
            '                                                                ';
        $dtazv = new DTAZV($teststring);
        $this->assertSame(2, $dtazv->count());
        $errors = $dtazv->getParsingErrors();
        $this->assertEquals('Payment_DTA_ChecksumException',
            get_class(array_pop($errors)));
    }

    public function testParserWrongLength()
    {
        $dates = strftime("%d%m%y00%d%m%y", time());
        $teststring = // same as in testContent() but 1 char longer
            '0256Q160500003503007767SENDERS NAME                             '.
            '                                                                '.
            '                                   '.$dates.'N0000000000    '.
            '                                                                '.
            '0768T16050000EUR350300776700000000000000   0000000000RZTIAT22263'.
            '                                                                '.
            '                                                                '.
            '               DE RECEIVERS NAME                                '.
            '                                                                '.
            '                                                                '.
            '                                    /DE21700519950000007229     '.
            '       EUR00000000000123450TEST-VERWENDUNGSZWECK                '.
            '                                                                '.
            '                                       00000000                 '.
            '        0013                                                    '.
            '          0                                                   00'.
            '0768T16050000EUR350300776700000000000000   0000000000RZTIAT22263'.
            '                                                                '.
            '                                                                '.
            '               DE SECOND RECEIVERS NAME                         '.
            '                                                                '.
            '                                                                '.
            '                                    /DE21700519950000007229     '.
            '       EUR00000000000234560TEST2                                '.
            '                                                                '.
            '                                       00000000                 '.
            '        0013                                                    '.
            '          0                                                   00'.
            '0256Z000000000000357000000000000002                             '.
            '                                                                '.
            '                                                                '.
            '                                                                 ';
        $dtazv = new DTAZV($teststring);
        $this->assertSame(0, $dtazv->count());
        $errors = $dtazv->getParsingErrors();
        $this->assertEquals('Payment_DTA_FatalParseException',
            get_class(array_pop($errors)));
    }

    public function testParserInvalidQRecord()
    {
        $dates = strftime("%d%m%y00%d%m%y", time());
        $teststring = // same as in testContent() but error in Q record
            '0256Q160500003503007767SENDERS NAME                             '.
            '                                                                '.
            '                                   '.$dates.'N0000000001    '.
            '                                                                '.
            '0768T16050000EUR350300776700000000000000   0000000000RZTIAT22263'.
            '                                                                '.
            '                                                                '.
            '               DE RECEIVERS NAME                                '.
            '                                                                '.
            '                                                                '.
            '                                    /DE21700519950000007229     '.
            '       EUR00000000000123450TEST-VERWENDUNGSZWECK                '.
            '                                                                '.
            '                                       00000000                 '.
            '        0013                                                    '.
            '          0                                                   00'.
            '0768T16050000EUR350300776700000000000000   0000000000RZTIAT22263'.
            '                                                                '.
            '                                                                '.
            '               DE SECOND RECEIVERS NAME                         '.
            '                                                                '.
            '                                                                '.
            '                                    /DE21700519950000007229     '.
            '       EUR00000000000234560TEST2                                '.
            '                                                                '.
            '                                       00000000                 '.
            '        0013                                                    '.
            '          0                                                   00'.
            '0256Z000000000000357000000000000002                             '.
            '                                                                '.
            '                                                                '.
            '                                                                ';
        $dtazv = new DTAZV($teststring);
        $this->assertSame(0, $dtazv->count());
        $errors = $dtazv->getParsingErrors();
        $this->assertEquals('Payment_DTA_FatalParseException',
            get_class(array_pop($errors)));
    }

    public function testParserSkipInvalidTRecord()
    {
        $dates = strftime("%d%m%y00%d%m%y", time());
        $teststring = // same as in testContent() but error in length in 1st T record
            '0256Q160500003503007767SENDERS NAME                             '.
            '                                                                '.
            '                                   '.$dates.'N0000000000    '.
            '                                                                '.
            '0769T16050000EUR350300776700000000000000   0000000000RZTIAT22263'.
            '                                                                '.
            '                                                                '.
            '               DE RECEIVERS NAME                                '.
            '                                                                '.
            '                                                                '.
            '                                    /DE21700519950000007229     '.
            '       EUR00000000000123450TEST-VERWENDUNGSZWECK                '.
            '                                                                '.
            '                                       00000000                 '.
            '        0013                                                    '.
            '          0                                                   00'.
            '0768T16050000EUR350300776700000000000000   0000000000RZTIAT22263'.
            '                                                                '.
            '                                                                '.
            '               DE SECOND RECEIVERS NAME                         '.
            '                                                                '.
            '                                                                '.
            '                                    /DE21700519950000007229     '.
            '       EUR00000000000234560TEST2                                '.
            '                                                                '.
            '                                       00000000                 '.
            '        0013                                                    '.
            '          0                                                   00'.
            '0256Z000000000000357000000000000002                             '.
            '                                                                '.
            '                                                                '.
            '                                                                ';
        $dtazv = new DTAZV($teststring);
        $this->assertSame(1, $dtazv->count());
        $errors = $dtazv->getParsingErrors();

        // first error for skipped C record, second error for checksum
        $this->assertEquals('Payment_DTA_ParseException',
            get_class($errors[0]));
        $this->assertEquals('Payment_DTA_ChecksumException',
            get_class($errors[1]));
    }

    public function testParserInvalidZRecord()
    {
        $dates = strftime("%d%m%y00%d%m%y", time());
        $teststring = // same as in testContent() but text in Z record padding
            '0256Q160500003503007767SENDERS NAME                             '.
            '                                                                '.
            '                                   '.$dates.'N0000000000    '.
            '                                                                '.
            '0768T16050000EUR350300776700000000000000   0000000000RZTIAT22263'.
            '                                                                '.
            '                                                                '.
            '               DE RECEIVERS NAME                                '.
            '                                                                '.
            '                                                                '.
            '                                    /DE21700519950000007229     '.
            '       EUR00000000000123450TEST-VERWENDUNGSZWECK                '.
            '                                                                '.
            '                                       00000000                 '.
            '        0013                                                    '.
            '          0                                                   00'.
            '0768T16050000EUR350300776700000000000000   0000000000RZTIAT22263'.
            '                                                                '.
            '                                                                '.
            '               DE SECOND RECEIVERS NAME                         '.
            '                                                                '.
            '                                                                '.
            '                                    /DE21700519950000007229     '.
            '       EUR00000000000234560TEST2                                '.
            '                                                                '.
            '                                       00000000                 '.
            '        0013                                                    '.
            '          0                                                   00'.
            '0256Z000000000000357000000000000002                             '.
            '           blubb                                                '.
            '                                                                '.
            '                                                                ';
        $dtazv = new DTAZV($teststring);
        $this->assertSame(2, $dtazv->count());
        $errors = $dtazv->getParsingErrors();
        // first error for skipped C record, second error for checksum
        $this->assertEquals('Payment_DTA_ParseException',
            get_class($errors[0]));
    }

    public function testParserTimestamp()
    {
        $date_creation  = '300810'; // 2010-08-30
        $date_execution = '310810'; // 2010-08-31
        $dates = $date_creation.'00'.$date_execution;
        $teststring = // same as in testContent()
            '0256Q160500003503007767SENDERS NAME                             '.
            '                                                                '.
            '                                   '.$dates.'N0000000000    '.
            '                                                                '.
            '0768T16050000EUR350300776700000000000000   0000000000RZTIAT22263'.
            '                                                                '.
            '                                                                '.
            '               DE RECEIVERS NAME                                '.
            '                                                                '.
            '                                                                '.
            '                                    /DE21700519950000007229     '.
            '       EUR00000000000123450TEST-VERWENDUNGSZWECK                '.
            '                                                                '.
            '                                       00000000                 '.
            '        0013                                                    '.
            '          0                                                   00'.
            '0768T16050000EUR350300776700000000000000   0000000000RZTIAT22263'.
            '                                                                '.
            '                                                                '.
            '               DE SECOND RECEIVERS NAME                         '.
            '                                                                '.
            '                                                                '.
            '                                    /DE21700519950000007229     '.
            '       EUR00000000000234560TEST2                                '.
            '                                                                '.
            '                                       00000000                 '.
            '        0013                                                    '.
            '          0                                                   00'.
            '0256Z000000000000357000000000000002                             '.
            '                                                                '.
            '                                                                '.
            '                                                                ';
        $dtazv = new DTAZV($teststring);
        $meta = $dtazv->getMetaData();
        $this->assertEquals("2", $meta["count"]);
        $this->assertEquals("CREDIT", $meta["type"]);
        $this->assertEquals($date_creation, strftime("%d%m%y", $meta["date"]));
    }
}
