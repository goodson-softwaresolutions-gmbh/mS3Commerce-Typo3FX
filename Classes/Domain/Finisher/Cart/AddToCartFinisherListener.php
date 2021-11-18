<?php
/***************************************************************
 * Part of mS3 Commerce Fx
 * Copyright (C) 2021 Goodson GmbH <http://www.goodson.at>
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


namespace Ms3\Ms3CommerceFx\Domain\Finisher\Cart;


use Extcode\Cart\Event\RetrieveProductsFromRequestEvent;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\Container\Container as ExtbaseContainer;

/**
 * Class AddToCartFinisherListener
 * Handler for carts v7 and above for add to cart events
 * @package Ms3\Ms3CommerceFx\Domain\Finisher\Cart
 */
class AddToCartFinisherListener extends AddToCartFinisher
{
    /** @var ExtbaseContainer */
    protected $objectManager;

    /**
     * @param ExtbaseContainer $objectManager
     */
    public function injectObjectManager(ExtbaseContainer $objectManager) {
        $this->objectManager = $objectManager;
    }
    public function __invoke(RetrieveProductsFromRequestEvent $event)
    {
        if (defined('MS3C_TX_CART_ADDTOCART_CUSTOM_CLASS') && MS3C_TX_CART_ADDTOCART_CUSTOM_CLASS) {
            // Legacy mode: check overriding class
            $name = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cart'][AddToCartFinisher::PRODUCT_TYPE]['Cart']['AddToCartFinisher'];
            if ($name) {
                $this->handleLegacyRequest($name, $event);
            }

            // If there is no overriding class, but specified there is a custom class,
            // assume it is registered as own event listener.
            // => Nothing to do for us
            return;
        } else {
            $this->handleListenerRequest($event);
        }
    }

    /**
     * @param RetrieveProductsFromRequestEvent $event
     */
    private function handleListenerRequest($event)
    {
        $request = $event->getRequest();
        $cart = $event->getCart();
        try {
            $res = $this->getCartProductForRequest($request, $cart);
            if ($res) {
                $event->addProduct($res);
            }
        } catch (\Exception $e) {
            $event->addError(
                GeneralUtility::makeInstance(FlashMessage::class,
                    $e->getMessage()
                )
            );
        }
    }

    /**
     * @param string $className
     * @param RetrieveProductsFromRequestEvent $event
     */
    private function handleLegacyRequest($className, $event) {
        $request = $event->getRequest();
        $cart = $event->getCart();
        $obj = $this->objectManager->getInstance($className);
        $res = $obj->getProductFromRequest($request, $cart);
        $errors = $res[0];
        $products = $res[1];
        foreach ($errors as $e) {
            $event->addError(GeneralUtility::makeInstance(FlashMessage::class, $e));
        }
        foreach ($products as $p) {
            $event->addProduct($p);
        }
    }

}
