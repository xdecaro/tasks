<?php
namespace xdecaro\Component\Tasks\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\CMS\WebAsset\WebAssetManager;
use xdecaro\Core\Asset\AssetService;
use xdecaro\Core\Integration\Capability;
use xdecaro\Core\Integration\CapabilityRegistry;
use xdecaro\Core\Integration\EntityReference;

final class CoreIntegrationService
{
    public function isAvailable(): bool
    {
        return class_exists(Capability::class) && class_exists(EntityReference::class);
    }

    public function getCapabilities(): array
    {
        if (!$this->isAvailable()) { return []; }
        return [
            new Capability('com_xdecarotasks', 'tasks.create', '1'),
            new Capability('com_xdecarotasks', 'tasks.assign', '1'),
            new Capability('com_xdecarotasks', 'tasks.complete', '1'),
            new Capability('com_xdecarotasks', 'tasks.query', '1'),
        ];
    }

    public function registerCapabilities($registry): bool
    {
        if (!class_exists(CapabilityRegistry::class) || !$registry instanceof CapabilityRegistry) { return false; }
        $registry->registerMany($this->getCapabilities());
        return true;
    }

    public function taskReference($id): ?EntityReference
    {
        return $this->isAvailable() ? new EntityReference('com_xdecarotasks', 'task', $id) : null;
    }

    public function useAssets(WebAssetManager $assets): bool
    {
        if (!class_exists(AssetService::class)) { return false; }
        return (new AssetService())->useComponents($assets);
    }
}
