# Tasks by xdecaro

Tasks manages actionable work across the xdecaro Joomla ecosystem.

It owns tasks, assignees, status, priority, due dates, checklists, comments and completion history. Source records remain owned by their original components and are referenced through stable Core integration references rather than cross-component database foreign keys.

Initial Core integration targets:

- `Xdecaro\Core\Integration\EntityReference`
- `Xdecaro\Core\Integration\Capability`
- `Xdecaro\Core\Integration\IntegrationEvent`
- shared Core UI assets when available

Initial capabilities:

- `tasks.create`
- `tasks.assign`
- `tasks.complete`
- `tasks.query`

Target Joomla 4, 5 and 6 where technically possible.
