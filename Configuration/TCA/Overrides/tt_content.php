<?php

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'Ms3.Ms3CommerceFx',
    'Pi1',
    'mS3 Commerce Fx',
    'EXT:ms3commercefx/Resources/Public/Icons/Extension.png'
);

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['ms3commercefx_pi1']='pages,recursive';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['ms3commercefx_pi1']='pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    'ms3commercefx_pi1',
    'FILE:EXT:ms3commercefx/Configuration/FlexForms/Ms3CommerceFxPi1.xml'
);

