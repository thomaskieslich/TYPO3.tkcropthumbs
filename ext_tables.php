<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}
$tempColumns = array (
		'tx_tkcropthumbs_aspectratio' => array (
				'exclude' => 0,
				'label' => 'LLL:EXT:tkcropthumbs/locallang_db.xml:tt_content.tx_tkcropthumbs_aspectratio',
				'config' => array (
						'type' => 'select',
						'items' => array (
								array('-:-', '0'),
								array('1:1', '1'),
								array('4:3', '2'),
								array('13:9', '3'),
								array('16:9', '4'),
						),
						'minitems'=>1,
						'maxitems'=>1,
				)
		),
		'tx_tkcropthumbs_cropvalues' => array(
			'l10n_display' => 'hideDiff',
			'label' => 'scale',
			'config' => array(
				'type' => 'text',
				'default' => '<?xml version="1.0" encoding="UTF-8"?>
				<images></images>'
			),
		)
);


t3lib_div::loadTCA('tt_content');
t3lib_extMgm::addTCAcolumns('tt_content',$tempColumns,tx_tkcroptumbs);


$GLOBALS['TCA']['tt_content']['palettes']['image_settings']['showitem'] .= ', tx_tkcropthumbs_aspectratio';
?>