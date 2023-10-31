<?php

namespace MichielRoos\Doctor\Utility;

use MichielRoos\Doctor\Utility\Frontend\TyposcriptUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ContentUtility
{
    /**
     * @var int
     */
    private $limit = 30;

    /**
     * @var array
     */
    private $results;

    /**
     * Get some basic content information.
     *
     * @param string $contentType The content type (CType) to inspect
     * @param string $listType The list type (plugin) to inspect
     * @param int $limit Show up to [limit] records found
     * @return array
     * @throws \TYPO3\CMS\Core\Error\Http\ServiceUnavailableException
     * @throws \TYPO3\CMS\Core\Error\Http\ServiceUnavailableException
     */
    public function getInfo($contentType, $listType, $limit)
    {
        if ($limit) {
            $this->limit = $limit;
        }

        TyposcriptUtility::setupTsfe();

        $this->getContentElements();
        $this->getContentTypes();
        $this->getPluginTypes();
        if ($contentType) {
            $this->getContentTypeUsage($contentType);
        }
        if ($listType) {
            $this->getPluginTypeUsage($listType);
        }

        return $this->results;
    }

    /**
     * Get total number of content elements.
     */
    public function getContentElements()
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll();

        $queryBuilder
            ->count('*')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq(
                    'deleted',
                    $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                ),
            );

        $total = $queryBuilder->execute()->fetchColumn(0);

        $this->results['info']['total'] = $total;
    }

    /**
     * Get content type usage count
     *
     * The TypoScript Frontend is probed for available content types.
     * The database is probed for content types that are in use.
     *
     * The result is an array of all content types and their usage count
     */
    public function getContentTypes()
    {
        $setup        = $GLOBALS['TSFE']->tmpl->setup;
        $contentTypes = ArrayUtility::noDots(array_keys($setup['tt_content.']));
        $usage        = $this->getContentElementUsage();
        $used         = [];
        $unused       = [];

        foreach ($usage as $row) {
            $used[$row['CType']] = $row;
        }

        $usedTypes = array_keys($used);

        foreach ($contentTypes as $contentType) {
            if (!in_array($contentType, $usedTypes)) {
                $unused[$contentType] = ['CType' => $contentType, 'total' => 0];
            }
        }

        foreach ($used as $key => $value) {
            $this->results['content_types'][$key] = $value;
        }
        foreach ($unused as $key => $value) {
            $this->results['content_types'][$key] = $value;
        }
    }

    /**
     * Get content type usage.
     * @param $contentType
     */
    public function getContentTypeUsage($contentType)
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll();

        $queryBuilder
            ->selectLiteral('
                p.uid        AS page_id,
                p.title      AS page_title,
                c.header     AS content_header,
                c.CType      AS content_type,
                c.uid        AS content_id
                ')
            ->from('tt_content', 'c')
            ->join('c', 'pages', 'p', 'c.pid = p.uid')
            ->where(
                $queryBuilder->expr()->eq('c.CType', $queryBuilder->createNamedParameter($contentType, Connection::PARAM_STR)),
                $queryBuilder->expr()->eq('c.hidden', 0),
                $queryBuilder->expr()->eq('c.deleted', 0),
                $queryBuilder->expr()->eq('p.hidden', 0),
                $queryBuilder->expr()->eq('p.deleted', 0),
            );

        if ($this->limit > 0) {
            $queryBuilder->setMaxResults($this->limit);
        }

        $result = $queryBuilder->execute()->fetchAll();

        $this->results['content_type_usage'] = $result;
    }

    /**
     * Get available plugin elements
     */
    public function getPluginTypes()
    {
        $setup       = $GLOBALS['TSFE']->tmpl->setup;
        $pluginTypes = ArrayUtility::noDots(array_keys($setup['tt_content.']['list.']['20.']));
        unset($pluginTypes['key']);
        unset($pluginTypes['stdWrap']);
        $usage  = $this->getPluginUsage();
        $used   = [];
        $unused = [];

        foreach ($usage as $row) {
            $used[$row['list_type']] = $row;
        }

        $usedTypes = array_keys($used);

        foreach ($pluginTypes as $pluginType) {
            if (!in_array($pluginType, $usedTypes)) {
                $unused[$pluginType] = ['list_type' => $pluginType, 'total' => 0];
            }
        }

        foreach ($used as $key => $value) {
            $this->results['plugin_types'][$key] = $value;
        }
        foreach ($unused as $key => $value) {
            $this->results['plugin_types'][$key] = $value;
        }
    }

    /**
     * Get plugin type usage
     * @param $listType
     */
    public function getPluginTypeUsage($listType)
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll();

        $queryBuilder
            ->selectLiteral('
                p.uid        AS page_id,
                p.title      AS page_title,
                c.header     AS content_header,
                c.list_type  AS list_type,
                c.uid        AS content_id
                ')
            ->from('tt_content', 'c')
            ->join('c', 'pages', 'p', 'c.pid = p.uid')
            ->where(
                $queryBuilder->expr()->eq('c.CType', $queryBuilder->createNamedParameter('list', Connection::PARAM_STR)),
                $queryBuilder->expr()->eq('c.list_type', $queryBuilder->createNamedParameter($listType, Connection::PARAM_STR)),
                $queryBuilder->expr()->eq('c.hidden', 0),
                $queryBuilder->expr()->eq('c.deleted', 0),
                $queryBuilder->expr()->eq('p.hidden', 0),
                $queryBuilder->expr()->eq('p.deleted', 0),
            );

        if ($this->limit > 0) {
            $queryBuilder->setMaxResults($this->limit);
        }

        $result = $queryBuilder->execute()->fetchAll();

        $this->results['plugin_type_usage'] = $result;
    }

    /**
     * Get content element usage information
     */
    private function getContentElementUsage(): array
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll();

        $queryBuilder
            ->selectLiteral('CType, COUNT(*) AS `total`')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('hidden', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
            )
            ->groupBy('CType')
            ->orderBy('total', 'DESC');

        $result = $queryBuilder->execute()->fetchAll();

        return $result;
    }

    /**
     * Get plugin usage information
     */
    private function getPluginUsage()
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll();

        $queryBuilder
            ->selectLiteral('list_type, COUNT(*) AS `total`')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq('CType', $queryBuilder->createNamedParameter('list', Connection::PARAM_STR)),
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('hidden', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
            )
            ->groupBy('list_type')
            ->orderBy('total', 'DESC');

        $result = $queryBuilder->execute()->fetchAll();

        return $result;
    }
}
