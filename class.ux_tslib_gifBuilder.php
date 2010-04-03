<?php
/**************************************************************
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
 * **************************************************************/

/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   40: class ux_tslib_gifBuilder extends tslib_gifBuilder
 *   57:     function imageMagickConvert($imagefile,$newExt='',$w='',$h='',$params='',$frame='',$options='',$mustCreate=0, $uid)
 *  299:     function createThumb($imagefile,$frame,$output,$command,$width_dest,$height_dest,$width_src,$height_src,$line) //,$width_dest,$height_dest,$width_src,$height_src)
 *
 * TOTAL FUNCTIONS: 2
 * (This index is automatically created/updated by the extension "extdeveval")
 *
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
	 * @param	[type]		$uid: ...
	 * @return	array		[0]/[1] is w/h, [2] is file extension and [3] is the filename.
	 * @see getImageScale(), typo3/show_item.php, fileList_ext::renderImage(), tslib_cObj::getImgResource(), SC_tslib_showpic::show(), maskImageOntoImage(), copyImageOntoImage(), scale()
	 */
	function imageMagickConvert($imagefile,$newExt='',$w='',$h='',$params='',$frame='',$options='',$mustCreate=0, $uid) {
		if ($this->NO_IMAGE_MAGICK) {
			// Returning file info right away
			return $this->getImageDimensions($imagefile);
		}

		$info=$this->getImageDimensions($imagefile);
		if($info) {
			//tkcropthumbs calculations
			$width_dest = 0;
			$height_dest = 0;
			$width_src = 0;
			$height_src = 0;
			$line = array();

			//check for standard Aspect ratios
			$select		= 'tx_tkcropthumbs_aspectratio';
			$table		= 'tt_content';
			$where		= 'uid = '.$uid;
			$res_aspect = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $table, $where);
			$isAspect	= $GLOBALS['TYPO3_DB']->sql_fetch_row($res_aspect);

			// check if aspect ratio thumbs has to be calculated
			$select		= 'x,y,x2,y2,tstamp';
			$table		= 'tx_tkcropthumbs';
			$where		= 'image = "'.$imagefile.'" AND uid = '.$uid;
			$res_crop	= $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $table, $where);
			$isCrop		= $GLOBALS['TYPO3_DB']->sql_num_rows($res_crop);

			//has crop
			if ($isCrop) {
				$line		= $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res_crop);
				$width_src	= $line["x2"]-$line["x"];
				$height_src = $line["y2"]-$line["y"];
				$ratio		= ($line["x2"]-$line["x"])/($line["y2"]-$line["y"]);
				//check for changes
				$this->filenamePrefix = $line['tstamp'].'_';

				if ($h) {
					$width_dest	 = $h*$ratio;
					$height_dest = $h;
				}
				else {
					if (!$w) {
						if ($options['maxW']) {
							if ($options['maxW'] > $info[0]) $w = $info[0];
							else $w = $options['maxW'];
						}
						else $w = $info[0];
					}
					$width_dest  = $w;
					$height_dest = $w/$ratio;
				}
				$info[0] = $width_dest;
				$info[1] = $height_dest;
			}

			if($isAspect[0] != 0) {
//                            t3lib_div::debug($isAspect[0], '$isAspect');
//                            t3lib_div::debug($w, '$w');
//                            t3lib_div::debug($h, '$h');
//                            t3lib_div::debug($options['maxW'], 'maxW');
				$aspect = array();
				if($isAspect[0] == 1){
					$aspect[0] = 1;
					$aspect[1] = 1;
				}
				else if($isAspect[0] == 2){
					$aspect[0] = 4;
					$aspect[1] = 3;
				}
				else if($isAspect[0] == 3){
					$aspect[0] = 13;
					$aspect[1] = 9;
				}
				else if($isAspect[0] == 4){
					$aspect[0] = 16;
					$aspect[1] = 9;
				}

				if($info[0]/$info[1] != $aspect[0]/$aspect[1]) {
					$orientation = ($info[0] > $info[1]) ? 'landscape' : 'portrait';

					if ($h)
					{
						$width_dest = $h;
						$height_dest = $h*$aspect[1]/$aspect[0];
					}
					else {
						if (!$w) {
							if ($options['maxW']) {
								if ($options['maxW'] > $info[0]) $w = $info[0];
								else $w = $options['maxW'];
							}
							else $w = $info[0];
						}
						$width_dest = $w;
						$height_dest = $w*$aspect[1]/$aspect[0];
					}

					if(!$line) {
						if($orientation == 'landscape') {
							$width_src =  $info[1]*$aspect[0]/$aspect[1];
							$height_src = $info[1];
							$line['x'] = $info[0]/2 - $width_src/2;
							$line['y'] = 0;
						}
						else if($orientation == 'portrait') {
							$width_src =  $info[0];
							$height_src = $info[0]*$aspect[1]/$aspect[0];
							$line['x'] = 0;
							$line['y'] = $info[1]/2 - $height_src/2;
						}
						else{

							$width_src =  $info[0];
							$height_src = $info[0]*$aspect[1]/$aspect[0];
							$line['x'] = 0;
							$line['y'] = $info[1]/2 - $height_src/2;
						}
					}
					$info[0] = $width_dest;
					$info[1] = $height_dest;
					$isCrop = 1;
				}
			}


			//end tkcropthumbs calculations


			$newExt=strtolower(trim($newExt));
			if (!$newExt) {	// If no extension is given the original extension is used
				$newExt = $info[2];
			}
			if ($newExt=='web') {
				if (t3lib_div::inList($this->webImageExt,$info[2])) {
					$newExt = $info[2];
				} else {
					$newExt = $this->gif_or_jpg($info[2],$info[0],$info[1]);
					if (!$params) {
						$params = $this->cmds[$newExt];
					}
				}
			}

			if (t3lib_div::inList($this->imageFileExt,$newExt)) {
				if (strstr($w.$h, 'm')) {
					$max=1;
				} else {
					$max=0;
				}

				$data = $this->getImageScale($info,$w,$h,$options);
				$w=$data['origW'];
				$h=$data['origH'];

				// if no convertion should be performed
//				$wh_noscale = (!$w && !$h) || ($data[0]==$info[0] && $data[1]==$info[1]);		// this flag is true if the width / height does NOT dictate the image to be scaled!! (that is if no w/h is given or if the destination w/h matches the original image-dimensions....
				$wh_noscale = (!$w && !$h);
				if ($wh_noscale && !$data['crs'] && !$params && !$frame && $newExt==$info[2] && !$mustCreate) {
					$info[3] = $imagefile;
					return $info;
				}
				$info[0]=$data[0];
				$info[1]=$data[1];

				$frame = $this->noFramePrepended ? '' : intval($frame);

				if (!$params) {
					$params = $this->cmds[$newExt];
				}

				// Cropscaling:
				if ($data['crs']) {
					if (!$data['origW']) {
						$data['origW'] = $data[0];
					}
					if (!$data['origH']) {
						$data['origH'] = $data[1];
					}
					$offsetX = intval(($data[0] - $data['origW']) * ($data['cropH']+100)/200);
					$offsetY = intval(($data[1] - $data['origH']) * ($data['cropV']+100)/200);
					$params .= ' -crop '.$data['origW'].'x'.$data['origH'].'+'.$offsetX.'+'.$offsetY.' ';
				}

				$command = $this->scalecmd.' '.$info[0].'x'.$info[1].'! '.$params.' ';
				$cropscale = ($data['crs'] ? 'crs-V'.$data['cropV'].'H'.$data['cropH'] : '');

				if ($this->alternativeOutputKey) {
					$theOutputName = t3lib_div::shortMD5($command.$cropscale.basename($imagefile).$this->alternativeOutputKey.'['.$frame.']');
				} else {
					$theOutputName = t3lib_div::shortMD5($command.$cropscale.$imagefile.filemtime($imagefile).'['.$frame.']');
				}
				if ($this->imageMagickConvert_forceFileNameBody) {
					$theOutputName = $this->imageMagickConvert_forceFileNameBody;
					$this->imageMagickConvert_forceFileNameBody='';
				}

				// Making the temporary filename:
				$this->createTempSubDir('pics/');
				$output = $this->absPrefix.$this->tempPath.'pics/'.$this->filenamePrefix.$theOutputName.'.'.$newExt;

				// Register temporary filename:
				$GLOBALS['TEMP_IMAGES_ON_PAGE'][] = $output;
				if ($this->dontCheckForExistingTempFile || !$this->file_exists_typo3temp_file($output, $imagefile)) {
					if ($isCrop)$this->createThumb($imagefile,$frame,$output,$command,$width_dest,$height_dest,$width_src,$height_src,$line);
					else $this->imageMagickExec($imagefile, $output, $command, $frame);
				}
				if (file_exists($output)) {
					$info[3] = $output;
					$info[2] = $newExt;
					if ($params) {	// params could realisticly change some imagedata!
						$info=$this->getImageDimensions($info[3]);
					}
					if ($info[2]==$this->gifExtension && !$this->dontCompress) {
						t3lib_div::gif_compress($info[3],'');		// Compress with IM (lzw) or GD (rle)  (Workaround for the absence of lzw-compression in GD)
					}
					return $info;
				}
			}
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$imagefile: ...
	 * @param	[type]		$frame: ...
	 * @param	[type]		$output: ...
	 * @param	[type]		$command: ...
	 * @param	[type]		$width_dest: ...
	 * @param	[type]		$height_dest: ...
	 * @param	[type]		$width_src: ...
	 * @param	[type]		$height_src: ...
	 * @param	[type]		$line) //: ...
	 * @param	[type]		$width_dest: ...
	 * @param	[type]		$height_dest: ...
	 * @param	[type]		$width_src: ...
	 * @param	[type]		$height_src: ...
	 * @return	[type]		...
	 */
	function createThumb($imagefile,$frame,$output,$command,$width_dest,$height_dest,$width_src,$height_src,$line) //,$width_dest,$height_dest,$width_src,$height_src)
    {
        $img2=imagecreatetruecolor($width_dest,$height_dest);
        $img=$this->imageCreateFromFile($imagefile);
        imagecopyresampled($img2,$img,0,0,$line["x"],$line["y"],$width_dest,$height_dest,$width_src,$height_src);
        ini_set(safe_mode,Off);
        $this->ImageWrite($img2,$output);
        ini_set(safe_mode,On);
        imagedestroy($img);
        imagedestroy($img2);
//        $this->imageMagickExec($output.$frame,$output,$command); //has to be executed to apply special features on thumb too
    }
}
?>
