<?php

/* * *************************************************************
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

class ext_update {

	/**
	 * Main function, returning the HTML content of the module
	 *
	 * @return	string		HTML
	 */
	function main() {
		$content = '';
		//update tt_content
		$updateArray = array(
			'tx_tkcropthumbs_aspectratio' => ''
		);
		$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tt_content', 'tx_tkcropthumbs_aspectratio=0', $updateArray);

		$updateArray = array(
			'tx_tkcropthumbs_aspectratio' => '1:1'
		);
		$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tt_content', 'tx_tkcropthumbs_aspectratio=1', $updateArray);
		
		$updateArray = array(
			'tx_tkcropthumbs_aspectratio' => '4:3'
		);
		$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tt_content', 'tx_tkcropthumbs_aspectratio=2', $updateArray);
		
		$updateArray = array(
			'tx_tkcropthumbs_aspectratio' => '13:9'
		);
		$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tt_content', 'tx_tkcropthumbs_aspectratio=3', $updateArray);
		
		$updateArray = array(
			'tx_tkcropthumbs_aspectratio' => '16:9'
		);
		$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tt_content', 'tx_tkcropthumbs_aspectratio=4', $updateArray);
		//1:1
//		$query = 'UPDATE tt_content SET tx_tkcropthumbs_aspectratio = \'1:1\' WHERE tx_tkcropthumbs_aspectratio = 1';
//		$renameAr = $GLOBALS['TYPO3_DB']->admin_query($query);

		//4:3
//		$query = 'UPDATE tt_content SET tx_tkcropthumbs_aspectratio = \'4:3\' WHERE tx_tkcropthumbs_aspectratio = 2';
//		$renameAr = $GLOBALS['TYPO3_DB']->admin_query($query);


		$content .= $res;
		return $content;
	}

	/**
	 * access is always allowed
	 *
	 * @return	boolean		Always returns true
	 */
	function access() {
		return true;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tkcropthumbs/class.ext_update.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tkcropthumbs/class.ext_update.php']);
}
?>