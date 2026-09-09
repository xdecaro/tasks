CREATE TABLE IF NOT EXISTS `#__xdecarotasks_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text NULL,
  `status` varchar(32) NOT NULL DEFAULT 'open',
  `priority` varchar(16) NOT NULL DEFAULT 'normal',
  `due_at` datetime NULL,
  `completed_at` datetime NULL,
  `source_component` varchar(64) NULL,
  `source_entity` varchar(64) NULL,
  `source_id` varchar(128) NULL,
  `external_key` varchar(191) NULL,
  `created_by` int unsigned NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  `updated_by` int unsigned NOT NULL DEFAULT 0,
  `updated_at` datetime NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_source_external` (`source_component`,`external_key`),
  KEY `idx_status` (`status`),
  KEY `idx_priority` (`priority`),
  KEY `idx_due_at` (`due_at`),
  KEY `idx_source` (`source_component`,`source_entity`,`source_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__xdecarotasks_assignees` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `task_id` int unsigned NOT NULL,
  `recipient_type` varchar(32) NOT NULL,
  `recipient_id` varchar(128) NOT NULL,
  `assigned_by` int unsigned NOT NULL DEFAULT 0,
  `assigned_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_task_recipient` (`task_id`,`recipient_type`,`recipient_id`),
  KEY `idx_recipient` (`recipient_type`,`recipient_id`),
  CONSTRAINT `fk_xdecarotasks_assignee_task` FOREIGN KEY (`task_id`) REFERENCES `#__xdecarotasks_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__xdecarotasks_checklist` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `task_id` int unsigned NOT NULL,
  `label` varchar(500) NOT NULL,
  `is_done` tinyint(1) NOT NULL DEFAULT 0,
  `ordering` int NOT NULL DEFAULT 0,
  `done_by` int unsigned NOT NULL DEFAULT 0,
  `done_at` datetime NULL,
  PRIMARY KEY (`id`),
  KEY `idx_task_ordering` (`task_id`,`ordering`),
  CONSTRAINT `fk_xdecarotasks_checklist_task` FOREIGN KEY (`task_id`) REFERENCES `#__xdecarotasks_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__xdecarotasks_comments` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `task_id` int unsigned NOT NULL,
  `author_user_id` int unsigned NOT NULL DEFAULT 0,
  `body` text NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_task_created` (`task_id`,`created_at`),
  CONSTRAINT `fk_xdecarotasks_comment_task` FOREIGN KEY (`task_id`) REFERENCES `#__xdecarotasks_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__xdecarotasks_history` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `task_id` int unsigned NOT NULL,
  `action` varchar(64) NOT NULL,
  `actor_user_id` int unsigned NOT NULL DEFAULT 0,
  `payload` text NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_task_created` (`task_id`,`created_at`),
  CONSTRAINT `fk_xdecarotasks_history_task` FOREIGN KEY (`task_id`) REFERENCES `#__xdecarotasks_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
