<?php
/***************************************************************
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
 ***************************************************************/

class ext_update {

	/**
	 * Main function, returning the HTML content of the module
	 *
	 * @return	string		HTML
	 */
	function main() {
		$content = '';
		//update tt_content
		$old = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows(
				'tx_tkcropthumbs_crop',
				'tt_content'
		);

		if($old) {
			$query = 'ALTER TABLE tt_content CHANGE tx_tkcropthumbs_aspectratio tx_tkcropthumbs_aspectratio_deleted_zzz  TINYINT( 3 ) NOT NULL DEFAULT 0';
			$renameNew = $GLOBALS['TYPO3_DB']->admin_query($query);

			$query = 'ALTER TABLE tt_content CHANGE tx_tkcropthumbs_crop tx_tkcropthumbs_aspectratio  TINYINT( 3 ) NOT NULL DEFAULT 0';
			$renameOld = $GLOBALS['TYPO3_DB']->admin_query($query);

			$query = 'ALTER TABLE tt_content DROP tx_tkcropthumbs_aspectratio_deleted_zzz';
			$deleteNew = $GLOBALS['TYPO3_DB']->admin_query($query);

			$content .= 'rename tt_content field tx_tkcropthumbs_crop to tx_tkcropthumbs_aspectratio oK<br />';
		}

		else $content .= 'nothing to do<br />';


		//updatee tx_tkcropthumbs
		$oldtx = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows(
				'pid',
				'tx_tkcropthumbs'
		);

		if($oldtx) {
			$query = 'ALTER TABLE tx_tkcropthumbs CHANGE uid uid_deleted_zzz INT( 11 ) UNSIGNED NOT NULL DEFAULT 0';
			$renameNew = $GLOBALS['TYPO3_DB']->admin_query($query);

			$query = 'ALTER TABLE tx_tkcropthumbs CHANGE pid uid INT( 11 ) UNSIGNED NOT NULL DEFAULT 0';
			$renameOld = $GLOBALS['TYPO3_DB']->admin_query($query);

			$query = 'ALTER TABLE tx_tkcropthumbs DROP uid_deleted_zzz';
			$deleteNew = $GLOBALS['TYPO3_DB']->admin_query($query);

			$content .= 'rename tx_tkcropthumbs field pid to uid oK<br />';
			$content .= 'update oK<br />';
		}

		else $content .= 'nothing to do<br />';
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