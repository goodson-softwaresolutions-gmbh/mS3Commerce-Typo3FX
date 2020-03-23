<?php

namespace Ms3\Ms3CommerceFx\Domain\Finisher\Cart;

use Extcode\Cart\Domain\Finisher\Cart\AddToCartFinisherInterface;
use Extcode\Cart\Domain\Model\Cart\Cart;
use Extcode\Cart\Domain\Model\Cart\Product;
use Extcode\Cart\Domain\Model\Dto\AvailabilityResponse;
use Ms3\Ms3CommerceFx\Domain\Model\PimObject;
use Ms3\Ms3CommerceFx\Domain\Repository\PimObjectRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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

        $requestArguments = $request->getArguments();
        $type = $request->getArgument('productType');
        $productId = $request->getArgument('productId');
        $qty = $request->getArgument('quantity');

        $taxClasses = $cart->getTaxClasses();


        $objectManager = new ObjectManager();
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
            $taxClasses[3],
            $qty
        );

        return [[/*errors*/], [/*product*/$product]];
    }
}