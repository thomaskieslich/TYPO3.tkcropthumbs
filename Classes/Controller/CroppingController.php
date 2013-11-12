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
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

/**
 * Class Cropping Controller
 */
class CroppingController extends ActionController {

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
	 * @var \ThomasKieslich\Tkcropthumbs\Domain\Repository\ContentRepository
	 *
	 * @inject
	 */
	protected $contentRepository;

	/**
	 * @var \TYPO3\CMS\Core\Resource\FileRepository
	 * @inject
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

		$referenceUid = intval(str_replace('sys_file_', '', htmlspecialchars($getVars['reference'])));

		if (is_int($referenceUid)) {
			//Reference
			$this->referenceObject = $this->fileRepository->findFileReferenceByUid($referenceUid);
			$this->referenceProperties = $this->referenceObject->getProperties();

			$cObj = $this->contentRepository->findByUid($this->referenceProperties[uid_foreign]);
			if ($cObj->getAspectratio()) {
				$this->aspectRatio = GeneralUtility::trimExplode(':', $cObj->getAspectratio(), TRUE);
				$this->fixAr = TRUE;
			}

			$this->resizeImage();

			if (isset($this->referenceProperties['tx_tkcropthumbs_crop'])) {
				$import = json_decode($this->referenceProperties['tx_tkcropthumbs_crop'], TRUE);
			}
			if (isset($import) && count($import) === 4) {
				$this->cropValues = $import;
			} else {
				$this->initValues();
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

//		DebuggerUtility::var_dump($script);

		$this->view->assign('fVars', $this->fVars);
		$this->view->assign('script', $script);
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