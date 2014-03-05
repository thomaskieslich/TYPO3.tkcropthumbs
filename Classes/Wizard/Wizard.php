<?php
namespace ThomasKieslich\Tkcropthumbs\Wizard;

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
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Wizard
 */
class Wizard {

	/**
	 * @param $fObj
	 * @return string
	 */
	public function showIcon($fObj) {
		$iconPath = ExtensionManagementUtility::extRelPath('tkcropthumbs') . 'Resources/Public/Icons';
		$icon = 'crop.png';
		if ($fObj['row']['tx_tkcropthumbs_crop']) {
			$icon = 'crop_act.png';
		}

		$moduleName = 'txtkcropthumbsM1';

		$allUrlParameters = array();
		$allUrlParameters['M'] = $moduleName;
		$allUrlParameters['moduleToken'] = FormProtectionFactory::get()->generateToken('moduleCall', $moduleName);
		$allUrlParameters['reference'] = $fObj['row']['uid'];
		$url = 'mod.php?' . ltrim(GeneralUtility::implodeArrayForUrl('', $allUrlParameters, '', TRUE, TRUE), '&');

		if (is_numeric($fObj['row']['uid'])) {
			$formField = '<a href="#"  onclick="window.open(\'';
			$formField .= $url;
			$formField .= '\',\'tkcropthumbs' . rand(0, 1000000) . '';
			$formField .= '\',\'height=620,width=820,status=0,menubar=0,scrollbars=0\');return false;">';
			$formField .= '<img src="' . $iconPath . '/' . $icon . '" id="' . $fObj['itemFormElName'] . '">';
			$formField .= '</a>';
		} else {
			$icon = 'crop_save.png';
			$formField = '<img src="' . $iconPath . '/' . $icon . '" id="' . $fObj['itemFormElName'] . '">';
		}
		return $formField;
	}
}