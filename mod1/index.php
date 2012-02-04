<?php

/* * *************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Thomas Kieslich <thomaskieslich@gmx.net>
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

/**
 * Functions for Crop Window in Backend
 */
$LANG->includeLLFile('EXT:tkcropthumbs/locallang.xml');

/**
 * class with crop functions
 */
class tx_tkcropthumbs_crop {

	public $template = '';
	protected $values = array ();
	protected $imageWidth;
	protected $imageHeight;
	protected $width;
	protected $height;
	protected $LANG;
	protected $relPath;
	protected $formVars = array ();

	/**
	 * init the object
	 * @param mixed $LANG Syslanguage
	 */
	function init ($LANG) {

		$this->LANG = $GLOBALS['LANG'];
		$this->relPath = t3lib_extMgm::extRelPath('tkcropthumbs');

		// check form vars
		$this->formVars[action] = htmlspecialchars(t3lib_div::_GET('action'));
		if (mb_strlen($this->formVars[action]) > 12) {
			$this->formVars[action] = mb_substr($this->formVars[action], 0, 12);
		}

		$this->formVars[image] = htmlspecialchars(t3lib_div::_GET('image'));

		$this->formVars[uid] = intval('0' . t3lib_div::_GET('uid'));

		$this->formVars[aspectratio] = t3lib_div::trimExplode(':', t3lib_div::_GET('aspectratio'), TRUE, 2);
		if (count($this->formVars[aspectratio]) == 2) {
			$this->formVars[aspectratio][0] = intval('0' . $this->formVars[aspectratio][0]);
			$this->formVars[aspectratio][1] = intval('0' . $this->formVars[aspectratio][1]);
		} else {
			$this->formVars[aspectratio] = null;
		}

		$this->formVars[x1] = intval('0' . t3lib_div::_GET('x1'));
		$this->formVars[y1] = intval('0' . t3lib_div::_GET('y1'));
		$this->formVars[x2] = intval('0' . t3lib_div::_GET('x2'));
		$this->formVars[y2] = intval('0' . t3lib_div::_GET('y2'));
		$this->formVars[w] = intval('0' . t3lib_div::_GET('w'));
		$this->formVars[h] = intval('0' . t3lib_div::_GET('h'));


		if ($this->formVars[action] == 'save') {
			$this->saveValues();
		} else if ($this->formVars[action] == 'resetSingle') {
			$this->resetSingle();
		} else if ($this->formVars[action] == 'resetAll') {
			$this->resetAll();
		} else {
			$this->resizeImage();
			$this->getValues();
			$this->display();
		}
	}

