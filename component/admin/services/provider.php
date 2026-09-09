<?php
namespace xdecaro\Component\Tasks\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use xdecaro\Component\Tasks\Administrator\Extension\TasksComponent;

return new class implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->registerServiceProvider(new MVCFactory('xdecaro\\Component\\Tasks'));
        $container->registerServiceProvider(new ComponentDispatcherFactory('xdecaro\\Component\\Tasks'));
        $container->share(CoreIntegrationService::class, static fn (): CoreIntegrationService => new CoreIntegrationService());
        $container->share(NotificationIntegrationService::class, static fn (): NotificationIntegrationService => new NotificationIntegrationService());
        $container->share(TaskService::class, static fn (Container $c): TaskService => new TaskService($c->get(DatabaseInterface::class), $c->get(NotificationIntegrationService::class)));
        $container->share(DueService::class, static fn (Container $c): DueService => new DueService($c->get(DatabaseInterface::class), $c->get(NotificationIntegrationService::class)));
        $container->set(ComponentInterface::class, static function (Container $container): ComponentInterface {
            $component = new TasksComponent($container->get(ComponentDispatcherFactoryInterface::class), $container->get(MVCFactoryInterface::class));
            $component->setTaskService($container->get(TaskService::class));
            $component->setCoreIntegrationService($container->get(CoreIntegrationService::class));
            $component->setDueService($container->get(DueService::class));
            return $component;
        });
    }
};
