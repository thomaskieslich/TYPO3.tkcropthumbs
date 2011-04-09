<?php

/* * ************************************************************
 *  Copyright notice
 *
 *  (c) 2009 Thomas Kieslich <thomaskieslich@gmx.net>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
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
 * ************************************************************* */

/**
 * extends tslib_gifBuilder
 */
class ux_tslib_gifBuilder extends tslib_gifBuilder {

	/**
	 * Converts $imagefile to another file in temp-dir of type $newExt (extension).
	 *
	 * @param	string		The image filepath
	 * @param	string		New extension, eg. "gif", "png", "jpg", "tif". If $newExt is NOT set, the new imagefile will be of the original format. If newExt = 'WEB' then one of the web-formats is applied.
	 * @param	string		Width. $w / $h is optional. If only one is given the image is scaled proportionally. If an 'm' exists in the $w or $h and if both are present the $w and $h is regarded as the Maximum w/h and the proportions will be kept
	 * @param	string		Height. See $w
	 * @param	string		Additional ImageMagick parameters.
	 * @param	string		Refers to which frame-number to select in the image. '' or 0 will select the first frame, 1 will select the next and so on...
	 * @param	array		An array with options passed to getImageScale (see this function).
	 * @param	boolean		If set, then another image than the input imagefile MUST be returned. Otherwise you can risk that the input image is good enough regarding messures etc and is of course not rendered to a new, temporary file in typo3temp/. But this option will force it to.
	 * @param       array		tkcropthumbs
	 * @return	array		[0]/[1] is w/h, [2] is file extension and [3] is the filename.
	 * @see getImageScale(), typo3/show_item.php, fileList_ext::renderImage(), tslib_cObj::getImgResource(), SC_tslib_showpic::show(), maskImageOntoImage(), copyImageOntoImage(), scale()
	 */
	function imageMagickConvert ($imagefile, $newExt = '', $w = '', $h = '', $params = '', $frame = '', $options = '', $mustCreate = 0, $tkcropthumbs = NULL) {
		if ($this->NO_IMAGE_MAGICK) {
			// Returning file info right away
			return $this->getImageDimensions($imagefile);
		}

		if ($info = $this->getImageDimensions($imagefile)) {
			$newExt = strtolower(trim($newExt));
			if (!$newExt) { // If no extension is given the original extension is used
				$newExt = $info[2];
			}
			if ($newExt == 'web') {
				if (t3lib_div::inList($this->webImageExt, $info[2])) {
					$newExt = $info[2];
				} else {
					$newExt = $this->gif_or_jpg($info[2], $info[0], $info[1]);
					if (!$params) {
						$params = $this->cmds[$newExt];
					}
				}
			}
			if (t3lib_div::inList($this->imageFileExt, $newExt)) {
				if (strstr($w . $h, 'm')) {
					$max = 1;
				} else {
					$max = 0;
				}
				
				// tkcropthumbs
				if (strlen($tkcropthumbs['cropvalues']) > 1) {
					$cropXml = simplexml_load_string($tkcropthumbs['cropvalues']);
					if ($cropXml) {
						$cropData = $cropXml->xpath('//image[. ="' . $info[3] . '"]');
						$cropValues = $cropData[0];

						$cWidth = intval($cropValues['x2'] - $cropValues['x1']);
						$cHeight = intval($cropValues['y2'] - $cropValues['y1']);
						$ratio = ($cropValues["x2"] - $cropValues["x1"]) / ($cropValues["y2"] - $cropValues["y1"]);
						
						if ($tkcropthumbs['aspectratio'] == 0) {
							$tkcropthumbs['aspectratio'] = ($cropValues["x2"] - $cropValues["x1"]).':'.($cropValues["y2"] - $cropValues["y1"]);
						}
					}
				}
				// tkcropthumbs
				if ($tkcropthumbs['aspectratio'] > 0) {
					$aspect = preg_split('/:/', $tkcropthumbs['aspectratio'], 2);
					if ($options['maxW'] && !$w) {
						$w = $options['maxW'] . 'c';
						$h = intval($options['maxW'] * ($aspect[1] / $aspect[0])) . 'c';
					}
					if ($options['maxW'] && $w) {
						$w = $w . 'c';
						$options['maxW'] = $w;
						$h = intval($options['maxW'] * ($aspect[1] / $aspect[0])) . 'c';
					} elseif ($h && $w) {
						$w = intval($h * ($aspect[0] / $aspect[1])) . 'c';
						$h = $h . 'c';
					}
				}
				//end tkcropthumbs 
				
				$data = $this->getImageScale($info, $w, $h, $options);
				$w = $data['origW'];
				$h = $data['origH'];
				// if no conversion should be performed
				// this flag is true if the width / height does NOT dictate
				// the image to be scaled!! (that is if no width / height is
				// given or if the destination w/h matches the original image
				// dimensions or if the option to not scale the image is set)
				$noScale = (!$w && !$h) || ($data[0] == $info[0] && $data[1] == $info[1]) || $options['noScale'];

				if ($noScale && !$data['crs'] && !$params && !$frame && $newExt == $info[2] && !$mustCreate) {
					// set the new width and height before returning,
					// if the noScale option is set
					if ($options['noScale']) {
						$info[0] = $data[0];
						$info[1] = $data[1];
					}
					$info[3] = $imagefile;
					return $info;
				}
				$file['w'] = $info[0];
				$file['h'] = $info[1];
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
					$params .= ' -crop ' . $data['origW'] . 'x' . $data['origH'] . '+' . $offsetX . '+' . $offsetY . ' ';
				}

				//tkcropthumbs					
				if ($cropValues) {
					if (!$data['origW']) {
						$data['origW'] = $file['w'];
					}
					if (!$data['origH']) {
						$data['origH'] = $file['h'];
					}


					$xRatio = $data['origW'] / $cWidth;
					$offsetX1 = intval($cropValues['x1'] * $xRatio);
					$yRatio = $data['origH'] / $cHeight;
					$offsetY1 = intval($cropValues['y1'] * $yRatio);


					$cropParams .= ' -crop ' . $data['origW'] . 'x' . $data['origH'] . '+' . $offsetX1 . '+' . $offsetY1 . ' ';
					$info[0] = intval($data['origW'] + ($file['w'] - $cWidth) * $xRatio);
					$info[1] = intval($data['origH'] + ($file['h'] - $cHeight) * $yRatio);
					$params = $paramsOrg . $cropParams;
				}



				$command = $this->scalecmd . ' ' . $info[0] . 'x' . $info[1] . '! ' . $params . ' ';
				$cropscale = ($data['crs'] ? 'crs-V' . $data['cropV'] . 'H' . $data['cropH'] : '');


				if ($this->alternativeOutputKey) {
					$theOutputName = t3lib_div::shortMD5($command . $cropscale . basename($imagefile) . $this->alternativeOutputKey . '[' . $frame . ']');
				} else {
					$theOutputName = t3lib_div::shortMD5($command . $cropscale . $imagefile . filemtime($imagefile) . '[' . $frame . ']');
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
					if ($params) { // params could realisticly change some imagedata!
						$info = $this->getImageDimensions($info[3]);
					}
					if ($info[2] == $this->gifExtension && !$this->dontCompress) {
						t3lib_div::gif_compress($info[3], ''); // Compress with IM (lzw) or GD (rle)  (Workaround for the absence of lzw-compression in GD)
					}
					return $info;
				}
			}
		}
	}

}

?>