	/**
	 * display the window content
	 */
	public function display () {
		$this->template = '<!DOCTYPE html>
<html>
<head>
	<title>' . $this->formVars[image] . '</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<link rel="stylesheet" type="text/css" href="' . $this->relPath . 'res/css/crop.css" media="all">
	<link rel="stylesheet" type="text/css" href="' . $this->relPath . 'res/css/imgareaselect-default.css" media="all">
	<script src="' . $this->relPath . 'res/js/jquery-1.6.2.min.js" type="text/javascript"></script>
	<script src="' . $this->relPath . 'res/js/jquery.imgareaselect-0.9.8.pack.js" type="text/javascript"></script>

	<script type="text/javascript">
		function preview(img, selection) {
			$("#x1").val(selection.x1);
			$("#y1").val(selection.y1);
			$("#x2").val(selection.x2);
			$("#y2").val(selection.y2);
			$("#w").val(selection.width);
			$("#h").val(selection.height);
			$("#aspectRatio").val(selection.aspectRatio);
		}

	jQuery(document).ready(function($){
	var cropbox = $("#cropbox").imgAreaSelect({ 
		x1: ' . $this->values["x1"] . ', 
		y1: ' . $this->values["y1"] . ', 
		x2: ' . $this->values["x2"] . ', 
		y2: ' . $this->values["y2"] . ', 
		aspectRatio: \'' . implode(":", $this->formVars[aspectratio]) . '\', 
		imageWidth:' . $this->imageWidth . ', imageHeight:' . $this->imageHeight . ', 
		handles: true, fadeSpeed: 200, onInit: preview, onSelectChange: preview, instance: true  
		});
	
		$("#setAR").click(function() {
			var selection = cropbox.getSelection(); 
			var newAR = $("#ratio").val();
			var ratio = newAR.split(":");
			var gcd = ratio[0]/ratio[1];
			cropbox.setOptions({ 
				aspectRatio : newAR,
			});
			var y2 = selection.y1 + selection.width * gcd;
			cropbox.setSelection(selection.x1, selection.y1, selection.x2, parseInt(y2), true);
			cropbox.update();
		});
		
	});
	
	
</script>
</head>
<body>';
		//Image
		$this->template .= '<div id="image">
		<img src="../' . $this->formVars[image] . '" width="' . $this->width . '" height="' . $this->height . '" id="cropbox" alt="croped image" />
	</div>';

		//Values
		$this->template .= '<div id="values"><h2>' . $this->LANG->getLL("editor_title") . '</h2>
		<form name="crop">
		<input type="hidden" name="M" value="tkcropthumbs_crop" />
		<input type="hidden" name="action" value="save" />
		<input type="hidden" name="image" value="' . $this->formVars[image] . '" />
		<input type="hidden" name="uid" value="' . $this->formVars[uid] . '" />
		<input type="hidden" name="aspectratio" value="' . implode(":", $this->formVars[aspectratio]) . '" />
			<fieldset>
				<label for="x1">X1</label><input type="text" size="4" id="x1" name="x1" value="' . $this->values['x1'] . '" />
				<label for="y1">Y1</label><input type="text" size="4" id="y1" name="y1" value="' . $this->values['y1'] . '" /><br />
				<label for="x2">X2</label><input type="text" size="4" id="x2" name="x2" value="' . $this->values['x2'] . '" />
				<label for="y2">Y2</label><input type="text" size="4" id="y2" name="y2" value="' . $this->values['y2'] . '" /><br />
				<label for="w">W&nbsp;</label><input type="text" size="4" id="w" name="w" readonly />
				<label for="h">H&nbsp;</label><input type="text" size="4" id="h" name="h" readonly />
				<label for="aspectratio">' . $this->LANG->getLL("aspectratio") . '</label><br>
				<input type="text" size="10" name="aspectratio" id="ratio" value="' . implode(":", $this->formVars[aspectratio]) . '" />
				<input type="button" id="setAR" value="< Set" />
			</fieldset>
			<input type="submit" value="' . $this->LANG->getLL("save_values") . '" />
		</form>
		<form name="resetSingle">
			<input type="hidden" name="M" value="tkcropthumbs_crop" />
			<input type="hidden" name="action" value="resetSingle" />
			<input type="hidden" name="image" value="' . $this->formVars[image] . '" />
			<input type="hidden" name="uid" value="' . $this->formVars[uid] . '" />
			<input type="hidden" name="aspectratio" value="' . implode(":", $this->formVars[aspectratio]) . '" />
			<input type="submit" value="' . $this->LANG->getLL("reset_single") . '" />
		</form>
		<form name="resetAll">
			<input type="hidden" name="M" value="tkcropthumbs_crop" />
			<input type="hidden" name="action" value="resetAll" />
			<input type="hidden" name="image" value="' . $this->formVars[image] . '" />
			<input type="hidden" name="uid" value="' . $this->formVars[uid] . '" />
			<input type="hidden" name="aspectratio" value="' . implode(":", $this->formVars[aspectratio]) . '" />
			<input type="submit" value="' . $this->LANG->getLL("reset_all") . '" />
		</form>
		<form name="close">
			<input type="submit" value="' . $this->LANG->getLL("close") . '" onClick="self.close();" />
		</form>
	</div>';



		$this->template .= ' </body>
</html>';
		echo $this->template;
	}

