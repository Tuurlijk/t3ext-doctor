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
use MichielRoos\Doctor\Domain\Model\Header;
use MichielRoos\Doctor\Domain\Model\KeyValueHeader;
use MichielRoos\Doctor\Domain\Model\KeyValuePair;
use MichielRoos\Doctor\Domain\Model\ListItem;
use MichielRoos\Doctor\Domain\Model\Notice;
use MichielRoos\Doctor\Domain\Model\Suggestion;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Extbase\Mvc\Controller\CommandController;

/**
 * Class BaseCommandController
 */
class BaseCommandController extends CommandController
{
    public const MAXIMUM_LINE_LENGTH = 120;

    /**
     * @var int
     */
    protected $lineLength = 80;

    /**
     * @var LogManager
     */
    protected $logManager;

    /**
     * @var \TYPO3\CMS\Core\Log\Logger
     */
    protected $logger;

    /**
     * @param LogManager $logManager
     */
    public function injectLogManager(LogManager $logManager)
    {
        $this->logManager = $logManager;
    }

    /**
     * Initialize the object
     */
    public function initializeObject()
    {
        if (defined('\TYPO3\CMS\Extbase\Mvc\Controller\CommandController::MAXIMUM_LINE_LENGTH')) {
            $this->lineLength = self::MAXIMUM_LINE_LENGTH;
        } elseif (function_exists('ncurses_getmaxyx')) {
            ncurses_getmaxyx(STDSCR, $rows, $columns);
            $this->lineLength = $columns;
        } elseif (@exec('tput cols')) {
            $this->lineLength = exec('tput cols');
        } elseif (getenv('COLUMNS')) {
            $this->lineLength = getenv('COLUMNS');
        }
        $this->logger = $this->objectManager->get(LogManager::class)->getLogger(__CLASS__);
    }

    /**
     * Write results
     * @param mixed $results
     */
    public function writeResults($results)
    {
        foreach ($results as $result) {
            if ($result instanceof Header) {
                $this->outputLine();
                $this->outputLine($result->getValue());
                $this->outputLine(str_repeat('-', $this->lineLength));
            } elseif ($result instanceof KeyValueHeader) {
                $this->outputLine();
                $line = wordwrap($result->getValue(), $this->lineLength - 43, PHP_EOL . str_repeat(' ', 43),
                    true);
                $this->outputLine('%-2s%-40s %s', [' ', $result->getKey() . ':', $line]);
                $this->outputLine(str_repeat('-', $this->lineLength));
            } elseif ($result instanceof KeyValuePair) {
                $line = wordwrap($result->getValue(), $this->lineLength - 43, PHP_EOL . str_repeat(' ', 43),
                    true);
                $this->outputLine('%-2s%-40s %s', [' ', $result->getKey(), $line]);
            } elseif ($result instanceof Suggestion) {
                $this->outputLine(str_repeat('-', $this->lineLength));
                $suggestionWidth = $this->lineLength - 2;
                $line = wordwrap($result->getValue(), $suggestionWidth, PHP_EOL . '| ', true);
                $this->outputLine('| %-' . $suggestionWidth . 's', [$line]);
                $this->outputLine(str_repeat('-', $this->lineLength));
            } elseif ($result instanceof Notice) {
                $this->outputLine(str_repeat('-', $this->lineLength));
                $suggestionWidth = $this->lineLength - 2;
                $line = wordwrap($result->getValue(), $suggestionWidth, PHP_EOL . '! ', true);
                $this->outputLine('! %-' . $suggestionWidth . 's', [$line]);
                $this->outputLine(str_repeat('-', $this->lineLength));
            } elseif ($result instanceof ListItem) {
                $this->outputLine(' - ' . $result->getValue());
            } else {
                $this->outputLine((string)$result);
            }
        }
    }

    /**
     * Convert text to bool
     *
     * @param $value
     * @return bool
     */
    protected function textToBool($value) {
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
}
