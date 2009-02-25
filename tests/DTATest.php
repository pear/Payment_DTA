<?php
require_once '../DTA.php';
require_once 'PHPUnit/Framework.php';

class DTATest extends PHPUnit_Framework_TestCase
{
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
                "Ein ganz lange Test-Verwendungszweck der über 35 Zeichen lang sein soll um umbrochen zu werden"
            );
            $this->fixture->addExchange(array(
                    'name' => "A Receivers Name",
                    'bank_code' => "16050000",
                    'account_number' => "3503007767"),
                (float) 321.9,
                "Ein ganz lange Test-Verwendungszweck der über 35 Zeichen lang sein soll um umbrochen zu werden"
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
            (float) (PHP_INT_MAX+1),
            "Ein Test-Verwendungszweck"
        );
        $this->assertEquals(0, $this->fixture->count());
    }

    public function testAmountSumOverflow()
    {
        /* add enough transfers so that the sum of
         * amounts will cause an integer overflow */
        $c = 10;
        for($i = 0; $i < $c; $i++) {
            $this->fixture->addExchange(array(
                    'name' => "A Receivers Name",
                    'bank_code' => "16050000",
                    'account_number' => "3503007767"
                ),
                /* +1 to prevent negative rounding errors or an exact fit: */
                (float) (PHP_INT_MAX/100/$c)+1,
                "Ein Test-Verwendungszweck"
            );
        }
        $this->assertEquals($c-1, $this->fixture->count());
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

    public function testAdditionalSenderAndRecvName()
    {
        # used to get coverage for additional extension records
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
                'additional_name' => "some very long additional receiver name"
            ),
            (float) 1234.56,
            "Kurzer Test-Verwendungszweck"
        ));

        $this->assertEquals(640, strlen($this->fixture->getFileContent()));
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

    public function testFileLengthNormal()
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

        $this->assertEquals(768, strlen($this->fixture->getFileContent()));
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

}
