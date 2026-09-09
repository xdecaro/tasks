<?php
namespace xdecaro\Component\Tasks\Administrator\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Extension\MVCComponent;
use LogicException;
use xdecaro\Component\Tasks\Administrator\Service\CoreIntegrationService;
use xdecaro\Component\Tasks\Administrator\Service\DueService;
use xdecaro\Component\Tasks\Administrator\Service\TaskService;

final class TasksComponent extends MVCComponent
{
    private $taskService;
    private $coreIntegrationService;
    private $dueService;

    public function setTaskService(TaskService $service): void { $this->taskService = $service; }
    public function setCoreIntegrationService(CoreIntegrationService $service): void { $this->coreIntegrationService = $service; }
    public function setDueService(DueService $service): void { $this->dueService = $service; }

    public function getTaskService(): TaskService
    {
        if ($this->taskService === null) { throw new LogicException('Tasks service has not been initialized.'); }
        return $this->taskService;
    }

    public function getCoreIntegrationService(): CoreIntegrationService
    {
        if ($this->coreIntegrationService === null) { throw new LogicException('Core integration service has not been initialized.'); }
        return $this->coreIntegrationService;
    }

    public function getDueService(): DueService
    {
        if ($this->dueService === null) { throw new LogicException('Due service has not been initialized.'); }
        return $this->dueService;
    }
}
