<?php
/***************************************************************
 * Part of mS3 Commerce Fx
 * Copyright (C) 2019 Goodson GmbH <http://www.goodson.at>
 *  All rights reserved
 *
 * Dieses Computerprogramm ist urheberrechtlich sowie durch internationale
 * Abkommen geschützt. Die unerlaubte Reproduktion oder Weitergabe dieses
 * Programms oder von Teilen dieses Programms kann eine zivil- oder
 * strafrechtliche Ahndung nach sich ziehen und wird gemäß der geltenden
 * Rechtsprechung mit größtmöglicher Härte verfolgt.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

namespace Ms3\Ms3CommerceFx\ViewHelpers\Shop;

use Ms3\Ms3CommerceFx\ViewHelpers\AbstractTagBasedViewHelper;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

class CartFormViewHelper extends AbstractTagBasedViewHelper
{
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('basketPid', 'int', '', true);
        $this->registerArgument('ajax', 'bool', '', false, false);
        $this->registerArgument('useDefaultResponses', 'bool', false, false);
    }

    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $isAjax = $arguments['ajax'];

        $content = '<input type="hidden" name="tx_cart_cart[productType]" value="mS3Commerce"/>';
        $renderingContext->getVariableProvider()->add('productFieldName', 'tx_cart_cart[productId]');
        $renderingContext->getVariableProvider()->add('quantityFieldName', 'tx_cart_cart[quantity]');
        $content .= $renderChildrenClosure();
        $renderingContext->getVariableProvider()->remove('productFieldName');
        $renderingContext->getVariableProvider()->remove('quantityFieldName');

        if ($isAjax && $arguments['useDefaultResponses']) {
            $content .= <<<XXX
    <div class="form-message" data-ajax-message-timeout="3000">
        <div class="form-success" style="display: none;" data-ajax-success-block="">
            <div class="alert alert-success" data-ajax-success-message=""></div>
        </div>
        <div class="form-error" style="display: none;" data-ajax-error-block>
            <div class="alert alert-warning" data-ajax-error-message></div>
        </div>
    </div>
XXX;
        }

        parent::registerTagArgument('action');
        $uri = self::getFormUri($arguments['basketPid'], $isAjax);
        if (!array_key_exists('method', $arguments)) {
            $arguments['method'] = 'POST';
        }
        $arguments['action'] = $uri;
        if ($isAjax) {
            $arguments['data']['ajax'] = 1;
        }

        return parent::renderTag('form', $content, $arguments);
    }

    private static function getFormUri($pid, $isAjax) {
        /** @var ObjectManager $objManager */
        $objManager = GeneralUtility::makeInstance(ObjectManager::class);
        /** @var UriBuilder $uriBuilder */
        $uriBuilder = $objManager->get(UriBuilder::class);
        $pid = $pid ?: $GLOBALS['TSFE']->id;
        $uriBuilder
            ->reset()
            ->setTargetPageUid($pid)
            ->setNoCache(true)
            ->setUseCacheHash(false)
        ;

        if ($isAjax) {
            $uriBuilder->setTargetPageType(2278001);
        };

        return $uriBuilder
            ->uriFor(null, [], 'Cart\Product', 'cart', 'cart')
            ;
    }
}
