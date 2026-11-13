-- Preserve completed transfer claims across upgrades and repeated installations.
CREATE TABLE IF NOT EXISTS `#__facileforms_transfer_operations` (
  `operation` binary(32) NOT NULL,
  PRIMARY KEY (`operation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
