<?php
/**
 * DTABase, base class for DTA and DTAZV
 *
 * DTA and DTAVZ provide functions to create DTA/DTAZV files used in
 * Germany to exchange informations about money transactions with banks
 * or online banking programs.
 *
 * PHP versions 4 and 5
 *
 * This LICENSE is in the BSD license style.
 *
 * Copyright (c) 2003-2005 Hermann Stainer, Web-Gear
 * http://www.web-gear.com/
 * Copyright (c) 2008 Martin Schütte
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
 * @author    Hermann Stainer <hs@web-gear.com>
 * @author    Martin Schütte <info@mschuette.name>
 * @copyright 2003-2005 Hermann Stainer, Web-Gear
 * @copyright 2008 Martin Schütte
 * @license   http://www.debian.org/misc/bsd.license  BSD License (3 Clause)
 * @version   CVS: $Id$
 * @link      http://pear.php.net/package/Payment_DTA
 */

/**
* DTABase class provides common functions to classes DTA and DTAZV.
*
* @category Payment
* @package  Payment_DTA
* @author   Hermann Stainer <hs@web-gear.com>
* @license  http://www.debian.org/misc/bsd.license  BSD License (3 Clause)
* @version  Release: @package_version@
* @link     http://pear.php.net/package/Payment_DTA
*/
class DTABase
{
    /**
    * Account data for the file sender.
    *
    * @var integer $account_file_sender
    * @access private
    */
    var $account_file_sender;

    /**
    * Array of ASCII Codes of valid chars for DTA field data.
    *
    * @var array $validString_chars
    * @access private
    */
    var $validString_chars;

    /**
    * Current timestamp.
    *
    * @var integer $timestamp
    * @access private
    */
    var $timestamp;

    /**
    * Array of exchanges that the DTA file should contain.
    *
    * @var integer $exchanges
    * @access private
    */
    var $exchanges;

    /**
    * Sum of amounts in exchanges (in Cents); for control total fields.
    *
    * @var integer $sum_amounts
    * @access private
    */
    var $sum_amounts;

    /**
    * Return number of exchanges
    *
    * @access public
    * @return integer
    */
    function count()
    {
        return count($this->exchanges);
    }

    /**
    * Constructor.
    *
    * @access protected
    */
    function DTABase()
    {
        $this->validString_chars   = array(32, 36, 37, 38, 42, 43, 44, 45,
            46, 47, 48, 49, 50, 51, 52, 53, 54, 55, 56, 57, 65, 66, 67, 68,
            69, 70, 71, 72, 73, 74, 75, 76, 77, 78, 79, 80, 81, 82, 83, 84,
            85, 86, 87, 88, 89, 90);
        $this->account_file_sender = array();
        $this->exchanges           = array();
        $this->timestamp           = time();
        $this->sum_amounts         = 0;
    }

    /**
    * Checks if the given string contains only chars valid for fields
    * in DTA files.
    *
    * @param string $string String that is checked.
    *
    * @access public
    * @return boolean
    */
    function validString($string)
    {
        // note: only ASCII is valid, so we may use count_chars()
        $occuring_chars = count_chars($string, 1);

        $result = true;

        foreach ($occuring_chars as $char_ord => $char_amount) {
            if (!in_array($char_ord, $this->validString_chars)) {
                $result = false;
                break;
            }
        }

        return $result;
    }

