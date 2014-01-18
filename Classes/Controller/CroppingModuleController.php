<?php
namespace ThomasKieslich\Tkcropthumbs\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Thomas Kieslich
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
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
 ***************************************************************/
use ThomasKieslich\Tkcropthumbs\Domain;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Cropping Controller
 */
class CroppingModuleController {

	/**
	 * @var array
	 */
	protected $formVars = array();

	/**
	 * @var object
	 */
	protected $referenceObject;

	/**
	 * @var array
	 */
	protected $referenceProperties;

	/**
	 * @var \TYPO3\CMS\Core\Resource\FileRepository
	 */
	protected $fileRepository;

	/**
	 * @var string
	 */
	protected $aspectRatio;

	/**
	 * @var int
	 */
	protected $imageWidth;

	/**
	 * @var int
	 */
	protected $imageHeight;

	/**
	 * @var int
	 */
	protected $height;

	/**
	 * @var int
	 */
	protected $width;

	/**
	 * @var array
	 */
	protected $fVars;

	/**
	 * @var array
	 */
	protected $cropValues;

	/**
	 * @var boolean
	 */
	protected $fixAr;

	/**
	 *
	 * @throws \RuntimeException
	 * @return void
	 */
	public function initializeAction() {
		$getVars = GeneralUtility::_GET();
		$this->fileRepository = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\FileRepository');

		$referenceUid = intval(str_replace('sys_file_', '', htmlspecialchars($getVars['reference'])));

		if (is_int($referenceUid)) {
			//Reference
			$this->referenceObject = $this->fileRepository->findFileReferenceByUid($referenceUid);
			$this->referenceProperties = $this->referenceObject->getProperties();

			//cObj
			$selectFields = 'uid, tx_tkcropthumbs_aspectratio';
			$fromTable = 'tt_content';
			$whereClause = 'uid = ' . $this->referenceProperties[uid_foreign];
			$whereClause .= ' AND hidden=0 AND deleted=0';

			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($selectFields, $fromTable, $whereClause);
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);

			if ($row['tx_tkcropthumbs_aspectratio']) {
				$this->aspectRatio = GeneralUtility::trimExplode(':', $row['tx_tkcropthumbs_aspectratio'], TRUE);
				$this->fixAr = TRUE;
			}

			$this->resizeImage();

			if (isset($this->referenceProperties['tx_tkcropthumbs_crop'])) {
				$import = json_decode($this->referenceProperties['tx_tkcropthumbs_crop'], TRUE);
			}
			if (isset($import) && count($import) === 4) {
				$this->cropValues = $import;
				$this->showAction();
			} else {
				$this->initValues();
				$this->showAction();
			}
		}
	}

	/**
	 * Show new Window for cropping
	 *
	 * @return void
	 */
	public function showAction() {
		$this->resizeImage();

		$this->fVars = array(
			'imgUid' => $this->referenceProperties['uid'],
			'imgName' => $this->referenceProperties['name'],
			'pubPath' => ExtensionManagementUtility::extRelPath('tkcropthumbs') . 'Resources/Public/',
			'publicUrl' => $this->referenceObject->getPublicUrl(TRUE),
			'imgHeight' => $this->height,
			'imgWidth' => $this->width,
			'ar' => implode(':', $this->aspectRatio),
			'fixAr' => $this->fixAr
		);

		$script = "
	<script>
		var uid = " . $this->referenceProperties['uid'] . ";

		var imgOrg = [" . $this->cropValues['x1'] . "," . $this->cropValues['y1'] . "," . $this->cropValues['x2'] . " ," . $this->cropValues['y2'] . "];

		$(function () {
			var cropbox = $('img#cropbox') . imgAreaSelect({
				x1: " . $this->cropValues['x1'] . ",
				y1: " . $this->cropValues['y1'] . ",
				x2: " . $this->cropValues['x2'] . " ,
				y2: " . $this->cropValues['y2'] . ",
				imageWidth: " . $this->imageWidth . ",
				imageHeight: " . $this->imageHeight . ",
				aspectRatio: '" . implode(':', $this->aspectRatio) . "',
				handles: true,
				fadeSpeed: 200,
				onInit: preview,
				onSelectChange: preview,
				instance: true
			});

			$('#setAR').click(function () {
				cropbox . setOptions({
					aspectRatio: $('#aspectRatio') . val()
				});
				cropbox.update();
			});
		});
	</script > ";

		$arField = '';
		if ($this->fVars['fixAr']) {
			$arField = '<input type="text" id="aspectRatio" name="aspectRatio" value="' . $this->fVars['ar'] . '" readonly="">';
		} else {
			$arField = '
				<input type="text" id="aspectRatio" name="aspectRatio">
				<input type="button" id="setAR" value="Set AR"/>
			';
		}

		$html = '
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<title>Cropping Image</title>
		<link rel="stylesheet" href="' . $this->fVars['pubPath'] . 'Css/extension.min.css">
	</head>
	<body>
		<div id="image">
			<figure>
				<img src="' . $this->fVars['publicUrl'] . '" id="cropbox" alt="' . $this->fVars['imgName'] . '" style="width:' . $this->fVars['imgWidth'] . 'px; height:' . $this->fVars['imgHeight'] . 'px">
			</figure>
		</div>

		<div id="values">
			<h2>' . $GLOBALS['LANG']->getLL('editor.title') . '</h2>

			<form id="edit">
				<fieldset>
					<div class="formRow">
						<label for="x1">X1</label>
						<input type="text" id="x1">
						<label for="y1">Y1</label>
						<input type="text" id="y1">
					</div>
					<div class="formRow">
						<label for="x2">X2</label>
						<input type="text" id="x2">
						<label for="y2">Y2</label>
						<input type="text" id="y2">
					</div>
					<div class="formRow">
						<label for="w">W</label>
						<input type="text" id="w" readonly>
						<label for="h">H</label>
						<input type="text" id="h" readonly>
					</div>
					<div class="formRow">
						<label for="aspectRatio">' . $GLOBALS['LANG']->getLL('editor.aspectratio') . '</label>
						' . $arField . '
					</div>
				</fieldset>
			</form>

			<div id="controller">
				<div id="resetSingle" class="btn">' . $GLOBALS['LANG']->getLL('editor.reset.single') . '</div>
				<div id="save" class="btn">' . $GLOBALS['LANG']->getLL('editor.save') . '</div>
				<div id="close" class="btn">' . $GLOBALS['LANG']->getLL('editor.close') . '</div>
			</div>
		</div>
		<script src="' . $this->fVars['pubPath'] . 'Js/jquery-1.10.2.min.js"></script>
		<script src="' . $this->fVars['pubPath'] . 'Js/jquery.imgareaselect-0.9.10.min.js"></script>
		' . $script . '
		<script src="' . $this->fVars['pubPath'] . 'Js/extension.min.js"></script>
	</body>
</html>
		';

		echo $html;
	}

	/**
	 * resize the view of the image in window
	 *
	 * @return void
	 */
	protected function resizeImage() {
		if ($this->referenceProperties['height']) {
			$this->imageHeight = $this->referenceProperties['height'];
		} else {
			$imageSize = getimagesize($this->referenceObject->getPublicUrl(TRUE));
			$this->imageHeight = $imageSize[1];
		}

		if ($this->referenceProperties['width']) {
			$this->imageWidth = $this->referenceProperties['width'];
		} else {
			$imageSize = getimagesize($this->referenceObject->getPublicUrl(TRUE));
			$this->imageWidth = $imageSize[0];
		}

		$displaySize = 500;
		if (($this->imageWidth > $displaySize) || ($this->imageHeight > $displaySize)) {
			if ($this->imageHeight > $this->imageWidth) {
				$this->height = $displaySize;
				$this->width = intval($this->imageWidth * $displaySize / $this->imageHeight);
			} else {
				$this->width = $displaySize;
				$this->height = intval($this->imageHeight * $displaySize / $this->imageWidth);
			}
		}
	}

	/**
	 * @return void
	 */
	protected function initValues() {

		if (!$this->aspectRatio) {
			$this->aspectRatio[0] = $this->imageWidth;
			$this->aspectRatio[1] = $this->imageHeight;
		}

		$orientation = ($this->imageWidth > $this->imageHeight) ? 'landscape' : 'portrait';
		if (intval($this->imageHeight * ($this->aspectRatio[0] / $this->aspectRatio[1])) > $this->imageWidth) {
			$orientation = 'portrait';
		}

		if ($orientation == 'landscape') {
			$cWidth = intval($this->imageHeight * ($this->aspectRatio[0] / $this->aspectRatio[1]));
			if ($cWidth == 0) {
				$cWidth = $this->imageWidth;
			}
			$this->cropValues['x1'] = intval($this->imageWidth / 2 - $cWidth / 2);
			$this->cropValues['y1'] = 0;
			$this->cropValues['x2'] = $this->imageWidth - $this->cropValues['x1'];
			$this->cropValues['y2'] = $this->imageHeight;
		} elseif ($orientation == 'portrait') {
			$cHeight = intval($this->imageWidth * ($this->aspectRatio[1] / $this->aspectRatio[0]));
			if ($cHeight == 0) {
				$cHeight = $this->imageHeight;
			}
			$this->cropValues['x1'] = 0;
			$this->cropValues['y1'] = intval($this->imageHeight / 2 - $cHeight / 2);
			$this->cropValues['x2'] = $this->imageWidth;
			$this->cropValues['y2'] = $this->imageHeight - $this->cropValues['y1'];
		}
	}
}