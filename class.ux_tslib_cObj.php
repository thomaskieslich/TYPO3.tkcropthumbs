<?php

/* * *************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Thomas Kieslich <thomaskieslich@gmx.net>
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
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   44: class ux_tslib_cObj extends tslib_cObj
 *   56:     function cImage($file,$conf)
 *   96:     function getImgResource($file,$fileArray,$uid=0)
 *
 * TOTAL FUNCTIONS: 2
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

/**
 * extends tslib_cObj
 *
 */
class ux_tslib_cObj extends tslib_cObj {

	/**
	 * Returns a <img> tag with the image file defined by $file and processed according to the properties in the TypoScript array.
	 * Mostly this function is a sub-function to the IMAGE function which renders the IMAGE cObject in TypoScript.
	 * This function is called by "$this->cImage($conf['file'],$conf);" from IMAGE().
	 *
	 * @param	string		File TypoScript resource
	 * @param	array		TypoScript configuration properties
	 * @return	string		<img> tag, (possibly wrapped in links and other HTML) if any image found.
	 * @access private
	 * @see IMAGE()
	 */
	function cImage($file, $conf) {
		//tkcropthumbs values
		$tkcropthumbs = array();
		$tkcropthumbs['hash'] = t3lib_div::shortMD5($this->data[tx_tkcropthumbs_aspectratio] . $this->data[tx_tkcropthumbs_cropvalues]);
		$tkcropthumbs['aspectratio'] = $this->data[tx_tkcropthumbs_aspectratio];
		$tkcropthumbs['cropvalues'] = $this->data[tx_tkcropthumbs_cropvalues];
		$info = $this->getImgResource($file, $conf['file.'], $tkcropthumbs);

		$GLOBALS['TSFE']->lastImageInfo = $info;
		if (is_array($info)) {
			$info[3] = t3lib_div::png_to_gif_by_imagemagick($info[3]);
			$GLOBALS['TSFE']->imagesOnPage[] = $info[3]; // This array is used to collect the image-refs on the page...
			// Backwards compatibility if altText is not set and alttext is set
			// @deprecated since TYPO3 4.3, will be removed in TYPO3 4.6
			if (strlen($conf['alttext']) || is_array($conf['alttext.'])) {
				$GLOBALS['TSFE']->logDeprecatedTyposcript(
						'IMAGE.alttext', 'use IMAGE.altText instead - src: ' . $info[3] . ' - original image: ' . $info['origFile']
				);
				if (!strlen($conf['altText']) && !is_array($conf['altText.'])) {
					$conf['altText'] = $conf['alttext'];
					$conf['altText.'] = $conf['alttext.'];
				}
			}

			$altParam = $this->getAltParam($conf);
			if ($conf['params'] && !isset($conf['params.'])) {
				$params = ' ' . $conf['params'];
			} else {
				$params = isset($conf['params.']) ? ' ' . $this->stdWrap($conf['params'], $conf['params.']) : '';
			}
			$theValue = '<img src="' . htmlspecialchars($GLOBALS['TSFE']->absRefPrefix .
							t3lib_div::rawUrlEncodeFP($info[3])) . '" width="' . $info[0] . '" height="' . $info[1] . '"' .
					$this->getBorderAttr(' border="' . intval($conf['border']) . '"') .
					$params .
					($altParam) . ' />';
			$linkWrap = isset($conf['linkWrap.']) ? $this->stdWrap($conf['linkWrap'], $conf['linkWrap.']) : $conf['linkWrap'];
			if ($linkWrap) {
				$theValue = $this->linkWrap($theValue, $linkWrap);
			} elseif ($conf['imageLinkWrap']) {
				$theValue = $this->imageLinkWrap($theValue, $info['origFile'], $conf['imageLinkWrap.']);
			}
			$wrap = isset($conf['wrap.']) ? $this->stdWrap($conf['wrap'], $conf['wrap.']) : $conf['wrap'];
			if ($wrap) {
				$theValue = $this->wrap($theValue, $conf['wrap']);
			}
			return $theValue;
		}
	}

