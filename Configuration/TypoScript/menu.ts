ms3commercefx.menu = USER
ms3commercefx.menu {
    userFunc = TYPO3\CMS\Extbase\Core\Bootstrap->run
    extensionName = Ms3CommerceFx
    pluginName = Pi1
    vendorName = Ms3
    controller = Menu
    action = menu
    switchableControllerActions.Menu.1 = menu
    mvc.callDefaultActionIfActionCantBeResolved = 1
    view < plugin.tx_ms3commercefx.view
    persistence < plugin.tx_ms3commercefx.persistence
    settings < plugin.tx_ms3commercefx.settings
}
