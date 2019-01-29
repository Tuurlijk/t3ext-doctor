<?php
namespace MichielRoos\Doctor\Utility\Frontend;

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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Class TyposcriptUtility
 */
class TyposcriptUtility
{
    /**
     * Find page that is marked as 'is_siteroot', preferabley with a domain record attached. If that is not available,
     * then find the first page with a domain record attached. Default to pid 1 if that exists.
     *
     * @return int
     */
    public static function getRootPageId()
    {
        $uid = 1;
        $databaseHandler = self::getDatabaseHandler();
        // Fetch all available domains
        $result = $databaseHandler->sql_query(
            'SELECT
			  d.pid,
			  d.domainName
			FROM
			  sys_domain AS d
			WHERE
			  d.hidden = 0
			ORDER BY d.pid, d.sorting;'
        );
        $domains = [];
        while ($row = $databaseHandler->sql_fetch_assoc($result)) {
            $domains[$row['domainName']] = $row['pid'];
        }
        $databaseHandler->sql_free_result($result);

        // Fetch pages with domains on them and site roots and page with uid 1
        if (count($domains)) {
            $domainIds = array_unique($domains);
        } else {
            $domainIds[] = 1;
        }
        $result = $databaseHandler->sql_query(
            'SELECT
			  p.uid,
			  p.is_siteroot
			FROM
			  pages AS p
			WHERE
			  p.deleted = 0
			  AND p.hidden = 0
			  AND (p.is_siteroot = 1 OR p.uid = 1 OR p.uid IN(' . implode(',', $domainIds) . '))
			ORDER BY p.is_siteroot DESC;'
        );
        $pages = [];
        $siteRoots = [];
        $siteRootsWithDomain = [];
        while ($row = $databaseHandler->sql_fetch_assoc($result)) {
            if ($row['is_siteroot']) {
                $uid = $row['uid'];
                $siteRoots[] = $row['uid'];
                if (in_array($uid, $domains)) {
                    $siteRootsWithDomain[] = $uid;
                }
            } else {
                $pages[] = $row['uid'];
            }
        }
        $databaseHandler->sql_free_result($result);
        if (count($siteRootsWithDomain)) {
            $uid = array_pop($siteRootsWithDomain);
        } elseif (count($siteRoots)) {
            $uid = array_pop($siteRoots);
        } elseif (in_array(1, $pages)) {
            $uid = 1;
        }

        return (int)$uid;
    }

    /**
     * Setup Typoscript Frontend controller
     * @throws \TYPO3\CMS\Core\Error\Http\ServiceUnavailableException
     */
    public static function setupTsfe()
    {
        $GLOBALS['TT'] = new \TYPO3\CMS\Core\TimeTracker\NullTimeTracker();

        $tsfe = GeneralUtility::makeInstance(
            TypoScriptFrontendController::class,
            $GLOBALS['TYPO3_CONF_VARS'],
            self::getRootPageId(),
            0
        );

        $GLOBALS['TSFE'] = $tsfe;

        $tsfe->connectToDB();
        $tsfe->initFEuser();
        $tsfe->determineId();
        $tsfe->initTemplate();
        $tsfe->getConfigArray();
    }

    /**
     * Returns the DatabaseConnection
     *
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseHandler()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
