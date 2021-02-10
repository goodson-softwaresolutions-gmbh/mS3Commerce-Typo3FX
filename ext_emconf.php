<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'mS3 Commerce Fx',
    'description' => 'mS3 Commerce Fluid Extension',
    'category' => 'plugin',
    'author' => 'Goodson GmbH',
    'author_company' => 'Goodson GmbH',
    'author_email' => 'info@goodson.at',
    'state' => 'stable',
    'clearCacheOnLoad' => true,
    'version' => '6.3.0.21060',
    'constraints' => [
        'depends' => [
            'typo3' => '9.0.0-9.9.99',
        ],
    ],
    'autoload' => [
        'psr-4' => [
            'Ms3\\Ms3CommerceFx\\' => 'Classes'
        ],
    ],
];