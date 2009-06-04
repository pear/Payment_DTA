<?php
/**
 * DTAZV
 *
 * DTAZV is a class that provides functions to create DTAZV files used
 * in Germany to exchange informations about european money transactions
 * with banks or online banking programs.
 *
 * Disclaimer: this only implements a subset of DTAZV as used for a
 * "EU-Standardüberweisung" and is only tested against locally used
 * accounting software.
 * If you use this class commercially and/or for large transfer amounts
 * then you might have to implement additional record types (V or W)
 * and fill additional data fields for notification requirements.
 *
 * Specification used:
 * http://www.bundesbank.de/download/meldewesen/aussenwirtschaft/vordrucke/pdf/dtazv_2007_kunde_bank.pdf
 * english version:
 * http://www.bundesbank.de/download/meldewesen/aussenwirtschaft/vordrucke/pdf/dtazv_2007_financial_inst_bbk.pdf
 *
 * PHP versions 4 and 5
 *
 * This LICENSE is in the BSD license style.
 *
 * Copyright (c) 2008 Martin Schütte
 * derived from class DTA
 * Copyright (c) 2003-2005 Hermann Stainer, Web-Gear
 * http://www.web-gear.com/
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 * Redistributions of source code must retain the above copyright
 * notice, this list of conditions and the following disclaimer.
 *
 * Redistributions in binary form must reproduce the above copyright
 * notice, this list of conditions and the following disclaimer in the
 * documentation and/or other materials provided with the distribution.
 *
 * Neither the name of Hermann Stainer, Web-Gear nor the names of his
 * contributors may be used to endorse or promote products derived from
 * this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED.  IN NO EVENT SHALL THE
 * REGENTS OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS
 * OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED
 * AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY
 * WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @category  Payment
 * @package   Payment_DTA
 * @author    Martin Schütte <info@mschuette.name>
 * @author    Hermann Stainer <hs@web-gear.com>
 * @copyright 2008 Martin Schütte
 * @copyright 2003-2005 Hermann Stainer, Web-Gear
 * @license   http://www.debian.org/misc/bsd.license  BSD License (3 Clause)
 * @version   CVS: $Id$
 * @link      http://pear.php.net/package/Payment_DTA
 */

/**
 * needs base class
 */
require_once 'DTABase.php';

/**
* The maximum allowed amount per transfer.
* Set to the maximum amount for a "EU-Standardüberweisung"
* that does not have to be reported (cf.
* http://www.bundesbank.de/meldewesen/mw_aussenwirtschaft.en.php).
*
* @const DTAZV_MAXAMOUNT
*/
define("DTAZV_MAXAMOUNT", 12500);

/**
 * DTAZV class provides functions to create and handle with DTAZV
 * files used in Germany to exchange informations about european
 * money transactions with banks or online banking programs.
 *
 * @category  Payment
 * @package   Payment_DTA
 * @author    Martin Schütte <info@mschuette.name>
 * @author    Hermann Stainer <hs@web-gear.com>
 * @copyright 2008 Martin Schütte
 * @license   http://www.debian.org/misc/bsd.license  BSD License (3 Clause)
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/Payment_DTA
 */
class DTAZV extends DTABase
{
    /**
    * Constructor.
    *
    * @access public
    */
    function DTAZV()
    {
        $this->DTABase();
    }

    /**
    * Set the sender of the DTAZV file. Must be set for valid DTAZV file.
    * The given account data is also used as default sender's account.
    * Account data contains
    *  name            Sender's name. Maximally 35 chars are allowed.
    *  additional_name Sender's additional name (max. 35 chars)
    *  street          Sender's street/PO Box (max. 35 chars)
    *  city            Sender's city (max. 35 chars)
    *  bank_code       Sender's bank code (BLZ, 8-digit)
    *  account_number  Sender's account number (10-digit)
    *
    * @param array $account Account data fot file sender.
    *
    * @access public
    * @return boolean
    */
    function setAccountFileSender($account)
    {
        $account['account_number'] =
            strval($account['account_number']);
        $account['bank_code']      =
            strval($account['bank_code']);

        if (strlen($account['name']) > 0
         && strlen($account['bank_code']) > 0
         && strlen($account['bank_code']) <= 8
         && ctype_digit($account['bank_code'])
         && strlen($account['account_number']) > 0
         && strlen($account['account_number']) <= 10
         && ctype_digit($account['account_number'])) {
            if (empty($account['additional_name'])) {
                $account['additional_name'] = "";
            }
            if (empty($account['street'])) {
                $account['street'] = "";
            }
            if (empty($account['city'])) {
                $account['city'] = "";
            }

            $this->account_file_sender = array(
                "name"            => substr($this->makeValidString($account['name']), 0, 35),
                "additional_name" => substr($this->makeValidString($account['additional_name']), 0, 35),
                "street"          => substr($this->makeValidString($account['street']), 0, 35),
                "city"            => substr($this->makeValidString($account['city']), 0, 35),
                "bank_code"       => $account['bank_code'],
                "account_number"  => $account['account_number']
            );

            $result = true;
        } else {
            $result = false;
        }

        return $result;
    }

