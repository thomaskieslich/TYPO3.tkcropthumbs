<?php

if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

//Cropping Single
TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig('options.hideModules.user := addToList(TkcropthumbsCrop)');

if (TYPO3_MODE === 'BE') {
	\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
		'ThomasKieslich.' . $_EXTKEY,
		'user',
		'crop',
		'',
		array(
			'Cropping' => 'show'
		),
		array()
	);
}

$sysFilereferenceTemp = array(
	'tx_tkcropthumbs_crop' => array(
		'label' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_db.xlf:tca.crop',
		'config' => array(
			'type' => 'user',
			'userFunc' => 'ThomasKieslich\\Tkcropthumbs\\Tca\\Wizard->showIcon',
		)
	)
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('sys_file_reference', $sysFilereferenceTemp, 'tx_tkcroptumbs');
$GLOBALS['TCA']['sys_file_reference']['palettes']['imageoverlayPalette']['showitem'] .= ',--linebreak--,tx_tkcropthumbs_crop';

//Aspectratio tt_content
$extConf = unserialize($TYPO3_CONF_VARS['EXT']['extConf']['tkcropthumbs']);
$aspectvalues = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $extConf['aspectratio']);
$aspectratios = array();
$aspectratios[] = array('-:-', ' ');
foreach ($aspectvalues as $value) {
	$aspectratios[] = array($value, $value);
}

$tempColumns = array(
	'tx_tkcropthumbs_aspectratio' => array(
		'exclude' => 0,
		'label' => 'LLL:EXT:tkcropthumbs/locallang_db.xml:tt_content.tx_tkcropthumbs_aspectratio',
		'config' => array(
			'type' => 'select',
			'items' => $aspectratios,
			'minitems' => 1,
			'maxitems' => 1,
		)
	)
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('tt_content', $tempColumns, tx_tkcroptumbs);

$GLOBALS['TCA']['tt_content']['palettes']['image_settings']['showitem'] .= ', tx_tkcropthumbs_aspectratio';