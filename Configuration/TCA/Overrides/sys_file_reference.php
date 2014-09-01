<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

$sysFilereferenceTemp = array(
	'tx_tkcropthumbs_crop' => array(
		'label' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_db.xlf:tca.crop',
		'config' => array(
			'type' => 'passthrough'
		)
	)
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
	'sys_file_reference',
	$sysFilereferenceTemp
);