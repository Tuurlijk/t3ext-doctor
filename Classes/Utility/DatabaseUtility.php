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
 * @package MichielRoos\Doctor\Utility
 */
class DatabaseUtility
{
    /**
     * Get database size.
     */
    public static function getDatabaseSize()
    {
        $databaseHandler = self::getDatabaseHandler();
        $result = $databaseHandler->sql_query("SELECT SUM( data_length + index_length ) AS size FROM information_schema.TABLES WHERE table_schema = '" . TYPO3_db . "'");
        $row = $databaseHandler->sql_fetch_assoc($result);
        $databaseHandler->sql_free_result($result);
        return array_pop($row);
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
                TYPO3_db
            ));
        $row = $databaseHandler->sql_fetch_assoc($result);
        $size = array_pop($row);
        $databaseHandler->sql_free_result($result);
        return $size;
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
