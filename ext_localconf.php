<?php
defined('TYPO3_MODE') || die('Access denied.');

require_once(\TYPO3\CMS\Core\Core\Environment::getPublicPath().'/dataTransfer/runtime_config.php');

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'Ms3.Ms3CommerceFx',
    'Pi1',
    [
        // Cacheables actions
        'Object' => 'list,detail',
        'Menu' => 'menu',
        'AjaxSearch' => 'filter',
        'Search' => 'search'
    ],
    [
        // Non-Cacheables actions
        'Object' => 'list,detail',
        'Menu' => 'menu',
        'AjaxSearch' => 'filter',
        'Search' => 'search'
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

$GLOBALS['TYPO3_CONF_VARS']['SYS']['routing']['aspects']['Ms3CommerceFxRoutingMapper'] =
    \Ms3\Ms3CommerceFx\Routing\Aspect\PersistedAspectMapper::class;

$fullTextClass = '';
if (MS3C_SEARCH_BACKEND == 'MySQL') {
    $fullTextClass = 'MySqlFullTextSearch';
} else if (MS3C_SEARCH_BACKEND == 'ElasticSearch') {
    $fullTextClass = 'ElasticFullTextSearch';
}

if (!empty($fullTextClass)) {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScriptSetup(
        <<<XXX
config.tx_extbase.objects {
	Ms3\Ms3CommerceFx\Search\FullTextSearchInterface {
		className = Ms3\Ms3CommerceFx\Search\\$fullTextClass
	}
}
XXX
    );
}
unset($fullTextClass);

if (TYPO3_MODE === 'FE') {
    if (MS3C_SHOP_SYSTEM == 'tx_cart') {
        if (!defined('MS3C_TX_CART_ADDTOCART_CUSTOM_CLASS') || !MS3C_TX_CART_ADDTOCART_CUSTOM_CLASS) {
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cart'][\Ms3\Ms3CommerceFx\Domain\Finisher\Cart\AddToCartFinisher::PRODUCT_TYPE]['Cart']['AddToCartFinisher'] =
                \Ms3\Ms3CommerceFx\Domain\Finisher\Cart\AddToCartFinisher::class;
        }
    }
}
