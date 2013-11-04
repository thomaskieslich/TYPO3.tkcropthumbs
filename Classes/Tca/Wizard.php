<?php
namespace ThomasKieslich\Tkcropthumbs\Tca;
/**
 * Created by PhpStorm.
 * User: Thomas
 * Date: 02.11.13
 * Time: 15:40
 */

/**
 * Class Wizard
 *
 * @package ThomasKieslich\Tkcropthumbs\Tca
 */
class Wizard {
	public function showIcon($fObj) {
//		\TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($fObj['row']['tx_tkcropthumbs_crop']);
		$iconPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('tkcropthumbs') . 'Resources/Public/Icons';
		$formField = '<a href="#"  onclick="window.open(\'';
		$formField .= 'mod.php?M=user_TkcropthumbsCrop&image=' . $fObj['row']['uid'];
		$formField .= '\');">';
		$formField .= '<img src="' . $iconPath . '/crop.png">';
		$formField .= '</a>';
//		$return = '<a href="#"  onclick="window.open(\''
//			. 'mod.php?M=tkcropthumbs_crop&image=' . $fObj['row']['uid_local']
//			. '&aspectratio=' . $fObj['itemFormElValue']
//			. '\',\'fenster' . rand(0, 1000000) . ''
//			. '\',\'height=620,width=820,status=0,menubar=0,scrollbars=0\');return false;">123
//			<img src="EXT:tkcropthumbs/Resources/Public/Icons/crop.png" width="100%" height="100%">
//			</a>';
//		$return .= '<input type="hidden" value="' . $fObj['itemFormElValue'] . '" name="' . $fObj['itemFormElName'] . '" id="zabus_crop_fal_input" />';

		return $formField;
	}
}