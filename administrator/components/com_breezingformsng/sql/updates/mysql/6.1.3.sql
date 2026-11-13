-- Preserve completed transfer claims across upgrades and repeated installations.
-- The created column of a table that already exists is added idempotently by script.php.
CREATE TABLE IF NOT EXISTS `#__facileforms_transfer_operations` (
  `operation` binary(32) NOT NULL,
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`operation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
