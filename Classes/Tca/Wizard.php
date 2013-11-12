<?php
namespace ThomasKieslich\Tkcropthumbs\Tca;

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
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Class Wizard
 */
class Wizard {

	public function showIcon($fObj) {
		$iconPath = ExtensionManagementUtility::extRelPath('tkcropthumbs') . 'Resources/Public/Icons';
		$icon = 'crop.png';
		if ($fObj['row']['tx_tkcropthumbs_crop']) {
			$icon = 'crop_act.png';
		}

		$formField = '<a href="#"  onclick="window.open(\'';
		$formField .= 'mod.php?M=user_TkcropthumbsCrop&reference=' . $fObj['row']['uid'];
		$formField .= '\',\'tkcropthumbs' . rand(0, 1000000) . '';
		$formField .= '\',\'height=620,width=820,status=0,menubar=0,scrollbars=0\');return false;">';
		$formField .= '<img src="' . $iconPath . '/' . $icon . '" id="' . $fObj['itemFormElName'] . '">';
		$formField .= '</a>';
		return $formField;
	}
}