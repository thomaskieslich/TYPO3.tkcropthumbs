<?php

/* * *************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Thomas Kieslich <thomaskieslich@gmx.net>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 * ************************************************************* */

if (substr_count($_SERVER['SCRIPT_FILENAME'], 'typo3conf') > 0) {
	define('TYPO3_MOD_PATH', '../typo3conf/ext/tkcropthumbs/');
} else {
	define('TYPO3_MOD_PATH', 'ext/tkcropthumbs/');
}

$BACK_PATH = '../../../typo3/';

// include
require ($BACK_PATH . 'init.php');
// lang
$LANG->includeLLFile('EXT:tkcropthumbs/locallang.xml');

$cropping = new crop($LANG);

echo $cropping->template;

class crop {

	public $template = '';
	protected $values = array();
	protected $zoom = 1;
	protected $imageWidth;
	protected $imageHeight;
	protected $width;
	protected $height;
	protected $aspect;
	protected $ratioField;
	protected $LANG;

	function __construct($LANG) {
		$this->LANG = $LANG;
		if ($_GET['action'] == 'save') {
			$this->saveValues();
		} else if ($_GET['action'] == 'resetSingle') {
			$this->resetSingle();
		} else if ($_GET['action'] == 'resetAll') {
			$this->resetAll();
		} else {
			$this->setAspect();
			$this->resizeImage();
			$this->getValues();
			$this->display();
		}
	}

