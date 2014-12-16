<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "tkcropthumbs".
 *
 * Auto generated 11-12-2014 17:26
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array (
	'title' => 'Crop and Square Thumbnails',
	'description' => 'Crop Thumbnails with image area select and has a switch to make thumbs with aspect ratios. Can use it for Detailviews or simple galeries.',
	'category' => 'misc',
	'version' => '6.2.8',
	'state' => 'stable',
	'uploadfolder' => false,
	'createDirs' => '',
	'clearcacheonload' => false,
	'author' => 'Thomas Kieslich',
	'author_email' => 'info@thomaskieslich.de',
	'author_company' => NULL,
	'constraints' =>
	array (
		'depends' =>
		array (
			'typo3' => '6.2.0-6.2.99',
		),
		'conflicts' =>
		array (
		),
		'suggests' =>
		array (
                        'fluidcontent_core' => ''
		),
	),
);

