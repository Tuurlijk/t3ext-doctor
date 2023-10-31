<?php

namespace MichielRoos\Doctor\Command;

use MichielRoos\Doctor\Utility\DatabaseUtility;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DatabaseCommand extends BaseCommandController
{
    /**
     * Configure the command by defining the name, options and arguments
     */
    protected function configure(): void
    {
        $this->setDescription('Show database information')
            ->addOption(
                'table',
                '',
                InputOption::VALUE_OPTIONAL,
                'The table to inspect'
            )->addOption(
                'limit',
                '',
                InputOption::VALUE_OPTIONAL,
                'Maximum number of results to fetch',
                30
            );
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);
        $limit    = $input->getOption('limit');
        $table    = $input->getOption('table');

        $db = new DatabaseUtility();

        $this->io->definitionList(
            'Database',
            ['Name' => $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['dbname']],
            ['Size' => GeneralUtility::formatSize($db->getDatabaseSize())],
            ['Tables' => $db->getTableCount()]
        );

        $this->io->title('largest tables');
        $largest = $db->getLargestTablesBySize($limit);
        $rows    = array_map(static function ($value) {
            $value['size'] = GeneralUtility::formatSize($value['size']);
            return $value;
        }, $largest);
        $this->table(['table', 'size'], $rows);

        $this->io->title('tables with most records');
        $largest = $db->getLargestTablesByRecordCount($limit);

        $rows = array_map(static function ($value) {
            $value['rows'] = number_format($value['rows']);
            return $value;
        }, $largest);
        $this->table(['table', 'rows'], $rows);

        $this->io->title('tables with least records');
        $largest = $db->getSmallestTablesByRecordCount($limit);

        $rows = array_map(static function ($value) {
            $value['rows'] = number_format($value['rows']);
            return $value;
        }, $largest);
        $this->table(['table', 'rows'], $rows);


        if ($table) {
            $columns = $db->getTableColumns($table);
            $columnInfo = $db->analyzeColumnsForTable($table);

            $this->io->title('colunmn values');

            $rows = [];


            foreach ($columnInfo['columnValues'] as $column => $values) {
                $rows[] = [$column, count($values)];
            }
            $this->table(['column', 'different values'], $rows);

            if (in_array('tstamp', $columns, true)) {
                $db->getRecordAge($table);
            }
            if (in_array('deleted', $columns, true)) {
                $db->getRecordAge($table, true);
            }
            if (in_array('hidden', $columns, true)) {
                $db->getRecordAge($table, false, true);
            }
        }

        return 0;
    }
}