	/**
	 * get crop values from database or make new values fro aspect ratio
	 */
	function getValues () {
		$select = 'tx_tkcropthumbs_cropvalues';
		$table = 'tt_content';
		$where = 'uid = ' . $this->formVars[uid];
		$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $table, $where);
		$this->values = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);
		$cropXml = simplexml_load_string(html_entity_decode($this->values['tx_tkcropthumbs_cropvalues']), 'SimpleXMLElement', LIBXML_NOCDATA);
		if ($cropXml) {
			$cropData = $cropXml->xpath('//image[. ="' . $this->formVars[image] . '"]');
			$this->values = $cropData[0];
		}

		if (!$cropData) {
			//if aspect empty
			if (!$this->formVars[aspectratio][0]) {
				$this->formVars[aspectratio][0] = $this->imageWidth;
				$this->formVars[aspectratio][1] = $this->imageHeight;
			}
			$orientation = ($this->imageWidth > $this->imageHeight) ? 'landscape' : 'portrait';
			if (intval($this->imageHeight * ($this->formVars[aspectratio][0] / $this->formVars[aspectratio][1])) > $this->imageWidth) {
				$orientation = 'portrait';
			}

			if ($orientation == 'landscape') {
				$cWidth = intval($this->imageHeight * ($this->formVars[aspectratio][0] / $this->formVars[aspectratio][1]));
				if ($cWidth == 0)
					$cWidth = $this->imageWidth;
				$this->values["x1"] = intval($this->imageWidth / 2 - $cWidth / 2);
				$this->values["y1"] = 0;
				$this->values["x2"] = $this->imageWidth - $this->values["x1"];
				$this->values["y2"] = $this->imageHeight;
			} else if ($orientation == 'portrait') {
				$cHeight = intval($this->imageWidth * ($this->formVars[aspectratio][1] / $this->formVars[aspectratio][0]));
				if ($cHeight == 0)
					$cHeight = $this->imageHeight;
				$this->values["x1"] = 0;
				$this->values["y1"] = intval($this->imageHeight / 2 - $cHeight / 2);
				$this->values["x2"] = $this->imageWidth;
				$this->values["y2"] = $this->imageHeight - $this->values["y1"];
			}
		}
	}

	/**
	 * resize the view of the image in window
	 */
	function resizeImage () {
		$imgsize = getimagesize(PATH_site . $this->formVars[image]);
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

	/**
	 * save values to database
	 */
	function saveValues () {
		$select = 'tx_tkcropthumbs_cropvalues';
		$table = 'tt_content';
		$where = 'uid = ' . $this->formVars[uid];
		$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $table, $where);
		$values = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);
		$cropXml = simplexml_load_string($values['tx_tkcropthumbs_cropvalues']);
		if ($cropXml) {
			$cropData = $cropXml->xpath('//image[. ="' . $this->formVars[image] . '"]');
			if (!$cropData) {
				$cropXml->addChild('image', $this->formVars[image]);
				$cropData = $cropXml->xpath('//image[. ="' . $this->formVars[image] . '"]');
			}
			$values = $cropData[0];
		} else {
			$xml = '<?xml version="1.0" encoding="UTF-8" ?>
				<images>
				  <image x1="0" y1="0" x2="0" y2="0" tstamp="0">' . $this->formVars[image] . '</image>
				</images>';
			$cropXml = simplexml_load_string($xml);
			$cropData = $cropXml->xpath('//image[. ="' . $this->formVars[image] . '"]');
			$values = $cropData[0];
		}



		$values["x1"] = $this->formVars[x1];
		$values["y1"] = $this->formVars[y1];
		$values["x2"] = $this->formVars[x2];
		$values["y2"] = $this->formVars[y2];
		$values["tstamp"] = time();

		$fieldValues = array (
			'tx_tkcropthumbs_cropvalues' => $cropXml->asXML()
		);
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, $where, $fieldValues);

		$this->resizeImage();
		$this->getValues();
		$this->display();
	}

	/**
	 * reset values for single image and update database
	 */
	function resetSingle () {
		$select = 'tx_tkcropthumbs_cropvalues';
		$table = 'tt_content';
		$where = 'uid = ' . $this->formVars[uid];
		$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $table, $where);
		$values = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);
		if (strlen($values['tx_tkcropthumbs_cropvalues']) > 1) {
			$cropXml = simplexml_load_string($values['tx_tkcropthumbs_cropvalues']);
			$cropData = $cropXml->xpath('//image[. ="' . $this->formVars[image] . '"]');
			$this->values = $cropData[0];
			$this->resizeImage();
			$orientation = ($this->imageWidth > $this->imageHeight) ? 'landscape' : 'portrait';
			if (intval($this->imageHeight * ($this->formVars[aspectratio][0] / $this->formVars[aspectratio][1])) > $this->imageWidth) {
				$orientation = 'portrait';
			}

			if ($orientation == 'landscape') {
				$cWidth = intval($this->imageHeight * ($this->formVars[aspectratio][0] / $this->formVars[aspectratio][1]));
				if ($cWidth == 0)
					$cWidth = $this->imageWidth;
				$this->values["x1"] = intval($this->imageWidth / 2 - $cWidth / 2);
				$this->values["y1"] = 0;
				$this->values["x2"] = $this->imageWidth - $this->values["x1"];
				$this->values["y2"] = $this->imageHeight;
			} else if ($orientation == 'portrait') {
				$cHeight = intval($this->imageWidth * ($this->formVars[aspectratio][1] / $this->formVars[aspectratio][0]));
				if ($cHeight == 0)
					$cHeight = $this->imageHeight;
				$this->values["x1"] = 0;
				$this->values["y1"] = intval($this->imageHeight / 2 - $cHeight / 2);
				$this->values["x2"] = $this->imageWidth;
				$this->values["y2"] = $this->imageHeight - $this->values["y1"];
			}

			$fieldValues = array (
				'tx_tkcropthumbs_cropvalues' => $cropXml->asXML()
			);
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, $where, $fieldValues);
			$this->getValues();
			$this->display();
		}
		else {
			$this->resizeImage();
			$this->getValues();
			$this->display();
		}
	}

	/**
	 * reset values for all images in contentelement - set databasefielt to NULL
	 */
	function resetAll () {
		$table = 'tt_content';
		$where = 'uid = ' . $this->formVars[uid];
		$fieldValues = array (
			'tx_tkcropthumbs_cropvalues' => NULL
		);
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, $where, $fieldValues);

		$this->resizeImage();
		$this->getValues();
		$this->display();
	}

}

// Make instance:
$CROP = t3lib_div::makeInstance('tx_tkcropthumbs_crop');
$CROP->init($LANG);
?>
