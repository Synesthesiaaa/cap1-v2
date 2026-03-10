-- Migration: Add tbl_notification table
-- Date: 2026-03-10
-- Purpose: Persistent in-app notifications for all user types (user, technician, admin)

CREATE TABLE IF NOT EXISTS `tbl_notification` (
  `notification_id` int(11) NOT NULL AUTO_INCREMENT,
  `recipient_id`    int(11) NOT NULL COMMENT 'user_id from tbl_user OR technician_id from tbl_technician',
  `recipient_type`  enum('user','technician') NOT NULL COMMENT 'Which table recipient_id belongs to',
  `type`            varchar(50) NOT NULL DEFAULT 'info' COMMENT 'reply, status_change, assignment, alert, info',
  `title`           varchar(255) NOT NULL,
  `message`         text NOT NULL,
  `is_read`         tinyint(1) NOT NULL DEFAULT 0,
  `link`            varchar(500) DEFAULT NULL COMMENT 'Relative URL to the related resource',
  `created_at`      timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`notification_id`),
  KEY `idx_recipient`   (`recipient_id`, `recipient_type`),
  KEY `idx_is_read`     (`is_read`),
  KEY `idx_created_at`  (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
