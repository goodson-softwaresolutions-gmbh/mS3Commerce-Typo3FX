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

namespace Ms3\Ms3CommerceFx\Domain\Finisher\Cart;

use Extcode\Cart\Domain\Finisher\Cart\AddToCartFinisherInterface;
use Extcode\Cart\Domain\Model\Cart\Cart;
use Extcode\Cart\Domain\Model\Cart\Product;
use Extcode\Cart\Domain\Model\Dto\AvailabilityResponse;
use Ms3\Ms3CommerceFx\Domain\Model\PimObject;
use Ms3\Ms3CommerceFx\Domain\Repository\PimObjectRepository;
use Ms3\Ms3CommerceFx\Persistence\QuerySettings;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Request;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class AddToCartFinisher implements AddToCartFinisherInterface
{
    /**
     * The product type for tx_carts
     */
    public const PRODUCT_TYPE = 'mS3Commerce';

    /**
     * Key to access the {@see \Ms3\Ms3CommerceFx\Domain\Model\Product}
     * of a {@see \Extcode\Cart\Domain\Model\Cart\Product} via {@see Product::getAdditional()}
     */
    public const PIM_PRODUCT_KEY = 'PimProduct';



    /** @var ObjectManager */
    private $objectManager = null;
    protected function getObjectManager() {
        if (!$this->objectManager) $this->objectManager = new ObjectManager();
        return $this->objectManager;
    }

    /**
     * @return array Returns the plugin configuration
     */
    protected function getConfiguration() {
        /** @var ConfigurationManagerInterface $configManager */
        $configManager = $this->getObjectManager()->get(ConfigurationManagerInterface::class);
        $config = $configManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS, 'ms3commercefx');
        return $config;
    }

    /**
     * Initializes the global query settings
     * @param array $config
     */
    protected function initQuerySettings($config) {
        /** @var QuerySettings $querySettings */
        $querySettings = $this->getObjectManager()->get(QuerySettings::class);
        $querySettings->initializeFromSettings($config);
    }

    /**
     * Returns the tax class for the given product
     * @param \Ms3\Ms3CommerceFx\Domain\Model\Product $product
     * @param Request $request
     * @param Cart $cart
     * @return \Extcode\Cart\Domain\Model\Cart\TaxClass
     */
    protected function getTaxClass(\Ms3\Ms3CommerceFx\Domain\Model\Product $product, Request $request, Cart $cart) {
        $taxClasses = $cart->getTaxClasses();

        // TODO tax classes. For now we choose 0% fixed (if exists, otherwise first)
        $tc = reset($taxClasses);
        foreach ($taxClasses as $tx) {
            if ($tx->getCalc() == 0) {
                $tc = $tx;
                break;
            }
        }
        return $tc;
    }

    /**
     * Gets the PIM Product for the request
     * @param Request $request
     * @return \Ms3\Ms3CommerceFx\Domain\Model\Product
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException
     */
    protected function getPimProductForRequest(Request $request) {
        $productId = $request->getArgument('productId');
        /** @var PimObjectRepository $repo */
        $repo = $this->getObjectManager()->get(PimObjectRepository::class);
        /** @var \Ms3\Ms3CommerceFx\Domain\Model\Product $product */
        $product = $repo->getObjectById(PimObject::TypeProduct, $productId);
        return $product;
    }

    /**
     * Builds a tx_cart mS3Commerce Product from the request
     * @param Request $request
     * @param Cart $cart
     * @return Product
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException
     */
    protected function getCartProductForRequest(Request $request, Cart $cart) {
        $type = $request->getArgument('productType');
        if ($type != AddToCartFinisher::PRODUCT_TYPE) {
            throw new \Exception('Invalid product type');
        }

        $config = $this->getConfiguration();
        $this->initQuerySettings($config);

        $productId = $request->getArgument('productId');
        $qty = $request->getArgument('quantity');

        $pimProduct = $this->getPimProductForRequest($request);
        if (!$pimProduct) {
            return null;
        }

        $tc = $this->getTaxClass($pimProduct, $request, $cart);

        $product = new \Extcode\Cart\Domain\Model\Cart\Product(
            self::PRODUCT_TYPE,
            $productId,
            $pimProduct->getName(),
            $pimProduct->getAuxiliaryName(),
            $pimProduct->getPrice()->getPrice(),
            $tc,
            $qty
        );
        $product->setAdditional(self::PIM_PRODUCT_KEY, $pimProduct);
        return $product;
    }

    /**
     * Handles the different modes for adding to basket
     * @param Product $product
     * @param Cart $cart
     */
    protected function handleAddBasketMode(Product $product, Cart $cart)
    {
        $config = $this->getConfiguration();
        // Add mode: replace product count, or sum (=default)?
        if ($config['tx_cart']['addBasketMode'] == 'replace') {
            $cart->removeProductById($product->getId());
        }
    }

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
        return [[/*errors*/], [/*product*/$product]];
    }
}
