<?php
defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use xdecaro\Plugin\Task\Tasks\Extension\Tasks;

return new class implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->set(PluginInterface::class, $container->lazy(Tasks::class, static function (): Tasks {
            return new Tasks((array) PluginHelper::getPlugin('task', 'xdecarotasks'));
        }));
    }
};
