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
//			DebuggerUtility::var_dump($processingConfiguration, '$processingConfiguration org');
			$cropParameters = $this->calcCrop($cropData, $processingConfiguration, $parent->data);

			$processingConfiguration = $cropParameters;

			$imageResource = $this->processImage($file, $processingConfiguration);
		}

		return $imageResource;
	}

	/**
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
		if (count($cropValues) === 4) {
			$cropData['cropValues'] = $cropValues;
		}

		if ($cropData) {
			$cropData['originalImage']['width'] = $currentFileProperties['width'];
			$cropData['originalImage']['height'] = $currentFileProperties['height'];
		}

		$this->currentFileObject++;

		return $cropData;
	}

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

//		DebuggerUtility::var_dump($processingConfiguration, '$processingConfiguration new');

//		$gifCreator = GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Imaging\\GifBuilder');
//		$gifCreator->init();
//		$gifCreator->mayScaleUp = 1;
//		$info = array($fileWidth, $fileHeight);
//		$scaleData = $gifCreator->getImageScale($info, $width, $height, $options);

//		DebuggerUtility::var_dump($width, '$width');
//		DebuggerUtility::var_dump($height, '$height');

		//-geometry 847x565! -colorspace RGB -quality 80 -crop 847x476+0+44!
		//'-geometry 1969x1311! -colorspace RGB -quality 80 -crop 847x564+937+432  '
		//'-geometry 2363x1852!  -crop 847x468+729+663  '

//			$w = intval($w);
//			$h = intval($h);
//
//			$srcWidth = intval($fileWidth * $w / $cropWidth);
//			$srcHeight = intval($fileHeight * $h / $cropHeight);
//
//			$xRatio = $fileWidth / $w;
//			$offsetX = intval($cropValues['x1'] * $xRatio);
//			$yRatio = $fileHeight / $h;
//			$offsetY = intval($cropValues['y1'] * $yRatio);
//
//			$cropParameters = ' -crop ' . $w . 'x' . $h . '+' . $offsetX . '+' . $offsetY . ' ';

//			$srcWidth = intval($scaleData['origW'] + ($cropData['originalImage']['width'] - $cropWidth) * $xRatio);

//			$srcHeight = intval($scaleData['origH'] + ($cropData['originalImage']['height'] - $cropHeight) * $yRatio);

//			DebuggerUtility::var_dump($xRatio, '$xRatio');
//			DebuggerUtility::var_dump($offsetX, '$offsetX');
//			DebuggerUtility::var_dump($w, 'w');

//			$graphicalFunctions = GeneralUtility::makeInstance('TYPO3\CMS\Core\Imaging\GraphicalFunctions');
//			$this->mayScaleUp = 0;
//			$scaleData = $graphicalFunctions::getImageScale($info, $w, $h, $options = NULL);
//			$scaleData = \TYPO3\CMS\Core\Imaging\GraphicalFunctions::getImageScale($info, $w, $h, $options = NULL);
//			$dims = $gifCreator->getImageScale($gifCreator->getImageDimensions($imageFile), $conf['width'], $conf['height'], array());

//			$gifCreator = GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Imaging\\GifBuilder');
//			$gifCreator->init();
//			$gifCreator->mayScaleUp = 0;
//			$info = array($cropData['originalImage']['width'], $cropData['originalImage']['height']);
//			$scaleData = $gifCreator->getImageScale($info, $w, $h, array());

//			if (!$scaleData['origW']) {
//				$scaleData['origW'] = $scaleData[0];
//			}
//			if (!$scaleData['origH']) {
//				$scaleData['origH'] = $scaleData[1];
//			}
//
//			if ($scaleData['crs']) {
//				$offsetX = intval(($scaleData[0] - $scaleData['origW']) * ($scaleData['cropH'] + 100) / 200);
//				$offsetY = intval(($scaleData[1] - $scaleData['origH']) * ($scaleData['cropV'] + 100) / 200);
//				$image['additionalParameters'] = ' -crop ' . $scaleData['origW'] . 'x' . $scaleData['origH'] . '+' . $offsetX . '+' . $offsetY . '! ';
//			}

//		DebuggerUtility::var_dump($processingConfiguration, '$processingConfiguration');

		return $processingConfiguration;
	}

	/**
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