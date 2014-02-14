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
use TYPO3\CMS\Core\Resource;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;
use TYPO3\CMS\Core\Utility;

/**
 * Class LocalCropScaleMaskHelper
 *
 * @package ThomasKieslich\Tkcropthumbs\Xclass
 */
class LocalCropScaleMaskHelper extends \TYPO3\CMS\Core\Resource\Processing\LocalCropScaleMaskHelper {

	/**
	 * This method actually does the processing of files locally
	 *
	 * Takes the original file (for remote storages this will be fetched from the remote server),
	 * does the IM magic on the local server by creating a temporary typo3temp/ file,
	 * copies the typo3temp/ file to the processing folder of the target storage and
	 * removes the typo3temp/ file.
	 *
	 * @param TaskInterface $task
	 * @return array
	 */
	public function process(TaskInterface $task) {
		$result = NULL;
		$targetFile = $task->getTargetFile();
		$sourceFile = $task->getSourceFile();

		$originalFileName = $sourceFile->getForLocalProcessing(FALSE);
		/** @var $gifBuilder \TYPO3\CMS\Frontend\Imaging\GifBuilder */
		$gifBuilder = Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Imaging\\GifBuilder');
		$gifBuilder->init();
		$gifBuilder->absPrefix = PATH_site;

		$configuration = $targetFile->getProcessingConfiguration();
		$configuration['additionalParameters'] = $this->modifyImageMagickStripProfileParameters($configuration['additionalParameters'], $configuration);

		if (empty($configuration['fileExtension'])) {
			$configuration['fileExtension'] = $task->getTargetFileExtension();
		}

		$options = $this->getConfigurationForImageCropScaleMask($targetFile, $gifBuilder);

		// Normal situation (no masking)
		if (!(is_array($configuration['maskImages']) && $GLOBALS['TYPO3_CONF_VARS']['GFX']['im'])) {
			// the result info is an array with 0=width,1=height,2=extension,3=filename
			$result = $gifBuilder->imageMagickConvert(
				$originalFileName,
				$configuration['fileExtension'],
				$configuration['width'],
				$configuration['height'],
				$configuration['additionalParameters'],
				$configuration['frame'],
				$options,
				FALSE,
				$configuration['tkcropthumbs']
			);
		} else {
			$targetFileName = $this->getFilenameForImageCropScaleMask($task);
			$temporaryFileName = $gifBuilder->tempPath . $targetFileName;
			$maskImage = $configuration['maskImages']['maskImage'];
			$maskBackgroundImage = $configuration['maskImages']['backgroundImage'];
			if ($maskImage instanceof Resource\FileInterface && $maskBackgroundImage instanceof Resource\FileInterface) {
				$temporaryExtension = 'png';
				if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['im_mask_temp_ext_gif']) {
					// If ImageMagick version 5+
					$temporaryExtension = $gifBuilder->gifExtension;
				}
				$tempFileInfo = $gifBuilder->imageMagickConvert(
					$originalFileName,
					$temporaryExtension,
					$configuration['width'],
					$configuration['height'],
					$configuration['additionalParameters'],
					$configuration['frame'],
					$options,
					FALSE,
					$configuration['tkcropthumbs']
				);
				if (is_array($tempFileInfo)) {
					$maskBottomImage = $configuration['maskImages']['maskBottomImage'];
					if ($maskBottomImage instanceof $maskBottomImage) {
						$maskBottomImageMask = $configuration['maskImages']['maskBottomImageMask'];
					} else {
						$maskBottomImageMask = NULL;
					}

					//	Scaling:	****
					$tempScale = array();
					$command = '-geometry ' . $tempFileInfo[0] . 'x' . $tempFileInfo[1] . '!';
					$command = $this->modifyImageMagickStripProfileParameters($command, $configuration);
					$tmpStr = $gifBuilder->randomName();
					//	m_mask
					$tempScale['m_mask'] = $tmpStr . '_mask.' . $temporaryExtension;
					$gifBuilder->imageMagickExec($maskImage->getForLocalProcessing(TRUE), $tempScale['m_mask'], $command);
					//	m_bgImg
					$tempScale['m_bgImg'] = $tmpStr . '_bgImg.miff';
					$gifBuilder->imageMagickExec($maskBackgroundImage->getForLocalProcessing(), $tempScale['m_bgImg'], $command);
					//	m_bottomImg / m_bottomImg_mask
					if ($maskBottomImage instanceof Resource\FileInterface && $maskBottomImageMask instanceof Resource\FileInterface) {
						$tempScale['m_bottomImg'] = $tmpStr . '_bottomImg.' . $temporaryExtension;
						$gifBuilder->imageMagickExec($maskBottomImage->getForLocalProcessing(), $tempScale['m_bottomImg'], $command);
						$tempScale['m_bottomImg_mask'] = ($tmpStr . '_bottomImg_mask.') . $temporaryExtension;
						$gifBuilder->imageMagickExec($maskBottomImageMask->getForLocalProcessing(), $tempScale['m_bottomImg_mask'], $command);
						// BEGIN combining:
						// The image onto the background
						$gifBuilder->combineExec($tempScale['m_bgImg'], $tempScale['m_bottomImg'], $tempScale['m_bottomImg_mask'], $tempScale['m_bgImg']);
					}
					// The image onto the background
					$gifBuilder->combineExec($tempScale['m_bgImg'], $tempFileInfo[3], $tempScale['m_mask'], $temporaryFileName);
					// Unlink the temp-images...
					foreach ($tempScale as $tempFile) {
						if (@is_file($tempFile)) {
							unlink($tempFile);
						}
					}
				}
				$result = $tempFileInfo;
			}
		}
		// check if the processing really generated a new file
		if ($result !== NULL) {
			if ($result[3] !== $originalFileName) {
				$result = array(
					'width' => $result[0],
					'height' => $result[1],
					'filePath' => $result[3],
				);
			} else {
				// No file was generated
				$result = NULL;
			}
		}

		return $result;
	}
} 