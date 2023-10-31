<?php

namespace MichielRoos\Doctor\Utility;

use Doctrine\DBAL\DBALException;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class DatabaseUtility
 */
class DatabaseUtility
{
    /**
     * @var string
     */
    private $table;

    /**
     * @var array
     */
    private $tableRowCount = [];

    /**
     * @var array
     */
    private $results;

    /**
     * Get database size.
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getDatabaseSize()
    {
        $size = 0;

        /** @var ConnectionPool $connectionPool */
        $connectionPool     = GeneralUtility::makeInstance(ConnectionPool::class);
        $databaseConnection = $connectionPool->getConnectionByName('Default');

        $queryBuilder = $databaseConnection->createQueryBuilder();
        $queryBuilder
            ->selectLiteral('SUM( data_length + index_length ) AS size')
            ->from('information_schema.TABLES')
            ->where(
                $queryBuilder->expr()->eq('table_schema', $queryBuilder->createNamedParameter($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['dbname']))
            );

        $statement = $queryBuilder->execute();

        if ($statement->rowCount()) {
            $row  = $statement->fetch();
            $size = $row['size'];
        }

        return $size;
    }

    /**
     * Get table size.
     * @param string $table
     * @return int
     * @throws DBALException
     */
    public function getTableSize(string $table = ''): int
    {
        $size = 0;
        if (!$table) {
            return $size;
        }

        /** @var ConnectionPool $connectionPool */
        $connectionPool     = GeneralUtility::makeInstance(ConnectionPool::class);
        $databaseConnection = $connectionPool->getConnectionByName('Default');

        $queryBuilder = $databaseConnection->createQueryBuilder();
        $queryBuilder
            ->selectLiteral('SUM(data_length + index_length) AS size')
            ->from('information_schema.TABLES')
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq('table_name', $queryBuilder->createNamedParameter($table)),
                    $queryBuilder->expr()->eq('table_schema', $queryBuilder->createNamedParameter($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['dbname']))
                )
            );

        $statement = $queryBuilder->execute();

        if ($statement->rowCount()) {
            $row  = $statement->fetch();
            $size = $row['size'];
        }

        return $size;
    }

    /**
     * Get table count.
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getTableCount(): int
    {
        return count($this->getTableNames());
    }

    /**
     * Get the largest tables by size
     */
    public function getLargestTablesBySize(int $limit = 30): array
    {
        /** @var ConnectionPool $connectionPool */
        $connectionPool     = GeneralUtility::makeInstance(ConnectionPool::class);
        $databaseConnection = $connectionPool->getConnectionByName('Default');

        /** @var \TYPO3\CMS\Core\Database\Query\QueryBuilder $queryBuilder */
        $queryBuilder = $databaseConnection->createQueryBuilder();
        $queryBuilder
            ->selectLiteral('table_name AS `table`, round(((data_length + index_length)), 2) `size`')
            ->from('information_schema.TABLES')
            ->where(
                $queryBuilder->expr()->eq('table_schema', $queryBuilder->createNamedParameter($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['dbname']))
            )->orderBy('size', 'DESC')
            ->setMaxResults($limit);

        $statement = $queryBuilder->execute();

        return $statement->fetchAll();
    }

    /**
     * Get the largest tables by size
     */
    public function getLargestTablesByRecordCount(int $limit = 30): array
    {
        /** @var ConnectionPool $connectionPool */
        $connectionPool     = GeneralUtility::makeInstance(ConnectionPool::class);
        $databaseConnection = $connectionPool->getConnectionByName('Default');

        /** @var \TYPO3\CMS\Core\Database\Query\QueryBuilder $queryBuilder */
        $queryBuilder = $databaseConnection->createQueryBuilder();
        $queryBuilder
            ->selectLiteral('TABLE_NAME as `table`, TABLE_ROWS as `rows`')
            ->from('information_schema.TABLES')
            ->where(
                $queryBuilder->expr()->eq('table_schema', $queryBuilder->createNamedParameter($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['dbname']))
            )->orderBy('rows', 'DESC')
            ->addOrderBy('table')
            ->setMaxResults($limit);

        $statement = $queryBuilder->execute();

        return $statement->fetchAll();
    }

    /**
     * Get table names
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getTableNames(): array
    {
        /** @var ConnectionPool $connectionPool */
        $connectionPool     = GeneralUtility::makeInstance(ConnectionPool::class);
        $databaseConnection = $connectionPool->getConnectionByName('Default');

        /** @var \TYPO3\CMS\Core\Database\Query\QueryBuilder $queryBuilder */
        $queryBuilder = $databaseConnection->createQueryBuilder();
        $queryBuilder
            ->selectLiteral('TABLE_NAME as `table`')
            ->from('information_schema.TABLES')
            ->where(
                $queryBuilder->expr()->eq('table_schema', $queryBuilder->createNamedParameter($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['dbname']))
            )
            ->orderBy('table');

        $result = $queryBuilder->execute();

        $names = [];
        while ($row = $result->fetch()) {
            $names[] = $row['table'];
        }
        return $names;
    }

    /**
     * Analyze the tables, so we can pull accurate information from the information_schema
     * @throws \Doctrine\DBAL\DBALException
     */
    public function analyzeTables(): void
    {
        $names = $this->getTableNames();

        /** @var ConnectionPool $connectionPool */
        $connectionPool     = GeneralUtility::makeInstance(ConnectionPool::class);

        foreach ($names as $name) {
            $connection = $connectionPool->getConnectionForTable($name);
            $connection->executeQuery('ANALYZE TABLE ' . $name);
        }
    }

    /**
     * Get the smallest tables by size
     */
    public function getSmallestTablesByRecordCount(int $limit = 30): array
    {
        /** @var ConnectionPool $connectionPool */
        $connectionPool     = GeneralUtility::makeInstance(ConnectionPool::class);
        $databaseConnection = $connectionPool->getConnectionByName('Default');

        /** @var \TYPO3\CMS\Core\Database\Query\QueryBuilder $queryBuilder */
        $queryBuilder = $databaseConnection->createQueryBuilder();
        $queryBuilder
            ->selectLiteral('TABLE_NAME as `table`, TABLE_ROWS as `rows`')
            ->from('information_schema.TABLES')
            ->where(
                $queryBuilder->expr()->eq('table_schema', $queryBuilder->createNamedParameter($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['dbname']))
            )->orderBy('rows')
            ->addOrderBy('table')
            ->setMaxResults($limit);

        $statement = $queryBuilder->execute();

        return $statement->fetchAll();
    }

    /**
     * Get tables and record count.
     * @return array
     * @throws DBALException
     */
    public function getTablesAndRecordCount(): array
    {
        $tableAndRowCount = [];
        
        foreach ($this->getTableNames() as $table) {
            $tableAndRowCount[$table] = $this->getTableRowCount($table);
        }

        return $tableAndRowCount;
    }

    /**
     * Check if a table has a column
     * @param string $table
     * @param string $column
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     */
    public function tableHasColumn(string $table, string $column): bool
    {
        /** @var ConnectionPool $connectionPool */
        $connectionPool     = GeneralUtility::makeInstance(ConnectionPool::class);
        $databaseConnection = $connectionPool->getConnectionByName('Default');

        $queryBuilder = $databaseConnection->createQueryBuilder();
        $queryBuilder
            ->select('COLUMN_NAME')
            ->from('INFORMATION_SCHEMA.COLUMNS')
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq('TABLE_SCHEMA', $queryBuilder->createNamedParameter($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['dbname'])),
                    $queryBuilder->expr()->eq('COLUMN_NAME', $queryBuilder->createNamedParameter($column)),
                    $queryBuilder->expr()->eq('TABLE_NAME', $queryBuilder->createNamedParameter($table))
                )
            );

        $statement = $queryBuilder->execute();

        return $statement->rowCount() === 1;
    }

    /**
     * Show all columns in table
     *
     * @param string $table
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getTableColumns(string $table = ''): array
    {
        $columns = [];
        if ($table === '') {
            return $columns;
        }

        /** @var ConnectionPool $connectionPool */
        $connectionPool     = GeneralUtility::makeInstance(ConnectionPool::class);
        $databaseConnection = $connectionPool->getConnectionByName('Default');

        $queryBuilder = $databaseConnection->createQueryBuilder();
        $queryBuilder
            ->select('COLUMN_NAME')
            ->from('INFORMATION_SCHEMA.COLUMNS')
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq('TABLE_SCHEMA', $queryBuilder->createNamedParameter($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['dbname'])),
                    $queryBuilder->expr()->eq('TABLE_NAME', $queryBuilder->createNamedParameter($table))
                )
            );

        $statement = $queryBuilder->execute();

        while ($row = $statement->fetch()) {
            $columns[] = $row['COLUMN_NAME'];
        }

        return $columns;
    }

    /**
     * Get deleted records
     *
     * @param string $table
     * @param int $tableRowCount
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getDeletedRecords(string $table = '', int $tableRowCount = 0): void
    {
        $tableSize = $this->getTableSize($table);

        $this->results[$table]['row_count']  = $tableRowCount;
        $this->results[$table]['table_size'] = $tableSize;

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll();

        $queryBuilder
            ->count('*')
            ->from($table)
            ->where($queryBuilder->expr()->eq('deleted', 1));
        $total = $queryBuilder->execute()->fetchColumn(0);

        if ($total > 0) {
            $percentage = $approximateSize = 0;
            if ($tableRowCount > 0) {
                $percentage      = 100 * $total / $tableRowCount;
                $approximateSize = $tableSize * $total / $tableRowCount;
            }
            $this->results[$table]['deleted_count']      = $total;
            $this->results[$table]['deleted_percentage'] = $percentage;
            $this->results[$table]['deleted_size']       = $approximateSize;
        } else {
            $this->results[$table]['deleted_count'] = 0;
        }
    }

    /**
     * Get hidden records
     *
     * @param string $table
     * @param int $tableRowCount
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getHiddenRecords(string $table = '', int $tableRowCount = 0): void
    {
        $tableSize = $this->getTableSize($table);

        $this->results[$table]['row_count']  = $tableRowCount;
        $this->results[$table]['table_size'] = $tableSize;

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll();

        $queryBuilder
            ->count('*')
            ->from($table)
            ->where($queryBuilder->expr()->eq('hidden', 1));
        $total = $queryBuilder->execute()->fetchColumn(0);

        if ($total > 0) {
            $percentage = $approximateSize = 0;
            if ($tableRowCount > 0) {
                $percentage      = $total * 100 / $tableRowCount;
                $approximateSize = $tableSize * $total / $tableRowCount;
            }
            $this->results[$table]['hidden_count']      = $total;
            $this->results[$table]['hidden_percentage'] = $percentage;
            $this->results[$table]['hidden_size']       = $approximateSize;
        } else {
            $this->results[$table]['hidden_count'] = 0;
        }
    }

    /**
     * Get table row count
     *
     * @param string $table
     * @param bool $withDeleted
     * @param bool $withHidden
     * @return int
     */
    public function getTableRowCount(string $table, bool $withDeleted = true, bool $withHidden = true): int
    {
        if (array_key_exists($table, $this->tableRowCount)) {
            return $this->tableRowCount[$table];
        }

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll();

        if (!$withDeleted) {
            $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        }

        if (!$withHidden) {
            $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(HiddenRestriction::class));
        }

        $queryBuilder
            ->count('*')
            ->from($table);

        $this->tableRowCount[$table] = (int)$queryBuilder->execute()->fetchColumn(0);

        return $this->tableRowCount[$table];
    }

    /**
     * Get table row count
     *
     * @param string $table
     * @param string $column
     * @param bool $withoutDeleted
     * @param bool $withoutHidden
     * @return array
     */
    public function getDistinctColumnValues(string $table, string $column, bool $withoutDeleted = true, bool $withoutHidden = true): array
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll();

        if ($withoutDeleted) {
            $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        }

        if ($withoutHidden) {
            $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(HiddenRestriction::class));
        }

        $queryBuilder
            ->selectLiteral('COUNT(*) as rows, ' . $column . ' as value')
            ->from($table)
            ->groupBy($column)
