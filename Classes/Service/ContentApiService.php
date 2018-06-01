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
use MichielRoos\Doctor\Utility\ArrayUtility;
use MichielRoos\Doctor\Utility\Frontend\TyposcriptUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Class ContentApiService
 * @package MichielRoos\Doctor\Service
 */
class ContentApiService extends BaseApiService
{
	/**
	 * Get some basic site information.
	 *
	 * @return array
	 * @throws \TYPO3\CMS\Core\Error\Http\ServiceUnavailableException
	 */
	public function getInfo()
	{
		TyposcriptUtility::setupTsfe();
		$this->results[] = new Header('Content information');

		$this->getContentElements();
		$this->getContentTypes();
		$this->getPluginTypes();

		return $this->results;
	}

	/**
	 * Get total number of content elements.
	 */
	public function getContentElements()
	{
		$databaseHandler = $this->getDatabaseHandler();
		$result = $databaseHandler->sql_query('SELECT COUNT(*) AS total FROM tt_content WHERE deleted = 0');
		$count = $databaseHandler->sql_fetch_assoc($result);
		$this->results[] = new KeyValuePair('Total number of content elements', number_format($count['total']));
		$databaseHandler->sql_free_result($result);
	}

	/**
	 * Get available content elements
	 */
	public function getContentTypes()
	{
		$setup = $GLOBALS['TSFE']->tmpl->setup;
		$contentTypes = ArrayUtility::noDots(array_keys($setup['tt_content.']));
		$this->results[] = new Header('Content usage');
		$usage = $this->getContentElementUsage();
		$used = [];
		$unused = [];
		foreach ($contentTypes as $contentType) {
			if (in_array($contentType, array_keys($usage))) {
				$used[$contentType] = $usage[$contentType];
			} else {
				$unused[$contentType] = 'unused';
			}
		}
		$this->results[] = new KeyValueHeader('contentType', 'count');
		arsort($used);
		foreach ($used as $key => $value) {
			$this->results[] = new KeyValuePair($key, number_format($value));
		}
		foreach ($unused as $key => $value) {
			$this->results[] = new KeyValuePair($key, $value);
		}
	}

	/**
	 * Get available plugin elements
	 */
	public function getPluginTypes()
	{
		$setup = $GLOBALS['TSFE']->tmpl->setup;
		$pluginTypes = ArrayUtility::noDots(array_keys($setup['tt_content.']['list.']['20.']));
		unset($pluginTypes['key']);
		unset($pluginTypes['stdWrap']);
		$this->results[] = new Header('Plugin usage');
		$usage = $this->getPluginUsage();
		$used = [];
		$unused = [];
		foreach ($pluginTypes as $pluginType) {
			if (in_array($pluginType, array_keys($usage))) {
				$used[$pluginType] = $usage[$pluginType];
			} else {
				$unused[$pluginType] = 'unused';
			}
		}
		arsort($used);
		$this->results[] = new KeyValueHeader('pluginType', 'count');
		foreach ($used as $key => $value) {
			$this->results[] = new KeyValuePair($key, number_format($value));
		}
		foreach ($unused as $key => $value) {
			$this->results[] = new KeyValuePair($key, $value);
		}
	}

	/**
	 * Get content element usage information
	 */
	private function getContentElementUsage()
	{
		$usage = [];
		$databaseHandler = $this->getDatabaseHandler();
		$result = $databaseHandler->sql_query(
			"SELECT
			  COUNT(*) AS `total`, CType
			FROM
			  `tt_content`
			GROUP BY CType
			ORDER BY total DESC;"
		);
		while ($row = $databaseHandler->sql_fetch_assoc($result)) {
			$usage[$row['CType']] = $row['total'];
		}
		$databaseHandler->sql_free_result($result);
		return $usage;
	}

	/**
	 * Get plugin usage information
	 */
	private function getPluginUsage()
	{
		$usage = [];
		$databaseHandler = $this->getDatabaseHandler();
		$result = $databaseHandler->sql_query(
			"SELECT
			  COUNT(*) AS `total`, list_type
			FROM
			  `tt_content`
			WHERE
			  CType = 'list'
			GROUP BY list_type
			ORDER BY total DESC;"
		);
		while ($row = $databaseHandler->sql_fetch_assoc($result)) {
			$usage[$row['list_type']] = number_format($row['total']);
		}
		$databaseHandler->sql_free_result($result);
		return $usage;
	}
}
