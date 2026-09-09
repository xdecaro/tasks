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
        return $this->publish($task, $recipientType, $recipientId, 'assignment', Text::sprintf('COM_XDECAROTASKS_NOTIFICATION_ASSIGNED', (string) $task['title']));
    }

    public function notifyDue(array $task, string $recipientType, string $recipientId, bool $overdue): bool
    {
        $phase = $overdue ? 'overdue' : 'due';
        $body = $overdue
            ? Text::sprintf('COM_XDECAROTASKS_NOTIFICATION_OVERDUE', (string) $task['title'])
            : Text::sprintf('COM_XDECAROTASKS_NOTIFICATION_DUE', (string) $task['title']);
        return $this->publish($task, $recipientType, $recipientId, $phase, $body);
    }

    private function publish(array $task, string $recipientType, string $recipientId, string $phase, string $body): bool
    {
        try {
            $component = Factory::getApplication()->bootComponent('com_xdecaronotifications');
            if (!is_object($component) || !method_exists($component, 'getNotificationService')) { return false; }
            $service = $component->getNotificationService();
            if (!is_object($service) || !method_exists($service, 'create')) { return false; }
            $dueKey = isset($task['due_at']) ? (string) $task['due_at'] : '';
            $service->create([
                'recipient_type' => $recipientType,
                'recipient_id' => $recipientId,
                'category' => 'tasks',
                'priority' => ($task['priority'] ?? 'normal') === 'urgent' ? 'urgent' : (($task['priority'] ?? 'normal') === 'high' ? 'high' : 'normal'),
                'title' => Text::_('COM_XDECAROTASKS_NOTIFICATION_TITLE'),
                'body' => $body,
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
}
