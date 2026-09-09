<?php
namespace xdecaro\Component\Tasks\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

final class DueService
{
    private $db;
    private $notifications;

    public function __construct(DatabaseInterface $db, NotificationIntegrationService $notifications)
    {
        $this->db = $db;
        $this->notifications = $notifications;
    }

    public function process(int $reminderMinutes = 1440, int $limit = 200): array
    {
        $reminderMinutes = max(15, min(10080, $reminderMinutes));
        $limit = max(1, min(1000, $limit));
        $now = gmdate('Y-m-d H:i:s');
        $until = gmdate('Y-m-d H:i:s', time() + ($reminderMinutes * 60));
        $query = $this->db->getQuery(true)->select('t.*')->from($this->db->quoteName('#__xdecarotasks_items', 't'))->where($this->db->quoteName('t.due_at') . ' IS NOT NULL')->where($this->db->quoteName('t.due_at') . ' <= :until')->where($this->db->quoteName('t.status') . " NOT IN ('completed','cancelled')")->order($this->db->quoteName('t.due_at') . ' ASC')->bind(':until', $until);
        $tasks = (array) $this->db->setQuery($query, 0, $limit)->loadAssocList();
        $sent = 0; $skipped = 0;
        foreach ($tasks as $task) {
            $taskId = (int) $task['id'];
            $q = $this->db->getQuery(true)->select(['recipient_type','recipient_id'])->from($this->db->quoteName('#__xdecarotasks_assignees'))->where($this->db->quoteName('task_id') . ' = :task')->bind(':task', $taskId, ParameterType::INTEGER);
            $assignees = (array) $this->db->setQuery($q)->loadAssocList();
            $overdue = (string) $task['due_at'] < $now;
            foreach ($assignees as $assignee) {
                if ($this->notifications->notifyDue($task, (string) $assignee['recipient_type'], (string) $assignee['recipient_id'], $overdue)) { $sent++; } else { $skipped++; }
            }
        }
        return ['tasks' => count($tasks), 'sent' => $sent, 'skipped' => $skipped];
    }
}
