<?php
use \Ms3\Ms3CommerceFx\Controller\ObjectController;
use \Ms3\Ms3CommerceFx\Controller\MenuController;
use \Ms3\Ms3CommerceFx\Controller\AjaxSearchController;
use \Ms3\Ms3CommerceFx\Controller\SearchController;

defined('TYPO3_MODE') || die('Access denied.');

require_once(\TYPO3\CMS\Core\Core\Environment::getPublicPath().'/dataTransfer/runtime_config.php');

(static function(){
    $pluginActions =
    $nonCacheable = [
        ObjectController::class => 'list,detail',
        MenuController::class => 'menu',
        AjaxSearchController::class => 'filter',
        SearchController::class => 'search'
    ];

    if (defined('MS3C_TYPO3_CACHED') && MS3C_TYPO3_CACHED) {
        //unset($nonCacheable['Object']);
        $nonCacheable[ObjectController::class] = 'detail';
        unset($nonCacheable[MenuController::class]);
        unset($nonCacheable[AjaxSearchController::class]);
    }

    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'Ms3.Ms3CommerceFx',
        'Pi1',
        $pluginActions,
        $nonCacheable
    );

    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'Ms3.Ms3CommerceFx',
        'Menu',
        [MenuController::class => $pluginActions[MenuController::class]],
        [MenuController::class => $nonCacheable[MenuController::class]]
    );

    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'Ms3.Ms3CommerceFx',
        'AjaxSearch',
        [AjaxSearchController::class => $pluginActions[AjaxSearchController::class]],
        [AjaxSearchController::class => $nonCacheable[AjaxSearchController::class]]
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
        $fullTextClass = \Ms3\Ms3CommerceFx\Search\MySqlFullTextSearch::class;
    } else if (MS3C_SEARCH_BACKEND == 'ElasticSearch') {
        // TODO Not yet supported
        //$fullTextClass = 'ElasticFullTextSearch';
    }

    if (!empty($fullTextClass)) {
        \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\Container\Container::class)
            ->registerImplementation(Ms3\Ms3CommerceFx\Search\FullTextSearchInterface::class, $fullTextClass);
    }

    if (TYPO3_MODE === 'FE') {
        if (MS3C_SHOP_SYSTEM == 'tx_cart') {
            \Ms3\Ms3CommerceFx\Integration\Carts\Hooks\CartHooks::initializeHooks();
        }
    }

})();
