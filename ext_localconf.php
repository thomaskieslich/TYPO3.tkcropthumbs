<?php
if (!defined ('TYPO3_MODE')) {die ('Access denied.'); }

$TYPO3_CONF_VARS["BE"]["XCLASS"]["t3lib/class.t3lib_tceforms.php"] = t3lib_extMgm::extPath($_EXTKEY)."class.ux_t3lib_tceforms.php";

$TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["tslib/class.tslib_content.php"] = t3lib_extMgm::extPath($_EXTKEY)."class.ux_tslib_cObj.php";
$TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["tslib/class.tslib_gifbuilder.php"] = t3lib_extMgm::extPath($_EXTKEY)."class.ux_tslib_gifBuilder.php";
?>