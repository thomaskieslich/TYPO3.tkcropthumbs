<?php

namespace ThomasKieslich\Tkcropthumbs\Controller;

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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class AjaxController
 *
 * @package ThomasKieslich\Tkcropthumbs\Controller
 */
class AjaxController {

	/**
	 * init Ajax
	 *
	 * @return void
	 */
	public function init() {
		$getVars = GeneralUtility::_GET();
		$getAction = htmlspecialchars($getVars['action']);
		$getUid = intval(htmlspecialchars($getVars['uid']));
		$getCropValues = $getVars['cropValues'];

		switch ($getAction) {
			case 'save';
				$this->save($getUid, $getCropValues);
				break;
			case 'delete';
				$this->delete($getUid);
				break;
			default;
				break;
		}
	}

	/**
	 * @param $uid
	 * @param $cropValues
	 * @return void
	 */
	protected function save($uid, $cropValues) {
		$table = 'sys_file_reference';
		$where = 'uid = ' . $uid;
		$fieldValues = array(
			'tx_tkcropthumbs_crop' => json_encode($cropValues)
		);
		$db = $GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, $where, $fieldValues);

		echo $db;
	}

	/**
	 * @param $uid
	 * @return void
	 */
	protected function delete($uid) {
		$table = 'sys_file_reference';
		$where = 'uid = ' . $uid;
		$fieldValues = array(
			'tx_tkcropthumbs_crop' => ''
		);
		$db = $GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, $where, $fieldValues);

		echo $db;
	}
}