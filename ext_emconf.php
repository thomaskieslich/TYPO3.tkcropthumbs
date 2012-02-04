<?php

########################################################################
# Extension Manager/Repository config file for ext "tkcropthumbs".
#
# Auto generated 04-02-2012 20:26
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Crop and Square Thumbnails',
	'description' => 'Crop Thumbnails with image area select and has a switch to make thumbs with aspect ratios. Can use it for Detailviews or simple galeries.',
	'category' => 'misc',
	'shy' => 0,
	'version' => '1.2.5',
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'state' => 'beta',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearcacheonload' => 0,
	'lockType' => '',
	'author' => 'Thomas Kieslich',
	'author_email' => 'thomaskieslich@gmx.net',
	'author_company' => '',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'constraints' => array(
		'depends' => array(
			'php' => '5.2.4-0.0.0',
			'typo3' => '4.5.0-4.5.99',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:26:{s:20:"class.ext_update.php";s:4:"c696";s:27:"class.ux_t3lib_tceforms.php";s:4:"b0b1";s:23:"class.ux_tslib_cObj.php";s:4:"d8cf";s:29:"class.ux_tslib_gifBuilder.php";s:4:"1419";s:27:"class.ux_tx_dam_tcefunc.php";s:4:"862f";s:12:"ext_icon.gif";s:4:"a51b";s:17:"ext_localconf.php";s:4:"3968";s:14:"ext_tables.php";s:4:"2d7f";s:14:"ext_tables.sql";s:4:"d69d";s:13:"locallang.xml";s:4:"bae3";s:16:"locallang_db.xml";s:4:"517c";s:14:"doc/manual.sxw";s:4:"e65b";s:13:"mod1/conf.php";s:4:"b928";s:14:"mod1/index.php";s:4:"e3f8";s:25:"res/css/border-anim-h.gif";s:4:"50da";s:25:"res/css/border-anim-v.gif";s:4:"a786";s:20:"res/css/border-h.gif";s:4:"033e";s:20:"res/css/border-v.gif";s:4:"d451";s:16:"res/css/crop.css";s:4:"6aff";s:34:"res/css/imgareaselect-animated.css";s:4:"e160";s:33:"res/css/imgareaselect-default.css";s:4:"95f9";s:36:"res/css/imgareaselect-deprecated.css";s:4:"f08a";s:18:"res/icons/crop.png";s:4:"91dc";s:22:"res/icons/crop_dam.png";s:4:"7bc2";s:26:"res/js/jquery-1.6.2.min.js";s:4:"a1a8";s:41:"res/js/jquery.imgareaselect-0.9.8.pack.js";s:4:"f759";}',
	'suggests' => array(
	),
);

?>