<?php

namespace ThomasKieslich\Tkcropthumbs\Controller;

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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class CroppingController
 *
 * @package ThomasKieslich\Tkcropthumbs\Controller
 */
class CroppingController {

	/**
	 * @var array
	 */
	protected $content;

	/**
	 * @var array
	 */
	protected $aspectRatio;

	/**
	 * @var int
	 */
	protected $imageWidth;

	/**
	 * @var int
	 */
	protected $imageHeight;

	/**
	 * @var array
	 */
	protected $cropValues;

	/**
	 * Init
	 *
	 * @return void
	 */
	public function init() {
		$this->content = array();
		$this->content['extPath'] = ExtensionManagementUtility::siteRelPath('tkcropthumbs');

		$referenceUid = (int)str_replace('sys_file_', '', htmlspecialchars(GeneralUtility::_GET('reference')));

		if (is_numeric($referenceUid)) {
			//image
			$fileRepository = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\FileRepository');
			/** @var FileReference  $referenceObject */
			$referenceObject = $fileRepository->findFileReferenceByUid($referenceUid);
			$referenceProperties = $referenceObject->getProperties();

			$this->content['image'] = $referenceObject->getPublicUrl(TRUE);
			$this->content['imageUid'] = $referenceProperties['uid'];
			$this->imageWidth = $referenceProperties['width'];
			$this->imageHeight = $referenceProperties['height'];

			//aspectratio
			$selectFields = 'uid, tx_tkcropthumbs_aspectratio';
			$fromTable = 'tt_content';
			$whereClause = 'uid = ' . $referenceProperties['uid_foreign'];
			$whereClause .= ' AND hidden=0 AND deleted=0';

			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($selectFields, $fromTable, $whereClause);
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);

			if ($row['tx_tkcropthumbs_aspectratio']) {
				$this->aspectRatio = GeneralUtility::trimExplode(':', $row['tx_tkcropthumbs_aspectratio'], TRUE);
				$this->content['aspectRatio'] = implode(':', $this->aspectRatio);
			} else {
				$extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['tkcropthumbs']);
				$confValues = GeneralUtility::trimExplode(',', $extConf['aspectratio']);
				$presets = array(
					'' => '-:-'
				);
				foreach ($confValues as $preset) {
					$presets[$preset] = $preset;
				}
				$this->content['aspectratioPresets'] = $presets;
			}

			//crop values
			if (isset($referenceProperties['tx_tkcropthumbs_crop'])) {
				$import = json_decode($referenceProperties['tx_tkcropthumbs_crop'], TRUE);
			}
			if (isset($import) && count($import) >= 4 && count($import) <= 6) {
				$this->cropValues = $import;
				$this->content['script'] = $this->makeScript();
				$this->renderContent();
			} else {
				$this->initializeValues();
				$this->content['script'] = $this->makeScript();
				$this->renderContent();
			}
		}
	}

	/**
	 * Render Fluid Template
	 * @return void
	 */
	protected function renderContent() {
		$renderer = GeneralUtility::makeInstance('TYPO3\\CMS\\Fluid\\View\\StandaloneView');
		$template = ExtensionManagementUtility::extPath('tkcropthumbs') . 'Resources/Private/Templates/Backend/Backend.html';
		$renderer->setTemplatePathAndFilename($template);
		$renderer->getRequest()->setControllerExtensionName('Tkcropthumbs');
		$renderer->assign('content', $this->content);
		$view = $renderer->render();
		echo $view;
	}

	/**
	 * @return string
	 */
	protected function makeScript() {
		$ajaxUrl = BackendUtility::getAjaxUrl('TkcropthumbsAjaxController::init');

		$script = '
		<script>
			var imgUid = ' . $this->content['imageUid'] . ';
			var imgX1 = ' . $this->cropValues['x1'] . ';
			var imgY1 = ' . $this->cropValues['y1'] . ';
			var imgX2 = ' . $this->cropValues['x2'] . ';
			var imgY2 = ' . $this->cropValues['y2'] . ';
			var imgWidth = ' . $this->imageWidth . ';
			var imgHeight = ' . $this->imageHeight . ';
			var imgAr = "' . implode(':', $this->aspectRatio) . '";
			var ajaxUrl = "' . $ajaxUrl . '";
		</script>';

		return $script;
	}

	/**
	 * Initialize Values
	 *
	 * @return void
	 */
	protected function initializeValues() {

		if (!$this->aspectRatio) {
			$this->aspectRatio[0] = $this->imageWidth;
			$this->aspectRatio[1] = $this->imageHeight;
		}

		$orientation = ($this->imageWidth > $this->imageHeight) ? 'landscape' : 'portrait';
		if ((int)$this->imageHeight * ($this->aspectRatio[0] / $this->aspectRatio[1]) > $this->imageWidth) {
			$orientation = 'portrait';
		}

		if ($orientation == 'landscape') {
			$cWidth = (int)$this->imageHeight * ($this->aspectRatio[0] / $this->aspectRatio[1]);
			if ($cWidth == 0) {
				$cWidth = $this->imageWidth;
			}
			$this->cropValues['x1'] = (int)$this->imageWidth / 2 - $cWidth / 2;
			$this->cropValues['y1'] = 0;
			$this->cropValues['x2'] = $this->imageWidth - $this->cropValues['x1'];
			$this->cropValues['y2'] = $this->imageHeight;
		} elseif ($orientation == 'portrait') {
			$cHeight = (int)$this->imageWidth * ($this->aspectRatio[1] / $this->aspectRatio[0]);
			if ($cHeight == 0) {
				$cHeight = $this->imageHeight;
			}
			$this->cropValues['x1'] = 0;
			$this->cropValues['y1'] = (int)$this->imageHeight / 2 - $cHeight / 2;
			$this->cropValues['x2'] = $this->imageWidth;
			$this->cropValues['y2'] = $this->imageHeight - $this->cropValues['y1'];
		}

	}
}