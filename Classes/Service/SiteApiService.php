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
use MichielRoos\Doctor\Domain\Model\KeyValuePair;
use MichielRoos\Doctor\Utility\DatabaseUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class SiteApiService
 */
class SiteApiService extends BaseApiService
{
    /**
     * Get some basic site information.
     *
     * @return array
     */
    public function getInfo()
    {
        $this->results[] = new Header('Site information');

        $this->results[] = new KeyValuePair('TYPO3 version', TYPO3_version);
        $this->results[] = new KeyValuePair('Site name', $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']);

        $this->getDiskUsage();
        $this->getDatabaseSize();
        $this->getSiteRoots();
        $this->getPages();

        return $this->results;
    }

    /**
     * Get disk usage.
     *
     * @param array $data
     *
     * @return array
     */
    public function getDiskUsage()
    {
        if (TYPO3_OS !== 'WIN') {
            $this->results[] = new KeyValuePair('Combined disk usage',
                trim(array_shift(explode("\t", shell_exec('du -sh ' . PATH_site)))));
        }
    }

    /**
     * Get database size.
     */
    public function getDatabaseSize()
    {
        $this->results[] = new KeyValuePair('Database size',
            GeneralUtility::formatSize(DatabaseUtility::getDatabaseSize()));
    }

    /**
     * Get site roots.
     */
    public function getSiteRoots()
    {
        $databaseHandler = $this->getDatabaseHandler();
        $result = $databaseHandler->sql_query('SELECT uid, title FROM pages WHERE hidden = 0 AND deleted = 0 AND is_siteroot = 1');
        $this->results[] = new KeyValuePair('Site count', $databaseHandler->sql_num_rows($result));
        while ($root = $databaseHandler->sql_fetch_assoc($result)) {
            $this->results[] = new KeyValuePair('', $root['title'] . ' [' . $root['uid'] . ']');
        }
        $databaseHandler->sql_free_result($result);
    }

    /**
     * Get total number of pages.
     */
    public function getPages()
    {
        $databaseHandler = $this->getDatabaseHandler();
        $result = $databaseHandler->sql_query('SELECT COUNT(*) AS total FROM pages WHERE hidden = 0 AND deleted = 0');
        $count = $databaseHandler->sql_fetch_assoc($result);
        $this->results[] = new KeyValuePair('Total number of pages', number_format($count['total']));
        $databaseHandler->sql_free_result($result);
    }
}
