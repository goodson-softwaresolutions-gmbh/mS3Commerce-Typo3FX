<?php


namespace Ms3\Ms3CommerceFx\Integration\Carts\Hooks;


use Extcode\Cart\Domain\Model\Cart\Cart;
use Extcode\Cart\Utility\OrderUtility;
use Ms3\Ms3CommerceFx\Domain\Model\PimObjectCollection;
use Ms3\Ms3CommerceFx\Domain\Model\Product;
use Ms3\Ms3CommerceFx\Integration\Carts\Domain\Finisher\Cart\AddToCartFinisher;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;

class CartHooks implements SingletonInterface
{
    public static function initializeHooks() {
        $cartVersion = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getExtensionVersion('cart');
        if (!$cartVersion) {
            return;
        }

        /** @var Dispatcher $dispatcher */
        $dispatcher = GeneralUtility::makeInstance(Dispatcher::class);
        $dispatcher->connect(
            OrderUtility::class,
            'addProductAdditionalData',
            self::class,
            'fixAdditionalDataForOrder',
            false
        );

        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cart']['showCartActionAfterCartWasLoaded'][] =
            \Ms3\Ms3CommerceFx\Integration\Carts\Hooks\CartHooks::class.'->afterRestoreSessionCart';

    }

    public function afterRestoreSessionCart(&$params, &$ref)
    {
        /** @var Cart $cart */
        $cart = $params['cart'];
        $pimProducts = $this->getPimProductsFromCart($cart);
        $this->createCollection($pimProducts);
    }

    public function fixAdditionalDataForOrder($params)
    {
        $additionalArray = &$params['additionalArray'];
        unset($additionalArray[AddToCartFinisher::PIM_PRODUCT_KEY]);
    }

    /**
     * @param Cart $cart
     * @return Product[]
     */
    private function getPimProductsFromCart($cart)
    {
        /** @var Product[] $pimProducts */
        $pimProducts = [];
        foreach ($cart->getProducts() as $product) {
            if ($product->getProductType() == AddToCartFinisher::PRODUCT_TYPE) {
                $pimProducts[] = $product->getAdditional(AddToCartFinisher::PIM_PRODUCT_KEY);
            }
        }
        return array_filter($pimProducts);
    }

    /**
     * @param Product[] $pimProducts
     */
    private function createCollection($pimProducts) {
        if (count($pimProducts) > 1) {
            PimObjectCollection::createCollection($pimProducts);
        }
    }

}
