<?php

/**
 * Example how to use the DTA/DTAZV parser.
 *
 * $dtafilestring is for easy testing. Rename/Remove it to upload own files.
 *
 * PHP version 5
 * 
 * @category  Payment
 * @package   Payment_DTA
 * @author    Martin Schütte <info@mschuette.name>
 * @copyright 2010 Martin Schütte
 * @license   http://www.debian.org/misc/bsd.license  BSD License (3 Clause)
 * @version   SVN: $Id$
 * @link      http://pear.php.net/package/Payment_DTA
 */

require_once "Payment/DTA.php";
require_once "Payment/DTAZV.php";

// correct file
$dtafilestring =
    '0128AGK1605000000000000SENDERS NAME               300110    3503'.
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

// incorrect file to check error handling/reporting
$dtafilestring =
    '0128AGK1605000000000000SENDERS NAME               300110    3503'.
    '0077670000000000                                               1'.
    '0188C16050000333344440013579000000000000000051000 00000000000160'.
    '50000350300776700000123456   FRANZ MUELLER                      '.
    'SENDERS NAME               TEST-VERWENDUNGSZWECK      1  00     '.
    '                                                                '.
    '0187C16050000333344440013579000000000000000051000 00000000000160'.
    '50000350300776700000032190   FRANZ MUELLER                      '.
    'SENDERS NAME               TEST-VERWENDUNGSZWECK      1  00     '.
    '                                                                '.
    '0128E     000000200000000000000000000002715800000000000066668888'.
    '0000000155646                                                   ';

// correct DTAZV file
$dtafilestring =
    '0256Q160500003503007767SENDERS NAME                             '.
    '                                                                '.
    '                                   05071000050710N0000000000    '.
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

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>PEAR Payment_DTA - Example Reader</title>
<style type="text/css">
    table,tbody,tr,td {
        padding: 0;
        margin: 0;
    }
    td.value {
        padding: 4px;
        background-color: yellow;
        font-size: smaller;
        font-family: monospace;
    }
    .status {
        font-size: x-small;
    }
    .error {
        color: red;
    }
    li.transaction {
        padding: 1em;
    }
</style>
</head>
<body>
<h1>DTA-Leser</h1>

