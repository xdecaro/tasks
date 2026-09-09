<?php
namespace xdecaro\Component\Tasks\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Throwable;

final class NotificationIntegrationService
{
    public function isAvailable(): bool
    {
        try {
            $component = Factory::getApplication()->bootComponent('com_xdecaronotifications');
            return is_object($component) && method_exists($component, 'getNotificationService');
        } catch (Throwable $exception) {
            return false;
        }
    }

    public function notifyAssignment(array $task, string $recipientType, string $recipientId): bool
    {
        $this->loadLanguage();
        return $this->publish($task, $recipientType, $recipientId, 'assignment', Text::sprintf('COM_XDECAROTASKS_NOTIFICATION_ASSIGNED', (string) $task['title']));
    }

    public function notifyDue(array $task, string $recipientType, string $recipientId, bool $overdue): bool
    {
        $this->loadLanguage();
        $phase = $overdue ? 'overdue' : 'due';
        $message = $overdue
            ? Text::sprintf('COM_XDECAROTASKS_NOTIFICATION_OVERDUE', (string) $task['title'])
            : Text::sprintf('COM_XDECAROTASKS_NOTIFICATION_DUE', (string) $task['title']);
        return $this->publish($task, $recipientType, $recipientId, $phase, $message);
    }

    private function publish(array $task, string $recipientType, string $recipientId, string $phase, string $message): bool
    {
        try {
            $component = Factory::getApplication()->bootComponent('com_xdecaronotifications');
            if (!is_object($component) || !method_exists($component, 'getNotificationService')) { return false; }
            $service = $component->getNotificationService();
            if (!is_object($service) || !method_exists($service, 'create')) { return false; }
            $priority = (string) ($task['priority'] ?? 'normal');
            if ($priority === 'urgent') { $priority = 'critical'; }
            if (!in_array($priority, ['low','normal','high','critical'], true)) { $priority = 'normal'; }
            $dueKey = isset($task['due_at']) ? (string) $task['due_at'] : '';
            $service->create([
                'recipient_type' => $recipientType,
                'recipient_id' => $recipientId,
                'category' => 'tasks',
                'priority' => $priority,
                'title' => Text::_('COM_XDECAROTASKS_NOTIFICATION_TITLE'),
                'message' => $message,
                'source_component' => 'com_xdecarotasks',
                'source_entity' => 'task',
                'source_id' => (string) $task['id'],
                'external_key' => 'task-' . $phase . '-' . (string) $task['id'] . '-' . sha1($dueKey . ':' . $recipientType . ':' . $recipientId),
                'payload' => ['task_id' => (int) $task['id'], 'phase' => $phase],
            ]);
            return true;
        } catch (Throwable $exception) {
            return false;
        }
    }

    private function loadLanguage(): void
    {
        try { Factory::getApplication()->getLanguage()->load('com_xdecarotasks', JPATH_ADMINISTRATOR . '/components/com_xdecarotasks', null, true); } catch (Throwable $exception) { }
    }
}
