<?php
namespace MichielRoos\Doctor\Command;

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

use MichielRoos\Doctor\Service\CacheApiService;
use MichielRoos\Doctor\Service\ContentApiService;
use MichielRoos\Doctor\Service\DatabaseApiService;
use MichielRoos\Doctor\Service\OverridesApiService;
use MichielRoos\Doctor\Service\SiteApiService;
use MichielRoos\Doctor\Service\TyposcriptApiService;

/**
 * Class DoctorCommandController
 * @package MichielRoos\Doctor\Command
 */
class DoctorCommandController extends BaseCommandController
{
	/**
	 * @var \MichielRoos\Doctor\Service\CacheApiService
	 */
	protected $cacheApiService;

	/**
	 * @var \MichielRoos\Doctor\Service\ContentApiService
	 */
	protected $contentApiService;

	/**
	 * @var \MichielRoos\Doctor\Service\DatabaseApiService
	 */
	protected $databaseApiService;

	/**
	 * @var \MichielRoos\Doctor\Service\TyposcriptApiService
	 */
	protected $typoscriptApiService;

	/**
	 * @var \MichielRoos\Doctor\Service\SiteApiService
	 */
	protected $siteApiService;

	/**
	 * @var \MichielRoos\Doctor\Service\OverridesApiService
	 */
	protected $overridesApiService;

	/**
	 * Information about the whole system
	 */
	public function infoCommand()
	{
		$this->siteCommand();
		$this->databaseCommand();
		$this->typoscriptCommand();
		$this->contentCommand();
		$this->cacheCommand();
		$this->overridesCommand();
	}

	/**
	 * Cache information
	 */
	public function cacheCommand()
	{
		$this->cacheApiService = $this->objectManager->get(CacheApiService::class);
		$results = $this->cacheApiService->getInfo();
		$this->writeResults($results);
	}

	/**
	 * Content information
	 */
	public function contentCommand()
	{
		$this->contentApiService = $this->objectManager->get(ContentApiService::class);
		$results = $this->contentApiService->getInfo();
		$this->writeResults($results);
	}

	/**
	 * Database information
	 *
	 * @param int $limit The limit to use in top [n] table queries
	 * @param string $table The table to inspect
	 */
	public function databaseCommand($limit = 30, $table = NULL)
	{
		$this->databaseApiService = $this->objectManager->get(DatabaseApiService::class);
		$results = $this->databaseApiService->getInfo($limit, $table);
		$this->writeResults($results);
	}

	/**
	 * Overrides information
	 */
	public function overridesCommand()
	{
		$this->overridesApiService = $this->objectManager->get(OverridesApiService::class);
		$results = $this->overridesApiService->getInfo();
		$this->writeResults($results);
	}

	/**
	 * System information
	 */
	public function siteCommand()
	{
		$this->siteApiService = $this->objectManager->get(SiteApiService::class);
		$results = $this->siteApiService->getInfo();
		$this->writeResults($results);
	}

	/**
	 * Typoscript information
	 */
	public function typoscriptCommand()
	{
		$this->typoscriptApiService = $this->objectManager->get(TyposcriptApiService::class);
		$results = $this->typoscriptApiService->getInfo();
		$this->writeResults($results);
	}
}
