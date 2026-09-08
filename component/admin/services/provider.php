<?php
namespace Xdecaro\Component\Decarotasks\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Xdecaro\Component\Decarotasks\Administrator\Extension\DecarotasksComponent;

return new class implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->registerServiceProvider(new MVCFactory('Xdecaro\\Component\\Decarotasks'));
        $container->registerServiceProvider(new ComponentDispatcherFactory('Xdecaro\\Component\\Decarotasks'));
        $container->share(CoreIntegrationService::class, static fn (): CoreIntegrationService => new CoreIntegrationService());
        $container->set(ComponentInterface::class, static fn (Container $container): ComponentInterface => new DecarotasksComponent(
            $container->get(ComponentDispatcherFactoryInterface::class),
            $container->get(MVCFactoryInterface::class)
        ));
    }
};
