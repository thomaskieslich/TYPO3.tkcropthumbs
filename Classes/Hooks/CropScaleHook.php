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
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectGetImageResourceHookInterface;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Class CropScaleHook
 *
 * @package ThomasKieslich\Tkcropthumbs\Hooks
 */
class CropScaleHook implements ContentObjectGetImageResourceHookInterface {

	/**
	 * @var int
	 */
	protected $currentContentObject;

	/**
	 * @var object
	 */
	protected $serviceClass;

	/**
	 * Hook for post-processing image resources
	 *
	 * @param string $file Original image file
	 * @param array $configuration TypoScript getImgResource properties
	 * @param array $imageResource Information of the created/converted image resource
	 * @param ContentObjectRenderer $parent Parent content object
	 * @return array Modified image resource information
	 */
	public function getImgResourcePostProcess($file, array $configuration, array $imageResource, ContentObjectRenderer $parent) {

		$currentTable = $parent->getCurrentTable();
		$confTables = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_tkcropthumbs.']['tables.'];
		$cropEnabled = FALSE;

		if (array_key_exists($currentTable . '.', $confTables)) {
			$data = $parent->data;
			$field = $confTables[$currentTable . '.']['field'];
			$values = GeneralUtility::trimExplode(',', $confTables[$currentTable . '.']['values'], TRUE);

			foreach ($values as $value) {
				if ($data[$field] == $value) {
					$cropEnabled = TRUE;
				}
			}
		}

		$serviceObject = NULL;
		if (!$cropEnabled) {
			return $imageResource;
		} else {
			$classPath = $confTables[$currentTable . '.']['classPath'];
			if (isset($classPath)) {
				if (!$this->currentContentObject || $this->currentContentObject != $parent->data['uid']) {
					$this->currentContentObject = $parent->data['uid'];
					$this->serviceClass = GeneralUtility::makeInstance($classPath);
				}
				$cropPopUp = $confTables[$currentTable . '.']['cropPopUp'];
				$serviceObject = $this->serviceClass->init($file, $configuration, $imageResource, $parent, $cropPopUp);
			}
		}

		if (!$serviceObject) {
			return $imageResource;
		} else {
			$imageResource = $this->processImage($file, $serviceObject);
		}

		return $imageResource;
	}

	/**
	 * process the image like ContentObjectRenderer
	 *
	 * @param $file
	 * @param $processingConfiguration
	 * @return mixed
	 */
	protected function processImage($file, $processingConfiguration) {
		$file = GeneralUtility::resolveBackPath($file);
		$fileObject = ResourceFactory::getInstance()->retrieveFileOrFolderObject($file);

		$processedFileObject = $fileObject->process(ProcessedFile::CONTEXT_IMAGECROPSCALEMASK, $processingConfiguration);

		$hash = $processedFileObject->calculateChecksum();
		// store info in the TSFE template cache (kept for backwards compatibility)
		if ($processedFileObject->isProcessed() && !isset($GLOBALS['TSFE']->tmpl->fileCache[$hash])) {
			$GLOBALS['TSFE']->tmpl->fileCache[$hash] = array(
				0 => $processedFileObject->getProperty('width'),
				1 => $processedFileObject->getProperty('height'),
				2 => $processedFileObject->getExtension(),
				3 => $processedFileObject->getPublicUrl(),
				'origFile' => $fileObject->getPublicUrl(),
				'origFile_mtime' => $fileObject->getModificationTime(),
				// This is needed by \TYPO3\CMS\Frontend\Imaging\GifBuilder,
				// in order for the setup-array to create a unique filename hash.
				'originalFile' => $fileObject,
				'processedFile' => $processedFileObject,
				'fileCacheHash' => $hash
			);
		}
		return $GLOBALS['TSFE']->tmpl->fileCache[$hash];
	}
}