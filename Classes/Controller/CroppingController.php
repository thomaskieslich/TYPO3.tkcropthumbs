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

/**
 * Class Cropping Controller
 */
class CroppingController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {

	/**
	 * @var array
	 */
	protected $getVars;

	/**
	 * @var array
	 */
	protected $formVars = array();

	/**
	 * @var object
	 */
	protected $referenceObject;

	/**
	 * @var \ThomasKieslich\Tkcropthumbs\Domain\Repository\ContentRepository
	 *
	 * @inject
	 */
	protected $contentRepository;

	/**
	 * @var \TYPO3\CMS\Core\Resource\FileRepository
	 * @inject
	 */
	protected $fileRepository;

	/**
	 * @var array
	 */
	protected $fVars = array();

	/**
	 *
	 * @throws \RuntimeException
	 * @return void
	 */
	public function cropAction() {
		$this->getVars = \TYPO3\CMS\Core\Utility\GeneralUtility::_GET();

		$referenceUid = intval(str_replace('sys_file_', '', $this->getVars['image']));

		if (is_int($referenceUid)) {
			//Reference
			$this->referenceObject = $this->fileRepository->findFileReferenceByUid($referenceUid);

			//cObj
			$referenceProperties = $this->referenceObject->getProperties();
			$this->cObj = $this->contentRepository->findByUid($referenceProperties[uid_foreign]);
		}

		$this->fVars['imgPath'] = $referenceProperties['identifier'];
		$this->fVars['imgName'] = $referenceProperties['name'];
		$this->fVars['pubPath'] = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('tkcropthumbs') . 'Resources/Public/';

//		\TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($this, 'this');

		$this->view->assign('fVars', $this->fVars);
	}
}