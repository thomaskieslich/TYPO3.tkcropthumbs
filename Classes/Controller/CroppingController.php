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
	 * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
	 */
	protected $contentObject;

	/**
	 * @var \TYPO3\CMS\Core\Resource\FileRepository
	 * @inject
	 */
	protected $fileRepository;

	/**
	 * @var Tx_Extbase_Persistence_Manager
	 * @inject
	 */
	protected $persistenceManager;

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

	}

	/**
	 * Show new Window for cropping
	 *
	 * @return void
	 */
	public function showAction(\ThomasKieslich\Tkcropthumbs\Domain\Model\FileReference $referenceObject) {
		$this->referenceObject = $referenceObject->getOriginalResource();
//		$this->referenceObject = $this->fileRepository->findFileReferenceByUid(17);
//		$this->referenceProperties = $this->referenceObject->getProperties();
		$this->contentObject = $this->contentRepository->findByUid($this->referenceObject->getProperty('uid_foreign'));
//		$cObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');
//		$this->configurationManager->getContentObject();

//		$this->aspectRatio = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(':', $cObj->getAspectratio(), TRUE);

//		$cObj->setAspectratio('1234');
//		$this->contentRepository->update($cObj);
		\TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($this->contentObject->getImage(), 'cObj', 12);

		$this->resizeImage();

		if ($this->referenceObject->getProperty('tx_tkcropthumbs_crop')) {
			$import = json_decode($this->referenceObject->getProperty('tx_tkcropthumbs_crop'), TRUE);
		}

		if (isset($import) && count($import) === 6) {
			$this->cropValues = $import;
		} else {
			$this->initValues();
		}

		$this->fVars = array(
			'imgUid' => $this->referenceObject->getUid(),
			'imgName' => $this->referenceObject->getProperty('name'),
			'pubPath' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('tkcropthumbs') . 'Resources/Public/',
			'publicUrl' => $this->referenceObject->getPublicUrl(TRUE),
			'imgHeight' => $this->height,
			'imgWidth' => $this->width,
		);

		$script = '<script>';
		$script .= 'var crop = ' . json_encode($this->cropValues) . ";\n";
		$script .= 'var uid = 13' . ";\n";
		$script .= '</script>';

//		$this->referenceObject->setTitle('title');


		$this->view->assign('fVars', $this->fVars);
		$this->view->assign('script', $script);
	}

	public function saveAction(\ThomasKieslich\Tkcropthumbs\Domain\Model\FileReference $image) {
		\TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($image, 'this');
//		$this->redirect(show,NULL,NULL,array('image'=>17));
	}

	/**
	 * resize the view of the image in window
	 *
	 * @return void
	 */
	protected function resizeImage() {
		if ($this->referenceObject->getProperty('height')) {
			$this->imageHeight = $this->referenceObject->getProperty('height');
		} else {
			$imageSize = getimagesize($this->referenceObject->getPublicUrl(TRUE));
			$this->imageHeight = $imageSize[1];
		}

		if ($this->referenceObject->getProperty('width')) {
			$this->imageWidth = $this->referenceObject->getProperty('width');
		} else {
			$imageSize = getimagesize($this->referenceObject->getPublicUrl(TRUE));
			$this->imageWidth = $imageSize[0];
		}

		$displaySize = 600;
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
			'ar' => implode(':', $this->aspectRatio),
			'x1' => 0,
			'y1' => 0,
			'x2' => $this->width,
			'y2' => $this->height,
			'width' => $this->width,
			'height' => $this->height
		);
	}
}