    /**
    * Makes the given string valid for DTA files.
    * Some diacritics, especially German umlauts become uppercase,
    * all other chars not allowed are replaced with space.
    *
    * @param string $string String that should made valid.
    *
    * @access public
    * @return string
    */
    function makeValidString($string)
    {
        $special_chars = array(
            'á' => 'a',
            'à' => 'a',
            'ä' => 'ae',
            'â' => 'a',
            'ã' => 'a',
            'å' => 'a',
            'æ' => 'ae',
            'ā' => 'a',
            'ă' => 'a',
            'ą' => 'a',
            'ȁ' => 'a',
            'ȃ' => 'a',
            'Á' => 'A',
            'À' => 'A',
            'Ä' => 'Ae',
            'Â' => 'A',
            'Ã' => 'A',
            'Å' => 'A',
            'Æ' => 'AE',
            'Ā' => 'A',
            'Ă' => 'A',
            'Ą' => 'A',
            'Ȁ' => 'A',
            'Ȃ' => 'A',
            'ç' => 'c',
            'ć' => 'c',
            'ĉ' => 'c',
            'ċ' => 'c',
            'č' => 'c',
            'Ç' => 'C',
            'Ć' => 'C',
            'Ĉ' => 'C',
            'Ċ' => 'C',
            'Č' => 'C',
            'ď' => 'd',
            'đ' => 'd',
            'Ď' => 'D',
            'Đ' => 'D',
            'é' => 'e',
            'è' => 'e',
            'ê' => 'e',
            'ë' => 'e',
            'ē' => 'e',
            'ĕ' => 'e',
            'ė' => 'e',
            'ę' => 'e',
            'ě' => 'e',
            'ȅ' => 'e',
            'ȇ' => 'e',
            'É' => 'E',
            'È' => 'E',
            'Ê' => 'E',
            'Ë' => 'E',
            'Ē' => 'E',
            'Ĕ' => 'E',
            'Ė' => 'E',
            'Ę' => 'E',
            'Ě' => 'E',
            'Ȅ' => 'E',
            'Ȇ' => 'E',
            'ĝ' => 'g',
            'ğ' => 'g',
            'ġ' => 'g',
            'ģ' => 'g',
            'Ĝ' => 'G',
            'Ğ' => 'G',
            'Ġ' => 'G',
            'Ģ' => 'G',
            'ĥ' => 'h',
            'ħ' => 'h',
            'Ĥ' => 'H',
            'Ħ' => 'H',
            'ì' => 'i',
            'ì' => 'i',
            'î' => 'i',
            'ï' => 'i',
            'ĩ' => 'i',
            'ī' => 'i',
            'ĭ' => 'i',
            'į' => 'i',
            'ı' => 'i',
            'ĳ' => 'ij',
            'ȉ' => 'i',
            'ȋ' => 'i',
            'Í' => 'I',
            'Ì' => 'I',
            'Î' => 'I',
            'Ï' => 'I',
            'Ĩ' => 'I',
            'Ī' => 'I',
            'Ĭ' => 'I',
            'Į' => 'I',
            'İ' => 'I',
            'Ĳ' => 'IJ',
            'Ȉ' => 'I',
            'Ȋ' => 'I',
            'ĵ' => 'j',
            'Ĵ' => 'J',
            'ķ' => 'k',
            'Ķ' => 'K',
            'ĺ' => 'l',
            'ļ' => 'l',
            'ľ' => 'l',
            'ŀ' => 'l',
            'ł' => 'l',
            'Ĺ' => 'L',
            'Ļ' => 'L',
            'Ľ' => 'L',
            'Ŀ' => 'L',
            'Ł' => 'L',
            'ñ' => 'n',
            'ń' => 'n',
            'ņ' => 'n',
            'ň' => 'n',
            'ŉ' => 'n',
            'Ñ' => 'N',
            'Ń' => 'N',
            'Ņ' => 'N',
            'Ň' => 'N',
            'ó' => 'o',
            'ò' => 'o',
            'ö' => 'oe',
            'ô' => 'o',
            'õ' => 'o',
            'ø' => 'o',
            'ō' => 'o',
            'ŏ' => 'o',
            'ő' => 'o',
            'œ' => 'oe',
            'ȍ' => 'o',
            'ȏ' => 'o',
            'Ó' => 'O',
            'Ò' => 'O',
            'Ö' => 'Oe',
            'Ô' => 'O',
            'Õ' => 'O',
            'Ø' => 'O',
            'Ō' => 'O',
            'Ŏ' => 'O',
            'Ő' => 'O',
            'Œ' => 'OE',
            'Ȍ' => 'O',
            'Ȏ' => 'O',
            'ŕ' => 'r',
            'ř' => 'r',
            'ȑ' => 'r',
            'ȓ' => 'r',
            'Ŕ' => 'R',
            'Ř' => 'R',
            'Ȑ' => 'R',
            'Ȓ' => 'R',
            'ß' => 'ss',
            'ś' => 's',
            'ŝ' => 's',
            'ş' => 's',
            'š' => 's',
            'ș' => 's',
            'Ś' => 'S',
            'Ŝ' => 'S',
            'Ş' => 'S',
            'Š' => 'S',
            'Ș' => 'S',
            'ţ' => 't',
            'ť' => 't',
            'ŧ' => 't',
            'ț' => 't',
            'Ţ' => 'T',
            'Ť' => 'T',
            'Ŧ' => 'T',
            'Ț' => 'T',
            'ú' => 'u',
            'ù' => 'u',
            'ü' => 'ue',
            'û' => 'u',
            'ũ' => 'u',
            'ū' => 'u',
            'ŭ' => 'u',
            'ů' => 'u',
            'ű' => 'u',
            'ų' => 'u',
            'ȕ' => 'u',
            'ȗ' => 'u',
            'Ú' => 'U',
            'Ù' => 'U',
            'Ü' => 'Ue',
            'Û' => 'U',
            'Ũ' => 'U',
            'Ū' => 'U',
            'Ŭ' => 'U',
            'Ů' => 'U',
            'Ű' => 'U',
            'Ų' => 'U',
            'Ȕ' => 'U',
            'Ȗ' => 'U',
            'ŵ' => 'w',
            'Ŵ' => 'W',
            'ý' => 'y',
            'ÿ' => 'y',
            'ŷ' => 'y',
            'Ý' => 'Y',
            'Ÿ' => 'Y',
            'Ŷ' => 'Y',
            'ź' => 'z',
            'ż' => 'z',
            'ž' => 'z',
            'Ź' => 'Z',
            'Ż' => 'Z',
            'Ž' => 'Z',
        );

        $result = "";
        if (strlen($string) == 0) {
            return "";
        }

        // ensure UTF-8, for single-byte-encodings use either
        //     the internal encoding or assume ISO-8859-1
        $utf8string = mb_convert_encoding($string,
            "UTF-8", array("UTF-8", mb_internal_encoding(), "ISO-8859-1"));
        $strlen     = mb_strlen($utf8string, "UTF-8");

        // replace known special chars
        for ($i = 0; $i < $strlen; $i++) {
            $char = mb_substr($utf8string, $i, 1, "UTF-8");
            if (in_array($char, array_keys($special_chars))) {
                $result .= $special_chars[$char];
            } elseif (ord($char) >= 32 && ord($char) <= 126) {
                // ASCII char
                $result .= $char;
            } else {
                // non-ASCII
                $result .= ' ';
            }
        }
        // upper case
        $result = strtoupper($result);
        // make valid (remove remaining invalid ASCII chars)
        for ($index = 0; $index < strlen($result); $index++) {
            if (!in_array(ord($result[$index]), $this->validString_chars)) {
                $result[$index] = " ";
            }
        }
        return $result;
    }

    /**
    * Writes the DTA file.
    *
    * @param string $filename Filename.
    *
    * @access public
    * @return boolean
    */
    function saveFile($filename)
    {
        $content = $this->getFileContent();

        $Dta_fp = @fopen($filename, "w");
        if (!$Dta_fp) {
            $result = false;
        } else {
            $result = @fwrite($Dta_fp, $content);
            @fclose($Dta_fp);
        }

        return $result;
    }

    /**
    * Returns an array with information about the transactions.
    * Can be used to print an accompanying document (Begleitzettel) for disks.
    *
    * @access public
    * @return array Returns an array with keys: "sender_name",
    *   "sender_bank_code", "sender_account", "sum_amounts",
    *   "count", "date"
    */
    function getMetaData()
    {
        return array(
            "sender_name"      => strval($this->account_file_sender['name']),
            "sender_bank_code" => intval($this->account_file_sender['bank_code']),
            "sender_account"   => floatval($this->account_file_sender['account_number']),
            "sum_amounts"      => floatval($this->sum_amounts / 100.0),
            "count"            => intval($this->count()),
            "date"             => $this->timestamp,
        );
    }
}