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
 *   38: class ux_tx_dam_tceFunc extends tx_dam_tceFunc
 *   48:     function getSingleField_typeMedia($PA, &$fObj)
 *  201:     function renderFileList($filesArray, $displayThumbs=true, $PA, $disabled=false)
 *
 * TOTAL FUNCTIONS: 2
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */
class ux_tx_dam_tceFunc extends tx_dam_tceFunc {

	/**
	 * Generation of TCEform element of the type "group" for media elements.
	 * This is used to select media records in eg. tt_content.
	 *
	 * @param	array		$PA An array with additional configuration options.
	 * @param	object		$fobj TCEForms object reference
	 * @return	string		The HTML code for the TCEform field
	 */
	function getSingleField_typeMedia($PA, &$fObj) {
		global $TYPO3_CONF_VARS;

		$this->tceforms = &$PA['pObj'];


		if (!(($msg = $this->isMMForeignActive()) === true)) {
			return $this->tceforms->getSingleField_typeNone_render(array('rows' => 1), $msg);
		}


		$table = $PA['table'];
		$field = $PA['field'];
		$row = $PA['row'];
		$config = $PA['fieldConf']['config'];

		$disabled = '';
		if ($this->tceforms->renderReadonly || $config['readOnly']) {
			$disabled = ' disabled="disabled"';
		}

		$minitems = t3lib_div::intInRange($config['minitems'], 0);
		$maxitems = t3lib_div::intInRange($config['maxitems'], 0);
		if (!$maxitems)
			$maxitems = 100000;

		$this->tceforms->requiredElements[$PA['itemFormElName']] = array($minitems, $maxitems, 'imgName' => $table . '_' . $row['uid'] . '_' . $field);

		$item = '';
		$item .= '<input type="hidden" name="' . $PA['itemFormElName'] . '_mul" value="' . ($config['multiple'] ? 1 : 0) . '"' . $disabled . ' />';

		$info = '';

		// Acting according to either "file" or "db" type:
		switch ((string) $config['internal_type']) {
			case 'db': // If the element is of the internal type "db":
				// Creating string showing allowed types:
				$tempFT_db = t3lib_div::trimExplode(',', $config['allowed'], true);
				while (list(, $theT) = each($tempFT_db)) {
					if ($theT) {
						$info .= '<span class="nobr">&nbsp;&nbsp;&nbsp;&nbsp;' .
								t3lib_iconWorks::getIconImage($theT, array(), $this->tceforms->backPath, 'align="top"') .
								$this->tceforms->sL($GLOBALS['TCA'][$theT]['ctrl']['title'], true) .
								'</span><br />';
					}
				}

				// Creating string showing allowed types:
				$tempFT = t3lib_div::trimExplode(',', $config['allowed_types'], true);
				if (!count($tempFT)) {
					$info .= '*';
				}
				foreach ($tempFT as $ext) {
					if ($ext) {
						$info .= strtoupper($ext) . ' ';
					}
				}

				// Creating string, showing disallowed types:
				$tempFT_dis = t3lib_div::trimExplode(',', $config['disallowed_types'], true);
				if (count($tempFT_dis)) {
					$info .= '<br />';
				}
				foreach ($tempFT_dis as $ext) {
					if ($ext) {
						$info .= '-' . strtoupper($ext) . ' ';
					}
				}



				// Collectiong file items:
				$itemArray = array();
				$filesArray = array();
				if (intval($row['uid'])) {
					$filesArray = tx_dam_db::getReferencedFiles($table, $row['uid'], $config['MM_match_fields'], $config['MM'], 'tx_dam.*');
					foreach ($filesArray['rows'] as $row) {
						$itemArray[] = array('table' => 'tx_dam', 'id' => $row['uid'], 'title' => ($row['title'] ? $row['title'] : $row['file_name']));
					}
				}

				$thumbsnails = $this->renderFileList($filesArray, $config['show_thumbs'], $PA);
				/*
				  // making thumbnails
				  $thumbsnails = '';
				  if ($config['show_thumbs'] AND count($filesArray))	{

				  foreach($filesArray['rows'] as $row)	{

				  // Icon
				  $absFilePath = tx_dam::file_absolutePath($row);
				  $fileExists = @file_exists($absFilePath);

				  $addAttrib = 'class="absmiddle"';
				  $addAttrib .= tx_dam_guiFunc::icon_getTitleAttribute($row);
				  $fileIcon = tx_dam::icon_getFileTypeImgTag($row, $addAttrib);


				  // add clickmenu
				  if ($fileExists AND !$disabled) {
				  #							$fileIcon = $this->tceforms->getClickMenu($fileIcon, $absFilePath);
				  $fileIcon = $this->tceforms->getClickMenu($fileIcon, 'tx_dam', $row['uid']);
				  }

				  $title = t3lib_div::fixed_lgd_cs($this->tceforms->noTitle($row['title']), $this->tceforms->titleLen);

				  $thumb = tx_dam_image::previewImgTag($row, '', 'align="middle"');

				  $thumbDescr = '<div class="nobr">'.$fileIcon.$title.'<br />'.$row['file_name'].'</div>';

				  $thumbsnails .= '<tr><td>'.$thumb.'</td><td>'.$thumbDescr.'</td></tr>';
				  }
				  $thumbsnails = '<table border="0">'.$thumbsnails.'</table>';
				  }
				 */

				// Creating the element:
				$params = array(
					'size' => intval($config['size']),
					'dontShowMoveIcons' => ($maxitems <= 1),
					'autoSizeMax' => t3lib_div::intInRange($config['autoSizeMax'], 0),
					'maxitems' => $maxitems,
					'style' => isset($config['selectedListStyle']) ? ' style="' . htmlspecialchars($config['selectedListStyle']) . '"' : ' style="' . $this->tceforms->defaultMultipleSelectorStyle . '"',
					'info' => $info,
					'thumbnails' => $thumbsnails,
					'readOnly' => $disabled
				);

				// Extra parameter for DAM element browser
				$user_eb_param = $config['allowed_types'];
				$item .= $this->dbFileIcons($PA['itemFormElName'], 'db', implode(',', $tempFT_db), $itemArray, '', $params, $PA['onFocus'], $user_eb_param);
				break;
		}

		// Wizards:
		if (!$disabled) {
			$specConf = $this->tceforms->getSpecConfFromString($PA['extra'], $PA['fieldConf']['defaultExtras']);
			$altItem = '<input type="hidden" name="' . $PA['itemFormElName'] . '" value="' . htmlspecialchars($PA['itemFormElValue']) . '" />';
			$item = $this->tceforms->renderWizards(array($item, $altItem), $config['wizards'], $table, $row, $field, $PA, $PA['itemFormElName'], $specConf);
		}

		return $item;
	}

