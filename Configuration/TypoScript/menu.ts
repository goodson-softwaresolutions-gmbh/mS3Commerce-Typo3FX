ms3commercefx.menu = USER
ms3commercefx.menu {
    userFunc = TYPO3\CMS\Extbase\Core\Bootstrap->run
    extensionName = Ms3CommerceFx
    pluginName = Menu
    vendorName = Ms3
    controller = Menu
    action = menu
    view < plugin.tx_ms3commercefx.view
    persistence < plugin.tx_ms3commercefx.persistence
    settings < plugin.tx_ms3commercefx.settings
}
