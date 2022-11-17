<?php
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Definition;

return function (ContainerConfigurator $container, ContainerBuilder $containerBuilder) {
    $registerEvent = function($event, $class, $id = '') use ($containerBuilder) {
        if (empty($id)) {
            $id = 'ms3commercefx/'.strtolower(str_replace('\\', '-', $event).'-'.strtolower(str_replace('\\', '_', $class)));
        }
        $d = new Definition();
        $d->setAutowired(true);
        $d->addTag('event.listener', [
            'identifier' => $id,
            'event' => $event,
        ]);
        $containerBuilder->setDefinition($class, $d);
    };

    /////// IMAGE CLEAR
    $registerEvent(
        'TYPO3\CMS\Core\Resource\Event\BeforeFileProcessingEvent',
        \Ms3\Ms3CommerceFx\EventListener\BeforeFileProcessingEvent::class,
        'ms3commercefx/check-processed-file-before-processing'
    );

    /////// CART
    if ($containerBuilder->hasDefinition('Extcode\Cart\Domain\Finisher\Cart\AddToCartFinisherInterface')) {
        // Cart version < 7.0
        $registerEvent(
            'Extcode\Cart\Event\RetrieveProductsFromRequestEvent',
            \Ms3\Ms3CommerceFx\Domain\Finisher\Cart\AddToCartFinisherListener::class,
            'ms3commercefx/cart-add-to-cart-finisher'
        );
    }

    ////// SEARCH
    (function () use ($container, $containerBuilder) {
        $fullTextClass = \Ms3\Ms3CommerceFx\Search\FullTextSearch::class;
        if (defined('MS3C_SEARCH_BACKEND')) {
            if (MS3C_SEARCH_BACKEND == 'MySQL') {
                $fullTextClass = \Ms3\Ms3CommerceFx\Search\MySqlFullTextSearch::class;
            } else if (MS3C_SEARCH_BACKEND == 'ElasticSearch') {
                // TODO Not yet supported
                //$fullTextClass = 'ElasticFullTextSearch';
            }
        }

        $services = $container->services();
        $services->alias(\Ms3\Ms3CommerceFx\Search\FullTextSearchInterface::class, $fullTextClass);
    })();
};

