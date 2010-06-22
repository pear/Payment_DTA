<?php

/**
 * Payment_DTA example:
 * Example how to offer a DTA file for download.
 *
 * PHP version 4 and 5
 *
 * Copyright (c) 2008-2010 Martin Schütte
 * All rights reserved.
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

/**
* Create a simple DTA credit file, see example_credit.php for detailed comments.
*/

$dta_file = new DTA(DTA_CREDIT);
$dta_file->setAccountFileSender(
    array(
        "name"           => "Michael Mustermann",
        "bank_code"      => 11112222,
        "account_number" => 654321
    )
);
$dta_file->addExchange(
    array(
        "name"           => "Franz Mueller",    // Name of account owner.
        "bank_code"      => 33334444,           // Bank code.
        "account_number" => 13579000,           // Account number.
    ),
    12.01,                                      // Amount of money.
    array(                                      // Description of the transaction ("Verwendungszweck").
        "Credit Nr. 01234",
        "Information"
    )
);

/**
* Assuming the program is executed on a web server and
* we want to offer the new DTA file for download.
*
* Use the header() function to set the required HTTP headers.
* These will tell the browser there is a file to download with
* a given filename and type; thus the browser will not display
* the file's content but show a download dialog.
*
* Note that there must not be any output before calling header().
* This example will generate filenames like "credit_2010-06-22.dta".
*
*/

$filename = "credit_".date("Y-m-d").".dta";

header("Content-disposition: attachment;filename=\"$filename\"");
header("Content-Type: text/plain");

echo $dta_file->getFileContent();
