<?php
defined('TYPO3_MODE') or die();

// USAGE: TCA Reference > $GLOBALS['TCA'] array reference > ['columns'][fieldname]['config'] / TYPE: "select"
$temporaryColumns = array (
    'ms3c_user_rights' => array (
        'exclude' => 0,
        'label' => 'ms3Commerce user rights',
        'config' => array (
            'type' => 'input',
            'size' => 40,
            'max' => 255
        )
    ),
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
    'fe_groups',
    $temporaryColumns
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'fe_groups',
    '-–div–-;mS3 Commerce,ms3c_user_rights'
);