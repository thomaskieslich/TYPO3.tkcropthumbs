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
 * *************************************************************
 */

/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   43: class ux_t3lib_tceforms extends t3lib_tceforms
 *   55:     function getSingleField_typeGroup($table,$field,$row,&$PA)
 *
 * TOTAL FUNCTIONS: 1
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

/**
 * extends t3lib_tceforms
 *
 */
class ux_t3lib_tceforms extends t3lib_tceforms {

	/**
	 * Generation of TCEform elements of the type "group"
	 * This will render a selectorbox into which elements from either the file system or database can be inserted. Relations.
	 *
	 * @param	string		The table name of the record
	 * @param	string		The field name which this element is supposed to edit
	 * @param	array		The record data array where the value(s) for the field can be found
	 * @param	array		An array with additional configuration options.
	 * @return	string		The HTML code for the TCEform field
	 */
	function getSingleField_typeGroup($table, $field, $row, &$PA) {
		// Init:
		$config = $PA['fieldConf']['config'];
		$internal_type = $config['internal_type'];
		$show_thumbs = $config['show_thumbs'];
		$size = intval($config['size']);
		$maxitems = t3lib_div::intInRange($config['maxitems'], 0);
		if (!$maxitems) {
			$maxitems = 100000;
		}
		$minitems = t3lib_div::intInRange($config['minitems'], 0);
		$allowed = trim($config['allowed']);
		$disallowed = trim($config['disallowed']);

		$disabled = '';
		if ($this->renderReadonly || $config['readOnly']) {
			$disabled = ' disabled="disabled"';
		}

		$item .= '<input type="hidden" name="' . $PA['itemFormElName'] . '_mul" value="' . ($config['multiple'] ? 1 : 0) . '"' . $disabled . ' />';
		$this->registerRequiredProperty('range', $PA['itemFormElName'], array($minitems, $maxitems, 'imgName' => $table . '_' . $row['uid'] . '_' . $field));
		$info = '';

		// "Extra" configuration; Returns configuration for the field based on settings found in the "types" fieldlist. See http://typo3.org/documentation/document-library/doc_core_api/Wizards_Configuratio/.
		$specConf = $this->getSpecConfFromString($PA['extra'], $PA['fieldConf']['defaultExtras']);

		// Acting according to either "file" or "db" type:
		switch ((string) $config['internal_type']) {
			case 'file_reference':
				$config['uploadfolder'] = '';
			// Fall through
			case 'file': // If the element is of the internal type "file":
				// Creating string showing allowed types:
				$tempFT = t3lib_div::trimExplode(',', $allowed, 1);
				if (!count($tempFT)) {
					$info .= '*';
				}
				foreach ($tempFT as $ext) {
					if ($ext) {
						$info .= strtoupper($ext) . ' ';
					}
				}
				// Creating string, showing disallowed types:
				$tempFT_dis = t3lib_div::trimExplode(',', $disallowed, 1);
				if (count($tempFT_dis)) {
					$info .= '<br />';
				}
				foreach ($tempFT_dis as $ext) {
					if ($ext) {
						$info .= '-' . strtoupper($ext) . ' ';
					}
				}

				// Making the array of file items:
				$itemArray = t3lib_div::trimExplode(',', $PA['itemFormElValue'], 1);

				// Showing thumbnails:
				$thumbsnail = '';
				if ($show_thumbs) {
					$imgs = array();
					foreach ($itemArray as $imgRead) {
						$imgP = explode('|', $imgRead);
						$imgPath = rawurldecode($imgP[0]);

						$rowCopy = array();
						$rowCopy[$field] = $imgPath;

						// Icon + clickmenu:
						$absFilePath = t3lib_div::getFileAbsFileName($config['uploadfolder'] ? $config['uploadfolder'] . '/' . $imgPath : $imgPath);

						$fI = pathinfo($imgPath);
						$fileIcon = t3lib_iconWorks::getSpriteIconForFile(
										strtolower($fI['extension']), array(
									'title' => htmlspecialchars(
											$fI['basename'] .
											($absFilePath && @is_file($absFilePath) ? ' (' . t3lib_div::formatSize(filesize($absFilePath)) . 'bytes)' :
													' - FILE NOT FOUND!'
											)
									)
										)
						);

						//tkcropthumbs
						$relPath = t3lib_extMgm::extRelPath('tkcropthumbs');
						$imgs[] = '<span class="nobr">
							<a href="#" onclick="window.open(\''
								. $relPath . 'class.crop.php?image=' . $config['uploadfolder'] . '/' . $imgP[0]
								. '&uid=' . $row[uid] . '&aspectratio=' . $row['tx_tkcropthumbs_aspectratio'] . '\',\'tkcropthumbs' . rand(0, 1000000)
								. '\',\'height=620,width=820,status=0,menubar=0,scrollbars=0\');return false;" style="position: relative;">
								<div style="position:absolute; margin: auto; width: 56px;"><img src="'. $relPath . 'res/icons/crop.png" title="'.$fI['basename'].'"></div>'
						. $this->croppedThumbs($rowCopy, $table, $field, $this->backPath, 'thumbs.php', $config['uploadfolder'], 0, ' align="middle"')
								. '</a></span>';

						/* $imgs[] = '<span class="nobr">' . t3lib_BEfunc::thumbCode($rowCopy, $table, $field, $this->backPath, 'thumbs.php', $config['uploadfolder'], 0, ' align="middle"') .
						  ($absFilePath ? $this->getClickMenu($fileIcon, $absFilePath) : $fileIcon) .
						  $imgPath .
						  '</span>'; */
					}
					$thumbsnail = implode('<br />', $imgs);
				}

				// Creating the element:
				$noList = isset($config['disable_controls']) && t3lib_div::inList($config['disable_controls'], 'list');
				$params = array(
					'size' => $size,
					'dontShowMoveIcons' => ($maxitems <= 1),
					'autoSizeMax' => t3lib_div::intInRange($config['autoSizeMax'], 0),
					'maxitems' => $maxitems,
					'style' => isset($config['selectedListStyle']) ? ' style="' . htmlspecialchars($config['selectedListStyle']) . '"' : ' style="' . $this->defaultMultipleSelectorStyle . '"',
					'info' => $info,
					'thumbnails' => $thumbsnail,
					'readOnly' => $disabled,
					'noBrowser' => $noList || (isset($config['disable_controls']) && t3lib_div::inList($config['disable_controls'], 'browser')),
					'noList' => $noList,
				);
				$item .= $this->dbFileIcons($PA['itemFormElName'], 'file', implode(',', $tempFT), $itemArray, '', $params, $PA['onFocus']);

				if (!$disabled && !(isset($config['disable_controls']) && t3lib_div::inList($config['disable_controls'], 'upload'))) {
					// Adding the upload field:
					if ($this->edit_docModuleUpload && $config['uploadfolder']) {
						$item .= '<input type="file" name="' . $PA['itemFormElName_file'] . '" size="35" onchange="' . implode('', $PA['fieldChangeFunc']) . '" />';
					}
				}
				break;
			case 'folder': // If the element is of the internal type "folder":
				// array of folder items:
				$itemArray = t3lib_div::trimExplode(',', $PA['itemFormElValue'], 1);

				// Creating the element:
				$noList = isset($config['disable_controls']) && t3lib_div::inList($config['disable_controls'], 'list');
				$params = array(
					'size' => $size,
					'dontShowMoveIcons' => ($maxitems <= 1),
					'autoSizeMax' => t3lib_div::intInRange($config['autoSizeMax'], 0),
					'maxitems' => $maxitems,
					'style' => isset($config['selectedListStyle']) ?
							' style="' . htmlspecialchars($config['selectedListStyle']) . '"' : ' style="' . $this->defaultMultipleSelectorStyle . '"',
					'info' => $info,
					'readOnly' => $disabled,
					'noBrowser' => $noList || (isset($config['disable_controls']) && t3lib_div::inList($config['disable_controls'], 'browser')),
					'noList' => $noList,
				);

				$item .= $this->dbFileIcons(
								$PA['itemFormElName'], 'folder', '', $itemArray, '', $params, $PA['onFocus']
				);
				break;
			case 'db': // If the element is of the internal type "db":
				// Creating string showing allowed types:
				$tempFT = t3lib_div::trimExplode(',', $allowed, TRUE);
				if (!strcmp(trim($tempFT[0]), '*')) {
					$onlySingleTableAllowed = false;
					$info .= '<span class="nobr">&nbsp;&nbsp;&nbsp;&nbsp;' .
							htmlspecialchars($this->getLL('l_allTables')) .
							'</span><br />';
				} elseif ($tempFT) {
					$onlySingleTableAllowed = (count($tempFT) == 1);
					foreach ($tempFT as $theT) {
						$info .= '<span class="nobr">&nbsp;&nbsp;&nbsp;&nbsp;' .
								t3lib_iconWorks::getSpriteIconForRecord($theT, array()) .
								htmlspecialchars($this->sL($GLOBALS['TCA'][$theT]['ctrl']['title'])) .
								'</span><br />';
					}
				}

				$perms_clause = $GLOBALS['BE_USER']->getPagePermsClause(1);
				$itemArray = array();
				$imgs = array();

				// Thumbnails:
				$temp_itemArray = t3lib_div::trimExplode(',', $PA['itemFormElValue'], 1);
				foreach ($temp_itemArray as $dbRead) {
					$recordParts = explode('|', $dbRead);
					list($this_table, $this_uid) = t3lib_BEfunc::splitTable_Uid($recordParts[0]);
					// For the case that no table was found and only a single table is defined to be allowed, use that one:
					if (!$this_table && $onlySingleTableAllowed) {
						$this_table = $allowed;
					}
					$itemArray[] = array('table' => $this_table, 'id' => $this_uid);
					if (!$disabled && $show_thumbs) {
						$rr = t3lib_BEfunc::getRecordWSOL($this_table, $this_uid);
						$imgs[] = '<span class="nobr">' .
								$this->getClickMenu(
										t3lib_iconWorks::getSpriteIconForRecord(
												$this_table, $rr, array(
											'style' => 'vertical-align:top',
											'title' => htmlspecialchars(t3lib_BEfunc::getRecordPath($rr['pid'], $perms_clause, 15) . ' [UID: ' . $rr['uid'] . ']')
												)
										), $this_table, $this_uid
								) .
								'&nbsp;' .
								t3lib_BEfunc::getRecordTitle($this_table, $rr, TRUE) . ' <span class="typo3-dimmed"><em>[' . $rr['uid'] . ']</em></span>' .
								'</span>';
					}
				}
				$thumbsnail = '';
				if (!$disabled && $show_thumbs) {
					$thumbsnail = implode('<br />', $imgs);
				}

				// Creating the element:
				$noList = isset($config['disable_controls']) && t3lib_div::inList($config['disable_controls'], 'list');
				$params = array(
					'size' => $size,
					'dontShowMoveIcons' => ($maxitems <= 1),
					'autoSizeMax' => t3lib_div::intInRange($config['autoSizeMax'], 0),
					'maxitems' => $maxitems,
					'style' => isset($config['selectedListStyle']) ? ' style="' . htmlspecialchars($config['selectedListStyle']) . '"' : ' style="' . $this->defaultMultipleSelectorStyle . '"',
					'info' => $info,
					'thumbnails' => $thumbsnail,
					'readOnly' => $disabled,
					'noBrowser' => $noList || (isset($config['disable_controls']) && t3lib_div::inList($config['disable_controls'], 'browser')),
					'noList' => $noList,
				);
				$item .= $this->dbFileIcons($PA['itemFormElName'], 'db', implode(',', $tempFT), $itemArray, '', $params, $PA['onFocus'], $table, $field, $row['uid']);

				break;
		}

		// Wizards:
		$altItem = '<input type="hidden" name="' . $PA['itemFormElName'] . '" value="' . htmlspecialchars($PA['itemFormElValue']) . '" />';
		if (!$disabled) {
			$item = $this->renderWizards(array($item, $altItem), $config['wizards'], $table, $row, $field, $PA, $PA['itemFormElName'], $specConf);
		}

		return $item;
	}

