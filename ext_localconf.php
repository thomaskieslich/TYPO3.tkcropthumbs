<?php
if (!defined('TYPO3_MODE')) {die('Access denied.');}

$_EXTCONF = unserialize($_EXTCONF);
if (t3lib_extMgm::isLoaded('dam')) {
	$TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/dam/lib/class.tx_dam_tcefunc.php"] = t3lib_extMgm::extPath($_EXTKEY) . "class.ux_tx_dam_tcefunc.php";
}

$TYPO3_CONF_VARS["BE"]["XCLASS"]["t3lib/class.t3lib_tceforms.php"] = t3lib_extMgm::extPath($_EXTKEY) . "class.ux_t3lib_tceforms.php";

$TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["tslib/class.tslib_content.php"] = t3lib_extMgm::extPath($_EXTKEY) . "class.ux_tslib_cObj.php";
$TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["tslib/class.tslib_gifbuilder.php"] = t3lib_extMgm::extPath($_EXTKEY) . "class.ux_tslib_gifBuilder.php";
?>