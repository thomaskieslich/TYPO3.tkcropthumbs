<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tceforms_inline.php']['tceformsInlineHook'][$_EXTKEY] =
	'ThomasKieslich\\Tkcropthumbs\\Hooks\\InlineElementHook';

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['getImgResource'][$_EXTKEY] =
	'ThomasKieslich\\Tkcropthumbs\\Hooks\\CropScaleHook';

if (TYPO3_MODE === 'BE') {
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerAjaxHandler(
		'TkcropthumbsAjaxController::init',
		'ThomasKieslich\\Tkcropthumbs\\Controller\\AjaxController->init'
	);
}