<?php
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Definition;

return function (ContainerConfigurator $container, ContainerBuilder $containerBuilder) {
    $cartVersion = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getExtensionVersion('cart');
    if (!$cartVersion || version_compare($cartVersion, '7.0.0', '<')) {
        return;
    }

    $d = new Definition();
    $d->setAutowired(true);
    $d->addTag('event.listener', [
        'identifier' => 'ms3commercefx/cart-add-to-cart-finisher',
        'event' => 'Extcode\Cart\Event\RetrieveProductsFromRequestEvent',
    ]);

    $containerBuilder->setDefinition(\Ms3\Ms3CommerceFx\Integration\Carts\Domain\Finisher\Cart\AddToCartFinisherListener::class, $d);
};
