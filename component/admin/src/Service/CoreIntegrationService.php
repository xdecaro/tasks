<?php
namespace Xdecaro\Component\Decarotasks\Administrator\Service;

defined('_JEXEC') or die;

use Xdecaro\Core\Integration\Capability;
use Xdecaro\Core\Integration\EntityReference;

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
            new Capability('com_decarotasks', 'tasks.create', '1'),
            new Capability('com_decarotasks', 'tasks.assign', '1'),
            new Capability('com_decarotasks', 'tasks.complete', '1'),
            new Capability('com_decarotasks', 'tasks.query', '1'),
        ];
    }

    /** @param int|string $id */
    public function taskReference($id): ?EntityReference
    {
        return $this->isAvailable()
            ? new EntityReference('com_decarotasks', 'task', $id)
            : null;
    }
}
