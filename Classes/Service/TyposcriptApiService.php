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
use MichielRoos\Doctor\Domain\Model\Notice;
use MichielRoos\Doctor\Domain\Model\Suggestion;
use MichielRoos\Doctor\Utility\ArrayUtility;
use MichielRoos\Doctor\Utility\Frontend\TyposcriptUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class TyposcriptApiService
 */
class TyposcriptApiService extends BaseApiService
{
    /**
     * Typoscript information
     *
     * @param string $key
     * @throws \TYPO3\CMS\Core\Error\Http\ServiceUnavailableException
     * @return array
     */
    public function getInfo($key = null)
    {
        $this->results[] = new Header('Typoscript information');

        $this->listTyposcriptTemplates();
        $this->getTyposcriptObjectSizes($key);

        return $this->results;
    }

    /**
     * List Typoscript templates
     */
    public function listTyposcriptTemplates()
    {
        $extTemplates = 0;
        $rootTemplatesOutsideSiteRoots = 0;
        $databaseHandler = $this->getDatabaseHandler();
        $result = $databaseHandler->sql_query(
            'SELECT
			  t.pid,
			  p.title AS pageTitle,
			  t.root,
			  t.uid,
			  t.title,
			  t.sitetitle,
			  t.clear,
			  p.is_siteroot
			FROM
			  `sys_template` AS t
			JOIN
			  pages AS p ON t.pid = p.uid
			WHERE
			  p.deleted = 0
			  AND p.hidden = 0
			  AND t.deleted = 0
			  AND t.hidden = 0
			ORDER BY p.is_siteroot DESC, t.root DESC;');

        $resultCount = $databaseHandler->sql_num_rows($result);
        $this->results[] = new Header('Typoscript templates found: %s', [$resultCount]);

        $this->results[] = new KeyValueHeader('Page title [uid]', 'Template title [uid]');
        while ($row = $databaseHandler->sql_fetch_assoc($result)) {
            if ((int)$row['root'] === 1 && (int)$row['is_siteroot'] === 0) {
                $rootTemplatesOutsideSiteRoots++;
            }
            if ((int)$row['root'] === 0) {
                $extTemplates++;
            }
            $key = $row['pageTitle'];
            if ($row['is_siteroot']) {
                $key .= ' [' . $row['pid'] . ', site root]';
            } else {
                $key .= ' [' . $row['pid'] . ']';
            }
            $value = $row['title'];
            if ($row['root']) {
                $value .= ' [' . $row['uid'] . ', root]';
            } else {
                $value .= ' [' . $row['uid'] . ']';
            }
            $this->results[] = new KeyValuePair($key, $value);
        }
        $databaseHandler->sql_free_result($result);
        if ($extTemplates > 5) {
            $this->results[] = new Suggestion('Site has %s extended Typoscript templates. Do you actually need all these templates?',
                [$extTemplates]);
        }
        if ($rootTemplatesOutsideSiteRoots) {
            $this->results[] = new Suggestion('Site has %s root Typoscript templates that are outside of actual siteroot pages. Is this intended?',
                [$rootTemplatesOutsideSiteRoots]);
        }
    }

    /**
     * Get Typoscript object sizes
     * @param null $key
     * @throws \TYPO3\CMS\Core\Error\Http\ServiceUnavailableException
     */
    public function getTyposcriptObjectSizes($key = null)
    {
        $this->results[] = new Header('Typoscript object sizes (strlen of serialized object)');
        TyposcriptUtility::setupTsfe();
        if ($key) {
            $setup = $this->getSetupByKey($key);
            if (!count($setup)) {
                $this->results[] = new Notice('Typoscript object not found for key "%s"', [$key]);

                return;
            }
            $this->results[] = new Notice('Report for objects within key "%s"', [$key]);
        } else {
            $setup = $GLOBALS['TSFE']->tmpl->setup;
        }
        $objects = ArrayUtility::dots(array_keys($setup));
        $objectsBySize = [];
        foreach ($objects as $object) {
            $objectsBySize[$object] = strlen(serialize($setup[$object]));
        }
        arsort($objectsBySize);

        foreach ($objectsBySize as $key => $value) {
            $this->results[] = new KeyValuePair($key, GeneralUtility::formatSize($value));
        }
    }

    /**
     * Get part of the TSFE by key
     *
     * @param $key
     * @return array
     */
    private function getSetupByKey($key)
    {
        $setup = [];
        $keyParts = explode('.', rtrim($key, '.'));
        if (count($keyParts)) {
            $setup = $GLOBALS['TSFE']->tmpl->setup;
            foreach ($keyParts as $keyPart) {
                $setup = $setup[$keyPart . '.'];
            }
        }

        return $setup;
    }
}
