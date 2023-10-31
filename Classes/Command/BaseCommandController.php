<?php

namespace MichielRoos\Doctor\Command;

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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;

/**
 * Class BaseCommandController
 */
class BaseCommandController extends Command
{
    /**
     * @var \Symfony\Component\Console\Style\SymfonyStyle
     */
    protected $io;

    /**
     * Convert text to bool
     *
     * @param $value
     * @return bool
     */
    protected function textToBool($value)
    {
        switch ($value) {
            case 'true':
            case 'yes':
            case 1:
            case '1':
                $value = true;
                break;
            default:
                $value = false;
        }

        return $value;
    }

    /**
     * Draw a boxy table we can use in markdown
     */
    public function table(array $headers, array $rows): void
    {
        $boxMarkdown = (new TableStyle())
            ->setHorizontalBorderChars('-')
            ->setVerticalBorderChars('|')
            ->setCrossingChars('|', '', '', '', '|', '', '', '', '', '|')
        ;

        $style = $boxMarkdown;
//        $style = clone Table::getStyleDefinition('box');
        $style->setCellHeaderFormat('<info>%s</info>');

        $table = new Table($this->io);
        $table->setHeaders($headers);
        $table->setRows($rows);
        $table->setStyle($style);

        $table->render();
        $this->io->newLine();
    }
}
