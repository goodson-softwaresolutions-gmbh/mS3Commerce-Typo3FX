<?php


namespace Ms3\Ms3CommerceFx\ViewHelpers\Shop;


use Ms3\Ms3CommerceFx\Domain\Finisher\Cart\AddToCartFinisher;
use Ms3\Ms3CommerceFx\Domain\Repository\PimObjectRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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
        /** @var \Extcode\Cart\Domain\Model\Cart\Product $cartProduct */
        $cartProduct = $arguments['product'];
        if (!$cartProduct instanceof \Extcode\Cart\Domain\Model\Cart\Product) {
            throw new \Exception('Argument product must be a tx_cart product');
        }
        if ($cartProduct->getProductType() != AddToCartFinisher::PRODUCT_TYPE) {
            throw new \Exception('Argument product must be a mS3Commerce tx_cart product');
        }

        $pimProduct = $cartProduct->getAdditional(AddToCartFinisher::PIM_PRODUCT_KEY);
        if (!$pimProduct) {
            // TODO
            $mgr = new ObjectManager();
            /** @var PimObjectRepository $repo */
            $repo = $mgr->get(PimObjectRepository::class);
            $pimProduct = $repo->getProductByName($cartProduct->getSku());
            $cartProduct->setAdditional(AddToCartFinisher::PIM_PRODUCT_KEY, $pimProduct);
        }

        $renderingContext->getVariableProvider()->add($arguments['as'], $pimProduct);
        $res = $renderChildrenClosure();
        $renderingContext->getVariableProvider()->remove($arguments['as']);

        return $res;
    }
}