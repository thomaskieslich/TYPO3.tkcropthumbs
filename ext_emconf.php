<?php

########################################################################
# Extension Manager/Repository config file for ext "tkcropthumbs".
#
# Auto generated 13-01-2011 12:13
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
	'version' => '1.1.5',
	'dependencies' => '',
	'conflicts' => 'dam',
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
			'dam' => '',
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:23:{s:14:"class.crop.php";s:4:"8c1c";s:27:"class.ux_t3lib_tceforms.php";s:4:"ed5a";s:23:"class.ux_tslib_cObj.php";s:4:"8217";s:29:"class.ux_tslib_gifBuilder.php";s:4:"6467";s:27:"class.ux_tx_dam_tcefunc.php";s:4:"3b70";s:12:"ext_icon.gif";s:4:"a51b";s:17:"ext_localconf.php";s:4:"3968";s:14:"ext_tables.php";s:4:"71cb";s:14:"ext_tables.sql";s:4:"36c6";s:13:"locallang.xml";s:4:"2ae9";s:16:"locallang_db.xml";s:4:"8f54";s:14:"doc/manual.sxw";s:4:"615f";s:25:"res/css/border-anim-h.gif";s:4:"50da";s:25:"res/css/border-anim-v.gif";s:4:"a786";s:20:"res/css/border-h.gif";s:4:"033e";s:20:"res/css/border-v.gif";s:4:"d451";s:16:"res/css/crop.css";s:4:"6aff";s:34:"res/css/imgareaselect-animated.css";s:4:"ac37";s:33:"res/css/imgareaselect-default.css";s:4:"8b9b";s:18:"res/icons/crop.png";s:4:"91dc";s:22:"res/icons/crop_dam.png";s:4:"7bc2";s:26:"res/js/jquery-1.4.4.min.js";s:4:"73a9";s:34:"res/js/jquery.imgareaselect.min.js";s:4:"add1";}',
	'suggests' => array(
	),
);

?>