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

namespace Ms3\Ms3CommerceFx\Integration\Carts\Domain\Finisher\Cart;


use Extcode\Cart\Domain\Model\Cart\Cart;
use Extcode\Cart\Domain\Model\Cart\Product;
use Extcode\Cart\Domain\Model\Dto\AvailabilityResponse;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Web\Request;

$__cartVersion = ExtensionManagementUtility::getExtensionVersion('cart');
if ($__cartVersion && version_compare($__cartVersion, '7.0.0', '<')) {

    /**
     * Class AddToCartFinisherAdapter
     * Legacy adapter for carts before 7.0.
     * This used to have a finisher interface. This adapter bridges to the implementation
     * @package Ms3\Ms3CommerceFx\Integration\Carts\Domain\Finisher\Cart
     */

    class AddToCartFinisherAdapter extends AddToCartFinisher implements \Extcode\Cart\Domain\Finisher\Cart\AddToCartFinisherInterface
    {
        /**
         * @inheritDoc
         */
        public function checkAvailability(Request $request, Product $cartProduct, Cart $cart): AvailabilityResponse
        {
            /** @var AvailabilityResponse $response */
            $response = GeneralUtility::makeInstance(AvailabilityResponse::class);
            return $response;
        }

        /**
         * @inheritDoc
         */
        public function getProductFromRequest(Request $request, Cart $cart)
        {
            $product = $this->getCartProductForRequest($request, $cart);
            $this->handleAddBasketMode($product, $cart);
            return [[/*errors*/], [/*product*/ $product]];
        }
    }
}
unset($__cartVersion);