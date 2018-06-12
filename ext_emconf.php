<?php

$EM_CONF['doctor'] = [
    'title' => 'Doctor',
    'description' => 'Doctor shows you how TYPO3 is doing',
    'category' => 'fe',
    'author' => 'Michiel Roos',
    'author_company' => 'Michiel Roos',
    'author_email' => 'michiel@michielroos.com',
    'clearCacheOnLoad' => 0,
    'dependencies' => '',
    'state' => 'stable',
    'uploadfolder' => 0,
    'version' => '0.0.1',
    'constraints' => [
        'depends' => [],
        'conflicts' => [],
        'suggests' => [],
    ],
    'autoload' => [
        'psr-4' => ['MichielRoos\\Doctor\\' => 'Classes']
    ],
    'conflicts' => '',
    'suggests' => [],
];
