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

/**
 * Class Cropping Controller
 */
class CroppingController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {

	/**
	 * @var array
	 */
	protected $getVars;

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
	 * @var array
	 */
	protected $aspectRatio;

	/**
	 * @var array
	 */
	protected $cropValues;

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
	 *
	 * @throws \RuntimeException
	 * @return void
	 */
	public function initializeAction() {
		$this->getVars = \TYPO3\CMS\Core\Utility\GeneralUtility::_GET();

		$referenceUid = intval(str_replace('sys_file_', '', htmlspecialchars($this->getVars['image'])));

		if (is_int($referenceUid)) {
			//Reference
			$this->referenceObject = $this->fileRepository->findFileReferenceByUid($referenceUid);
			$this->referenceProperties = $this->referenceObject->getProperties();
			$cObj = $this->contentRepository->findByUid($this->referenceProperties[uid_foreign]);
			$this->aspectRatio = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(':', $cObj->getAspectratio(), TRUE);

			$this->resizeImage();

			if (isset($this->referenceProperties['tx_tkcropthumbs_crop'])) {
				$import = json_decode($this->referenceProperties['tx_tkcropthumbs_crop'], TRUE);
			}

			if (isset($import) && count($import) === 6) {
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
		$this->fVars = array(
			'imgName' => $this->referenceProperties['name'],
			'pubPath' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('tkcropthumbs') . 'Resources/Public/',
			'publicUrl' => $this->referenceObject->getPublicUrl(TRUE),
			'imgHeight' => $this->height,
			'imgWidth' => $this->width,
			'values' => json_encode($this->cropValues)
		);

//		\TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($this->fVars, 'this', 12);
		$this->view->assign('fVars', $this->fVars);
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
//		if (intval($this->imageHeight * ($this->aspectRatio[0] / $this->aspectRatio[1])) > $this->imageWidth) {
//			$orientation = 'portrait';
//		}

		if ($orientation == 'landscape') {
			$cWidth = intval($this->imageHeight * ($this->formVars[aspectratio][0] / $this->formVars[aspectratio][1]));
			if ($cWidth == 0) {
				$cWidth = $this->imageWidth;
			}
			$this->values['x1'] = intval($this->imageWidth / 2 - $cWidth / 2);
			$this->values['y1'] = 0;
			$this->values['x2'] = $this->imageWidth - $this->values['x1'];
			$this->values['y2'] = $this->imageHeight;
		} elseif ($orientation == 'portrait') {
			$cHeight = intval($this->imageWidth * ($this->formVars[aspectratio][1] / $this->formVars[aspectratio][0]));
			if ($cHeight == 0) {
				$cHeight = $this->imageHeight;
			}
			$this->values['x1'] = 0;
			$this->values['y1'] = intval($this->imageHeight / 2 - $cHeight / 2);
			$this->values['x2'] = $this->imageWidth;
			$this->values['y2'] = $this->imageHeight - $this->values['y1'];
		}

		$this->cropValues = array(
			'ar' => (string)$this->aspectRatio,
			'x1' => 0,
			'y1' => 0,
			'x2' => $this->width,
			'y2' => $this->height,
			'width' => $this->width,
			'height' => $this->height
		);
//		\TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($orientation, 'crop');
	}
}