<?php
if (empty($dtafilestring) && (empty($_FILES) || empty($_FILES["userfile"]))) {
    ?>
    <form enctype="multipart/form-data" action="<?php
        print 'http://'.$_SERVER["HTTP_HOST"].'/'.$_SERVER["SCRIPT_NAME"];
        ?>" method="POST">
        <input type="hidden" name="MAX_FILE_SIZE" value="10000" />
        Send this file: <input name="userfile" type="file" />
        <input type="submit" value="Send File" />
    </form>
    <?php
} else {
    $dtafilestring = $dtafilestring ? $dtafilestring
        : file_get_contents($_FILES["userfile"]["tmp_name"]);
    if (!$dtafilestring) {
        print "<p class='status'>Fehler: Kann DTA-Datei nicht lesen...</p>".
            "</body></html>";
        die();
    }

    // determine if DTA or DTAZV
    if ("0256Q" == substr($dtafilestring, 0, 5)) {
        print "<p class='status'>Lese DTAZV-Datei ...</p>";
        $dta = new DTAZV($dtafilestring);
    } else {
        // assume DTA, errors are catched later
        print "<p class='status'>Lese DTA-Datei ...</p>";
        $dta = new DTA($dtafilestring);
    }

    $errors = $dta->getParsingErrors();
    if (count($errors)) {
        print "<h2>Fehler</h2>";
        print "<ol>";
        foreach ($errors as $e) {
            if (get_class($e) == "Payment_DTA_FatalParseException") {
                print "<li class='status error'>Schwerer Fehler: ".
                    $e->getMessage()."</li></ol></body></html>";
                die();
            } elseif (get_class($e) == "Payment_DTA_ParseException") {
                print "<li class='status error'>Fehler: ".
                    $e->getMessage()."</li>";
            } elseif (get_class($e) == "Payment_DTA_ChecksumException") {
                print "<li class='status error'>Datei enthält falsche Prüfsumme: ".
                    $e->getMessage()."</li>";
            } else {
                print "<li class='status error'>Unerwarteter Fehler: ".
                    $e->getMessage()."</li>";
            }
        }
        print "</ol>";
    }
    $meta = $dta->getMetaData();
    ?>

    <h2>Begleitdaten:</h2>
    <table>
    <tr>
    <td class="label">Erstellungsdatum:</td>
    <td class="value"><?php print strftime("%d.%m.%y", $meta["date"]); ?></td>
    </tr>

    <tr>
    <td class="label">Ausführungsdatum:</td>
    <td class="value"><?php print strftime("%d.%m.%y", $meta["date"]); ?></td>
    </tr>

    <tr> <td colspan="2">&nbsp;</td> </tr>

    <tr>
    <td class="label">Anzahl der Überweisungen:</td>
    <td class="value"><?php print $meta["count"]; ?></td>
    </tr>

    <tr>
    <td class="label">Summe der Beträge in EUR:</td>
    <td class="value"><?php print $meta["sum_amounts"]; ?></td>
    </tr>

    <tr>
    <td class="label">Kontrollsumme Kontonummern:</td>
    <td class="value"><?php 
        // this value is only set for DTA, not for DTAZV
        print isset($meta["sum_accounts"]) ? $meta["sum_accounts"] : "n/a";
        ?></td>
    </tr>

    <tr>
    <td class="label">Kontrollsumme Bankleitzahlen:</td>
    <td class="value"><?php
        // this value is only set for DTA, not for DTAZV
        print isset($meta["sum_bankcodes"]) ? $meta["sum_bankcodes"] : "n/a";
        ?></td>
    </tr>

    <tr> <td colspan="2">&nbsp;</td> </tr>

    <tr>
    <td class="label">Auftraggeber:</td>
    <td class="value">Michael Mustermann</td>
    </tr>

    <tr>
    <td class="label">Beauftragtes Bankinstitut:</td>
    <td class="value">Kreissparkasse Musterhausen</td>
    </tr>

    <tr>
    <td class="label">Bankleitzahl:</td>
    <td class="value"><?php print $meta["sender_bank_code"]; ?></td>
    </tr>

    <tr>
    <td class="label">Kontonummer:</td>
    <td class="value"><?php print $meta["sender_account"]; ?></td>
    </tr>
    </table>

    <h2>Überweisungen:</h2>
    <ol>
    <?php
    foreach ($dta as $transaction) {
        ?>
        <li class="transaction">
            <table>
                <tr>
                    <td class="label">Zahlungssender:</td>
                    <td class="value"><?php
                        print join(
                            " ", array($transaction["sender_name"],
                            $transaction["sender_additional_name"])
                        ); ?>
                    </td>
                </tr>
                <tr>
                    <td class="label">Sender-Konto:</td>
                    <td class="value"><?php
                        print $transaction["sender_account_number"]; ?>
                    </td>
                </tr>
                <tr>
                    <td class="label">Sender-BLZ:</td>
                    <td class="value"><?php
                        print $transaction["sender_bank_code"]; ?>
                    </td>
                </tr>
                <tr>
                    <td class="label">Zahlungsempfänger:</td>
                    <td class="value"><?php
                        print join(
                            " ", array($transaction["receiver_name"],
                            $transaction["receiver_additional_name"])
                        ); ?>
                    </td>
                </tr>
                <tr>
                    <td class="label">Empfänger-Konto:</td>
                    <td class="value"><?php
                        print $transaction["receiver_account_number"]; ?>
                    </td>
                </tr>
                <tr>
                    <td class="label">Empfänger-BLZ:</td>
                    <td class="value"><?php
                        print $transaction["receiver_bank_code"]; ?>
                    </td>
                </tr>
                <tr>
                    <td class="label">Betrag:</td>
                    <td class="value"><?php
                        print number_format($transaction["amount"], 2, ',', '.'); ?>
                    </td>
                </tr>
                <tr>
                    <td class="label">Verwendungszweck:</td>
                    <td class="value"><?php
                        print join("<br/>", $transaction["purposes"]); ?>
                    </td>
                </tr>
            </table>
        </li>
    <?php
    }
}
?>
</ol>
</body>
</html>