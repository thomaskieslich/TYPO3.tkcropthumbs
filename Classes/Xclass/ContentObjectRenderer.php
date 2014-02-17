<?php

namespace ThomasKieslich\Tkcropthumbs\Xclass;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

/**
 * Class ContentObjectRenderer
 *
 * @package ThomasKieslich\Tkcropthumbs\Xclass
 */
class ContentObjectRenderer extends \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer {

	/**
	 * @var array
	 */
	protected $fileReferences;

	/**
	 * @var int
	 */
	protected $currentFileReference;

	/**
	 * Creates and returns a TypoScript "imgResource".
	 * The value ($file) can either be a file reference (TypoScript resource) or the string "GIFBUILDER".
	 * In the first case a current image is returned, possibly scaled down or otherwise processed.
	 * In the latter case a GIFBUILDER image is returned; This means an image is made by TYPO3 from layers of elements as GIFBUILDER defines.
	 * In the function IMG_RESOURCE() this function is called like $this->getImgResource($conf['file'], $conf['file.']);
	 *
	 * @param string $file A "imgResource" TypoScript data type. Either a TypoScript file resource or the string GIFBUILDER. See description above.
	 * @param array $fileArray TypoScript properties for the imgResource type
	 * @return array Returns info-array. info[origFile] = original file. [0]/[1] is w/h, [2] is file extension and [3] is the filename.
	 * @see IMG_RESOURCE(), cImage(), \TYPO3\CMS\Frontend\Imaging\GifBuilder
	 * @todo Define visibility
	 */
	public function getImgResource($file, $fileArray) {
		if (!is_array($fileArray)) {
			$fileArray = (array)$fileArray;
		}

		switch ($file) {
			case 'GIFBUILDER':
				$gifCreator = GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Imaging\\GifBuilder');
				$gifCreator->init();
				$theImage = '';
				if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib']) {
					$gifCreator->start($fileArray, $this->data);
					$theImage = $gifCreator->gifBuild();
				}
				$imageResource = $gifCreator->getImageDimensions($theImage);
				break;
			default:
				try {
					if ($fileArray['import.']) {
						$importedFile = trim($this->stdWrap('', $fileArray['import.']));
						if (!empty($importedFile)) {
							$file = $importedFile;
						}
					}

					if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($file)) {
						if (!empty($fileArray['treatIdAsReference'])) {
							$fileObject = $this->getResourceFactory()->getFileReferenceObject($file)->getOriginalFile();
						} else {
							$fileObject = $this->getResourceFactory()->getFileObject($file);
						}
					} elseif (preg_match('/^(0|[1-9][0-9]*):/', $file)) { // combined identifier
						$fileObject = $this->getResourceFactory()->retrieveFileOrFolderObject($file);
					} else {
						if (isset($importedFile) && !empty($importedFile) && !empty($fileArray['import'])) {
							$file = $fileArray['import'] . $file;
						}
						// clean ../ sections of the path and resolve to proper string. This is necessary for the Tx_File_BackwardsCompatibility_TslibContentAdapter to work.
						$file = GeneralUtility::resolveBackPath($file);
						$fileObject = $this->getResourceFactory()->retrieveFileOrFolderObject($file);
					}
				} catch (\TYPO3\CMS\Core\Resource\Exception $exception) {
					/** @var \TYPO3\CMS\Core\Log\Logger $logger */
					$logger = GeneralUtility::makeInstance('TYPO3\CMS\Core\Log\LogManager')->getLogger();
					$logger->warning('The image "' . $file . '" could not be found and won\'t be included in frontend output');
					return NULL;
				}
				if ($fileObject instanceof \TYPO3\CMS\Core\Resource\FileInterface) {
					$processingConfiguration = array();
					$processingConfiguration['width'] = isset($fileArray['width.']) ? $this->stdWrap($fileArray['width'], $fileArray['width.']) : $fileArray['width'];
					$processingConfiguration['height'] = isset($fileArray['height.']) ? $this->stdWrap($fileArray['height'], $fileArray['height.']) : $fileArray['height'];
					$processingConfiguration['fileExtension'] = isset($fileArray['ext.']) ? $this->stdWrap($fileArray['ext'], $fileArray['ext.']) : $fileArray['ext'];
					$processingConfiguration['maxWidth'] = isset($fileArray['maxW.']) ? intval($this->stdWrap($fileArray['maxW'], $fileArray['maxW.'])) : intval($fileArray['maxW']);
					$processingConfiguration['maxHeight'] = isset($fileArray['maxH.']) ? intval($this->stdWrap($fileArray['maxH'], $fileArray['maxH.'])) : intval($fileArray['maxH']);
					$processingConfiguration['minWidth'] = isset($fileArray['minW.']) ? intval($this->stdWrap($fileArray['minW'], $fileArray['minW.'])) : intval($fileArray['minW']);
					$processingConfiguration['minHeight'] = isset($fileArray['minH.']) ? intval($this->stdWrap($fileArray['minH'], $fileArray['minH.'])) : intval($fileArray['minH']);
					$processingConfiguration['noScale'] = isset($fileArray['noScale.']) ? $this->stdWrap($fileArray['noScale'], $fileArray['noScale.']) : $fileArray['noScale'];
					$processingConfiguration['additionalParameters'] = isset($fileArray['params.']) ? $this->stdWrap($fileArray['params'], $fileArray['params.']) : $fileArray['params'];

					// Possibility to cancel/force profile extraction
					// see $TYPO3_CONF_VARS['GFX']['im_stripProfileCommand']
					if (isset($fileArray['stripProfile'])) {
						$processingConfiguration['stripProfile'] = $fileArray['stripProfile'];
					}
					// Check if we can handle this type of file for editing
					if (GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'], $fileObject->getExtension())) {
						$maskArray = $fileArray['m.'];
						// Must render mask images and include in hash-calculating
						// - otherwise we cannot be sure the filename is unique for the setup!
						if (is_array($maskArray)) {
							$processingConfiguration['maskImages']['m_mask'] = $this->getImgResource($maskArray['mask'], $maskArray['mask.']);
							$processingConfiguration['maskImages']['m_bgImg'] = $this->getImgResource($maskArray['bgImg'], $maskArray['bgImg.']);
							$processingConfiguration['maskImages']['m_bottomImg'] = $this->getImgResource($maskArray['bottomImg'], $maskArray['bottomImg.']);
							$processingConfiguration['maskImages']['m_bottomImg_mask'] = $this->getImgResource($maskArray['bottomImg_mask'], $maskArray['bottomImg_mask.']);
						}
						if ($GLOBALS['TSFE']->config['config']['meaningfulTempFilePrefix']) {
							$processingConfiguration['useTargetFileNameAsPrefix'] = 1;
						}

						//tkcropthumbs
//						if (is_array($fileArray) && $fileArray['import.']['current'] == 1) {
							$tkcropthumbs = \ThomasKieslich\Tkcropthumbs\Utility\CalcCrop::getCropValues($this->data, $this->fileReferences);
//							$tkcropthumbs = $this->getCropValues();
//							if ($tkcropthumbs) {
//								$processingConfiguration['tkcropthumbs'] = $tkcropthumbs;
//							}
//						}

						$processedFileObject = $fileObject->process(\TYPO3\CMS\Core\Resource\ProcessedFile::CONTEXT_IMAGECROPSCALEMASK, $processingConfiguration);

						$hash = $processedFileObject->calculateChecksum();
						// store info in the TSFE template cache (kept for backwards compatibility)
						if ($processedFileObject->isProcessed() && !isset($GLOBALS['TSFE']->tmpl->fileCache[$hash])) {
							$GLOBALS['TSFE']->tmpl->fileCache[$hash] = array(
								0 => $processedFileObject->getProperty('width'),
								1 => $processedFileObject->getProperty('height'),
								2 => $processedFileObject->getExtension(),
								3 => $processedFileObject->getPublicUrl(),
								'origFile' => $fileObject->getPublicUrl(),
								'origFile_mtime' => $fileObject->getModificationTime(),
								// This is needed by \TYPO3\CMS\Frontend\Imaging\GifBuilder,
								// in order for the setup-array to create a unique filename hash.
								'originalFile' => $fileObject,
								'processedFile' => $processedFileObject,
								'fileCacheHash' => $hash
							);
						}
						$imageResource = $GLOBALS['TSFE']->tmpl->fileCache[$hash];
					} else {
						$imageResource = NULL;
					}
				}
				break;
		}
		$theImage = $GLOBALS['TSFE']->tmpl->getFileName($file);
		// If image was processed by GIFBUILDER:
		// ($imageResource indicates that it was processed the regular way)
		if (!isset($imageResource) && $theImage) {
			$gifCreator = GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Imaging\\GifBuilder');
			/** @var $gifCreator \TYPO3\CMS\Frontend\Imaging\GifBuilder */
			$gifCreator->init();
			$info = $gifCreator->imageMagickConvert($theImage, 'WEB');
			$info['origFile'] = $theImage;
			// This is needed by \TYPO3\CMS\Frontend\Imaging\GifBuilder, ln 100ff in order for the setup-array to create a unique filename hash.
			$info['origFile_mtime'] = @filemtime($theImage);
			$imageResource = $info;
		}
		// Hook 'getImgResource': Post-processing of image resources
		if (isset($imageResource)) {
			foreach ($this->getGetImgResourceHookObjects() as $hookObject) {
				$imageResource = $hookObject->getImgResourcePostProcess($file, (array)$fileArray, $imageResource, $this);
			}
		}
		return $imageResource;
	}



	/**
	 * Get instance of FAL resource factory
	 * < 6.2 only
	 *
	 * @return \TYPO3\CMS\Core\Resource\ResourceFactory
	 */
	protected function getResourceFactory() {
		return \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance();
	}
}