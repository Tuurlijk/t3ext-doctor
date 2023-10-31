<?php
/**
 * Commands to be executed by typo3, where the key of the array
 * is the name of the command (to be called as the first argument after typo3).
 * Required parameter is the "class" of the command which needs to be a subclass
 * of Symfony/Console/Command.
 *
 * example: bin/typo3 backend:lock
 */
return [
    'doctor:backenduser' => [
        'class' => \MichielRoos\Doctor\Command\BackendUserCommand::class
    ],
    'doctor:database-analyze' => [
        'class' => \MichielRoos\Doctor\Command\DatabaseAnalyzeCommand::class
    ],
    'doctor:database-cruft' => [
        'class' => \MichielRoos\Doctor\Command\DatabaseCruftCommand::class
    ],
    'doctor:content' => [
        'class' => \MichielRoos\Doctor\Command\ContentCommand::class
    ],
    'doctor:database' => [
        'class' => \MichielRoos\Doctor\Command\DatabaseCommand::class
    ],
];
