ms3commercefx.ajax = PAGE
ms3commercefx.ajax {
    typeNum = 159
    config.disableAllHeaderCode = 1
    additionalHeaders = Content-type:application/json
    xhtml_cleaning = 0
    admPanel = 0
    10 = COA
    10 < tt_content.list.20.ms3commercefx_pi1
    10.switchableControllerActions.AjaxSearch.1 = filter
}
