-- ============================================================
-- Wallet Feature Migration
-- Run this SQL against your database to set up the wallet system
-- ============================================================

-- 1. Create wallet_transactions table
-- This replaces settlement_history + cash_collection tracking
-- with a unified transaction log for the provider wallet
CREATE TABLE IF NOT EXISTS `wallet_transactions` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `provider_id` int(11) NOT NULL,
  `order_id` int(11) UNSIGNED DEFAULT NULL COMMENT 'linked order for commission deductions/refunds',
  `type` enum('topup','commission_deduction','commission_refund','withdrawal','admin_credit','admin_debit') NOT NULL,
  `amount` double NOT NULL DEFAULT 0,
  `balance_before` double NOT NULL DEFAULT 0,
  `balance_after` double NOT NULL DEFAULT 0,
  `commission_percentage` double DEFAULT NULL COMMENT 'commission % at time of deduction',
  `description` varchar(512) DEFAULT NULL,
  `payment_method` varchar(64) DEFAULT NULL COMMENT 'for top-ups: razorpay, stripe, paypal, etc.',
  `payment_status` varchar(32) DEFAULT 'success' COMMENT 'pending, success, failed',
  `txn_id` varchar(256) DEFAULT NULL COMMENT 'external payment gateway transaction ID',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_provider_id` (`provider_id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_type` (`type`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. Add minimum_wallet_balance to settings table
-- This is the minimum balance a provider must have to accept bookings
INSERT INTO `settings` (`variable`, `value`) 
VALUES ('minimum_wallet_balance', '0')
ON DUPLICATE KEY UPDATE `value` = `value`;

-- 3. Ensure categories.admin_commission has a sensible default
-- The column already exists but is hardcoded to 0 in code.
-- No schema change needed, just ensure it's usable.

-- 4. Add commission_deducted column to orders table to track if commission was already taken
ALTER TABLE `orders` ADD COLUMN IF NOT EXISTS `commission_deducted` double NOT NULL DEFAULT 0 COMMENT 'commission amount deducted from provider wallet on acceptance';
ALTER TABLE `orders` ADD COLUMN IF NOT EXISTS `commission_percentage_applied` double NOT NULL DEFAULT 0 COMMENT 'commission percentage applied at time of acceptance';