	public function display() {
		$this->template = '<!DOCTYPE html>
<html>
<head>
	<title>' . $_GET["image"] . '</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<link rel="stylesheet" type="text/css" href="res/css/crop.css" media="all">
	<link rel="stylesheet" type="text/css" href="res/css/imgareaselect-default.css" media="all">
	<script src="res/js/jquery-1.4.4.min.js" type="text/javascript"></script>
	<script src="res/js/jquery.imgareaselect.min.js" type="text/javascript"></script>

	<script type="text/javascript">
		function preview(img, selection) {
			$("#x1").val(selection.x1);
			$("#y1").val(selection.y1);
			$("#x2").val(selection.x2);
			$("#y2").val(selection.y2);
			$("#w").val(selection.width);
			$("#h").val(selection.height);
		}

jQuery(document).ready(function($){
$("#cropbox").imgAreaSelect({ x1: ' . $this->values["x1"] . ', y1: ' . $this->values["y1"] . ', x2: ' . $this->values["x2"] . ', y2: ' . $this->values["y2"] . ', aspectRatio: \'' . $this->aspect . '\', imageWidth:' . $this->imageWidth . ', imageHeight:' . $this->imageHeight . ', handles: true, fadeSpeed: 200, onInit: preview, onSelectChange: preview  });
});
</script>
</head>
<body>';
		//Image
		$this->template .= '<div id="image">
		<img src="' . '../../../' . $_GET["image"] . '" width="' . $this->width . '" height="' . $this->height . '" id="cropbox" alt="croped image" />
	</div>';

		//Values
		$this->template .= '<div id="values"><h2>' . $this->LANG->getLL("editor_title") . '</h2>
		<form name="crop" method="get" action="">
		<input type="hidden" name="action" value="save" />
		<input type="hidden" name="image" value="' . $_GET['image'] . '" />
		<input type="hidden" name="uid" value="' . $_GET['uid'] . '" />
		<input type="hidden" name="aspectratio" value="' . $_GET['aspectratio'] . '" />
			<fieldset>
				<label for="x1">X1</label><input type="text" size="4" id="x1" name="x1" value="' . $this->values['x1'] . '" />
				<label for="y1">Y1</label><input type="text" size="4" id="y1" name="y1" value="' . $this->values['y1'] . '" /><br />
				<label for="x2">X2</label><input type="text" size="4" id="x2" name="x2" value="' . $this->values['x2'] . '" />
				<label for="y2">Y2</label><input type="text" size="4" id="y2" name="y2" value="' . $this->values['y2'] . '" /><br />
				<label for="w">W&nbsp;</label><input type="text" size="4" id="w" name="w" readonly />
				<label for="h">H&nbsp;</label><input type="text" size="4" id="h" name="h" readonly />
				<label for="ratio">' . $this->LANG->getLL("aspectratio") . '</label><input type="text" size="2" id="ratio" value="' . $this->aspect . '" readonly />
		' . $this->ratioField . '
			</fieldset>
			<input type="submit" value="' . $this->LANG->getLL("save_values") . '" />
		</form>
		<form name="resetSingle" method="get" action="">
			<input type="hidden" name="action" value="resetSingle" />
			<input type="hidden" name="image" value="' . $_GET['image'] . '" />
			<input type="hidden" name="uid" value="' . $_GET['uid'] . '" />
			<input type="hidden" name="aspectratio" value="' . $_GET['aspectratio'] . '" />
			<input type="submit" value="' . $this->LANG->getLL("reset_single") . '" />
		</form>
		<form name="resetAll" method="get" action="">
			<input type="hidden" name="action" value="resetAll" />
			<input type="hidden" name="image" value="' . $_GET['image'] . '" />
			<input type="hidden" name="uid" value="' . $_GET['uid'] . '" />
			<input type="hidden" name="aspectratio" value="' . $_GET['aspectratio'] . '" />
			<input type="submit" value="' . $this->LANG->getLL("reset_all") . '" />
		</form>
		<form name="close" method="get" action="">
			<input type="submit" value="' . $this->LANG->getLL("close") . '" onClick="self.close();" />
		</form>
	</div>';



		$this->template .= ' </body>
</html>';
	}

	function getValues() {
		$select = 'tx_tkcropthumbs_cropvalues';
		$table = 'tt_content';
		$where = 'uid = ' . $_GET['uid'];
		$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $table, $where);
		$this->values = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);
		$cropXml = simplexml_load_string($this->values['tx_tkcropthumbs_cropvalues']);
		if ($cropXml) {
			$cropData = $cropXml->xpath('//image[. ="' . $_GET['image'] . '"]');
			$this->values = $cropData[0];
		}
		if (!$cropData) {
			$aspectArray = preg_split('/:/', $this->aspect);
			$orientation = ($this->imageWidth > $this->imageHeight) ? 'landscape' : 'portrait';
			if (intval($this->imageHeight * ($aspectArray[0] / $aspectArray[1])) > $this->imageWidth) {
				$orientation = 'portrait';
			}

			if ($orientation == 'landscape') {
				$cWidth = intval($this->imageHeight * ($aspectArray[0] / $aspectArray[1]));
				if ($cWidth == 0)
					$cWidth = $this->imageWidth;
				$this->values["x1"] = intval($this->imageWidth / 2 - $cWidth / 2);
				$this->values["y1"] = 0;
				$this->values["x2"] = $this->imageWidth - $this->values["x1"];
				$this->values["y2"] = $this->imageHeight;
			} else if ($orientation == 'portrait') {
				$cHeight = intval($this->imageWidth * ($aspectArray[1] / $aspectArray[0]));
				if ($cHeight == 0)
					$cHeight = $this->imageHeight;
				$this->values["x1"] = 0;
				$this->values["y1"] = intval($this->imageHeight / 2 - $cHeight / 2);
				$this->values["x2"] = $this->imageWidth;
				$this->values["y2"] = $this->imageHeight - $this->values["y1"];
			}
		}
	}

	function resizeImage() {
		$imgsize = getimagesize(PATH_site . $_GET["image"]);
		$this->imageWidth = $imgsize[0];
		$this->imageHeight = $imgsize[1];
		//css width height
		if (($this->imageWidth > 600) || ($this->imageHeight > 600)) {
			if ($this->imageHeight > $this->imageWidth) {
				$this->height = 600;
				$this->width = $this->imageWidth * 600 / $this->imageHeight;
			} else {
				$this->width = 600;
				$this->height = $this->imageHeight * 600 / $this->imageWidth;
			}
		}
	}

	function setAspect() {
		if ($_GET['aspectratio'] == 0)
			$this->aspect = '';
		else if ($_GET['aspectratio'] == 1)
			$this->aspect = '1:1';
		else if ($_GET['aspectratio'] == 2)
			$this->aspect = '4:3';
		else if ($_GET['aspectratio'] == 3)
			$this->aspect = '13:9';
		else if ($_GET['aspectratio'] == 4)
			$this->aspect = '16:9';		
	}

