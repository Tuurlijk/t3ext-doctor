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
 * Class Header
 * @package MichielRoos\Doctor\Domain\Model
 */
class Header extends ListItem
{
    /**
     * @var integer
     */
    private $level = 1;

    /**
     * Header constructor.
     * @param string $value
     * @param array $valueArguments
     * @param int $level
     */
    public function __construct($value = '', $valueArguments = [], $level = 1)
    {
        if ($value) {
            if ($valueArguments !== array()) {
                $value = vsprintf($value, $valueArguments);
            }
            $this->setValue($value);
        }
    }

    /**
     * @return int
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * @param int $level
     */
    public function setLevel($level)
    {
        $this->level = $level;
    }
}
