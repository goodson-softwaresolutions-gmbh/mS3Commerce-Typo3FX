<?php

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
     * @inheritDoc
     */
    public function checkAvailability(Request $request, Product $cartProduct, Cart $cart): AvailabilityResponse
    {
        $response = GeneralUtility::makeInstance(AvailabilityResponse::class);
        return $response;
    }

    /**
     * @inheritDoc
     */
    public function getProductFromRequest(Request $request, Cart $cart)
    {
        $type = $request->getArgument('productType');
        if ($type != 'mS3Commerce') {
            throw new \Exception('Invalid product type');
        }

        $objectManager = new ObjectManager();
        /** @var ConfigurationManagerInterface $configManager */
        $configManager = $objectManager->get(ConfigurationManagerInterface::class);
        $config = $configManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS, 'ms3commercefx');

        /** @var QuerySettings $querySettings */
        $querySettings = $objectManager->get(QuerySettings::class);
        $querySettings->initializeFromSettings($config);

        $productId = $request->getArgument('productId');
        $qty = $request->getArgument('quantity');

        $taxClasses = $cart->getTaxClasses();

        // TODO tax classes. For now we choose 0% fixed (if exists, otherwise first)
        $tc = reset($taxClasses);
        foreach ($taxClasses as $tx) {
            if ($tx->getCalc() == 0) {
                $tc = $tx;
                break;
            }
        }

        /** @var PimObjectRepository $repo */
        $repo = $objectManager->get(PimObjectRepository::class);
        /** @var \Ms3\Ms3CommerceFx\Domain\Model\Product $product */
        $product = $repo->getObjectById(PimObject::TypeProduct, $productId);

        $product = new \Extcode\Cart\Domain\Model\Cart\Product(
            'mS3Commerce',
            $productId,
            $product->getName(),
            $product->getAuxiliaryName(),
            $product->getPrice()->getPrice(),
            $tc,
            $qty
        );

        // Add mode: replace product count, or sum (=default)?
        if ($config['tx_cart']['addBasketMode'] == 'replace') {
            $cart->removeProductById($product->getId());
        }

        return [[/*errors*/], [/*product*/$product]];
    }
}