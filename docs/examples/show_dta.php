<?php

/* Small example how to use the DTA parser.
 *
 * $dtafilestring is for easy testing. Rename/Remove it to upload own files.
 */

//require_once("Payment/DTA.php");
require_once("../../DTA.php");

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

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
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
    p.status {
        font-size: x-small;
        margin: 0;
    }
    ol li {
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
    if(!$dtafilestring) {
        print "<p class='status'>Fehler: Kann DTA-Datei nicht lesen...</p></body></html>";
        die();
    }

    print "<p class='status'>Lese DTA-Datei ...</p>";

    try {
        $dta = new DTA($dtafilestring);
    } catch (Payment_DTA_FatalParseException $e) {
        print "<p class='status'>Schwerer Fehler: $e</p></body></html>";
        die();
    } catch (Payment_DTA_ParseException $e) {
        print "<p class='status'>Fehler: $e</p>";
    } catch (Payment_DTA_ChecksumException $e) {
        print "<p class='status'>Datei enthält falsche Prüfsumme: $e</p>";
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
<td class="value"><?php print $meta["sum_accounts"]; ?></td>
</tr>

<tr>
<td class="label">Kontrollsumme Bankleitzahlen:</td>
<td class="value"><?php print $meta["sum_bankcodes"]; ?></td>
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
    <li>
        <table>
            <tr>
                <td class="label">Zahlungssender:</td>
                <td class="value"><?php print join(" ",
                    array($transaction["sender_name"],
                        $transaction["sender_additional_name"]));
                ?></td>
            </tr>
            <tr>
                <td class="label">Sender-Konto:</td>
                <td class="value"><?php print $transaction["sender_account_number"]; ?></td>
            </tr>
            <tr>
                <td class="label">Sender-BLZ:</td>
                <td class="value"><?php print $transaction["sender_bank_code"]; ?></td>
            </tr>
            <tr>
                <td class="label">Zahlungsempfänger:</td>
                <td class="value"><?php print join(" ",
                    array($transaction["receiver_name"],
                        $transaction["receiver_additional_name"]));
                ?></td>
            </tr>
            <tr>
                <td class="label">Empfänger-Konto:</td>
                <td class="value"><?php print $transaction["receiver_account_number"]; ?></td>
            </tr>
            <tr>
                <td class="label">Empfänger-BLZ:</td>
                <td class="value"><?php print $transaction["receiver_bank_code"]; ?></td>
            </tr>
            <tr>
                <td class="label">Betrag:</td>
                <td class="value"><?php print number_format($transaction["amount"], 2, ',', '.'); ?></td>
            </tr>
            <tr>
                <td class="label">Verwendungszweck:</td>
                <td class="value"><?php print join("<br/>", $transaction["purposes"]); ?></td>
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
