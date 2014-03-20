<?php

namespace ThomasKieslich\Tkcropthumbs\Hooks;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Thomas Kieslich
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

use TYPO3\CMS\Backend\Form\Element\InlineElementHookInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class InlineElementHook
 *
 * @package ThomasKieslich\Tkcropthumbs\Hooks
 */
class InlineElementHook implements InlineElementHookInterface {

	/**
	 * Initializes this hook object.
	 *
	 * @param \TYPO3\CMS\Backend\Form\Element\InlineElement $parentObject
	 * @return void
	 */
	public function init(&$parentObject) {
	}

	/**
	 * Pre-processing to define which control items are enabled or disabled.
	 *
	 * @param string $parentUid The uid of the parent (embedding) record (uid or NEW...)
	 * @param string $foreignTable The table (foreign_table) we create control-icons for
	 * @param array $childRecord The current record of that foreign_table
	 * @param array $childConfig TCA configuration of the current field of the child record
	 * @param boolean $isVirtual Defines whether the current records is only virtually shown and not physically part of the parent record
	 * @param array &$enabledControls (reference) Associative array with the enabled control items
	 * @return void
	 */
	public function renderForeignRecordHeaderControl_preProcess($parentUid, $foreignTable, array $childRecord, array $childConfig, $isVirtual, array &$enabledControls) {
	}

	/**
	 * Post-processing to define which control items to show. Possibly own icons can be added here.
	 *
	 * @param string $parentUid The uid of the parent (embedding) record (uid or NEW...)
	 * @param string $foreignTable The table (foreign_table) we create control-icons for
	 * @param array $childRecord The current record of that foreign_table
	 * @param array $childConfig TCA configuration of the current field of the child record
	 * @param boolean $isVirtual Defines whether the current records is only virtually shown and not physically part of the parent record
	 * @param array &$controlItems (reference) Associative array with the currently available control items
	 * @return void
	 */
	public function renderForeignRecordHeaderControl_postProcess($parentUid, $foreignTable, array $childRecord, array $childConfig, $isVirtual, array &$controlItems) {
		if (is_int((int)$parentUid) && $foreignTable === 'sys_file_reference' && $childRecord) {
			$confTables = $this->getTsConfig($childRecord);
			$parentTable = $childRecord['tablenames'];
			$cropEnabled = FALSE;

			if (array_key_exists($parentTable . '.', $confTables)) {
				$field = $confTables[$parentTable . '.']['field'];
				$values = GeneralUtility::trimExplode(',', $confTables[$parentTable . '.']['values'], TRUE);
				$record = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('*', $parentTable, "uid = '" . $parentUid . "'");
				foreach ($values as $value) {
					if ($record[$field] == $value) {
						$cropEnabled = TRUE;
					}
				}
			}
			if ($cropEnabled) {
				$icon = $this->getIcon($childRecord);
				$extraItem = array('imageCrop' => $icon);
				$controlItems = $extraItem + $controlItems;
			}
		}
	}

	/**
	 * @param $childRecord
	 * @return mixed
	 */
	protected function getTsConfig($childRecord) {

		/** @var $template \TYPO3\CMS\Core\TypoScript\TemplateService */
		$template = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\TypoScript\\TemplateService');
		$template->tt_track = 0;
		$template->setProcessExtensionStatics(TRUE);
		$template->init();
		$rootline = array();
		if ($childRecord['pid'] > 0) {
			/** @var $sysPage \TYPO3\CMS\Frontend\Page\PageRepository */
			$sysPage = GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Page\\PageRepository');
			// Get the rootline for the current page
			$rootline = $sysPage->getRootLine($childRecord['pid'], '', TRUE);
		}
		$template->runThroughTemplates($rootline, 0);
		$template->generateConfig();

		$confTables = $template->setup['plugin.']['tx_tkcropthumbs.']['tables.'];

		return $confTables;
	}

	/**
	 * @param $childRecord
	 * @return string
	 */
	protected function getIcon($childRecord) {
		$iconPath = '../' . ExtensionManagementUtility::siteRelPath('tkcropthumbs') . 'Resources/Public/Icons';
		$icon = 'crop.png';
		if ($childRecord['tx_tkcropthumbs_crop']) {
			$icon = 'crop_act.png';
		}

		$moduleName = 'txtkcropthumbsM1';
		$urlParameters = array(
			'reference' => $childRecord['uid']
		);
		$url = BackendUtility::getModuleUrl($moduleName, $urlParameters);

		if (is_numeric($childRecord['uid'])) {
			$formField = '<a href="#" onclick="window.open(\'';
			$formField .= $url;
			$formField .= '\',\'tkcropthumbs' . rand(0, 1000000) . '';
			$formField .= '\',\'height=640,width=800,status=0,menubar=0,scrollbars=0\');return false;">';
			$formField .= '<img src="' . $iconPath . '/' . $icon . '">';
			$formField .= '</a>';
		} else {
			$icon = 'crop_save.png';
			$formField = '<img src="' . $iconPath . '/' . $icon . '" id="12">';
		}

		return $formField;
	}
}