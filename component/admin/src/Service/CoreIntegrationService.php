<?php
namespace xdecaro\Component\Tasks\Administrator\Service;

defined('_JEXEC') or die;

use xdecaro\Core\Integration\Capability;
use xdecaro\Core\Integration\EntityReference;

final class CoreIntegrationService
{
    public function isAvailable(): bool
    {
        return class_exists(Capability::class) && class_exists(EntityReference::class);
    }

    /** @return array<int,Capability> */
    public function getCapabilities(): array
    {
        if (!$this->isAvailable()) {
            return [];
        }

        return [
            new Capability('com_xdecarotasks', 'tasks.create', '1'),
            new Capability('com_xdecarotasks', 'tasks.assign', '1'),
            new Capability('com_xdecarotasks', 'tasks.complete', '1'),
            new Capability('com_xdecarotasks', 'tasks.query', '1'),
        ];
    }

    /** @param int|string $id */
    public function taskReference($id): ?EntityReference
    {
        return $this->isAvailable()
            ? new EntityReference('com_xdecarotasks', 'task', $id)
            : null;
    }
}
