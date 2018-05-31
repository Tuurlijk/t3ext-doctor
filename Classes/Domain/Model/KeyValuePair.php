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
 * Class KeyValuePair
 * @package MichielRoos\Doctor\Domain\Model
 */
class KeyValuePair extends ListItem
{
	/**
	 * @var string
	 */
	private $key;

	/**
	 * KeyValuePair constructor.
	 * @param string $key
	 * @param string $value
	 */
	public function __construct($key = '', $value = '')
	{
		if ($key) {
			$this->setKey($key);
		}
		if ($value) {
			$this->setValue($value);
		}
	}

	/**
	 * @return string
	 */
	public function getKey()
	{
		return $this->key;
	}

	/**
	 * @param string $key
	 */
	public function setKey($key)
	{
		$this->key = $key;
	}
}
