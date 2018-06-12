<?php
defined('TYPO3_MODE') or die('¯\_(ツ)_/¯');

if (TYPO3_MODE === 'BE') {
    // Register commands
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = MichielRoos\Doctor\Command\DoctorCommandController::class;
}
