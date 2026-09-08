# Tasks by xdecaro

Tasks manages actionable work across the xdecaro Joomla ecosystem.

## Technical identity

- Component: `com_xdecarotasks`
- PHP namespace: `xdecaro\Component\Tasks`
- Reserved package identity: `pkg_xdecarotasks`
- Reserved database namespace: `#__xdecarotasks_*`

The package and database identifiers are reserved for future implementation; they must not be treated as shipped until their manifests/schema actually exist.

Tasks owns tasks, assignees, status, priority, due dates, checklists, comments and completion history. Source records remain owned by their original components and are referenced through stable Core integration references rather than cross-component database foreign keys.

Initial Core integration targets:

- `xdecaro\Core\Integration\EntityReference`
- `xdecaro\Core\Integration\Capability`
- `xdecaro\Core\Integration\IntegrationEvent`
- shared Core UI assets when available

Initial capabilities:

- `tasks.create`
- `tasks.assign`
- `tasks.complete`
- `tasks.query`

Target Joomla 4, 5 and 6 only where runtime compatibility is actually verified.
