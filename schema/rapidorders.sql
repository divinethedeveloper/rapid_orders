-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: May 08, 2026 at 03:07 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `rapidorders`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(80) NOT NULL,
  `slug` varchar(80) NOT NULL,
  `color_class` varchar(60) DEFAULT 'bg-gray-100 text-gray-700',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `color_class`, `created_at`) VALUES
(1, 'Electronics', 'electronics', 'bg-[#f0f2f5] text-[#1f2a3e]', '2026-05-04 01:28:57'),
(2, 'Groceries', 'groceries', 'bg-[#edf2f7] text-[#1f2a3e]', '2026-05-04 01:28:57'),
(3, 'Fashion', 'fashion', 'bg-[#f1f0f4] text-[#1f2a3e]', '2026-05-04 01:28:57'),
(4, 'Food', 'food', 'bg-[#f2efe9] text-[#1f2a3e]', '2026-05-04 01:28:57'),
(5, 'Home & Living', 'home-living', 'bg-[#eef2ef] text-[#1f2a3e]', '2026-05-04 01:28:57'),
(6, 'Audio', 'audio', 'bg-[#f0f2f5] text-[#1f2a3e]', '2026-05-04 01:28:57'),
(7, 'Laptops', 'laptops', 'bg-[#f0f2f5] text-[#1f2a3e]', '2026-05-04 01:28:57'),
(8, 'Phones', 'phones', 'bg-[#f0f2f5] text-[#1f2a3e]', '2026-05-04 01:28:57'),
(9, 'Accessories', 'accessories', 'bg-[#f1f0f4] text-[#1f2a3e]', '2026-05-04 01:28:57'),
(10, 'Tablets', 'tablets', 'bg-[#f0f2f5] text-[#1f2a3e]', '2026-05-04 01:28:57'),
(11, 'TVs', 'tvs', 'bg-[#f0f2f5] text-[#1f2a3e]', '2026-05-04 01:28:57');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_ref` varchar(20) NOT NULL,
  `vendor_id` int(10) UNSIGNED NOT NULL,
  `customer_name` varchar(120) DEFAULT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `status` enum('pending','confirmed','processing','shipped','delivered','cancelled') DEFAULT 'pending',
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `channel` enum('whatsapp','web') DEFAULT 'whatsapp',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED DEFAULT NULL,
  `product_name` varchar(180) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `qty` int(11) NOT NULL DEFAULT 1,
  `subtotal` decimal(10,2) GENERATED ALWAYS AS (`price` * `qty`) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(10) UNSIGNED NOT NULL,
  `vendor_id` int(10) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED DEFAULT NULL,
  `name` varchar(180) NOT NULL,
  `slug` varchar(180) NOT NULL,
  `description` text DEFAULT NULL,
  `image1` varchar(500) DEFAULT NULL,
  `image2` varchar(500) DEFAULT NULL,
  `image3` varchar(500) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `in_stock` tinyint(1) DEFAULT 1,
  `stock_qty` int(11) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `vendor_id`, `category_id`, `name`, `slug`, `description`, `image1`, `image2`, `image3`, `price`, `in_stock`, `stock_qty`, `active`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 1, 7, 'MacBook Air M3', 'macbook-air-m2', '8GB RAM, 256GB SSD, Space Grey. Apple warranty included.', NULL, NULL, NULL, 8500.00, 1, NULL, 1, 1, '2026-05-04 01:28:57', '2026-05-04 15:13:08'),
(2, 1, 8, 'iPhone 15 Pro', 'iphone-15-pro', '256GB, Natural Titanium. Brand new sealed.', NULL, NULL, NULL, 12000.00, 1, NULL, 1, 2, '2026-05-04 01:28:57', '2026-05-04 01:28:57'),
(3, 1, 11, 'Samsung 65\" 4K TV', 'samsung-65-4k-tv', 'Crystal UHD, HDR10+, Smart TV with built-in apps.', NULL, NULL, NULL, 6800.00, 0, NULL, 1, 3, '2026-05-04 01:28:57', '2026-05-04 01:28:57'),
(4, 1, 9, 'USB-C Hub 7-in-1', 'usb-c-hub-7in1', 'HDMI 4K, 3x USB-A, SD card, 100W PD charging.', NULL, NULL, NULL, 180.00, 1, NULL, 1, 4, '2026-05-04 01:28:57', '2026-05-04 01:28:57'),
(5, 1, 6, 'AirPods Pro 2', 'airpods-pro-2', 'Active noise cancellation, MagSafe charging case.', NULL, NULL, NULL, 1800.00, 1, NULL, 1, 5, '2026-05-04 01:28:57', '2026-05-04 01:28:57'),
(6, 1, 9, 'Logitech MX Master 3', 'logitech-mx-master-3', 'Ergonomic wireless mouse, multi-device, fast scroll.', NULL, NULL, NULL, 650.00, 1, NULL, 1, 6, '2026-05-04 01:28:57', '2026-05-04 01:28:57'),
(7, 1, 10, 'iPad Air M1', 'ipad-air-m1', '10.9-inch, 64GB WiFi, includes Apple Pencil support.', NULL, NULL, NULL, 5200.00, 1, NULL, 1, 7, '2026-05-04 01:28:57', '2026-05-04 01:28:57'),
(8, 1, 7, 'Dell XPS 13', 'dell-xps-13', 'Intel Core i7, 16GB RAM, 512GB SSD, OLED display.', NULL, NULL, NULL, 9200.00, 1, NULL, 1, 8, '2026-05-04 01:28:57', '2026-05-04 15:13:24'),
(9, 1, 1, 'Test', 'est', 'Test', NULL, NULL, NULL, 1000.00, 1, NULL, 1, 0, '2026-05-04 15:14:28', '2026-05-08 01:02:38'),
(10, 1, 6, 'test 2', 'test-2', 'test 2', NULL, NULL, NULL, 100.00, 1, NULL, 1, 0, '2026-05-08 01:06:28', '2026-05-08 01:06:28');

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_primary` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `sort_order`, `is_primary`, `created_at`) VALUES
(4, 2, 'https://images.unsplash.com/photo-1695048133142-1a20484d2569?w=600', 0, 1, '2026-05-04 01:28:57'),
(5, 2, 'https://images.unsplash.com/photo-1592286927505-dced6aad6a21?w=600', 1, 0, '2026-05-04 01:28:57'),
(6, 4, 'https://images.unsplash.com/photo-1629182743271-06f1c0d4b7c9?w=600', 0, 1, '2026-05-04 01:28:57'),
(7, 5, 'https://images.unsplash.com/photo-1606220945770-b5b6c2c55bf1?w=600', 0, 1, '2026-05-04 01:28:57'),
(8, 6, 'https://images.unsplash.com/photo-1615663245857-ac93bb7c39e7?w=600', 0, 1, '2026-05-04 01:28:57'),
(9, 7, 'https://images.unsplash.com/photo-1544244015-0df4b3ffc6b0?w=600', 0, 1, '2026-05-04 01:28:57'),
(10, 1, 'https://images.unsplash.com/photo-1611186871348-b1ce696e52c9?w=600', 0, 1, '2026-05-04 15:13:08'),
(11, 1, 'https://images.unsplash.com/photo-1531297484001-80022131f5a1?w=600', 1, 0, '2026-05-04 15:13:08'),
(12, 1, 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=600', 2, 0, '2026-05-04 15:13:08');

-- --------------------------------------------------------

--
-- Table structure for table `vendors`
--

CREATE TABLE `vendors` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `slug` varchar(120) NOT NULL,
  `description` text DEFAULT NULL,
  `category_id` int(10) UNSIGNED DEFAULT NULL,
  `whatsapp` varchar(20) NOT NULL,
  `cover_image` varchar(255) DEFAULT NULL,
  `is_open` tinyint(1) DEFAULT 1,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `vendors`
--

INSERT INTO `vendors` (`id`, `name`, `slug`, `description`, `category_id`, `whatsapp`, `cover_image`, `is_open`, `active`, `created_at`, `updated_at`) VALUES
(1, 'TechHub Gh', 'techhub-gh', 'Premium laptops, certified phones & accessories. Fast delivery in Kumasi.', 1, '233244000001', '/logo/vendor_1.jpg', 1, 1, '2026-05-04 01:28:57', '2026-05-05 14:19:23'),
(2, 'FreshMart', 'freshmart', 'Organic produce, pantry staples, dairy & artisanal bread. Daily fresh.', 2, '233244000002', NULL, 1, 1, '2026-05-04 01:28:57', '2026-05-04 01:28:57'),
(3, 'StyleHub', 'stylehub', 'Minimalist clothing, tailored pieces and accessories for modern wardrobes.', 3, '233244000003', NULL, 1, 1, '2026-05-04 01:28:57', '2026-05-04 01:28:57'),
(4, 'GadgetZone', 'gadgetzone', 'Gadgets, chargers, audio & smart home — curated essentials.', 1, '233244000004', NULL, 1, 1, '2026-05-04 01:28:57', '2026-05-04 01:28:57'),
(5, 'HomePlus', 'homeplus', 'Contemporary furniture, ambient decor and functional homeware.', 5, '233244000005', NULL, 0, 1, '2026-05-04 01:28:57', '2026-05-04 01:28:57'),
(6, 'SnackStop', 'snackstop', 'Local snacks, gourmet bites & refreshing drinks from the city\'s best spots.', 4, '233244000006', NULL, 1, 1, '2026-05-04 01:28:57', '2026-05-04 01:28:57'),
(7, 'Urban Grind', 'urban-grind', 'Specialty coffee, fresh pastries and breakfast bowls.', 4, '233244000007', NULL, 1, 1, '2026-05-04 01:28:57', '2026-05-04 01:28:57'),
(8, 'Vintage Threads', 'vintage-threads', 'Curated vintage & second-hand premium fashion.', 3, '233244000008', NULL, 1, 1, '2026-05-04 01:28:57', '2026-05-04 01:28:57');

-- --------------------------------------------------------

--
-- Table structure for table `vendor_stats`
--

CREATE TABLE `vendor_stats` (
  `id` int(10) UNSIGNED NOT NULL,
  `vendor_id` int(10) UNSIGNED NOT NULL,
  `page_views` int(10) UNSIGNED DEFAULT 0,
  `orders_count` int(10) UNSIGNED DEFAULT 0,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vendor_stats`
--

INSERT INTO `vendor_stats` (`id`, `vendor_id`, `page_views`, `orders_count`, `last_updated`) VALUES
(1, 1, 0, 0, '2026-05-05 13:57:51'),
(2, 4, 0, 0, '2026-05-05 13:57:51'),
(3, 2, 0, 0, '2026-05-05 13:57:51'),
(4, 3, 0, 0, '2026-05-05 13:57:51'),
(5, 8, 0, 0, '2026-05-05 13:57:51'),
(6, 6, 0, 0, '2026-05-05 13:57:51'),
(7, 7, 0, 0, '2026-05-05 13:57:51'),
(8, 5, 0, 0, '2026-05-05 13:57:51');

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_products`
-- (See below for the actual view)
--
CREATE TABLE `v_products` (
`id` int(10) unsigned
,`vendor_id` int(10) unsigned
,`name` varchar(180)
,`slug` varchar(180)
,`description` text
,`price` decimal(10,2)
,`in_stock` tinyint(1)
,`stock_qty` int(11)
,`active` tinyint(1)
,`sort_order` int(11)
,`category` varchar(80)
,`category_slug` varchar(80)
,`primary_image` varchar(255)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_vendors`
-- (See below for the actual view)
--
CREATE TABLE `v_vendors` (
`id` int(10) unsigned
,`name` varchar(120)
,`slug` varchar(120)
,`description` text
,`whatsapp` varchar(20)
,`cover_image` varchar(255)
,`is_open` tinyint(1)
,`active` tinyint(1)
,`category` varchar(80)
,`category_slug` varchar(80)
,`color_class` varchar(60)
,`product_count` bigint(21)
);

-- --------------------------------------------------------

--
-- Structure for view `v_products`
--
DROP TABLE IF EXISTS `v_products`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_products`  AS SELECT `p`.`id` AS `id`, `p`.`vendor_id` AS `vendor_id`, `p`.`name` AS `name`, `p`.`slug` AS `slug`, `p`.`description` AS `description`, `p`.`price` AS `price`, `p`.`in_stock` AS `in_stock`, `p`.`stock_qty` AS `stock_qty`, `p`.`active` AS `active`, `p`.`sort_order` AS `sort_order`, `c`.`name` AS `category`, `c`.`slug` AS `category_slug`, (select `pi`.`image_url` from `product_images` `pi` where `pi`.`product_id` = `p`.`id` and `pi`.`is_primary` = 1 limit 1) AS `primary_image` FROM (`products` `p` left join `categories` `c` on(`c`.`id` = `p`.`category_id`)) WHERE `p`.`active` = 1 ;

-- --------------------------------------------------------

--
-- Structure for view `v_vendors`
--
DROP TABLE IF EXISTS `v_vendors`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_vendors`  AS SELECT `v`.`id` AS `id`, `v`.`name` AS `name`, `v`.`slug` AS `slug`, `v`.`description` AS `description`, `v`.`whatsapp` AS `whatsapp`, `v`.`cover_image` AS `cover_image`, `v`.`is_open` AS `is_open`, `v`.`active` AS `active`, `c`.`name` AS `category`, `c`.`slug` AS `category_slug`, `c`.`color_class` AS `color_class`, (select count(0) from `products` `p` where `p`.`vendor_id` = `v`.`id` and `p`.`active` = 1) AS `product_count` FROM (`vendors` `v` left join `categories` `c` on(`c`.`id` = `v`.`category_id`)) WHERE `v`.`active` = 1 ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_ref` (`order_ref`),
  ADD KEY `idx_orders_vendor` (`vendor_id`),
  ADD KEY `idx_orders_status` (`status`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_item_order` (`order_id`),
  ADD KEY `fk_item_product` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_product_vendor_slug` (`vendor_id`,`slug`),
  ADD KEY `idx_products_vendor` (`vendor_id`),
  ADD KEY `idx_products_category` (`category_id`),
  ADD KEY `idx_products_active` (`active`,`in_stock`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_images_product` (`product_id`,`sort_order`);

--
-- Indexes for table `vendors`
--
ALTER TABLE `vendors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `fk_vendor_category` (`category_id`);

--
-- Indexes for table `vendor_stats`
--
ALTER TABLE `vendor_stats`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_vendor_id` (`vendor_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `vendors`
--
ALTER TABLE `vendors`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `vendor_stats`
--
ALTER TABLE `vendor_stats`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_order_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_item_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_item_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_product_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_product_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `fk_image_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `vendors`
--
ALTER TABLE `vendors`
  ADD CONSTRAINT `fk_vendor_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `vendor_stats`
--
ALTER TABLE `vendor_stats`
  ADD CONSTRAINT `vendor_stats_ibfk_1` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