//            ->orderBy('rows', 'DESC')
            ->addOrderBy('value');

        return $queryBuilder->execute()->fetchAll();
    }

    /**
     * Analyze all tx_* columns for table excluding hidden and deleted rows
     * @throws \Doctrine\DBAL\DBALException
     */
    public function analyzeColumnsForTable(string $table = ''): array
    {
        $result = [];
        if ($table === '') {
            return $result;
        }

        $columns = $this->getTableColumns($table);

        $valuesWithPrefix = ArrayUtility::getValuesWithPrefix($columns, 'tx_');

//        $withoutDeleted = false;
//        if ($this->tableHasColumn($table, 'deleted')) {
//            $withoutDeleted = 1;
//        }
//        $withoutHidden = false;
//        if ($this->tableHasColumn($table, 'hidden')) {
//            $withoutHidden = true;
//        }

        $result['columnValues'] = [];
        foreach ($valuesWithPrefix as $tableColumn) {
            if (strpos($tableColumn, 'tx_') !== 0) {
                continue;
            }

            $columnValues                         = $this->getDistinctColumnValues($table, $tableColumn);
            $result['columnValues'][$tableColumn] = $columnValues;
        }

        return $result;
    }

    /**
     * Get record age with or without deleted and or hidden grouped by years ago from today
     * @param string $table
     * @param bool $onlyDeleted
     * @param bool $onlyHidden
     */
    public function getRecordAge(string $table, bool $onlyDeleted = false, bool $onlyHidden = false)
    {

        $tableRowCount = $this->getTableRowCount($table);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll();

        if ($onlyDeleted) {
            $queryBuilder
                ->count('*')
                ->from($table)
                ->where(
                    $queryBuilder->expr()->andX(
                        $queryBuilder->expr()->gt('tstamp', $queryBuilder->createNamedParameter(strtotime('-1 years'))),
                        $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)),
                    )
                );
        } else if ($onlyHidden) {
            $queryBuilder
                ->count('*')
                ->from($table)
                ->where(
                    $queryBuilder->expr()->andX(
                        $queryBuilder->expr()->gt('tstamp', $queryBuilder->createNamedParameter(strtotime('-1 years'))),
                        $queryBuilder->expr()->eq('hidden', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)),
                    )
                );
        } else {
            $queryBuilder
                ->count('*')
                ->from($table)
                ->where(
                    $queryBuilder->expr()->gt('tstamp', $queryBuilder->createNamedParameter(strtotime('-1 years'))),
                );
        }

        $total = (int)$queryBuilder->execute()->fetchColumn(0);

        if ($total > 0) {
            if ($tableRowCount > 0) {
                $percentage = $total * 100 / $tableRowCount;
            } else {
                $percentage = 0;
            }
            $this->results[$table]['Younger than 1 year'] = number_format($total) . ' - ' . number_format($percentage, 2) . '%';
        }

//        $ages = [];
//        for ($i = 1; $i <= 5; $i++) {
//            $ages[sprintf('Older than %s years', $i)] = strtotime(sprintf('-%d years', $i));
//        }

//        foreach ($ages as $key => $age) {
//            $result = $databaseHandler->sql_query(sprintf(
//                'SELECT COUNT(*) as total FROM %s WHERE tstamp < %d %s',
//                $this->table,
//                $age,
//                $where
//            ));
//            $row = $databaseHandler->sql_fetch_assoc($result);
//            $databaseHandler->sql_free_result($result);
//            if ($row['total'] > 0) {
//                if ($tableRowCount > 0) {
//                    $percentage = $row['total'] * 100 / $tableRowCount;
//                } else {
//                    $percentage = 0;
//                }
//                $this->results[] = new KeyValuePair(
//                    $key,
//                    number_format($row['total']) . ' - ' . number_format($percentage, 2) . '%'
//                );
//            }
//        }
    }

    public function getResults(): array
    {
        return $this->results;
    }
}
