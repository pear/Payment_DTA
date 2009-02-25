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
    * Sum of amounts in exchanges (in Cents).
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
            85, 86, 87, 88, 89, 90, 196, 214, 220, 223);
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

        $result = "";

        if (strlen($string) > 0) {
            $search  = array("'ä'", "'á'", "'à'", "'ã'", "'å'", "'ç'",
                             "'é'", "'è'", "'ë'", "'í'", "'ì'", "'ï'",
                             "'ñ'", "'ö'", "'ó'", "'ò'", "'ø'", "'ß'",
                             "'ü'", "'ú'", "'ù'",
                             "'Ä'", "'Á'", "'À'", "'Ã'", "'Å'", "'Ç'",
                             "'É'", "'È'", "'Ë'", "'Í'", "'Ì'", "'Ï'",
                             "'Ñ'", "'Ö'", "'Ó'", "'Ò'", "'Ø'",
                             "'Ü'", "'Ú'", "'Ù'");
            $replace = array("ae", "a"  , "a"  , "a"  , "a"  , "c"  ,
                             "e" , "e"  , "e"  , "i"  , "i"  , "i"  ,
                             "n" , "oe" , "o"  , "o"  , "o"  , "ss" ,
                             "ue", "u"  , "u",
                             "AE", "A"  , "A"  , "A"  , "A"  , "C"  ,
                             "E" , "E"  , "E"  , "I"  , "I"  , "I"  ,
                             "N" , "Oe" , "O"  , "O"  , "O"  ,
                             "UE", "U"  , "U");

            $result = strtoupper(preg_replace($search, $replace, $string));

            for ($index = 0;$index < strlen($result);$index++) {
                if (!in_array(ord(substr($result, $index, 1)),
                              $this->validString_chars)) {
                    $result[$index] = " ";
                }
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
}