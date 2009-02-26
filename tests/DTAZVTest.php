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
        $DTAZV_asta_account = array(
            'name' => "Senders Name",
            'additional_name' => '',
            'bank_code' => "16050000",
            'account_number' => "3503007767",
        );
        $this->fixture->setAccountFileSender($DTAZV_asta_account);
    }

    public function testInstantiate()
    {
        $this->assertEquals("DTAZV", get_class($this->fixture));
    }

    public function testInstantiateShortBankCode()
    {
        $dtaus = new DTAZV();
        $DTAZV_asta_account = array(
             'name' => "Senders Name",
             'additional_name' => '',
             'bank_code' => "16050",
             'account_number' => "3503007767",
         );

        $this->assertTrue($dtaus->setAccountFileSender($DTAZV_asta_account));
    }

    public function testInstantiateLongBankCode()
    {
        $dtaus = new DTAZV();
        $DTAZV_asta_account = array(
             'name' => "Senders Name",
             'additional_name' => '',
             'bank_code' => "160500001",
             'account_number' => "3503007767",
         );

        $this->assertFalse($dtaus->setAccountFileSender($DTAZV_asta_account));
    }

    public function testInstantiateLongAccount()
    {
        $dtaus = new DTAZV();
        $DTAZV_asta_account = array(
             'name' => "Senders Name",
             'additional_name' => '',
             'bank_code' => "16050000",
             'account_number' => "35030077671",
         );

        $this->assertFalse($dtaus->setAccountFileSender($DTAZV_asta_account));
    }

    public function testInstantiateLetterInAccount()
    {
        $dtaus = new DTAZV();
        $DTAZV_asta_account = array(
             'name' => "Senders Name",
             'additional_name' => '',
             'bank_code' => "16050000",
             'account_number' => "3503007A67",
         );

        $this->assertFalse($dtaus->setAccountFileSender($DTAZV_asta_account));
    }

    public function testCountEmpty()
    {
        $this->assertEquals(0, $this->fixture->count());
        $this->assertEquals(256+256, strlen($this->fixture->getFileContent()));
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

        $this->assertEquals(2, $this->fixture->count());
        $this->assertEquals(256+768+768+256, strlen($this->fixture->getFileContent()));
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

        $this->assertEquals(1, $this->fixture->count());
        $this->assertEquals(256+768+256, strlen($this->fixture->getFileContent()));
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

        $this->assertEquals(1, $this->fixture->count());
    }

    public function testDTAZVMaxAmountFail()
    {
        $this->assertFalse($this->fixture->addExchange(array(
                'name' => "A Receivers Name",
                'bank_code' => "MARKDEF",
                'account_number' => "DE68210501700012345678"
            ),
            12500.01,
            "Ein ganz lange Test-Verwendungszweck der über 35 Zeichen lang sein soll um umbrochen zu werden"
        ));
        $this->assertEquals(0, $this->fixture->count());
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

        $this->assertEquals(1, $this->fixture->count());
        $this->assertEquals(256+768+256, strlen($this->fixture->getFileContent()));
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

        $this->assertEquals(1, $this->fixture->count());
        $this->assertEquals(256+768+256, strlen($this->fixture->getFileContent()));
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
        $this->assertEquals("AE AE AOEOUE SS", $result);
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

        $this->assertEquals(256+768+768+256, strlen($this->fixture->getFileContent()));
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

        $this->assertEquals(256+768+768+256, strlen($this->fixture->getFileContent()));
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
            $this->assertEquals(256+768+256, strlen($file_content));
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
        $this->assertTrue(strftime("%d%m%y", $meta["date"])
                            == strftime("%d%m%y", time()));
    }
}