	function saveValues() {
		$select = 'tx_tkcropthumbs_cropvalues';
		$table = 'tt_content';
		$where = 'uid = ' . $_GET['uid'];
		$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $table, $where);
		$values = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);
		$cropXml = simplexml_load_string($values['tx_tkcropthumbs_cropvalues']);
		if ($cropXml) {
			$cropData = $cropXml->xpath('//image[. ="' . $_GET['image'] . '"]');
			if (!$cropData) {
				$cropXml->addChild('image', $_GET['image']);
				$cropData = $cropXml->xpath('//image[. ="' . $_GET['image'] . '"]');
			}
			$values = $cropData[0];
		} else {
			$xml = '<?xml version="1.0" encoding="UTF-8"?>
				<images>
					<image x1="0" y1="0" x2="0" y2="0" tstamp="0">' . $_GET['image'] . '</image>
				</images>';
			$cropXml = simplexml_load_string($xml);
			$cropData = $cropXml->xpath('//image[. ="' . $_GET['image'] . '"]');
			$values = $cropData[0];
		}



		$values["x1"] = $_GET['x1'];
		$values["y1"] = $_GET['y1'];
		$values["x2"] = $_GET['x2'];
		$values["y2"] = $_GET['y2'];
		$values["tstamp"] = time();
		$fieldValues = array(
			'tx_tkcropthumbs_cropvalues' => $cropXml->asXML()
		);
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, $where, $fieldValues);
		$this->setAspect();
		$this->resizeImage();
		$this->getValues();
		$this->display();
	}

	function resetSingle() {
		$select = 'tx_tkcropthumbs_cropvalues';
		$table = 'tt_content';
		$where = 'uid = ' . $_GET['uid'];
		$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $table, $where);
		$values = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);
		if (strlen($values['tx_tkcropthumbs_cropvalues']) > 1) {
			$cropXml = simplexml_load_string($values['tx_tkcropthumbs_cropvalues']);
			$cropData = $cropXml->xpath('//image[. ="' . $_GET['image'] . '"]');
			$this->values = $cropData[0];
			$this->setAspect();
			$this->resizeImage();
			$aspectArray = preg_split('/:/', $this->aspect);
			$orientation = ($this->imageWidth > $this->imageHeight) ? 'landscape' : 'portrait';
			if (intval($this->imageHeight * ($aspectArray[0] / $aspectArray[1])) > $this->imageWidth) {
				$orientation = 'portrait';
			}

			if ($orientation == 'landscape') {
				$cWidth = intval($this->imageHeight * ($aspectArray[0] / $aspectArray[1]));
				if ($cWidth == 0)
					$cWidth = $this->imageWidth;
				$this->values["x1"] = intval($this->imageWidth / 2 - $cWidth / 2);
				$this->values["y1"] = 0;
				$this->values["x2"] = $this->imageWidth - $this->values["x1"];
				$this->values["y2"] = $this->imageHeight;
			} else if ($orientation == 'portrait') {
				$cHeight = intval($this->imageWidth * ($aspectArray[1] / $aspectArray[0]));
				if ($cHeight == 0)
					$cHeight = $this->imageHeight;
				$this->values["x1"] = 0;
				$this->values["y1"] = intval($this->imageHeight / 2 - $cHeight / 2);
				$this->values["x2"] = $this->imageWidth;
				$this->values["y2"] = $this->imageHeight - $this->values["y1"];
			}

			$fieldValues = array(
				'tx_tkcropthumbs_cropvalues' => $cropXml->asXML()
			);
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, $where, $fieldValues);
			$this->getValues();
			$this->display();
		}
		else {
			$this->setAspect();
			$this->resizeImage();
			$this->getValues();
			$this->display();
		}
	}

	function resetAll() {
		$table = 'tt_content';
		$where = 'uid = ' . $_GET['uid'];
		$fieldValues = array(
			'tx_tkcropthumbs_cropvalues' => Null
		);
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, $where, $fieldValues);
		$this->setAspect();
		$this->resizeImage();
		$this->getValues();
		$this->display();
	}

}

?>