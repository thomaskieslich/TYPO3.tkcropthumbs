<?php

namespace ThomasKieslich\Tkcropthumbs\Services;

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
use ThomasKieslich\Tkcropthumbs\Hooks\CropScaleHook;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Class Ttcontent
 *
 * @package ThomasKieslich\Tkcropthumbs\Services
 */
class Ttcontent extends CropScaleHook {

	/**
	 * @param $file
	 * @param $fileArray
	 * @param $imageResource
	 * @param ContentObjectRenderer $parent
	 * @return array|null
	 */
	public function init($file, $fileArray, $imageResource, ContentObjectRenderer $parent) {
		$cropData = array();
		if (MathUtility::canBeInterpretedAsInteger($file) && $fileArray['import.']) {
			$cropData = $this->getData($parent);
		}

		if (empty($cropData)) {
			return NULL;
		}
		/** @var ProcessedFile $processedFile */
		$processedFile = $imageResource['processedFile'];
		$processingConfiguration = $processedFile->getProcessingConfiguration();
		$cropParameters = $this->calcCrop($cropData, $processingConfiguration);
		return $cropParameters;
	}

	/**
	 * get crop and aspectRatio Data
	 *
	 * @param ContentObjectRenderer $parent
	 * @return array
	 */
	protected function getData(ContentObjectRenderer $parent) {
		$cropData = array();
		$aspectRatio = GeneralUtility::trimExplode(':', $parent->data['tx_tkcropthumbs_aspectratio']);
		if (count($aspectRatio) === 2) {
			$cropData['aspectRatio'] = $aspectRatio;
		}

		$fileRepository = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\FileRepository');
		/** @var  FileReference $fileReference */
		$fileReference = $fileRepository->findFileReferenceByUid($parent->getCurrentVal());
		$currentFileProperties = $fileReference->getProperties();

		$cropValues = json_decode($currentFileProperties['tx_tkcropthumbs_crop'], TRUE);
		if (count($cropValues) >= 4 && count($cropValues) <= 6) {
			$cropData['cropValues'] = $cropValues;
		}

		if ($cropData) {
			$cropData['originalImage']['width'] = $currentFileProperties['width'];
			$cropData['originalImage']['height'] = $currentFileProperties['height'];
		}

		return $cropData;
	}

	/**
	 * calculate new processing values
	 *
	 * @param array $cropData
	 * @param array $processingConfiguration
	 * @return array mixed
	 */
	protected function calcCrop($cropData, $processingConfiguration) {
		$cropWidth = '';
		$cropHeight = '';
		$srcWidth = '';
		$srcHeight = '';
		$cropParameters = '';

		$width = (int)$processingConfiguration['width'];
		$height = (int)$processingConfiguration['height'];

		$maxWidth = (int)$processingConfiguration['maxWidth'];
		$maxHeight = (int)$processingConfiguration['maxHeight'];

		$fileWidth = $cropData['originalImage']['width'];
		$fileHeight = $cropData['originalImage']['height'];

		if ($maxWidth && $width > $maxWidth) {
			$width = $maxWidth;
		}

		if ($maxHeight && $height > $maxHeight) {
			$height = $maxHeight;
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
			$cropWidth = (int)$cropValues['x2'] - $cropValues['x1'];
			$cropHeight = (int)$cropValues['y2'] - $cropValues['y1'];

			if (!$arValues) {
				$arValues[0] = $cropValues['x2'] - $cropValues['x1'];
				$arValues[1] = $cropValues['y2'] - $cropValues['y1'];
			}
		}

		if ($maxWidth && !$width && !$height) {
			$width = $maxWidth;
			$height = (int)$width * ($arValues[1] / $arValues[0]);
		} elseif ($maxWidth && $width) {
			$height = (int)$width * ($arValues[1] / $arValues[0]);
		} elseif ($height && $width) {
			$width = (int)$height * ($arValues[0] / $arValues[1]);
			if ($maxWidth && $maxWidth <= $width) {
				$width = $maxWidth;
				$height = (int)$width * ($arValues[1] / $arValues[0]);
			}
		}

		//cropping
		if ($cropData['cropValues']) {
			$srcWidth = (int)$fileWidth * $width / $cropWidth;
			$srcHeight = (int)$fileHeight * $height / $cropHeight;

			$offsetX = (int)$cropValues['x1'] * ($width / $cropWidth);
			$offsetY = (int)$cropValues['y1'] * ($height / $cropHeight);

			$cropParameters = ' -crop ' . (int)$width . 'x' . (int)$height . '+' . (int)$offsetX . '+' . (int)$offsetY . ' ';
		}

		//set values
		$processingConfiguration['maxWidth'] = '';
		$processingConfiguration['maxHeight'] = '';

		if (!$cropValues) {
			$processingConfiguration['width'] = (int)$width . 'c';
			$processingConfiguration['height'] = (int)$height . 'c';
		} else {
			$processingConfiguration['width'] = (int)$srcWidth;
			$processingConfiguration['height'] = (int)$srcHeight;
			$processingConfiguration['additionalParameters'] = $cropParameters . $processingConfiguration['additionalParameters'];
		}

		return $processingConfiguration;
	}
}