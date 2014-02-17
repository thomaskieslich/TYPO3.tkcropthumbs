<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['getImgResource'][$_EXTKEY] =
	'ThomasKieslich\\Tkcropthumbs\\Hooks\\CropScaleHook';

$TYPO3_CONF_VARS['BE']['AJAX']['TkcropthumbsAjaxController::init'] = 'ThomasKieslich\\Tkcropthumbs\\Controller\\AjaxController->init';
