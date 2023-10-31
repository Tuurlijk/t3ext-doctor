<?php

namespace MichielRoos\Doctor\Command;

use MichielRoos\Doctor\Utility\ContentUtility;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ContentCommand extends BaseCommandController
{
    /**
     * Configure the command by defining the name, options and arguments
     */
    protected function configure(): void
    {
        $this->setDescription('Show content information')
            ->addOption(
                'content-type',
                '',
                InputOption::VALUE_OPTIONAL,
                'The content type to inspect'
            )->addOption(
                'list-type',
                '',
                InputOption::VALUE_OPTIONAL,
                'The list type to inspect'
            )->addOption(
                'limit',
                '',
                InputOption::VALUE_OPTIONAL,
                'Maximum number of results to fetch',
                30
            );
    }

    /**
     * @throws \TYPO3\CMS\Core\Error\Http\ServiceUnavailableException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io     = new SymfonyStyle($input, $output);
        $content_type = $input->getOption('content-type');
        $list_type    = $input->getOption('list-type');
        $limit        = $input->getOption('limit');

        $utility = new ContentUtility();

        $this->io->title('info');
        $info = $utility->getInfo($content_type, $list_type, $limit);

        $this->io->definitionList(
            $info['info'],
        );

        $headers = array_keys(current($info['content_types']));
        $rows    = array_map(static function ($value) {
            $value['total'] = number_format($value['total']);
            return $value;
        }, $info['content_types']);
        $this->table($headers, $rows);

        $headers = array_keys(current($info['plugin_types']));
        $rows    = array_map(static function ($value) {
            $value['total'] = number_format($value['total']);
            return $value;
        }, $info['plugin_types']);
        $this->table($headers, $rows);

        if (array_key_exists('content_type_usage', $info)) {
            $headers = array_keys(current($info['content_type_usage']));
            $this->table($headers, $info['content_type_usage']);
        }

        if (array_key_exists('plugin_type_usage', $info)) {
            $headers = array_keys(current($info['plugin_type_usage']));
            $this->table($headers, $info['plugin_type_usage']);
        }

        return 0;
    }
}
