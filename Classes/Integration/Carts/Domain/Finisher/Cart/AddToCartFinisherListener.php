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


namespace Ms3\Ms3CommerceFx\Integration\Carts\Domain\Finisher\Cart;


use Extcode\Cart\Event\RetrieveProductsFromRequestEvent;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class AddToCartFinisherListener
 * Handler for carts v7 and above for add to cart events
 * @package Ms3\Ms3CommerceFx\Integration\Carts\Domain\Finisher\Cart
 */
class AddToCartFinisherListener extends AddToCartFinisher
{
    public function __invoke(RetrieveProductsFromRequestEvent $event)
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
}
