<?php
defined('TYPO3_MODE') || die('Access denied.');

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'Ms3.Ms3CommerceFx',
    'Pi1',
    [
        // Cacheables actions
        'Object' => 'list,detail',
        'Menu' => 'menu',
        'AjaxSearch' => 'filter'
    ],
    [
        // Non-Cacheables actions
        'Object' => 'list,detail',
        'Menu' => 'menu',
        'AjaxSearch' => 'filter'
    ]
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
    '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:ms3commercefx/Configuration/TsConfig/Page/Mod/Wizards/NewContentElement.tsconfig">'
);
/** @var \TYPO3\CMS\Core\Imaging\IconRegistry $iconRegistry */
$iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
$iconRegistry->registerIcon(
    'ms3commercefx-pi1',
    \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
    ['source' => 'EXT:ms3commercefx/Resources/Public/Icons/Extension.png']
);
