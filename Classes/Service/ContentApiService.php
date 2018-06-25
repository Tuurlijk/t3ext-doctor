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
use MichielRoos\Doctor\Utility\ArrayUtility;
use MichielRoos\Doctor\Utility\Frontend\TyposcriptUtility;

/**
 * Class ContentApiService
 */
class ContentApiService extends BaseApiService
{
    /**
     * @var int
     */
    private $limit = 30;

    /**
     * Get some basic site information.
     *
     * @param string $contentType The content type (CType) to inspect
     * @param string $listType The list type (plugin) to inspect
     * @param int $limit Show up to [limit] records found
     * @throws \TYPO3\CMS\Core\Error\Http\ServiceUnavailableException
     * @return array
     */
    public function getInfo($contentType, $listType, $limit)
    {
        if ((int)$limit) {
            $this->limit = (int)$limit;
        }

        TyposcriptUtility::setupTsfe();
        $this->results[] = new Header('Content information');

        $this->getContentElements();
        $this->getContentTypes();
        $this->getPluginTypes();
        if ($contentType) {
            $contentType = mysqli_real_escape_string($this->getDatabaseHandler()->getDatabaseHandle(), $contentType);
            $this->getContentTypeUsage($contentType);
        }
        if ($listType) {
            $listType = mysqli_real_escape_string($this->getDatabaseHandler()->getDatabaseHandle(), $listType);
            $this->getPluginTypeUsage($listType);
        }

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
        $this->results[] = new Header('Content usage (excluding hidden and deleted rows)');
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
     * Get content type usage.
     * @param $contentType
     */
    public function getContentTypeUsage($contentType)
    {
        $databaseHandler = $this->getDatabaseHandler();
        $result = $databaseHandler->sql_query(sprintf("SELECT
			  p.uid        AS pageId,
			  p.title      AS pageTitle,
			  c.header     AS contentHeader,
			  c.CType      AS contentType,
			  c.uid        AS contentId,
			  c.hidden     AS contentHidden,
			  c.deleted    AS contentDeleted,
			  p.hidden     AS pageHidden,
			  p.deleted    AS pageDeleted
			FROM tt_content AS c
			  JOIN pages AS p ON p.uid = c.pid
			WHERE c.CType LIKE ('%%%s%%');
			", $contentType, $this->limit));
        $count = $databaseHandler->sql_num_rows($result);
        if (!$count) {
            $this->results[] = new Header('No content elements of type "%s" found', [$contentType]);

            return;
        }
        $this->results[] = new Header('Content elements of type "%s"', [$contentType]);
        $this->results[] = new KeyValuePair('Total number of content elements', number_format($count));
        $this->results[] = new KeyValueHeader('pageTitle [id,deleted,hidden]',
            'contentHeader [id,deleted,hidden,CType]');
        $i = 0;
        while ($row = $databaseHandler->sql_fetch_assoc($result)) {
            $key = $row['pageTitle'];
            $note = [];
            $note[] = $row['pageId'];
            ($row['pageDeleted']) ? $note[] = 'd' : '';
            ($row['pageHidden']) ? $note[] = 'h' : '';
            $key .= ' [' . implode(',', $note) . ']';
            $value = $row['contentHeader'];
            $note = [];
            $note[] = $row['contentId'];
            ($row['contentDeleted']) ? $note[] = 'd' : '';
            ($row['contentHidden']) ? $note[] = 'h' : '';
            $note[] = $row['contentType'];
            $value .= ' [' . implode(',', $note) . ']';
            $this->results[] = new KeyValuePair($key, $value);
            $i++;
            if ($i >= $this->limit) {
                $this->results[] = new Notice('%s records found, increase the "limit" of %s if you want to see more.',
                    [number_format($count), $this->limit]);
                break;
            }
        }
        $databaseHandler->sql_free_result($result);
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
        $this->results[] = new Header('Plugin usage (excluding hidden and deleted rows)');
        $usage = $this->getPluginUsage();
        $used = [];
        $unused = [];
        $usedKeys = array_keys($usage);
        foreach ($pluginTypes as $pluginType) {
            if (in_array($pluginType, $usedKeys)) {
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
     * Get plugin type usage
     * @param $listType
     */
    public function getPluginTypeUsage($listType)
    {
        $databaseHandler = $this->getDatabaseHandler();
        $result = $databaseHandler->sql_query(sprintf("SELECT
			  p.uid        AS pageId,
			  p.title      AS pageTitle,
			  c.header     AS contentHeader,
			  c.list_type  AS listType,
			  c.uid        AS contentId,
			  c.hidden     AS contentHidden,
			  c.deleted    AS contentDeleted,
			  p.hidden     AS pageHidden,
			  p.deleted    AS pageDeleted
			FROM tt_content AS c
			  JOIN pages AS p ON p.uid = c.pid
			WHERE
			  CType = 'list'
			  AND c.list_type LIKE ('%%%s%%');
			", $listType, $this->limit));
        $count = $databaseHandler->sql_num_rows($result);
        if (!$count) {
            $this->results[] = new Header('No plugins of type "%s" found', [$listType]);

            return;
        }
        $this->results[] = new Header('Plugins of type "%s"', [$listType]);
        $this->results[] = new KeyValuePair('Total number of plugins', number_format($count));
        $this->results[] = new KeyValueHeader('pageTitle [id,deleted,hidden]',
            'contentHeader [id,deleted,hidden,type]');
        $i = 0;
        while ($row = $databaseHandler->sql_fetch_assoc($result)) {
            $key = $row['pageTitle'];
            $note = [];
            $note[] = $row['pageId'];
            ($row['pageDeleted']) ? $note[] = 'd' : '';
            ($row['pageHidden']) ? $note[] = 'h' : '';
            $key .= ' [' . implode(',', $note) . ']';
            $value = $row['contentHeader'];
            $note = [];
            $note[] = $row['contentId'];
            ($row['contentDeleted']) ? $note[] = 'd' : '';
            ($row['contentHidden']) ? $note[] = 'h' : '';
            $note[] = $row['listType'];
            $value .= ' [' . implode(',', $note) . ']';
            $this->results[] = new KeyValuePair($key, $value);
            $i++;
            if ($i >= $this->limit) {
                $this->results[] = new Notice('%s records found, increase the "limit" of %s if you want to see more.',
                    [number_format($count), $this->limit]);
                break;
            }
        }
        $databaseHandler->sql_free_result($result);
    }

    /**
     * Get content element usage information
     */
    private function getContentElementUsage()
    {
        $usage = [];
        $databaseHandler = $this->getDatabaseHandler();
        $result = $databaseHandler->sql_query(
            'SELECT
			  COUNT(*) AS `total`, CType
			FROM
			  `tt_content`
			WHERE
			  deleted = 0
			  AND hidden = 0
			GROUP BY CType
			ORDER BY total DESC;'
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
			  AND deleted = 0
			  AND hidden = 0
			GROUP BY list_type
			ORDER BY total DESC;"
        );
        while ($row = $databaseHandler->sql_fetch_assoc($result)) {
            $usage[$row['list_type']] = $row['total'];
        }
        $databaseHandler->sql_free_result($result);

        return $usage;
    }
}
