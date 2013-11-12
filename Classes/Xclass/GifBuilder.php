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
 * Class GifBuilder
 *
 * @package ThomasKieslich\Tkcropthumbs\Xclass
 */
class GifBuilder extends \TYPO3\CMS\Frontend\Imaging\GifBuilder {

	/***********************************
	 *
	 * Scaling, Dimensions of images
	 *
	 ***********************************/
	/**
	 * Converts $imagefile to another file in temp-dir of type $newExt (extension).
	 *
	 * @param string $imagefile The image filepath
	 * @param string $newExt New extension, eg. "gif", "png", "jpg", "tif". If $newExt is NOT set, the new imagefile will be of the original format. If newExt = 'WEB' then one of the web-formats is applied.
	 * @param string $w Width. $w / $h is optional. If only one is given the image is scaled proportionally. If an 'm' exists in the $w or $h and if both are present the $w and $h is regarded as the Maximum w/h and the proportions will be kept
	 * @param string $h Height. See $w
	 * @param string $params Additional ImageMagick parameters.
	 * @param string $frame Refers to which frame-number to select in the image. '' or 0 will select the first frame, 1 will select the next and so on...
	 * @param array $options An array with options passed to getImageScale (see this function).
	 * @param boolean $mustCreate If set, then another image than the input imagefile MUST be returned. Otherwise you can risk that the input image is good enough regarding messures etc and is of course not rendered to a new, temporary file in typo3temp/. But this option will force it to.
	 * @param array $tkcropthumbs
	 * @return array [0]/[1] is w/h, [2] is file extension and [3] is the filename.
	 * @see getImageScale(), typo3/show_item.php, fileList_ext::renderImage(), \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::getImgResource(), SC_tslib_showpic::show(), maskImageOntoImage(), copyImageOntoImage(), scale()
	 * @todo Define visibility
	 */
	public function imageMagickConvert($imagefile, $newExt = '', $w = '', $h = '', $params = '', $frame = '', $options = array(), $mustCreate = FALSE, $tkcropthumbs = NULL) {
		if ($this->NO_IMAGE_MAGICK) {
			// Returning file info right away
			return $this->getImageDimensions($imagefile);
		}
		if ($info = $this->getImageDimensions($imagefile)) {
			$newExt = strtolower(trim($newExt));
			// If no extension is given the original extension is used
			if (!$newExt) {
				$newExt = $info[2];
			}
			if ($newExt == 'web') {
				if (GeneralUtility::inList($this->webImageExt, $info[2])) {
					$newExt = $info[2];
				} else {
					$newExt = $this->gif_or_jpg($info[2], $info[0], $info[1]);
					if (!$params) {
						$params = $this->cmds[$newExt];
					}
				}
			}
			if (GeneralUtility::inList($this->imageFileExt, $newExt)) {
				if (strstr($w . $h, 'm')) {
					$max = 1;
				} else {
					$max = 0;
				}

				//tkcropthumbs
				if ($tkcropthumbs && $tkcropthumbs['cropValues']) {
					$cropValues = $tkcropthumbs['cropValues'];
					$cWidth = intval($cropValues['x2'] - $cropValues['x1']);
					$cHeight = intval($cropValues['y2'] - $cropValues['y1']);

					if (!$tkcropthumbs['aspectRatio']) {
						$tkcropthumbs['aspectRatio'][0] = $cropValues['x2'] - $cropValues['x1'];
						$tkcropthumbs['aspectRatio'][1] = $cropValues['y2'] - $cropValues['y1'];
					}
				}

				if ($tkcropthumbs && $tkcropthumbs['aspectRatio']) {
					$aspect = $tkcropthumbs['aspectRatio'];
					if ($options['maxW'] && !$w) {
						$w = $options['maxW'];
						$h = intval($options['maxW'] * ($aspect[1] / $aspect[0])) . 'c';
					}
					if ($options['maxW'] && $w) {
						$w = $w . 'c';
						$options['maxW'] = $w;
						$h = intval($options['maxW'] * ($aspect[1] / $aspect[0])) . 'c';
					} elseif ($h || $w) {
						$w = intval($h * ($aspect[0] / $aspect[1])) . 'c';
						$h = $h . 'c';
					}
				}
				//tkcropthumbs end

				$data = $this->getImageScale($info, $w, $h, $options);
				$w = $data['origW'];
				$h = $data['origH'];
				// If no conversion should be performed
				// this flag is TRUE if the width / height does NOT dictate
				// the image to be scaled!! (that is if no width / height is
				// given or if the destination w/h matches the original image
				// dimensions or if the option to not scale the image is set)
				$noScale = !$w && !$h || $data[0] == $info[0] && $data[1] == $info[1] || $options['noScale'];
				if ($noScale && !$data['crs'] && !$params && !$frame && $newExt == $info[2] && !$mustCreate) {
					// Set the new width and height before returning,
					// if the noScale option is set
					if (!empty($options['noScale'])) {
						$info[0] = $data[0];
						$info[1] = $data[1];
					}
					$info[3] = $imagefile;
					return $info;
				}
				$file['origW'] = $info[0];
				$file['origH'] = $info[1];
				$info[0] = $data[0];
				$info[1] = $data[1];
				$frame = $this->noFramePrepended ? '' : intval($frame);
				if (!$params) {
					$params = $this->cmds[$newExt];
				}
				// Cropscaling:
				$paramsOrg = $params;
				if ($data['crs']) {
					if (!$data['origW']) {
						$data['origW'] = $data[0];
					}
					if (!$data['origH']) {
						$data['origH'] = $data[1];
					}
					$offsetX = intval(($data[0] - $data['origW']) * ($data['cropH'] + 100) / 200);
					$offsetY = intval(($data[1] - $data['origH']) * ($data['cropV'] + 100) / 200);
					$params .= ' -crop ' . $data['origW'] . 'x' . $data['origH'] . '+' . $offsetX . '+' . $offsetY . '! ';
				}


				//tkcropthumbs
				if ($cropValues) {
					if (!$data['origW']) {
						$data['origW'] = $file['origW'];
					}
					if (!$data['origH']) {
						$data['origH'] = $file['origH'];
					}

					$xRatio = $data['origW'] / $cWidth;
					$offsetX1 = intval($cropValues['x1'] * $xRatio);
					$yRatio = $data['origH'] / $cHeight;
					$offsetY1 = intval($cropValues['y1'] * $yRatio);

					$cropParams = ' -crop ' . $data['origW'] . 'x' . $data['origH'] . '+' . $offsetX1 . '+' . $offsetY1 . ' ';
					$info[0] = intval($data['origW'] + ($file['origW'] - $cWidth) * $xRatio);
					$info[1] = intval($data['origH'] + ($file['origH'] - $cHeight) * $yRatio);
					$params = $paramsOrg . $cropParams;
				}

				//tkcropthumbs end

				$command = $this->scalecmd . ' ' . $info[0] . 'x' . $info[1] . '! ' . $params . ' ';
				$cropscale = $data['crs'] ? 'crs-V' . $data['cropV'] . 'H' . $data['cropH'] : '';
				if ($this->alternativeOutputKey) {
					$theOutputName = GeneralUtility::shortMD5($command . $cropscale . basename($imagefile) . $this->alternativeOutputKey . '[' . $frame . ']');
				} else {
					$theOutputName = GeneralUtility::shortMD5($command . $cropscale . $imagefile . filemtime($imagefile) . '[' . $frame . ']');
				}
				if ($this->imageMagickConvert_forceFileNameBody) {
					$theOutputName = $this->imageMagickConvert_forceFileNameBody;
					$this->imageMagickConvert_forceFileNameBody = '';
				}
				// Making the temporary filename:
				$this->createTempSubDir('pics/');
				$output = $this->absPrefix . $this->tempPath . 'pics/' . $this->filenamePrefix . $theOutputName . '.' . $newExt;
				// Register temporary filename:
				$GLOBALS['TEMP_IMAGES_ON_PAGE'][] = $output;
				if ($this->dontCheckForExistingTempFile || !$this->file_exists_typo3temp_file($output, $imagefile)) {
					$this->imageMagickExec($imagefile, $output, $command, $frame);
				}
				if (file_exists($output)) {
					$info[3] = $output;
					$info[2] = $newExt;
					// params could realisticly change some imagedata!
					if ($params) {
						$info = $this->getImageDimensions($info[3]);
					}
					if ($info[2] == $this->gifExtension && !$this->dontCompress) {
						// Compress with IM (lzw) or GD (rle)  (Workaround for the absence of lzw-compression in GD)
						GeneralUtility::gif_compress($info[3], '');
					}
					return $info;
				}
			}
		}
	}
}