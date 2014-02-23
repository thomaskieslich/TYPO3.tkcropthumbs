<?php

namespace ThomasKieslich\Tkcropthumbs\Hooks;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Thomas Kieslich
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
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectGetImageResourceHookInterface;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Class CropScaleHook
 *
 * @package ThomasKieslich\Tkcropthumbs\Hooks
 */
class CropScaleHook implements ContentObjectGetImageResourceHookInterface {

	var $currentFileObject;

	var $contentUid;

	/**
	 * Hook for post-processing image resources
	 *
	 * @param string $file Original image file
	 * @param array $configuration TypoScript getImgResource properties
	 * @param array $imageResource Information of the created/converted image resource
	 * @param ContentObjectRenderer $parent Parent content object
	 * @return array Modified image resource information
	 */
	public function getImgResourcePostProcess($file, array $configuration, array $imageResource, ContentObjectRenderer $parent) {
		if (!$this->contentUid || $this->contentUid != $parent->data['uid']) {
			$this->contentUid = $parent->data['uid'];
			$this->currentFileObject = 0;
		}

		$cropData = $this->getData($parent->data);
		if (!$cropData) {
			return $imageResource;
		} else {
			$processingConfiguration = $imageResource['processedFile']->getProcessingConfiguration();
			$cropParameters = $this->calcCrop($cropData, $processingConfiguration, $parent->data);

			$processingConfiguration = $cropParameters;

			$imageResource = $this->processImage($file, $processingConfiguration);
		}

		return $imageResource;
	}

	/**
	 * get crop and aspectRatio Data
	 *
	 * @param $data
	 * @return array
	 */
	protected function getData($data) {
		$cropData = array();

		$aspectRatio = GeneralUtility::trimExplode(':', $data['tx_tkcropthumbs_aspectratio']);
		if (count($aspectRatio) === 2) {
			$cropData['aspectRatio'] = $aspectRatio;
		}

		$fileRepository = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\FileRepository');
		$fileObjects = $fileRepository->findByRelation('tt_content', 'image', $data['uid']);
		$currentFileReference = $fileObjects[$this->currentFileObject]->getReferenceProperties();
		$currentFileProperties = $fileObjects[$this->currentFileObject]->getProperties();

		$cropValues = json_decode($currentFileReference['tx_tkcropthumbs_crop'], TRUE);
		if (count($cropValues) >= 4 && count($cropValues) <= 6) {
			$cropData['cropValues'] = $cropValues;
		}

		if ($cropData) {
			$cropData['originalImage']['width'] = $currentFileProperties['width'];
			$cropData['originalImage']['height'] = $currentFileProperties['height'];
		}

		$this->currentFileObject++;

		return $cropData;
	}

	/**
	 * calculate new processing values
	 *
	 * @param array $cropData
	 * @param array $processingConfiguration
	 * @param array $data
	 * @return array mixed
	 */
	public static function calcCrop($cropData, $processingConfiguration, $data) {
		$width = intval($data['imagewidth']);
		$height = intval($data['imageheight']);

		$fileWidth = $cropData['originalImage']['width'];
		$fileHeight = $cropData['originalImage']['height'];

		if ($processingConfiguration['maxWidth']) {
			$maxWidth = $processingConfiguration['maxWidth'];
		} elseif (!$processingConfiguration['maxWidth'] && $processingConfiguration['width']) {
			$maxWidth = $processingConfiguration['width'];
		}
		if ($width > $maxWidth) {
			$width = $maxWidth;
		}

		if ($cropData['cropValues']) {
			$cropValues = $cropData['cropValues'];
		} else {
			$cropValues = array();
		}

		if ($cropData['aspectRatio']) {
			$arValues = $cropData['aspectRatio'];
		} else {
			$arValues = array();
		}

		if ($cropValues) {
			$cropWidth = intval($cropValues['x2'] - $cropValues['x1']);
			$cropHeight = intval($cropValues['y2'] - $cropValues['y1']);

			if (!$arValues) {
				$arValues[0] = $cropValues['x2'] - $cropValues['x1'];
				$arValues[1] = $cropValues['y2'] - $cropValues['y1'];
			}
		}

		if ($maxWidth && !$width && !$height) {
			$width = $maxWidth;
			$height = intval($width * ($arValues[1] / $arValues[0]));
		}

		if ($maxWidth && $width) {
			$width = $width;
			$height = intval($width * ($arValues[1] / $arValues[0]));
		} elseif ($height || $width) {
			$width = intval($height * ($arValues[0] / $arValues[1]));
			$height = $height;
			if ($maxWidth <= $width) {
				$width = $maxWidth;
				$height = intval($width * ($arValues[1] / $arValues[0]));
			}
		}

		//cropping
		if ($cropData['cropValues']) {
			$srcWidth = intval($fileWidth * $width / $cropWidth);
			$srcHeight = intval($fileHeight * $height / $cropHeight);

			$offsetX = intval($cropValues['x1'] * ($width / $cropWidth));
			$offsetY = intval($cropValues['y1'] * ($height / $cropHeight));

			$cropParameters = ' -crop ' . $width . 'x' . $height . '+' . $offsetX . '+' . $offsetY . ' ';
		}

		//set values
		$processingConfiguration['maxWidth'] = '';
		$processingConfiguration['maxHeight'] = '';

		if (!$cropValues) {
			$processingConfiguration['width'] = $width . 'c';
			$processingConfiguration['height'] = $height . 'c';
		} else {
			$processingConfiguration['width'] = $srcWidth;
			$processingConfiguration['height'] = $srcHeight;
			$processingConfiguration['additionalParameters'] = $cropParameters . $processingConfiguration['additionalParameters'];
		}

		return $processingConfiguration;
	}

	/**
	 * process the image like ContentObjectRenderer
	 *
	 * @param $file
	 * @param $processingConfiguration
	 * @return mixed
	 */
	protected function processImage($file, $processingConfiguration) {
		$file = GeneralUtility::resolveBackPath($file);
		$fileObject = ResourceFactory::getInstance()->retrieveFileOrFolderObject($file);

		$processedFileObject = $fileObject->process(ProcessedFile::CONTEXT_IMAGECROPSCALEMASK, $processingConfiguration);

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
		return $GLOBALS['TSFE']->tmpl->fileCache[$hash];
	}
}