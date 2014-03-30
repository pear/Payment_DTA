<?php
/*
 * helper script to generate PEAR's package.xml
 * 
 * my command lines to test and package:
 *  phpcs DTA*.php
 *  phpunit .
 *  phpunit --coverage-html coverage .
 *  phpdoc -p on -f DTABase.php,DTA.php,DTAZV.php -t docs -o "XML:DocBook/peardoc2:default" -dc Payment
 *  phpdoc -p on -f DTABase.php,DTA.php,DTAZV.php -t htmldocs -o "HTML:frames:default" -dc Payment
 *
 *  php package.php
 *  php package.php make
 *  pear package-validate
 *  pear package
 *  pear svntag package.xml
 *  pear sign Payment_DTA-*.tgz
 *
 */
 
error_reporting(E_ALL ^ E_DEPRECATED);

require_once('PEAR/PackageFileManager2.php');
PEAR::setErrorHandling(PEAR_ERROR_DIE);
$packagexml = new PEAR_PackageFileManager2;
echo("...setup\n");

$options = array(
 'packagedirectory' => dirname(__FILE__),
 'baseinstalldir' => 'Payment',
 'filelistgenerator' => 'file'
);
$packagexml = $packagexml->importOptions('./package.xml',$options);
echo("...imported\n");
$packagexml->setAPIStability('stable');
$packagexml->setReleaseStability('stable');
$packagexml->setAPIVersion('1.4.3');
$packagexml->setReleaseVersion('1.4.3');
$packagexml->setNotes('[+] PHPUnit fixes (thanks to Daniel Convissor, #19159)
[-] string comparison in DTA::getMetaData() (thanks to Christian Stoller, #19305)
( [+] Added   [-] Fixed   [*] Improved   [!] Note )');
$packagexml->addReplacement('*.php', 'package-info', '@package_version@', 'version');
$packagexml->addIgnore(array('README.md', 'package.php', '.gitignore', '.travis.yml'));
echo("...options set\n");
$packagexml->generateContents();
echo("...generated\n");
// note use of debugPackageFile() - this is VERY important
if (isset($_GET['make']) || @$_SERVER['argv'][1] == 'make') {
    $e = $packagexml->writePackageFile();
} else {
    $e = $packagexml->debugPackageFile();
}
if (PEAR::isError($e)) {
    echo $e->getMessage();
    die();
}

