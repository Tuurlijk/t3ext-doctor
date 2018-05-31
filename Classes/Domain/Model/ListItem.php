<?php
namespace MichielRoos\Doctor\Domain\Model;

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
 * Class ListItem
 * @package MichielRoos\Doctor\Domain\Model
 */
class ListItem
{
	/**
	 * @var string
	 */
	protected $value;

	/**
	 * ListItem constructor.
	 * @param string $value
	 * @param array $valueArguments
	 */
	public function __construct($value = '', $valueArguments = [])
	{
		if ($value) {
			if ($valueArguments !== array()) {
				$value = vsprintf($value, $valueArguments);
			}
			$this->setValue($value);
		}
	}

	/**
	 * @return string
	 */
	public function getValue()
	{
		return $this->value;
	}

	/**
	 * @param string $value
	 */
	public function setValue($value)
	{
		$this->value = $value;
	}
}
