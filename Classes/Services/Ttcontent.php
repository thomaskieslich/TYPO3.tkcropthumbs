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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Ttcontent
 *
 * @package ThomasKieslich\Tkcropthumbs\Services
 */
class Ttcontent extends CropScaleHook {

	/**
	 * @var int
	 */
	protected $currentFileObject;

	/**
	 * @var array
	 */
	protected $cropData;

	/**
	 * @param $file
	 * @param $conf
	 * @param $imageResource
	 * @param $parent
	 * @param $cropPopUp
	 * @return array|null
	 */
	public function init($file, $conf, $imageResource, $parent, $cropPopUp = NULL) {

		if (!$this->currentFileObject) {
			$this->currentFileObject = 0;
		}

		if ($conf['import.']['current'] == 1) {
			$this->cropData = $this->getData($parent->data);
		} elseif (!$cropPopUp) {
			return NULL;
		}

		if (!$this->cropData) {
			return NULL;
		} else {
			$processingConfiguration = $imageResource['processedFile']->getProcessingConfiguration();
			$cropParameters = $this->calcCrop($this->cropData, $processingConfiguration);

			return $cropParameters;
		}

		return NULL;
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
	 * @return array mixed
	 */
	protected function calcCrop($cropData, $processingConfiguration) {
		$width = intval($processingConfiguration['width']);
		$height = intval($processingConfiguration['height']);

		$maxWidth = intval($processingConfiguration['maxWidth']);
		$maxHeight = intval($processingConfiguration['maxHeight']);

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
		} elseif ($maxWidth && $width) {
			$width = $width;
			$height = intval($width * ($arValues[1] / $arValues[0]));
		} elseif ($height && $width) {
			$width = intval($height * ($arValues[0] / $arValues[1]));
			if ($maxWidth && $maxWidth <= $width) {
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
}