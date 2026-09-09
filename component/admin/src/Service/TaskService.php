<?php
namespace xdecaro\Component\Tasks\Administrator\Service;

defined('_JEXEC') or die;

use InvalidArgumentException;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use RuntimeException;

final class TaskService
{
    private const STATUSES = ['open','in_progress','blocked','completed','cancelled'];
    private const PRIORITIES = ['low','normal','high','urgent'];
    private const RECIPIENT_TYPES = ['user','person','organization','role'];

    private $db;
    private $notifications;

    public function __construct(DatabaseInterface $db, NotificationIntegrationService $notifications)
    {
        $this->db = $db;
        $this->notifications = $notifications;
    }

    public function create(array $data, int $actorUserId = 0): int
    {
        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '') { throw new InvalidArgumentException('Task title is required.'); }
        $sourceComponent = trim((string) ($data['source_component'] ?? ''));
        $externalKey = trim((string) ($data['external_key'] ?? ''));
        if ($sourceComponent !== '' && $externalKey !== '') {
            $existing = $this->findByExternalKey($sourceComponent, $externalKey);
            if ($existing > 0) { return $existing; }
        }
        $now = $this->now();
        $sourceEntity = trim((string) ($data['source_entity'] ?? ''));
        $sourceId = trim((string) ($data['source_id'] ?? ''));
        $row = (object) [
            'title' => mb_substr($title, 0, 255),
            'description' => (string) ($data['description'] ?? ''),
            'status' => $this->status((string) ($data['status'] ?? 'open')),
            'priority' => $this->priority((string) ($data['priority'] ?? 'normal')),
            'due_at' => $this->nullableDate($data['due_at'] ?? null),
            'completed_at' => null,
            'source_component' => $sourceComponent !== '' ? mb_substr($sourceComponent, 0, 64) : null,
            'source_entity' => $sourceEntity !== '' ? mb_substr($sourceEntity, 0, 64) : null,
            'source_id' => $sourceId !== '' ? mb_substr($sourceId, 0, 128) : null,
            'external_key' => $externalKey !== '' ? mb_substr($externalKey, 0, 191) : null,
            'created_by' => max(0, $actorUserId),
            'created_at' => $now,
            'updated_by' => 0,
            'updated_at' => null,
        ];
        $this->db->insertObject('#__xdecarotasks_items', $row);
        $id = (int) $this->db->insertid();
        if ($id <= 0) { throw new RuntimeException('Task was not created.'); }
        $this->history($id, 'created', $actorUserId, ['status' => $row->status, 'priority' => $row->priority]);
        return $id;
    }

    public function update(int $id, array $data, int $actorUserId = 0): void
    {
        $task = $this->getTask($id);
        if ($task === null) { throw new RuntimeException('Task not found.'); }
        $title = trim((string) ($data['title'] ?? $task['title']));
        if ($title === '') { throw new InvalidArgumentException('Task title is required.'); }
        $status = $this->status((string) ($data['status'] ?? $task['status']));
        $priority = $this->priority((string) ($data['priority'] ?? $task['priority']));
        $row = (object) [
            'id' => $id,
            'title' => mb_substr($title, 0, 255),
            'description' => (string) ($data['description'] ?? $task['description']),
            'status' => $status,
            'priority' => $priority,
            'due_at' => array_key_exists('due_at', $data) ? $this->nullableDate($data['due_at']) : $task['due_at'],
            'completed_at' => $status === 'completed' ? ($task['completed_at'] ?: $this->now()) : null,
            'updated_by' => max(0, $actorUserId),
            'updated_at' => $this->now(),
        ];
        $this->db->updateObject('#__xdecarotasks_items', $row, 'id');
        $this->history($id, 'updated', $actorUserId, ['status' => $status, 'priority' => $priority]);
    }

    public function assign(int $taskId, string $recipientType, string $recipientId, int $actorUserId = 0, bool $notify = true): void
    {
        $task = $this->getTask($taskId);
        if ($task === null) { throw new RuntimeException('Task not found.'); }
        $recipientType = trim($recipientType);
        $recipientId = trim($recipientId);
        if (!in_array($recipientType, self::RECIPIENT_TYPES, true) || $recipientId === '') { throw new InvalidArgumentException('Invalid task assignee.'); }
        $query = $this->db->getQuery(true)->select('COUNT(*)')->from($this->db->quoteName('#__xdecarotasks_assignees'))->where($this->db->quoteName('task_id') . ' = :task')->where($this->db->quoteName('recipient_type') . ' = :type')->where($this->db->quoteName('recipient_id') . ' = :recipient');
        $query->bind(':task', $taskId, ParameterType::INTEGER)->bind(':type', $recipientType)->bind(':recipient', $recipientId);
        if ((int) $this->db->setQuery($query)->loadResult() === 0) {
            $row = (object) ['task_id' => $taskId, 'recipient_type' => $recipientType, 'recipient_id' => mb_substr($recipientId, 0, 128), 'assigned_by' => max(0, $actorUserId), 'assigned_at' => $this->now()];
            $this->db->insertObject('#__xdecarotasks_assignees', $row);
            $this->history($taskId, 'assigned', $actorUserId, ['recipient_type' => $recipientType, 'recipient_id' => $recipientId]);
            if ($notify) { $this->notifications->notifyAssignment($task, $recipientType, $recipientId); }
        }
    }

    public function complete(int $taskId, int $actorUserId = 0): void
    {
        $task = $this->getTask($taskId);
        if ($task === null) { throw new RuntimeException('Task not found.'); }
        if ($task['status'] === 'completed') { return; }
        $now = $this->now();
        $row = (object) ['id' => $taskId, 'status' => 'completed', 'completed_at' => $now, 'updated_by' => max(0, $actorUserId), 'updated_at' => $now];
        $this->db->updateObject('#__xdecarotasks_items', $row, 'id');
        $this->history($taskId, 'completed', $actorUserId, []);
    }

    public function cancel(int $taskId, int $actorUserId = 0): void
    {
        $task = $this->getTask($taskId);
        if ($task === null || $task['status'] === 'cancelled') { return; }
        $row = (object) ['id' => $taskId, 'status' => 'cancelled', 'completed_at' => null, 'updated_by' => max(0, $actorUserId), 'updated_at' => $this->now()];
        $this->db->updateObject('#__xdecarotasks_items', $row, 'id');
        $this->history($taskId, 'cancelled', $actorUserId, []);
    }

    public function addChecklistItem(int $taskId, string $label, int $actorUserId = 0): int
    {
        if ($this->getTask($taskId) === null) { throw new RuntimeException('Task not found.'); }
        $label = trim($label);
        if ($label === '') { throw new InvalidArgumentException('Checklist label is required.'); }
        $row = (object) ['task_id' => $taskId, 'label' => mb_substr($label, 0, 500), 'is_done' => 0, 'ordering' => 0, 'done_by' => 0, 'done_at' => null];
        $this->db->insertObject('#__xdecarotasks_checklist', $row);
        $id = (int) $this->db->insertid();
        $this->history($taskId, 'checklist_added', $actorUserId, ['checklist_id' => $id]);
        return $id;
    }

    public function toggleChecklistItem(int $taskId, int $itemId, bool $done, int $actorUserId = 0): void
    {
        $taskIdVar = $taskId; $itemIdVar = $itemId;
        $query = $this->db->getQuery(true)->select('COUNT(*)')->from($this->db->quoteName('#__xdecarotasks_checklist'))->where($this->db->quoteName('id') . ' = :id')->where($this->db->quoteName('task_id') . ' = :task')->bind(':id', $itemIdVar, ParameterType::INTEGER)->bind(':task', $taskIdVar, ParameterType::INTEGER);
        if ((int) $this->db->setQuery($query)->loadResult() === 0) { throw new RuntimeException('Checklist item not found.'); }
        $row = (object) ['id' => $itemId, 'is_done' => $done ? 1 : 0, 'done_by' => $done ? max(0, $actorUserId) : 0, 'done_at' => $done ? $this->now() : null];
        $this->db->updateObject('#__xdecarotasks_checklist', $row, 'id');
        $this->history($taskId, 'checklist_toggled', $actorUserId, ['checklist_id' => $itemId, 'done' => $done]);
    }

    public function addComment(int $taskId, string $body, int $actorUserId = 0): int
    {
        if ($this->getTask($taskId) === null) { throw new RuntimeException('Task not found.'); }
        $body = trim($body);
        if ($body === '') { throw new InvalidArgumentException('Comment body is required.'); }
        $row = (object) ['task_id' => $taskId, 'author_user_id' => max(0, $actorUserId), 'body' => $body, 'created_at' => $this->now()];
        $this->db->insertObject('#__xdecarotasks_comments', $row);
        $id = (int) $this->db->insertid();
        $this->history($taskId, 'comment_added', $actorUserId, ['comment_id' => $id]);
        return $id;
    }

    public function getTask(int $id): ?array
    {
        if ($id <= 0) { return null; }
        $idVar = $id;
        $query = $this->db->getQuery(true)->select('*')->from($this->db->quoteName('#__xdecarotasks_items'))->where($this->db->quoteName('id') . ' = :id')->bind(':id', $idVar, ParameterType::INTEGER);
        $task = $this->db->setQuery($query, 0, 1)->loadAssoc();
        if (!$task) { return null; }
        $task['assignees'] = $this->related('#__xdecarotasks_assignees', $id, 'assigned_at ASC');
        $task['checklist'] = $this->related('#__xdecarotasks_checklist', $id, 'ordering ASC, id ASC');
        $task['comments'] = $this->related('#__xdecarotasks_comments', $id, 'created_at ASC');
        $task['history'] = $this->related('#__xdecarotasks_history', $id, 'created_at DESC, id DESC', 100);
        return $task;
    }

    public function query(array $filters = []): array
    {
        $query = $this->db->getQuery(true)->select('t.*')->from($this->db->quoteName('#__xdecarotasks_items', 't'));
        $status = trim((string) ($filters['status'] ?? ''));
        if ($status !== '') { $status = $this->status($status); $query->where($this->db->quoteName('t.status') . ' = :status')->bind(':status', $status); }
        $priority = trim((string) ($filters['priority'] ?? ''));
        if ($priority !== '') { $priority = $this->priority($priority); $query->where($this->db->quoteName('t.priority') . ' = :priority')->bind(':priority', $priority); }
        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') { $searchLike = '%' . $this->db->escape($search, true) . '%'; $query->where('(' . $this->db->quoteName('t.title') . ' LIKE :search OR ' . $this->db->quoteName('t.description') . ' LIKE :search)')->bind(':search', $searchLike); }
        if (!empty($filters['overdue'])) { $now = $this->now(); $query->where($this->db->quoteName('t.due_at') . ' < :now')->where($this->db->quoteName('t.status') . " NOT IN ('completed','cancelled')")->bind(':now', $now); }
        $query->order($this->db->quoteName('t.due_at') . ' IS NULL ASC, ' . $this->db->quoteName('t.due_at') . ' ASC, ' . $this->db->quoteName('t.id') . ' DESC');
        $limit = max(1, min(500, (int) ($filters['limit'] ?? 100)));
        return (array) $this->db->setQuery($query, 0, $limit)->loadAssocList();
    }

    public function getDashboardStats(): array
    {
        $stats = ['open' => 0, 'in_progress' => 0, 'blocked' => 0, 'completed' => 0, 'overdue' => 0];
        $query = $this->db->getQuery(true)->select([$this->db->quoteName('status'), 'COUNT(*) AS total'])->from($this->db->quoteName('#__xdecarotasks_items'))->group($this->db->quoteName('status'));
        foreach ((array) $this->db->setQuery($query)->loadAssocList() as $row) { if (isset($stats[$row['status']])) { $stats[$row['status']] = (int) $row['total']; } }
        $now = $this->now();
        $query = $this->db->getQuery(true)->select('COUNT(*)')->from($this->db->quoteName('#__xdecarotasks_items'))->where($this->db->quoteName('due_at') . ' < :now')->where($this->db->quoteName('status') . " NOT IN ('completed','cancelled')")->bind(':now', $now);
        $stats['overdue'] = (int) $this->db->setQuery($query)->loadResult();
        return $stats;
    }

    private function related(string $table, int $taskId, string $order, int $limit = 0): array
    {
        $id = $taskId;
        $query = $this->db->getQuery(true)->select('*')->from($this->db->quoteName($table))->where($this->db->quoteName('task_id') . ' = :task')->order($order)->bind(':task', $id, ParameterType::INTEGER);
        return (array) $this->db->setQuery($query, 0, $limit > 0 ? $limit : 0)->loadAssocList();
    }

    private function findByExternalKey(string $sourceComponent, string $externalKey): int
    {
        $source = $sourceComponent; $key = $externalKey;
        $query = $this->db->getQuery(true)->select($this->db->quoteName('id'))->from($this->db->quoteName('#__xdecarotasks_items'))->where($this->db->quoteName('source_component') . ' = :source')->where($this->db->quoteName('external_key') . ' = :key')->bind(':source', $source)->bind(':key', $key);
        return (int) $this->db->setQuery($query, 0, 1)->loadResult();
    }

    private function history(int $taskId, string $action, int $actorUserId, array $payload): void
    {
        $row = (object) ['task_id' => $taskId, 'action' => mb_substr($action, 0, 64), 'actor_user_id' => max(0, $actorUserId), 'payload' => $payload ? json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null, 'created_at' => $this->now()];
        $this->db->insertObject('#__xdecarotasks_history', $row);
    }

    private function status(string $value): string { $value = trim($value); if (!in_array($value, self::STATUSES, true)) { throw new InvalidArgumentException('Invalid task status.'); } return $value; }
    private function priority(string $value): string { $value = trim($value); if (!in_array($value, self::PRIORITIES, true)) { throw new InvalidArgumentException('Invalid task priority.'); } return $value; }
    private function nullableDate($value): ?string { if ($value === null || trim((string) $value) === '') { return null; } $time = strtotime((string) $value); if ($time === false) { throw new InvalidArgumentException('Invalid due date.'); } return gmdate('Y-m-d H:i:s', $time); }
    private function now(): string { return gmdate('Y-m-d H:i:s'); }
}
