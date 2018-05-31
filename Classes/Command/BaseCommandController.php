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
use TYPO3\CMS\Extbase\Mvc\Controller\CommandController;

/**
 * Class BaseCommandController
 * @package MichielRoos\Doctor\Command
 */
class BaseCommandController extends CommandController
{
	/**
	 * @var \TYPO3\CMS\Core\Log\LogManager $logManager
	 */
	protected $logManager;

	/**
	 * @var \TYPO3\CMS\Core\Log\Logger $logger
	 */
	protected $logger;

	/**
	 * @param \TYPO3\CMS\Core\Log\LogManager $logManager
	 */
	public function injectLogManager(\TYPO3\CMS\Core\Log\LogManager $logManager)
	{
		$this->logManager = $logManager;
	}

	/**
	 * Initialize the object
	 */
	public function initializeObject()
	{
		$this->logger = $this->objectManager->get('TYPO3\CMS\Core\Log\LogManager')->getLogger(__CLASS__);
	}

	/**
	 * Write results
	 */
	public function writeResults($results)
	{
		foreach ($results as $result) {
			if ($result instanceof Header) {
				$this->outputLine('');
				$this->outputLine($result->getValue());
				$this->outputLine(str_repeat('-', self::MAXIMUM_LINE_LENGTH));
			} elseif ($result instanceof KeyValueHeader) {
				$line = wordwrap($result->getValue(), self::MAXIMUM_LINE_LENGTH - 43, PHP_EOL . str_repeat(' ', 43),
					true);
				$this->outputLine('%-2s%-40s %s', [' ', $result->getKey(), $line]);
				$this->outputLine(str_repeat('-', self::MAXIMUM_LINE_LENGTH));
			} elseif ($result instanceof KeyValuePair) {
				$line = wordwrap($result->getValue(), self::MAXIMUM_LINE_LENGTH - 43, PHP_EOL . str_repeat(' ', 43),
					true);
				$this->outputLine('%-2s%-40s %s', [' ', $result->getKey(), $line]);
			} elseif ($result instanceof Suggestion) {
				$this->outputLine(str_repeat('-', self::MAXIMUM_LINE_LENGTH));
				$suggestionWidth = self::MAXIMUM_LINE_LENGTH - 2;
				$line = wordwrap($result->getValue(), $suggestionWidth, PHP_EOL . '| ', true);
				$this->outputLine('| %-' . $suggestionWidth . 's', [$line]);
				$this->outputLine(str_repeat('-', self::MAXIMUM_LINE_LENGTH));
			} elseif ($result instanceof Notice) {
				$this->outputLine(str_repeat('-', self::MAXIMUM_LINE_LENGTH));
				$suggestionWidth = self::MAXIMUM_LINE_LENGTH - 2;
				$line = wordwrap($result->getValue(), $suggestionWidth, PHP_EOL . '! ', true);
				$this->outputLine('! %-' . $suggestionWidth . 's', [$line]);
				$this->outputLine(str_repeat('-', self::MAXIMUM_LINE_LENGTH));
			} elseif ($result instanceof ListItem) {
				$this->outputLine(' - ' . $result->getValue());
			}
		}
	}
}
