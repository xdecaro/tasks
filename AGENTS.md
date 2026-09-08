# Tasks — Repository Guidelines

## Scope

Tasks by xdecaro manages actionable work across the xdecaro Joomla ecosystem. It owns tasks, assignees, status, priority, due dates, checklists, comments and completion history.

Source-domain records remain owned by their original components. Tasks must not duplicate source business logic or use cross-component database foreign keys.

## Core integration

Use Xdecaro Core only through documented public APIs. Prefer `EntityReference`, `Capability`, `IntegrationEvent` and shared UI assets when available. Core integration must remain optional and degrade safely when Core is missing or too old.

Initial public capabilities are `tasks.create`, `tasks.assign`, `tasks.complete` and `tasks.query`.

A task may link to a source entity through `EntityReference`. The source component still owns ACL and source-state validation.

## Joomla

Target Joomla 4, 5 and 6 where technically possible. Use namespaces, MVC, service providers, DI, ACL, CSRF protection, filtered input, escaped output, Language API and Web Asset Manager. Use `#__` for future tables and preserve data on updates.

## UI

Use Core shared UI assets when available. Keep responsive, accessible, light/dark compatible administrator screens with Joomla toolbar conventions.

## Working rule

When the user says `procedi`, execute directly after inspecting current code and dependencies. Preserve working behavior and avoid unnecessary refactors.
