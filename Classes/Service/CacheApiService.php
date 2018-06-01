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
use MichielRoos\Doctor\Domain\Model\Suggestion;
use MichielRoos\Doctor\Utility\DatabaseUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class CacheApiService
 * @package MichielRoos\Doctor\Service
 */
class CacheApiService extends BaseApiService
{
	/**
	 * Get some basic cache information.
	 *
	 * @return array
	 */
	public function getInfo()
	{
		$this->results[] = new Header('Cache information');

		$this->listCacheConfigurations();
		$this->getCacheHashUsage();
		$this->getCacheHashSizeByType();

		return $this->results;
	}

	/**
	 * List cache configurations
	 */
	public function listCacheConfigurations()
	{
		$hasDbCaches = false;
		$configurations = $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'];
		$this->results[] = new Header('Cache Configurations');
		$this->results[] = new KeyValueHeader('name', 'backend / frontend');
		foreach ($configurations as $key => $configuration) {
			$value = '';
			if (array_key_exists('backend', $configuration) && $configuration['backend']) {
				$backendCache = $this->getEnd($configuration['backend']);
				$value = $backendCache . ' / ';

				if (strpos($backendCache, 'DatabaseBackend') || strpos($backendCache, 'DbBackend')) {
					$hasDbCaches = true;
				}
			}
			$this->results[] = new KeyValuePair($key, $value . $this->getEnd($configuration['frontend']));
		}
		if ($hasDbCaches) {
			$this->results[] = new Suggestion('Site has one or more database caches. Consider using Redis cache backends where possible to reduce the load on your database server.');
		}
	}

	/**
	 * Return last part of cache class
	 *
	 * @param $string
	 * @return bool|string
	 */
	private function getEnd($string)
	{
		if (strpos($string, '\\') === false) {
			return $string;
		}
		return substr(strrchr($string, "\\"), 1);
	}

	/**
	 * Get cache_hash usage
	 */
	public function getCacheHashUsage()
	{
		$databaseHandler = $this->getDatabaseHandler();
		$result = $databaseHandler->sql_query("SELECT COUNT(*) as `total`, tag FROM `cf_cache_hash_tags` GROUP BY tag ORDER BY total DESC");
		$this->results[] = new Header('Cache hash usage by tag');
		$this->results[] = new KeyValueHeader('tag', 'rows');
		while ($row = $databaseHandler->sql_fetch_assoc($result)) {
			$this->results[] = new KeyValuePair($row['tag'], number_format($row['total']));
		}
		$databaseHandler->sql_free_result($result);
	}

	/**
	 * Get cache_hash usage: size used per type
	 */
	public function getCacheHashSizeByType()
	{
		$databaseHandler = $this->getDatabaseHandler();
		$result = $databaseHandler->sql_query("
			SELECT SUM( LENGTH( c.content ) ) AS size, t.tag
			FROM cf_cache_hash AS c
			JOIN  `cf_cache_hash_tags` AS t ON c.identifier = t.identifier
			GROUP BY t.tag
			ORDER BY size DESC");
		$this->results[] = new Header('Cache hash size by tag (total size: %s)', [GeneralUtility::formatSize(DatabaseUtility::getTableSize('cf_cache_hash'))]);
		$this->results[] = new KeyValueHeader('tag', 'total size');
		while ($row = $databaseHandler->sql_fetch_assoc($result)) {
			$this->results[] = new KeyValuePair($row['tag'], GeneralUtility::formatSize($row['size']));
		}
		$databaseHandler->sql_free_result($result);
	}
}
