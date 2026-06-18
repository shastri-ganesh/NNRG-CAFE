CREATE TABLE IF NOT EXISTS `coupons` (
  `coupon_id` int(11) NOT NULL AUTO_INCREMENT,
  `coupon_code` varchar(50) NOT NULL,
  `discount_amount` decimal(10,2) NOT NULL,
  `min_order_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`coupon_id`),
  UNIQUE KEY `coupon_code` (`coupon_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `coupons` (`coupon_code`, `discount_amount`, `min_order_amount`, `status`) VALUES
('DIWALI150', 150.00, 300.00, 'active');
