<?php

namespace MichielRoos\Doctor\Command;

use MichielRoos\Doctor\Utility\DatabaseUtility;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DatabaseAnalyzeCommand extends BaseCommandController
{
    /**
     * Configure the command by defining the name, options and arguments
     */
    protected function configure(): void
    {
        $this->setDescription('Analyze database tables so we can pull more accurate information from TABLE_SCHEMA');
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);
        $db       = new DatabaseUtility();

        $db->analyzeTables();

        return 0;
    }
}
