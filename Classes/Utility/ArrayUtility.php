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
 * Class ArrayUtility
 * @package MichielRoos\Doctor\Utility
 */
class ArrayUtility
{
	/**
	 * Filter items not containing dots from array
	 *
	 * @param $array
	 * @return array
	 */
	public static function dots($array)
	{
		$noDots = [];
		foreach ($array as $item) {
			if (strpos($item, '.') !== false) {
				$noDots[] = $item;
			}
		}
		return $noDots;
	}

	/**
	 * Filter items containing dots from array
	 *
	 * @param $array
	 * @return array
	 */
	public static function noDots($array)
	{
		$noDots = [];
		foreach ($array as $item) {
			if (strpos($item, '.') === false) {
				$noDots[] = $item;
			}
		}
		return $noDots;
	}
}