	/**
	 * Creates and returns a TypoScript "imgResource".
	 * The value ($file) can either be a file reference (TypoScript resource) or the string "GIFBUILDER".
	 * In the first case a current image is returned, possibly scaled down or otherwise processed.
	 * In the latter case a GIFBUILDER image is returned; This means an image is made by TYPO3 from layers of elements as GIFBUILDER defines.
	 * In the function IMG_RESOURCE() this function is called like $this->getImgResource($conf['file'],$conf['file.']);
	 *
	 * @param	string		A "imgResource" TypoScript data type. Either a TypoScript file resource or the string GIFBUILDER. See description above.
	 * @param	array		TypoScript properties for the imgResource type
	 * @param   array		tkcropthumbs
	 * @return	array		Returns info-array. info[origFile] = original file.
	 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=315&cHash=63b593a934
	 * @see IMG_RESOURCE(), cImage(), tslib_gifBuilder
	 */
	function getImgResource($file, $fileArray, $tkcropthumbs = NULL) {
		if (is_array($fileArray)) {
			switch ($file) {
				case 'GIFBUILDER' :
					$gifCreator = t3lib_div::makeInstance('tslib_gifbuilder');
					$gifCreator->init();
					$theImage = '';
					if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib']) {
						$gifCreator->start($fileArray, $this->data);
						$theImage = $gifCreator->gifBuild();
					}
					$imageResource = $gifCreator->getImageDimensions($theImage);
					break;
				default :
					if ($fileArray['import.']) {
						$ifile = $this->stdWrap('', $fileArray['import.']);
						if ($ifile) {
							$file = $fileArray['import'] . $ifile;
						}
					}
					$theImage = $GLOBALS['TSFE']->tmpl->getFileName($file);
					if ($theImage) {
						$fileArray['width'] = isset($fileArray['width.']) ? $this->stdWrap($fileArray['width'], $fileArray['width.']) : $fileArray['width'];
						$fileArray['height'] = isset($fileArray['height.']) ? $this->stdWrap($fileArray['height'], $fileArray['height.']) : $fileArray['height'];
						$fileArray['ext'] = isset($fileArray['ext.']) ? $this->stdWrap($fileArray['ext'], $fileArray['ext.']) : $fileArray['ext'];
						$fileArray['maxW'] = isset($fileArray['maxW.']) ? intval($this->stdWrap($fileArray['maxW'], $fileArray['maxW.'])) : intval($fileArray['maxW']);
						$fileArray['maxH'] = isset($fileArray['maxH.']) ? intval($this->stdWrap($fileArray['maxH'], $fileArray['maxH.'])) : intval($fileArray['maxH']);
						$fileArray['minW'] = isset($fileArray['minW.']) ? intval($this->stdWrap($fileArray['minW'], $fileArray['minW.'])) : intval($fileArray['minW']);
						$fileArray['minH'] = isset($fileArray['minH.']) ? intval($this->stdWrap($fileArray['minH'], $fileArray['minH.'])) : intval($fileArray['minH']);
						$fileArray['noScale'] = isset($fileArray['noScale.']) ? $this->stdWrap($fileArray['noScale'], $fileArray['noScale.']) : $fileArray['noScale'];
						$maskArray = $fileArray['m.'];
						$maskImages = array();
						if (is_array($fileArray['m.'])) { // Must render mask images and include in hash-calculating - else we cannot be sure the filename is unique for the setup!
							$maskImages['m_mask'] = $this->getImgResource($maskArray['mask'], $maskArray['mask.']);
							$maskImages['m_bgImg'] = $this->getImgResource($maskArray['bgImg'], $maskArray['bgImg.']);
							$maskImages['m_bottomImg'] = $this->getImgResource($maskArray['bottomImg'], $maskArray['bottomImg.']);
							$maskImages['m_bottomImg_mask'] = $this->getImgResource($maskArray['bottomImg_mask'], $maskArray['bottomImg_mask.']);
						}

						//tkcropthumbs add uid!!!
//						$hash = t3lib_div::shortMD5($theImage . serialize($fileArray) . serialize($maskImages));
						$hash = t3lib_div::shortMD5($theImage . serialize($fileArray) . serialize($maskImages) . serialize($tkcropthumbs['hash']));
						if (!isset($GLOBALS['TSFE']->tmpl->fileCache[$hash])) {
							$gifCreator = t3lib_div::makeInstance('tslib_gifbuilder');
							$gifCreator->init();

							if ($GLOBALS['TSFE']->config['config']['meaningfulTempFilePrefix']) {
								$filename = basename($theImage);
								// remove extension
								$filename = substr($filename, 0, strrpos($filename, '.'));
								// strip everything non-ascii
								$filename = preg_replace('/[^A-Za-z0-9_-]/', '', trim($filename));
								$gifCreator->filenamePrefix = substr($filename, 0, intval($GLOBALS['TSFE']->config['config']['meaningfulTempFilePrefix'])) . '_';
								unset($filename);
							}

							if ($fileArray['sample']) {
								$gifCreator->scalecmd = '-sample';
								$GLOBALS['TT']->setTSlogMessage('Sample option: Images are scaled with -sample.');
							}
							if ($fileArray['alternativeTempPath'] && t3lib_div::inList($GLOBALS['TYPO3_CONF_VARS']['FE']['allowedTempPaths'], $fileArray['alternativeTempPath'])) {
								$gifCreator->tempPath = $fileArray['alternativeTempPath'];
								$GLOBALS['TT']->setTSlogMessage('Set alternativeTempPath: ' . $fileArray['alternativeTempPath']);
							}

							if (!trim($fileArray['ext'])) {
								$fileArray['ext'] = 'web';
							}
							$options = array();
							if ($fileArray['maxW']) {
								$options['maxW'] = $fileArray['maxW'];
							}
							if ($fileArray['maxH']) {
								$options['maxH'] = $fileArray['maxH'];
							}
							if ($fileArray['minW']) {
								$options['minW'] = $fileArray['minW'];
							}
							if ($fileArray['minH']) {
								$options['minH'] = $fileArray['minH'];
							}
							if ($fileArray['noScale']) {
								$options['noScale'] = $fileArray['noScale'];
							}

							// checks to see if m (the mask array) is defined
							if (is_array($maskArray) && $GLOBALS['TYPO3_CONF_VARS']['GFX']['im']) {
								// Filename:
								$fI = t3lib_div::split_fileref($theImage);
								$imgExt = (strtolower($fI['fileext']) == $gifCreator->gifExtension ? $gifCreator->gifExtension : 'jpg');
								$dest = $gifCreator->tempPath . $hash . '.' . $imgExt;
								if (!file_exists($dest)) { // Generate!
									$m_mask = $maskImages['m_mask'];
									$m_bgImg = $maskImages['m_bgImg'];
									if ($m_mask && $m_bgImg) {
										$negate = $GLOBALS['TYPO3_CONF_VARS']['GFX']['im_negate_mask'] ? ' -negate' : '';

										$temp_ext = 'png';
										if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['im_mask_temp_ext_gif']) { // If ImageMagick version 5+
											$temp_ext = $gifCreator->gifExtension;
										}
										//tkcropthumbs
//										$tempFileInfo = $gifCreator->imageMagickConvert($theImage, $temp_ext, $fileArray['width'], $fileArray['height'], $fileArray['params'], $fileArray['frame'], $options);
										$tempFileInfo = $gifCreator->imageMagickConvert($theImage, $temp_ext, $fileArray['width'], $fileArray['height'], $fileArray['params'], $fileArray['frame'], $options, 0, $tkcropthumbs);
										if (is_array($tempFileInfo)) {
											$m_bottomImg = $maskImages['m_bottomImg'];
											if ($m_bottomImg) {
												$m_bottomImg_mask = $maskImages['m_bottomImg_mask'];
											}
											//	Scaling:	****
											$tempScale = array();
											$command = '-geometry ' . $tempFileInfo[0] . 'x' . $tempFileInfo[1] . '!';
											$command = $this->modifyImageMagickStripProfileParameters($command, $fileArray);
											$tmpStr = $gifCreator->randomName();

											//	m_mask
											$tempScale['m_mask'] = $tmpStr . '_mask.' . $temp_ext;
											$gifCreator->imageMagickExec($m_mask[3], $tempScale['m_mask'], $command . $negate);
											//	m_bgImg
											$tempScale['m_bgImg'] = $tmpStr . '_bgImg.' . trim($GLOBALS['TYPO3_CONF_VARS']['GFX']['im_mask_temp_ext_noloss']);
											$gifCreator->imageMagickExec($m_bgImg[3], $tempScale['m_bgImg'], $command);

											//	m_bottomImg / m_bottomImg_mask
											if ($m_bottomImg && $m_bottomImg_mask) {
												$tempScale['m_bottomImg'] = $tmpStr . '_bottomImg.' . $temp_ext;
												$gifCreator->imageMagickExec($m_bottomImg[3], $tempScale['m_bottomImg'], $command);
												$tempScale['m_bottomImg_mask'] = $tmpStr . '_bottomImg_mask.' . $temp_ext;
												$gifCreator->imageMagickExec($m_bottomImg_mask[3], $tempScale['m_bottomImg_mask'], $command . $negate);

												// BEGIN combining:
												// The image onto the background
												$gifCreator->combineExec($tempScale['m_bgImg'], $tempScale['m_bottomImg'], $tempScale['m_bottomImg_mask'], $tempScale['m_bgImg']);
											}
											// The image onto the background
											$gifCreator->combineExec($tempScale['m_bgImg'], $tempFileInfo[3], $tempScale['m_mask'], $dest);
											// Unlink the temp-images...
											foreach ($tempScale as $file) {
												if (@is_file($file)) {
													unlink($file);
												}
											}
										}
									}
								}
								// Finish off
								if (($fileArray['reduceColors'] || ($imgExt == 'png' && !$gifCreator->png_truecolor)) && is_file($dest)) {
									$reduced = $gifCreator->IMreduceColors($dest, t3lib_div::intInRange($fileArray['reduceColors'], 256, $gifCreator->truecolorColors, 256));
									if (is_file($reduced)) {
										unlink($dest);
										rename($reduced, $dest);
									}
								}
								$GLOBALS['TSFE']->tmpl->fileCache[$hash] = $gifCreator->getImageDimensions($dest);
							} else { // Normal situation:
								$fileArray['params'] = $this->modifyImageMagickStripProfileParameters($fileArray['params'], $fileArray);
								//tkcropthumbs
//								$GLOBALS['TSFE']->tmpl->fileCache[$hash] = $gifCreator->imageMagickConvert($theImage, $fileArray['ext'], $fileArray['width'], $fileArray['height'], $fileArray['params'], $fileArray['frame'], $options);
								$GLOBALS['TSFE']->tmpl->fileCache[$hash] = $gifCreator->imageMagickConvert($theImage, $fileArray['ext'], $fileArray['width'], $fileArray['height'], $fileArray['params'], $fileArray['frame'], $options, 0, $tkcropthumbs);
								if (($fileArray['reduceColors'] || ($imgExt == 'png' && !$gifCreator->png_truecolor)) && is_file($GLOBALS['TSFE']->tmpl->fileCache[$hash][3])) {
									$reduced = $gifCreator->IMreduceColors($GLOBALS['TSFE']->tmpl->fileCache[$hash][3], t3lib_div::intInRange($fileArray['reduceColors'], 256, $gifCreator->truecolorColors, 256));
									if (is_file($reduced)) {
										unlink($GLOBALS['TSFE']->tmpl->fileCache[$hash][3]);
										rename($reduced, $GLOBALS['TSFE']->tmpl->fileCache[$hash][3]);
									}
								}
							}
							$GLOBALS['TSFE']->tmpl->fileCache[$hash]['origFile'] = $theImage;
							$GLOBALS['TSFE']->tmpl->fileCache[$hash]['origFile_mtime'] = @filemtime($theImage); // This is needed by tslib_gifbuilder, ln 100ff in order for the setup-array to create a unique filename hash.
							$GLOBALS['TSFE']->tmpl->fileCache[$hash]['fileCacheHash'] = $hash;
						}
						$imageResource = $GLOBALS['TSFE']->tmpl->fileCache[$hash];
					}

					break;
			}
		}
		$theImage = $GLOBALS['TSFE']->tmpl->getFileName($file);
		// If image was processed by GIFBUILDER:
		// ($imageResource indicates that it was processed the regular way)
		if (!isset($imageResource) && $theImage) {
			$gifCreator = t3lib_div::makeInstance('tslib_gifbuilder');
			/* @var $gifCreator tslib_gifbuilder */
			$gifCreator->init();
			$info = $gifCreator->imageMagickConvert($theImage, 'WEB', '', '', '', '', '');
			$info['origFile'] = $theImage;
			$info['origFile_mtime'] = @filemtime($theImage); // This is needed by tslib_gifbuilder, ln 100ff in order for the setup-array to create a unique filename hash.
			$imageResource = $info;
		}

		// Hook 'getImgResource': Post-processing of image resources
		if (isset($imageResource)) {
			foreach ($this->getGetImgResourceHookObjects() as $hookObject) {
				$imageResource = $hookObject->getImgResourcePostProcess($file, (array) $fileArray, $imageResource, $this);
			}
		}

		return $imageResource;
	}

}

?>