	function croppedThumbs($row, $table, $field, $backPath, $thumbScript = '', $uploaddir = NULL, $abs = 0, $tparams = '', $size = '') {
		global $TCA;
		// Load table.
		t3lib_div::loadTCA($table);

		// Find uploaddir automatically
		$uploaddir = (is_null($uploaddir)) ? $TCA[$table]['columns'][$field]['config']['uploadfolder'] : $uploaddir;
		$uploaddir = preg_replace('#/$#', '', $uploaddir);

		// Set thumbs-script:
		if (!$GLOBALS['TYPO3_CONF_VARS']['GFX']['thumbnails']) {
			$thumbScript = 'gfx/notfound_thumb.gif';
		} elseif (!$thumbScript) {
			$thumbScript = 'thumbs.php';
		}
		// Check and parse the size parameter
		$sizeParts = array();
		if ($size = trim($size)) {
			$sizeParts = explode('x', $size . 'x' . $size);
			if (!intval($sizeParts[0])) {
				$size = '';
			}
		}

		// Traverse files:
		$thumbs = explode(',', $row[$field]);
		$thumbData = '';
		foreach ($thumbs as $theFile) {
			if (trim($theFile)) {
				$fI = t3lib_div::split_fileref($theFile);
				$ext = $fI['fileext'];
				// New 190201 start
				$max = 0;
				if (t3lib_div::inList('gif,jpg,png', $ext)) {
					$imgInfo = @getimagesize(PATH_site . $uploaddir . '/' . $theFile);
					if (is_array($imgInfo)) {
						$max = max($imgInfo[0], $imgInfo[1]);
					}
				}
				// use the original image if it's size fits to the thumbnail size
				if ($max && $max <= (count($sizeParts) && max($sizeParts) ? max($sizeParts) : 56)) {
					$theFile = $url = ($abs ? '' : '../') . ($uploaddir ? $uploaddir . '/' : '') . trim($theFile);
					$onClick = 'top.launchView(\'' . $theFile . '\',\'\',\'' . $backPath . '\');return false;';
					$thumbData .= '<img src="' . $backPath . $url . '" ' . $imgInfo[3] . ' hspace="2" border="0" title="Crop"' . $tparams . ' alt="" />';
					// New 190201 stop
				} elseif ($ext == 'ttf' || t3lib_div::inList($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'], $ext)) {
					$theFile_abs = PATH_site . ($uploaddir ? $uploaddir . '/' : '') . trim($theFile);
					$theFile = ($abs ? '' : '../') . ($uploaddir ? $uploaddir . '/' : '') . trim($theFile);

					if (!is_readable($theFile_abs)) {
						$flashMessage = t3lib_div::makeInstance(
										't3lib_FlashMessage', $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:warning.file_missing_text') . ' <abbr title="' . $theFile_abs . '">' . $theFile . '</abbr>', $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:warning.file_missing'), t3lib_FlashMessage::ERROR
						);
						$thumbData .= $flashMessage->render();
						continue;
					}

					$check = basename($theFile_abs) . ':' . filemtime($theFile_abs) . ':' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'];
					$params = '&file=' . rawurlencode($theFile);
					$params .= $size ? '&size=' . $size : '';
					$params .= '&md5sum=' . t3lib_div::shortMD5($check);

					$url = $thumbScript . '?&dummy=' . $GLOBALS['EXEC_TIME'] . $params;
					$onClick = 'top.launchView(\'' . $theFile . '\',\'\',\'' . $backPath . '\');return false;';
					$thumbData .= '<img src="' . htmlspecialchars($backPath . $url) . '" hspace="2" border="0" title="Crop"' . $tparams . ' alt="" /> ';
				} else {
					$icon = self::getFileIcon($ext);
					$url = 'gfx/fileicons/' . $icon;
					$thumbData .= '<img src="' . $backPath . $url . '" hspace="2" border="0" title="' . trim($theFile) . '"' . $tparams . ' alt="" /> ';
				}
			}
		}
		return $thumbData;
	}

}

?>