	/**
	 * Render list of files.
	 *
	 * @param	array		$filesArray List of files. See tx_dam_db::getReferencedFiles
	 * @param	boolean		$displayThumbs
	 * @param	array		$PA
	 * @param	boolean		$disabled
	 * @return	string		HTML output
	 */
	function renderFileList($filesArray, $displayThumbs=true, $PA = NULL, $disabled=false) {
		global $LANG;


		$out = '';

		// Listing the files:
		if (is_array($filesArray) && count($filesArray)) {

			$lines = array();
			foreach ($filesArray['rows'] as $row) {

				$absFilePath = tx_dam::file_absolutePath($row);
				$fileExists = @file_exists($absFilePath);


				$addAttrib = 'class="absmiddle"';
				$addAttrib .= tx_dam_guiFunc::icon_getTitleAttribute($row);
				$iconTag = tx_dam::icon_getFileTypeImgTag($row, $addAttrib);


				// add clickmenu
				if ($fileExists && !$disabled) {
#							$fileIcon = $this->tceforms->getClickMenu($fileIcon, $absFilePath);
					$iconTag = $this->tceforms->getClickMenu($iconTag, 'tx_dam', $row['uid']);
				}

				$title = $row['title'] ? t3lib_div::fixed_lgd_cs($row['title'], $this->tceforms->titleLen) : t3lib_BEfunc::getNoRecordTitle();

				// Create link to showing details about the file in a window:
				if ($fileExists) {
					#$Ahref = $GLOBALS['BACK_PATH'].'show_item.php?table='.rawurlencode($absFilePath).'&returnUrl='.rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI'));
					$onClick = 'top.launchView(\'tx_dam\', \'' . $row['uid'] . '\');';
					$onClick = 'top.launchView(\'' . $absFilePath . '\');';
					$ATag_info = '<a href="#" onclick="' . htmlspecialchars($onClick) . '">';
					$info = $ATag_info . '<img' . t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], 'gfx/zoom2.gif', 'width="12" height="12"') . ' title="' . $LANG->getLL('info', 1) . '" alt="" /> ' . $LANG->getLL('info', 1) . '</a>';
				} else {
					$info = '&nbsp;';
				}

				// Thumbnail/size generation:
				$clickThumb = '';
				if ($displayThumbs && $fileExists && tx_dam_image::isPreviewPossible($row)) {
					$clickThumb = tx_dam_image::previewImgTag($row);
					$clickThumb = '<div class="clickThumb">' . $clickThumb . '</div>';
				} elseif ($displayThumbs) {
					$clickThumb = '<div style="width:68px"></div>';
				}


				// Show element:
				$lines[] = '
					<tr class="bgColor4">
						<td valign="top" nowrap="nowrap" style="min-width:20em">' . $iconTag . htmlspecialchars($title) . '&nbsp;</td>
						<td valign="top" nowrap="nowrap" width="1%">' . $info . '</td>
					</tr>';


				$infoText = tx_dam_guiFunc::meta_compileInfoData($row, 'file_name, file_size:filesize, _dimensions, caption:truncate:50', 'table');
				$infoText = str_replace('<table>', '<table border="0" cellpadding="0" cellspacing="1">', $infoText);
				$infoText = str_replace('<strong>', '<strong style="font-weight:normal;">', $infoText);
				$infoText = str_replace('</td><td>', '</td><td class="bgColor-10">', $infoText);

				//tkcropthumbs
				$relPath = t3lib_extMgm::extRelPath('tkcropthumbs');
				$uid = $PA[row][uid];
				$croplink = "<a href=\"#\" onclick=\"window.open('"
						. "mod.php?M=tkcropthumbs_crop&image=" . $row['file_path'] . $row['file_name']
						. "&uid=" . $uid . "&aspectratio=" . $PA[row][tx_tkcropthumbs_aspectratio] . "','tkcropthumbs" . rand(0, 1000000)
						. "','height=620,width=820,status=0,menubar=0,scrollbars=0');return false;\"><img src=\""
						. $relPath . "res/icons/crop_dam.png\" border=\"0\" /></a>";


				if ($displayThumbs) {
					$lines[] = '
						<tr class="bgColor">
							<td valign="top" colspan="2">
							<table border="0" cellpadding="0" cellspacing="0"><tr>
								<td valign="top">' . $clickThumb . '</td>
								<td valign="top" style="padding-left:1em">' . $infoText . '</td>
								<td valign="top">'.$croplink.'</td>	
								</tr>
							</table>
							<div style="height:0.5em;"></div>
							</td>
						</tr>';
				} else {
					$lines[] = '
						<tr class="bgColor">
							<td valign="top" colspan="2" style="padding-left:22px">
							' . $infoText . '
							<div style="height:0.5em;"></div>
							</td>
						</tr>';
				}

				$lines[] = '
						<tr>
							<td colspan="2"><div style="height:0.5em;"></div></td>
						</tr>';
			}

			// Wrap all the rows in table tags:
			$out .= '

		<!--
			File listing
		-->
				<table border="0" cellpadding="1" cellspacing="1">
					' . implode('', $lines) . '
				</table>';
		}

		// Return accumulated content for filelisting:
		return $out;
	}

}

?>
