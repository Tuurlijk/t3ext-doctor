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
use MichielRoos\Doctor\Domain\Model\Notice;
use MichielRoos\Doctor\Domain\Model\Suggestion;
use MichielRoos\Doctor\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class DatabaseApiService
 * @package MichielRoos\Doctor\Service
 */
class DatabaseApiService extends BaseApiService
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
    private $tableColumns;

    /**
     * @var int
     */
    private $limit = 30;

    /**
     * Get some basic database information.
     *
     * @param int $limit Show up to [limit] of largest tables
     * @param string $table The table to inspect
     *
     * @return array
     */
    public function getInfo($limit = null, $table = null)
    {
        if ((int)$limit) {
            $this->limit = (int)$limit;
        }
        $this->results[] = new Header('Database information');

        $this->getDatabaseSize();
        $this->getTableCount();
        $this->getLargestTablesBySize();
        $this->getLargestTablesByRecordCount();
        $this->getSmallestTablesByRecordCount();
        if ($table) {
            $this->table = mysqli_real_escape_string($this->getDatabaseHandler()->getDatabaseHandle(), $table);
            $this->tableColumns = $this->getTableColumns();
            $this->analyzeColumnsForTable();
            if ($this->tableHasColumn('tstamp')) {
                $this->getRecordAge();
            }
            if ($this->tableHasColumn('deleted')) {
                $this->getDeletedRecords();
            }
            if ($this->tableHasColumn('hidden')) {
                $this->getHiddenRecords();
            }
        }

        return $this->results;
    }

    /**
     * Get database size.
     */
    public function getDatabaseSize()
    {
        $databaseHandler = $this->getDatabaseHandler();
        $result = $databaseHandler->sql_query("SELECT SUM( data_length + index_length ) AS size FROM information_schema.TABLES WHERE table_schema = '" . TYPO3_db . "'");
        $row = $databaseHandler->sql_fetch_assoc($result);
        $databaseSize = array_pop($row);
        $this->results[] = new KeyValuePair('Database size', GeneralUtility::formatSize($databaseSize));
        $databaseHandler->sql_free_result($result);
    }

    /**
     * Get deleted records
     */
    public function getDeletedRecords()
    {
        $databaseHandler = $this->getDatabaseHandler();
        $this->results[] = new Header('Deleted records for table %s', [$this->table]);

        $tableRowCount = $this->getTableRowCount();
        $this->results[] = new KeyValuePair('total records', number_format($tableRowCount));

        $result = $databaseHandler->sql_query(sprintf('SELECT COUNT(*) as total FROM %s WHERE deleted = 1',
            $this->table));
        $row = $databaseHandler->sql_fetch_assoc($result);
        $databaseHandler->sql_free_result($result);
        if ($row['total'] > 0) {
            $percentage = $row['total'] * 100 / $tableRowCount;
            $this->results[] = new KeyValuePair(
                'deleted records',
                number_format($row['total']) . ' - ' . number_format($percentage, 2) . '%'
            );
        }
        $this->getRecordAge('deleted = 1', 'Deleted record');
    }

    /**
     * Get hidden records
     */
    public function getHiddenRecords()
    {
        $databaseHandler = $this->getDatabaseHandler();
        $this->results[] = new Header('Hidden records for table %s', [$this->table]);

        $tableRowCount = $this->getTableRowCount();
        $this->results[] = new KeyValuePair('total records', number_format($tableRowCount));

        $result = $databaseHandler->sql_query(sprintf('SELECT COUNT(*) as total FROM %s WHERE hidden = 1',
            $this->table));
        $row = $databaseHandler->sql_fetch_assoc($result);
        $databaseHandler->sql_free_result($result);
        if ($row['total'] > 0) {
            $percentage = $row['total'] * 100 / $tableRowCount;
            $this->results[] = new KeyValuePair(
                'hidden records',
                number_format($row['total']) . ' - ' . number_format($percentage, 2) . '%'
            );
        }
        $this->getRecordAge('hidden = 1', 'Hidden record');
    }

    /**
     * Get record age
     * @param string $where
     * @param string $header
     */
    public function getRecordAge($where = '', $header = 'Record')
    {
        $databaseHandler = $this->getDatabaseHandler();
        $this->results[] = new Header('%s age for table %s', [$header, $this->table]);

        $tableRowCount = $this->getTableRowCount($where);
        $this->results[] = new KeyValuePair('total', number_format($tableRowCount));

        if ($where) {
            $where = 'AND ' . $where;
        }

        $result = $databaseHandler->sql_query(sprintf('SELECT COUNT(*) as total FROM %s WHERE tstamp > %d %s',
            $this->table, strtotime('-1 years'), $where));
        $row = $databaseHandler->sql_fetch_assoc($result);
        $databaseHandler->sql_free_result($result);
        if ($row['total'] > 0) {
            $percentage = $row['total'] * 100 / $tableRowCount;
            $this->results[] = new KeyValuePair(
                'Younger than 1 year',
                number_format($row['total']) . ' - ' . number_format($percentage, 2) . '%'
            );
        }

        $ages = [];
        for ($i = 1; $i <= 5; $i++) {
            $ages[sprintf('Older than %s years', $i)] = strtotime(sprintf('-%d years', $i));
        }

        foreach ($ages as $key => $age) {
            $result = $databaseHandler->sql_query(sprintf('SELECT COUNT(*) as total FROM %s WHERE tstamp < %d %s',
                $this->table, $age, $where));
            $row = $databaseHandler->sql_fetch_assoc($result);
            $databaseHandler->sql_free_result($result);
            if ($row['total'] > 0) {
                $percentage = $row['total'] * 100 / $tableRowCount;
                $this->results[] = new KeyValuePair(
                    $key,
                    number_format($row['total']) . ' - ' . number_format($percentage, 2) . '%'
                );
            }
        }
    }

    /**
     * Get table count.
     */
    public function getTableCount()
    {
        $databaseHandler = $this->getDatabaseHandler();
        $result = $databaseHandler->sql_query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '" . TYPO3_db . "'");
        $row = $databaseHandler->sql_fetch_assoc($result);
        $this->results[] = new KeyValuePair('Database tables', array_pop($row));
        $databaseHandler->sql_free_result($result);
    }

    /**
     * Get largest tables by size
     */
    public function getLargestTablesBySize()
    {
        $showSuggestion = false;
        $databaseHandler = $this->getDatabaseHandler();
        $result = $databaseHandler->sql_query(
            "SELECT
				 table_name AS `table`,
				 round(((data_length + index_length)), 2) `size`
			FROM information_schema.TABLES
			WHERE table_schema = '" . TYPO3_db . "'
			ORDER BY (data_length + index_length) DESC
			LIMIT " . (int)$this->limit . ";");
        $this->results[] = new Header('%s Largest tables by size', [$this->limit]);
        while ($row = $databaseHandler->sql_fetch_assoc($result)) {
            $this->results[] = new KeyValuePair($row['table'], GeneralUtility::formatSize($row['size']));
            if ($row['size'] > 500 * 1024 * 1024) {
                $showSuggestion = true;
            }
        }
        $databaseHandler->sql_free_result($result);
        if ($showSuggestion) {
            $this->results[] = new Suggestion('One ore more tables are more than 500 MB. This is quite large for a table, inspect the tables and see if you can reduce their size.');
        }
    }

    /**
     * Get largest tables by record count
     */
    public function getLargestTablesByRecordCount()
    {
        $showSuggestion = false;
        $databaseHandler = $this->getDatabaseHandler();
        $result = $databaseHandler->sql_query(
            "SELECT
			  TABLE_NAME as `table`,
			  TABLE_ROWS as `rows`
			FROM
			  INFORMATION_SCHEMA.TABLES
			WHERE
			  TABLE_SCHEMA = '" . TYPO3_db . "'
			ORDER BY
			  rows DESC, `table`
			LIMIT " . (int)$this->limit . ";");
        $this->results[] = new Header('%s Largest tables by record count', [$this->limit]);

        while ($row = $databaseHandler->sql_fetch_assoc($result)) {
            $this->results[] = new KeyValuePair($row['table'], number_format($row['rows']));
            if ($row['rows'] > 1000000) {
                $showSuggestion = true;
            }
        }
        $databaseHandler->sql_free_result($result);
        if ($showSuggestion) {
            $this->results[] = new Suggestion('One ore more tables have more than a million records. This is quite a lot for a table, inspect the tables and see if you can reduce their size.');
        }
    }

    /**
     * Get smallest tables by record count
     */
    public function getSmallestTablesByRecordCount()
    {
        $showSuggestion = false;
        $databaseHandler = $this->getDatabaseHandler();
        $result = $databaseHandler->sql_query(
            "SELECT
			  TABLE_NAME as `table`,
			  TABLE_ROWS as `rows`
			FROM
			  INFORMATION_SCHEMA.TABLES
			WHERE
			  TABLE_SCHEMA = '" . TYPO3_db . "'
			ORDER BY
			  rows, `table` 
			LIMIT " . (int)$this->limit . ";");
        $this->results[] = new Header('%s Smallest tables by record count', [$this->limit]);

        while ($row = $databaseHandler->sql_fetch_assoc($result)) {
            $this->results[] = new KeyValuePair($row['table'],
                $row['rows'] !== '0' ? number_format($row['rows']) : 'zero');
            if ($row['rows'] === '0') {
                $showSuggestion = true;
            }
        }
        $databaseHandler->sql_free_result($result);
        if ($showSuggestion) {
            $this->results[] = new Suggestion('One ore more tables have zero records. Do you need these tables?');
        }
    }

    /**
     * Analyze all tx_* columns for table
     *
     */
    public function analyzeColumnsForTable()
    {
        $this->results[] = new Header('Field info for table %s', [$this->table]);
        $tableRowCount = $this->getTableRowCount();

        $unusedColumns = [];

        $databaseHandler = $this->getDatabaseHandler();

        $valuesWithPrefix = ArrayUtility::getValuesWithPrefix($this->tableColumns, 'tx_');
        $this->results[] = new ListItem('%d tx_* columns found', [count($valuesWithPrefix)]);

        foreach ($valuesWithPrefix as $tableColumn) {
            if (strpos($tableColumn, 'tx_') !== 0) {
                continue;
            }
            $result = $databaseHandler->sql_query(sprintf(
                'SELECT COUNT(*) AS rows, %1$s FROM %2$s GROUP BY %1$s ORDER BY rows DESC, %1$s',
                mysqli_real_escape_string($databaseHandler->getDatabaseHandle(), $tableColumn),
                mysqli_real_escape_string($databaseHandler->getDatabaseHandle(), $this->table)
            ));
            $rowCount = $databaseHandler->sql_num_rows($result);
            $this->results[] = new Header('Field info for %s', [$tableColumn]);
            $this->results[] = new KeyValueHeader('value', 'count');
            $i = 0;
            while ($row = $databaseHandler->sql_fetch_assoc($result)) {
                $this->results[] = new KeyValuePair($row[$tableColumn] ?: 'empty', number_format($row['rows']));
                if ((int)$tableRowCount === (int)$row['rows']) {
                    $this->results[] = new Suggestion('All rows in this table have the same value. Do you really need this field?');
                    $unusedColumns[$tableColumn] = $row[$tableColumn] ?: 'empty';
                }
                $i++;
                if ($i >= $this->limit) {
                    $this->results[] = new Notice('%s different values found, increase the "limit" of %s if you want to see more.',
                        [number_format($rowCount), $this->limit]);
                    break;
                }
            }
            $databaseHandler->sql_free_result($result);
        }

        if (count($unusedColumns)) {
            $this->results[] = new Header('%s columns found that hold the same value for all rows in table %s.',
                [count($unusedColumns), $this->table]);
            foreach ($unusedColumns as $column => $value) {
                $this->results[] = new KeyValuePair($column, $value);
            }
        }
    }

    /**
     * Get table row count
     *
     * @param string $where
     * @return int
     */
    private function getTableRowCount($where = '')
    {
        if (array_key_exists($where, $this->tableRowCount)) {
            return $this->tableRowCount[$where];
        }
        if ($where) {
            $where = 'WHERE ' . $where;
        }
        $databaseHandler = $this->getDatabaseHandler();
        $result = $databaseHandler->sql_query(sprintf(
            "SELECT COUNT(*) AS total FROM %s %s",
            mysqli_real_escape_string($databaseHandler->getDatabaseHandle(), $this->table),
            $where
        ));
        $row = $databaseHandler->sql_fetch_assoc($result);
        $databaseHandler->sql_free_result($result);
        $this->tableRowCount[$where] = (int)$row['total'];
        return $this->tableRowCount[$where];
    }

    /**
     * Show all columns in table
     *
     * @return array
     */
    private function getTableColumns()
    {
        $columns = [];
        if (!$this->table) {
            return $columns;
        }
        $databaseHandler = $this->getDatabaseHandler();
        $result = $databaseHandler->sql_query(
            "SELECT
				`COLUMN_NAME` 
			FROM
				`INFORMATION_SCHEMA`.`COLUMNS` 
			WHERE
				`TABLE_SCHEMA`='" . TYPO3_db . "' 
			AND
				`TABLE_NAME`='" . mysqli_real_escape_string($databaseHandler->getDatabaseHandle(),
                $this->table) . "';");

        while ($row = $databaseHandler->sql_fetch_assoc($result)) {
            $columns[] = $row['COLUMN_NAME'];
        }
        $databaseHandler->sql_free_result($result);
        return $columns;
    }

    /**
     * Returns true if the table contains $column
     *
     * @param string $column
     * @return bool
     */
    private function tableHasColumn($column)
    {
        return in_array($column, $this->tableColumns);
    }
}
