<?php
namespace MichielRoos\Doctor\Utility;

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

/**
 * Class DatabaseUtility
 */
class DatabaseUtility
{
    /**
     * Get database size.
     */
    public static function getDatabaseSize()
    {
        $databaseHandler = self::getDatabaseHandler();
        $result = $databaseHandler->sql_query("SELECT SUM( data_length + index_length ) AS size FROM information_schema.TABLES WHERE table_schema = '" . $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['dbname'] . "'");
        $row = $databaseHandler->sql_fetch_assoc($result);
        $databaseHandler->sql_free_result($result);

        return array_pop($row);
    }

    /**
     * Returns the DatabaseConnection
     *
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected static function getDatabaseHandler()
    {
        return $GLOBALS['TYPO3_DB'];
    }

    /**
     * Get table size.
     * @param string $table
     * @return int
     */
    public static function getTableSize($table = '')
    {
        $size = 0;
        if (!$table) {
            return $size;
        }
        $databaseHandler = self::getDatabaseHandler();
        $result = $databaseHandler->sql_query(
            sprintf(
                "SELECT data_length + index_length AS size FROM information_schema.TABLES WHERE table_name = '%s' AND  table_schema = '%s'",
                mysqli_real_escape_string(self::getDatabaseHandler()->getDatabaseHandle(), $table),
                $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['dbname']
            ));
        if ($databaseHandler->sql_num_rows($result)) {
            $row = $databaseHandler->sql_fetch_assoc($result);
            $size = array_pop($row);
        }
        $databaseHandler->sql_free_result($result);

        return $size;
    }

    /**
     * Get tables and record count.
     * @return array
     */
    public static function getTablesAndRecordCount()
    {
        $tableAndCount = [];
        $databaseHandler = self::getDatabaseHandler();
        $result = $databaseHandler->sql_query(
            "SELECT
			  TABLE_NAME as `table`,
			  TABLE_ROWS as `rows`
			FROM
			  INFORMATION_SCHEMA.TABLES
			WHERE
			  TABLE_SCHEMA = '" . $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['dbname'] . "'
			ORDER BY
			  `rows` DESC, `table`");

        while ($row = $databaseHandler->sql_fetch_assoc($result)) {
            $tableAndCount[$row['table']] = $row['rows'];
        }
        $databaseHandler->sql_free_result($result);

        return $tableAndCount;
    }

    /**
     * Check if a table has a column
     * @param $table
     * @param $column
     * @return bool
     */
    public static function tableHasColumn($table, $column)
    {
        $databaseHandler = self::getDatabaseHandler();
        $result = $databaseHandler->sql_query(
            sprintf("SELECT
                        `COLUMN_NAME` 
                    FROM
                        `INFORMATION_SCHEMA`.`COLUMNS` 
                    WHERE
                        `TABLE_SCHEMA`='%s' 
                    AND
                        `COLUMN_NAME`='%s' 
                    AND
                        `TABLE_NAME`='%s';",
                $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['dbname'],
                mysqli_real_escape_string($databaseHandler->getDatabaseHandle(), $column),
                mysqli_real_escape_string($databaseHandler->getDatabaseHandle(), $table)
            ));

        return self::getDatabaseHandler()->sql_num_rows($result) === 1;
    }
}
