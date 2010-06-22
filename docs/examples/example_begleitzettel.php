<?php

/**
 * Example how to use the meta data to create a "Begleitzettel",
 * a document to accompany and summarize a DTA disk.
 *
 * PHP version 5
 *
 * @category  Payment
 * @package   Payment_DTA
 * @author    Martin Schütte <info@mschuette.name>
 * @copyright 2009 Martin Schütte
 * @license   http://www.debian.org/misc/bsd.license  BSD License (3 Clause)
 * @version   SVN: $Id$
 * @link      http://pear.php.net/package/Payment_DTA
 */

require_once 'Payment_DTA/DTA.php';
date_default_timezone_set('Europe/Berlin');

$dtaus = new DTA(DTA_CREDIT);
$DTA_test_account = array(
    'name' => "Michael Mustermann",
    'bank_code' => "16050000",
    'account_number' => "3503007767",
);
$dtaus->setAccountFileSender($DTA_test_account);

$dtaus->addExchange(
    array(
        'name' => "Emil Empfänger",
        'bank_code' => "16050000",
        'account_number' => "3503007767"
    ),
    (float) 123.45,
    "Ein Verwendungszweck"
);
/*
 * some more transactions ...
 *
 * $dtaus->saveFile($filename)
 *
 */

$meta = $dtaus->getMetaData();

?>
<!doctype html>
<head>
    <title>Example DTA Begleitzettel</title>
</head>
<body>
<h1>Datenträger-Begleitzettel</h1>

<table>
<tr>
<td>Erstellungsdatum:</td>
<td><?php print strftime("%d.%m.%y", $meta["date"]); ?></td>
</tr>

<tr>
<td>Ausführungsdatum:</td>
<td><?php print strftime("%d.%m.%y", $meta["date"]); ?></td>
</tr>

<tr> <td colspan="2">&nbsp;</td> </tr>

<tr>
<td>Anzahl der Überweisungen:</td>
<td><?php print $meta["count"]; ?></td>
</tr>

<tr>
<td>Summe der Beträge in EUR:</td>
<td><?php print $meta["sum_amounts"]; ?></td>
</tr>

<tr>
<td>Kontrollsumme Kontonummern:</td>
<td><?php print $meta["sum_accounts"]; ?></td>
</tr>

<tr>
<td>Kontrollsumme Bankleitzahlen:</td>
<td><?php print $meta["sum_bankcodes"]; ?></td>
</tr>

<tr> <td colspan="2">&nbsp;</td> </tr>

<tr>
<td>Auftraggeber:</td>
<td>Michael Mustermann</td>
</tr>

<tr>
<td>Beauftragtes Bankinstitut:</td>
<td>Kreissparkasse Musterhausen</td>
</tr>

<tr>
<td>Bankleitzahl:</td>
<td><?php print $meta["sender_bank_code"]; ?></td>
</tr>

<tr>
<td>Kontonummer:</td>
<td><?php print $meta["sender_account"]; ?></td>
</tr>

</table>

</body>