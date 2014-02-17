<?php

namespace ThomasKieslich\Tkcropthumbs\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

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
//-geometry 954x476! -colorspace RGB -quality 80 -crop 847x476+53+0
class CalcCrop {

	public static function getCropValues($data, $fileReferences) {
		if (!$fileReferences || $fileReferences['contentUid'] != $data['uid']) {
			$fileReferences = array(
				'contentUid' => $data['uid'],
				'refUid' => GeneralUtility::trimExplode(',', $data['image_fileReferenceUids'])
			);
			$currentFileReference = 0;
		}

		$aspectRatio = GeneralUtility::trimExplode(':', $data['tx_tkcropthumbs_aspectratio']);

		$fileRepository = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\FileRepository');
		$fileObjects = $fileRepository->findByRelation('tt_content', 'image', $data['uid']);

		$currentFile = $fileObjects[$currentFileReference]->getReferenceProperties();

		$cropValues = json_decode($currentFile['tx_tkcropthumbs_crop'], TRUE);

		$tkcropthumbs = array();
		if (count($aspectRatio) === 2) {
			$tkcropthumbs['aspectRatio'] = $aspectRatio;
		}
		if (count($cropValues) === 4) {
			$tkcropthumbs['cropValues'] = $cropValues;
		}

		$currentFileReference++;

//		DebuggerUtility::var_dump($currentFile, 'utility');

		if (!empty($tkcropthumbs)) {
			return $tkcropthumbs;
		}
	}

	public static function scaleImage($image, $cropData = NULL) {
		$gfxConf = $GLOBALS['TYPO3_CONF_VARS']['GFX'];
		if ($gfxConf['im_noScaleUp']) {
			$this->mayScaleUp = 0;
		}

		$info = array($image['fileW'], $image['fileH']);
		$w = intval($this->data['imagewidth']);
		$h = intval($this->data['imageheight']);
		if ($this->settings['image']['maxW']) {
			$options['maxW'] = $this->settings['image']['maxW'];
		}
		if ($this->settings['image']['maxH']) {
			$options['maxH'] = $this->settings['image']['maxH'];
		}
		$options['noScale'] = NULL;

		if ($cropData) {
			$cropValues = json_decode($cropData, TRUE);
			$this->mayScaleUp = 1;
		}

		if ($this->data['tx_tkcropthumbs_aspectratio']) {
			$aspectValues = GeneralUtility::trimExplode(':', $this->data['tx_tkcropthumbs_aspectratio']);
			$this->mayScaleUp = 1;
		}

		if ($cropValues) {
			$cWidth = intval($cropValues['x2'] - $cropValues['x1']);
			$cHeight = intval($cropValues['y2'] - $cropValues['y1']);
			if (!$aspectValues) {
				$aspectValues[0] = $cropValues['x2'] - $cropValues['x1'];
				$aspectValues[1] = $cropValues['y2'] - $cropValues['y1'];
			}
		}

		if ($aspectValues) {
			if ($options['maxW'] && !$w) {
				$w = $options['maxW'];
				$h = intval($options['maxW'] * ($aspectValues[1] / $aspectValues[0])) . 'c';
			}
			if ($options['maxW'] && $w) {
				$w = $w . 'c';
				$options['maxW'] = $w;
				$h = intval($options['maxW'] * ($aspectValues[1] / $aspectValues[0])) . 'c';
			} elseif ($h || $w) {
				$w = intval($h * ($aspectValues[0] / $aspectValues[1])) . 'c';
				$h = $h . 'c';
			}
		}

		$data = \TYPO3\CMS\Core\Imaging\GraphicalFunctions::getImageScale($info, $w, $h, $options);
		$image['geomW'] = $data[0];
		$image['geomH'] = $data[1];

		if ($data['crs']) {
			if (!$data['origW']) {
				$data['origW'] = $data[0];
			}
			if (!$data['origH']) {
				$data['origH'] = $data[1];
			}

			$offsetX = intval(($data[0] - $data['origW']) * ($data['cropH'] + 100) / 200);
			$offsetY = intval(($data[1] - $data['origH']) * ($data['cropV'] + 100) / 200);
			$image['additionalParameters'] = ' -crop ' . $data['origW'] . 'x' . $data['origH'] . '+' . $offsetX . '+' . $offsetY . '! ';
		}

		if ($cropValues) {
			if (!$data['origW']) {
				$data['origW'] = $image['fileW'];
			}
			if (!$data['origH']) {
				$data['origH'] = $image['fileH'];
			}

			$xRatio = $data['origW'] / $cWidth;
			$offsetX1 = intval($cropValues['x1'] * $xRatio);
			$yRatio = $data['origH'] / $cHeight;
			$offsetY1 = intval($cropValues['y1'] * $yRatio);

			$image['additionalParameters'] = ' -crop ' . $data['origW'] . 'x' . $data['origH'] . '+' . $offsetX1 . '+' . $offsetY1 . ' ';
			$image['geomW'] = intval($data['origW'] + ($image['fileW'] - $cWidth) * $xRatio);
			$image['geomH'] = intval($data['origH'] + ($image['fileH'] - $cHeight) * $yRatio);
		}

		if (!$data['origW']) {
			$data['origW'] = $data[0];
		}
		if (!$data['origH']) {
			$data['origH'] = $data[1];
		}

		$image['finalW'] = $data['origW'];
		$image['finalH'] = $data['origH'];

		return $image;
	}
}