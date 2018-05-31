<?php
namespace MichielRoos\Doctor\Service;

/**
 * ⓒ 2018 Michiel Roos <michiel@michielroos.com>
 * All rights reserved
 *
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html
 *
 * The TYPO3 project - inspiring people to share!
 */

use MichielRoos\Doctor\Domain\Model\Header;
use MichielRoos\Doctor\Domain\Model\KeyValueHeader;
use MichielRoos\Doctor\Domain\Model\KeyValuePair;
use MichielRoos\Doctor\Domain\Model\ListItem;

/**
 * Class OverridesApiService
 * @package MichielRoos\Doctor\Service
 */
class OverridesApiService extends BaseApiService
{
	/**
	 * Get information about overrides
	 *
	 * @return array
	 */
	public function getInfo()
	{
		$this->results[] = new Header('Overrides and Xclasses');

		$this->getOldXclassUsageStatus();
		$this->getObjectOverrideStatus();

		return $this->results;
	}


	/**
	 * Check for usage of old way of implementing XCLASSes
	 */
	protected function getOldXclassUsageStatus()
	{
		$xclasses = array_merge(
			(array)$GLOBALS['TYPO3_CONF_VARS']['BE']['XCLASS'],
			(array)$GLOBALS['TYPO3_CONF_VARS']['FE']['XCLASS']
		);

		$numberOfXclasses = count($xclasses);
		if ($numberOfXclasses > 0) {
			$this->results[] = new Header('%s Xclasses found', [$numberOfXclasses]);

			foreach ($xclasses as $xclass) {
				$this->results[] = new ListItem($xclass);
			}
		}
	}

	/**
	 * List any Object overrides registered in the stystem 
	 */
	protected function getObjectOverrideStatus()
	{
		$xclassFoundArray = [];
		if (array_key_exists('Objects', $GLOBALS['TYPO3_CONF_VARS']['SYS'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'] as $originalClass => $override) {
				if (array_key_exists('className', $override)) {
					$xclassFoundArray[$originalClass] = $override['className'];
				}
			}
		}
		if (count($xclassFoundArray) > 0) {
			$this->results[] = new Header('%s Object overrides found:', [$xclassFoundArray]);
			$this->results[] = new KeyValueHeader('original class', 'override');
			foreach ($xclassFoundArray as $originalClass => $xClassName) {
				$this->results[] = new KeyValuePair($originalClass, $xClassName);
			}
		}
	}
}
