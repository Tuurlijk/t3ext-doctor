<?php

namespace MichielRoos\Doctor\Command;

use MichielRoos\Doctor\Utility\DatabaseUtility;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DatabaseCruftCommand extends BaseCommandController
{
    /**
     * Configure the command by defining the name, options and arguments
     */
    protected function configure(): void
    {
        $this->setDescription('Show amount and percentage of deleted and hidden records in tables that contain those columns');
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);
        $db       = new DatabaseUtility();

        $tablesAndCount = $db->getTablesAndRecordCount();

        var_dump($tablesAndCount);
        
        
        foreach ($tablesAndCount as $table => $rows) {
            if ($db->tableHasColumn($table, 'deleted')) {
                $db->getDeletedRecords($table, (int)$rows);
            }
            if ($db->tableHasColumn($table, 'hidden')) {
                $db->getHiddenRecords($table, (int)$rows);
            }
        }

        $rows    = [];
        $indexes = [
            'row_count'          => 'rows',
            'table_size'         => 'table size',
            'deleted_count'      => 'deleted count',
            'deleted_size'       => 'deleted size',
            'deleted_percentage' => 'deleted %',
            'hidden_count'       => 'hidden count',
            'hidden_size'        => 'hidden size',
            'hidden_percentage'  => 'hidden %',
        ];
        $headers = array_merge(['table'], array_values($indexes));

        foreach ($db->getResults() as $table => $info) {
            $row = ['table' => $table];
            foreach ($indexes as $key => $value) {
                if (!array_key_exists($key, $info)) {
                    $row[] = '';
                    continue;
                }
                if (str_ends_with($key, '_percentage')) {
                    $row[] = number_format($info[$key], 2) . '%';
                }
                if (str_ends_with($key, '_count')) {
                    $row[] = number_format($info[$key]);
                }
                if (str_ends_with($key, '_size')) {
                    $row[] = GeneralUtility::formatSize($info[$key]);
                }
            }
            $rows[] = $row;
        }

        $this->table($headers, $rows);

        return 0;
    }
}