    /**
    * Adds an exchange.
    *
    * First the account data for the receiver of the exchange is set.
    * In the case the DTA file contains credits, this is the payment receiver.
    * In the other case (the DTA file contains debits), this is the account,
    * from which money is taken away.
    *
    * If the sender is not specified, values of the file sender are used by default.
    * Account data for sender contain
    *  name            Sender's name. Maximally 35 chars are allowed.
    *  additional_name Sender's additional name (max. 35 chars)
    *  street          Sender's street/PO Box (max. 35 chars)
    *  city            Sender's city (max. 35 chars)
    *  bank_code       Sender's bank code (8-digit BLZ)
    *  account_number  Sender's account number (10-digit)
    *
    * Account data for receiver contain
    *  name            Receiver's name. Maximally 35 chars are allowed.
    *  additional_name Receiver's additional name (max. 35 chars)
    *  street          Receiver's street/PO Box (max. 35 chars)
    *  city            Receiver's city (max. 35 chars)
    *  bank_code       Receiver's bank code (8 or 11 char BIC)
    *  account_number  Receiver's account number (up to 34 char IBAN)
    *
    * @param array  $account_receiver Receiver's account data.
    * @param double $amount           Amount of money (Euro) in this exchange.
    * @param array  $purposes         Array of up to 4 lines (max. 35 chars each)
    *                                 for description of the exchange.
    * @param array  $account_sender   Sender's account data.
    *
    * @access public
    * @return boolean
    */
    function addExchange($account_receiver, $amount, $purposes, $account_sender = array())
    {
        if (empty($account_receiver['additional_name'])) {
            $account_receiver['additional_name'] = "";
        }
        if (empty($account_receiver['street'])) {
            $account_receiver['street'] = "";
        }
        if (empty($account_receiver['city'])) {
            $account_receiver['city'] = "";
        }

        if (empty($account_sender['name'])) {
            $account_sender['name'] =
                $this->account_file_sender['name'];
        }
        if (empty($account_sender['additional_name'])) {
            $account_sender['additional_name'] =
                $this->account_file_sender['additional_name'];
        }
        if (empty($account_sender['street'])) {
            $account_sender['street'] =
                $this->account_file_sender['street'];
        }
        if (empty($account_sender['city'])) {
            $account_sender['city'] =
                $this->account_file_sender['city'];
        }
        if (empty($account_sender['bank_code'])) {
            $account_sender['bank_code'] =
                $this->account_file_sender['bank_code'];
        }
        if (empty($account_sender['account_number'])) {
            $account_sender['account_number'] =
                $this->account_file_sender['account_number'];
        }

        // check arguments
        if (strlen($account_receiver['bank_code']) == 8) {
            if (is_numeric($account_receiver['bank_code'])) {
                // german BLZ -> allowed with special format
                $account_receiver['bank_code'] =
                    '///' . $account_receiver['bank_code'];
            } else {
                // short BIC -> fill to 11 chars
                $account_receiver['bank_code'] =
                    $account_receiver['bank_code'] . 'XXX';
            }
        }

        $account_sender['account_number'] =
            strval($account_sender['account_number']);
        $account_sender['bank_code']      =
            strval($account_sender['bank_code']);

        /*
         * notes for IBAN: currently only checked for length;
         *   we can use PEAR::Validate_Finance_IBAN once it
         *   gets a 'beta' or 'stable' status
         * the minimum length of 12 is chosen arbitrarily as
         *   an additional plausibility check; currently the
         *   shortest real IBANs have 15 chars
         */
        $cents = (int)(round($amount * 100));
        if (strlen($account_receiver['name']) > 0
         && strlen($account_receiver['bank_code']) == 11
         && strlen($account_receiver['account_number']) > 12
         && strlen($account_receiver['account_number']) <= 34
         && strlen($account_sender['name']) > 0
         && strlen($account_sender['bank_code']) > 0
         && strlen($account_sender['bank_code']) <= 8
         && ctype_digit($account_sender['bank_code'])
         && strlen($account_sender['account_number']) > 0
         && strlen($account_sender['account_number']) <= 10
         && ctype_digit($account_sender['account_number'])
         && is_numeric($amount) && $cents > 0
         && $cents <= DTAZV_MAXAMOUNT*100
         && $this->sum_amounts <= PHP_INT_MAX - $cents
         && ((is_array($purposes) && count($purposes) >= 1 && count($purposes) <= 4)
             || (is_string($purposes) && strlen($purposes) > 0))) {

            $this->sum_amounts += $cents;

            if (is_string($purposes)) {
                $filtered_purposes = str_split($this->makeValidString($purposes), 35);
                $filtered_purposes = array_slice($filtered_purposes, 0, 14);
            } else {
                $filtered_purposes = array();
                array_slice($purposes, 0, 4);
                foreach ($purposes as $purposeline) {
                    $filtered_purposes[] = substr($this->makeValidString($purposeline), 0, 35);
                }
            }
            // ensure four lines
            $filtered_purposes = array_slice(array_pad($filtered_purposes, 4, ""), 0, 4);

            $this->exchanges[] = array(
                "sender_name"              => substr($this->makeValidString($account_sender['name']), 0, 35),
                "sender_additional_name"   => substr($this->makeValidString($account_sender['additional_name']), 0, 35),
                "sender_street"            => substr($this->makeValidString($account_sender['street']), 0, 35),
                "sender_city"              => substr($this->makeValidString($account_sender['city']), 0, 35),
                "sender_bank_code"         => $account_sender['bank_code'],
                "sender_account_number"    => $account_sender['account_number'],
                "receiver_name"            => substr($this->makeValidString($account_receiver['name']), 0, 35),
                "receiver_additional_name" => substr($this->makeValidString($account_receiver['additional_name']), 0, 35),
                "receiver_street"          => substr($this->makeValidString($account_receiver['street']), 0, 35),
                "receiver_city"            => substr($this->makeValidString($account_receiver['city']), 0, 35),
                "receiver_bank_code"       => $account_receiver['bank_code'],
                "receiver_account_number"  => $account_receiver['account_number'],
                "amount"                   => $cents,
                "purposes"                 => $filtered_purposes
            );

            $result = true;
        } else {
            $result = false;
        }

        return $result;
    }

