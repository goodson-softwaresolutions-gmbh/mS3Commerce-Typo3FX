<?php


namespace Ms3\Ms3CommerceFx\ViewHelpers\Shop;


use Ms3\Ms3CommerceFx\Integration\Carts\Domain\Finisher\Cart\AddToCartFinisher;
use Ms3\Ms3CommerceFx\Domain\Repository\PimObjectRepository;
use Ms3\Ms3CommerceFx\Persistence\QuerySettings;
use Ms3\Ms3CommerceFx\Service\ShopService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class PimProductViewHelper extends AbstractViewHelper
{
    protected $escapeOutput = false;
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('product', 'mixed', 'tx_cart product', true);
        $this->registerArgument('as', 'string', '', false, 'pimProduct');
    }

    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $cartProduct = $arguments['product'];
        if ($cartProduct instanceof \Extcode\Cart\Domain\Model\Cart\Product) {
            /** @var \Extcode\Cart\Domain\Model\Cart\Product $cartProduct */
            if ($cartProduct->getProductType() != AddToCartFinisher::PRODUCT_TYPE) {
                throw new \Exception('Argument product must be a mS3Commerce tx_cart product');
            }

            // Can already be cached in additional
            $pimProduct = $cartProduct->getAdditional(AddToCartFinisher::PIM_PRODUCT_KEY);
            if (!$pimProduct) {
                $pimProduct = self::loadPimProduct($cartProduct->getSku());
                $cartProduct->setAdditional(AddToCartFinisher::PIM_PRODUCT_KEY, $pimProduct);
            }
        } else if ($cartProduct instanceof \Extcode\Cart\Domain\Model\Order\Product) {
            /** @var \Extcode\Cart\Domain\Model\Order\Product $cartProduct */
            if ($cartProduct->getProductType() != AddToCartFinisher::PRODUCT_TYPE) {
                throw new \Exception('Argument product must be a mS3Commerce tx_cart product');
            }
            $pimProduct = self::loadPimProduct($cartProduct->getSku());
        } else {
            throw new \Exception('Argument product must be a tx_cart product');
        }

        $renderingContext->getVariableProvider()->add($arguments['as'], $pimProduct);
        $res = $renderChildrenClosure();
        $renderingContext->getVariableProvider()->remove($arguments['as']);

        return $res;
    }


    private static function loadPimProduct($sku)
    {
        $mgr = GeneralUtility::makeInstance(ObjectManager::class);
        /** @var PimObjectRepository $repo */
        $repo = $mgr->get(PimObjectRepository::class);
        /** @var ShopService $shopService */
        $shopService = $mgr->get(ShopService::class);
        $shopService->ensureShopParametersSet();
        return $repo->getProductByName($sku);
    }

}