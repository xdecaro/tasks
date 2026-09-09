# Tasks by xdecaro

Tasks is the actionable-work component for the xdecaro Joomla ecosystem.

## 1.0 scope

- tasks with status, priority and due date;
- typed assignees (`user`, `person`, `organization`, `role`);
- checklist, comments and task history;
- stable source references without cross-component foreign keys;
- idempotent creation through `source_component` + `external_key`;
- public services for `tasks.create`, `tasks.assign`, `tasks.complete`, `tasks.query`;
- optional Core 1.4 `CapabilityRegistry` and `EntityReference` integration;
- optional Notifications alerts for assignment and due dates;
- Joomla Scheduled Tasks routine for due/overdue reminders.

Tasks means **what must be done**. Notifications means **what a user must know**. Tasks does not implement official communications, project management, accounting, document storage or product-specific workflows.

Target: Joomla 4, 5 and 6 where tested; PHP 7.4+.