    /**
    * Returns the full content of the generated DTAZV file.
    * All added exchanges are processed.
    *
    * @access public
    * @return string
    */
    function getFileContent()
    {
        $content = "";

        /* The checksum in DTAZV adds only the integer parts of all
         * transfered amounts and is different from the sum of amounts.
         */
        $checksum_amounts = 0;
        $sum_amounts      = 0;

        /**
         * data record Q
         */

        // Q01 record length (256 Bytes)
        $content .= "0256";
        // Q02 record type
        $content .= "Q";
        // Q03 BLZ receiving this file (usually the sender's bank)
        $content .= str_pad($this->account_file_sender['bank_code'],
                        8, "0", STR_PAD_LEFT);
        // Q04 customer number (usually the sender's account)
        $content .= str_pad($this->account_file_sender['account_number'],
                        10, "0", STR_PAD_LEFT);
        // Q05 sender's address
        $content .= str_pad($this->account_file_sender['name'],
                        35, " ", STR_PAD_RIGHT);
        $content .= str_pad($this->account_file_sender['additional_name'],
                        35, " ", STR_PAD_RIGHT);
        $content .= str_pad($this->account_file_sender['street'],
                        35, " ", STR_PAD_RIGHT);
        $content .= str_pad($this->account_file_sender['city'],
                        35, " ", STR_PAD_RIGHT);
        // Q06 date of file creation
        $content .= strftime("%d%m%y", $this->timestamp);
        // Q07 daily counter
        // UNSURE if necessary
        $content .= "00";
        // Q08 execution date
        $content .= strftime("%d%m%y", $this->timestamp);
        // Q09 notification to federal bank
        // according to specification (see above)
        // transfers <= 12500 Euro do not have to be reported
        $content .= "N";
        // Q10 notification data
        $content .= "00";
        // Q11 notification BLZ
        $content .= str_repeat("0", 8);
        // Q12 reserve
        $content .= str_repeat(" ", 68);

        assert(strlen($content) == 256);

        /**
         * data record(s) T
         */

        foreach ($this->exchanges as $exchange) {
            $sum_amounts      += intval($exchange['amount']);
            $checksum_amounts += intval($exchange['amount']/100);

            // T01 record length
            $content .= "0768";
            // T02 record type
            $content .= "T";
            // T03 sender's bank
            $content .= str_pad($exchange['sender_bank_code'],
                            8, "0", STR_PAD_LEFT);
            // T04a currency (fixed)
            $content .= "EUR";
            // T04b sender's account
            $content .= str_pad($exchange['sender_account_number'],
                            10, "0", STR_PAD_LEFT);
            // T05 execution date (optional, if != Q6)
            $content .= str_repeat("0", 6);
            // T06 BLZ, empty for Standardüberweisung
            $content .= str_repeat("0", 8);
            // T07a currency, empty for Standardüberweisung
            $content .= str_repeat(" ", 3);
            // T07b account, empty for Standardüberweisung
            $content .= str_repeat("0", 10);
            // T08 receiver's BIC
            $content .= str_pad($exchange['receiver_bank_code'],
                            11, "X", STR_PAD_RIGHT);
            // T09a country code, empty for Standardüberweisung
            $content .= str_repeat(" ", 3);
            // T09b receiver's bank address, empty for Standardüberweisung
            $content .= str_repeat(" ", 4*35);
            // T10a receiver's country code --> use cc from IBAN
            $content .= substr($exchange['receiver_account_number'], 0, 2) . ' ';
            // T10b receiver's address
            $content .= str_pad($exchange['receiver_name'],
                            35, " ", STR_PAD_RIGHT);
            $content .= str_pad($exchange['receiver_additional_name'],
                            35, " ", STR_PAD_RIGHT);
            $content .= str_pad($exchange['receiver_street'],
                            35, " ", STR_PAD_RIGHT);
            $content .= str_pad($exchange['receiver_city'],
                            35, " ", STR_PAD_RIGHT);
            // T11 empty for Standardüberweisung
            $content .= str_repeat(" ", 2*35);
            // T12 receiver's IBAN
            $content .= '/' . str_pad($exchange['receiver_account_number'],
                            34, " ", STR_PAD_RIGHT);
            // T13 currency
            $content .= "EUR";
            // T14a amount (integer)
            $content .= str_pad(intval($exchange['amount']/100),
                            14, "0", STR_PAD_LEFT);
            // T14b amount (decimal places)
            $content .= str_pad(($exchange['amount']%100)*10, 3, "0", STR_PAD_LEFT);
            // T15 purpose
            $content .= str_pad($exchange['purposes'][0], 35, " ", STR_PAD_RIGHT);
            $content .= str_pad($exchange['purposes'][1], 35, " ", STR_PAD_RIGHT);
            $content .= str_pad($exchange['purposes'][2], 35, " ", STR_PAD_RIGHT);
            $content .= str_pad($exchange['purposes'][3], 35, " ", STR_PAD_RIGHT);
            // T16--T20 instruction code, empty for Standardüberweisung
            $content .= str_repeat("0", 4*2);
            $content .= str_repeat(" ", 25);
            // T21 fees
            $content .= "00";
            // T22 payment type
            $content .= "13";
            // T23 free text for accounting
            $content .= str_repeat(" ", 27);
            // T24 contact details
            $content .= str_repeat(" ", 35);
            // T25 reporting key
            $content .= "0";
            // T26 reserve
            $content .= str_repeat(" ", 51);
            // T26 following report extension
            $content .= "00";
        }

        assert((strlen($content) - 256) % 768 == 0);

        /**
         * data record Z
         */

        // Z01 record length
        $content .= "0256";
        // Z02 record type
        $content .= "Z";
        // Z03 sum of amounts (integer parts in T14a)
        assert($sum_amounts == $this->sum_amounts);
        $content .= str_pad(intval($checksum_amounts), 15, "0", STR_PAD_LEFT);
        // Z04 number of records type T
        $content .= str_pad(count($this->exchanges), 15, "0", STR_PAD_LEFT);
        // Z05 reserve
        $content .= str_repeat(" ", 221);

        assert(strlen($content) >= 512);
        assert((strlen($content) - 512) % 768 == 0);

        return $content;
    }
}
