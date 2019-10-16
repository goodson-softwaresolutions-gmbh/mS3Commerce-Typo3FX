<?php
defined('TYPO3_MODE') || die('Access denied.');

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'Ms3.Ms3CommerceFx',
    'Pi1',
    [
        // Cacheables actions
        'Object' => 'list,detail'
    ],
    [
        // Non-Cacheables actions
        'Object' => 'list,detail'
    ]
);
