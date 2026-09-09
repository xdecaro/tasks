<?php
namespace xdecaro\Plugin\Task\Tasks\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\Scheduler\Administrator\Event\ExecuteTaskEvent;
use Joomla\Component\Scheduler\Administrator\Task\Status;
use Joomla\Component\Scheduler\Administrator\Traits\TaskPluginTrait;
use Joomla\Event\SubscriberInterface;
use Throwable;
use xdecaro\Component\Tasks\Administrator\Extension\TasksComponent;

final class Tasks extends CMSPlugin implements SubscriberInterface
{
    use TaskPluginTrait;

    protected const TASKS_MAP = [
        'xdecarotasks.due' => [
            'langConstPrefix' => 'PLG_TASK_XDECAROTASKS_DUE',
            'form' => 'due',
            'method' => 'processDue',
        ],
    ];

    protected $autoloadLanguage = true;

    public static function getSubscribedEvents(): array
    {
        return [
            'onTaskOptionsList' => 'advertiseRoutines',
            'onExecuteTask' => 'standardRoutineHandler',
            'onContentPrepareForm' => 'enhanceTaskItemForm',
        ];
    }

    protected function processDue(ExecuteTaskEvent $event): int
    {
        try {
            $component = $this->bootComponent();
            $global = ComponentHelper::getParams('com_xdecarotasks');
            $params = $event->getArgument('params');
            $minutes = max(15, min(10080, (int) ($params->reminder_minutes ?? $global->get('due_reminder_minutes', 1440))));
            $limit = max(1, min(1000, (int) ($params->limit ?? 200)));
            $stats = $component->getDueService()->process($minutes, $limit);
            $this->logTask(sprintf('Tasks due check: tasks=%d notifications=%d skipped=%d', $stats['tasks'], $stats['sent'], $stats['skipped']));
            return Status::OK;
        } catch (Throwable $exception) {
            $this->logTask('Tasks due check error: ' . $exception->getMessage(), 'error');
            return Status::KNOCKOUT;
        }
    }

    private function bootComponent(): TasksComponent
    {
        $component = Factory::getApplication()->bootComponent('com_xdecarotasks');
        if (!$component instanceof TasksComponent) { throw new \RuntimeException('Tasks component is unavailable.'); }
        return $component;
    }
}
