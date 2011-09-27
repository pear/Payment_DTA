<?php

/**
 * Payment_DTA example:
 * Example of creating a DTAZV credit file with one transaction.
 *
 * PHP version 4 and 5
 *
 * @category  Payment
 * @package   Payment_DTA
 * @author    Martin Schütte <info@mschuette.name>
 * @copyright 2010 Martin Schütte
 * @license   http://www.debian.org/misc/bsd.license  BSD License (3 Clause)
 * @version   SVN: $Id$
 * @link      http://pear.php.net/package/Payment_DTA
 */

require_once "Payment/DTAZV.php";


/**
* Initialize new DTAZV file.
* In this example the file contains credits.
* This means that in an exchange the sender is the person who pays money
* to the receiver.
*/

$dtazv_file = new DTAZV();

/**
* Set file sender. This is also the default sender for transactions.
*/

$dtazv_file->setAccountFileSender(
    array(
        "name"           => "Michael Mustermann",
        "bank_code"      => 11112222,
        "account_number" => 654321
    )
);

/**
* Add transaction.
*/

$dtazv_file->addExchange(
    array(
        "name"           => "Franz Mueller",            // Name of account owner.
        "bank_code"      => "COBADEFF374",              // Bank code / BIC.
        "account_number" => "DE89370400440532013000",   // Account number / IBAN.
    ),
    12.01,                                      // Amount of money.
    array(                                      // Description of the transaction ("Verwendungszweck").
        "Credit Nr. 01234",
        "Information"
    )
);

/**
* Output DTAZV-File.
*/

echo $dtazv_file->getFileContent();

/**
* Write DTAZV-File.
*/

// $dta_file->saveFile("DTAZV0");

?>