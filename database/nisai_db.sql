-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 18, 2025 at 12:12 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `nisai_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `category`
--

CREATE TABLE `category` (
  `category_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `category`
--

INSERT INTO `category` (`category_id`, `name`, `description`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'អាវយឺត', '', 1, '2025-06-13 17:35:34', '2025-06-13 22:52:29'),
(3, 'ខោ', '', 1, '2025-06-30 19:42:57', '2025-07-01 00:42:57'),
(4, 'សម្លៀកបំពាក់យប់', '', 1, '2025-06-30 20:12:22', '2025-07-01 01:12:22'),
(5, 'សម្លៀកបំពាក់កីឡា', '', 1, '2025-06-30 20:12:35', '2025-07-01 01:12:35'),
(6, 'អាវធម្មតា', '', 1, '2025-07-01 09:04:22', '2025-07-06 23:35:50');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `mobile_phone` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `created_by` int(11) NOT NULL COMMENT 'User ID who created the customer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Timestamp when customer was created'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `customer_name`, `email`, `mobile_phone`, `address`, `created_by`, `created_at`) VALUES
(2, 'Soknov', 'soknov236@gmail.com', '0882256288', 'takoe', 0, '2025-06-10 16:19:20'),
(6, 'Noy Soknov', 'soknov6236@gmail.com', '0987654321', 'takoe', 10, '2025-07-06 16:09:51'),
(7, 'Customer1', '', '0978643323', 'Phnom​ phenh', 1, '2025-07-24 05:49:31'),
(8, 'Customer1', 'soknov6236@gmail.com', '0882256288', 'takoe', 10, '2025-08-14 10:18:10');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_history`
--

CREATE TABLE `inventory_history` (
  `history_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `action` enum('in','out','adjust','transfer') NOT NULL COMMENT 'in=stock in, out=stock out',
  `quantity` decimal(10,2) NOT NULL,
  `previous_quantity` decimal(10,2) DEFAULT NULL,
  `new_quantity` decimal(10,2) DEFAULT NULL,
  `user_name` varchar(100) NOT NULL,
  `notes` text DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL COMMENT 'ID of related transaction (sale, return, etc)',
  `reference_type` varchar(50) DEFAULT NULL COMMENT 'sale, return, purchase, adjustment, etc',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Track all inventory movements';

-- --------------------------------------------------------

--
-- Table structure for table `inventory_log`
--

CREATE TABLE `inventory_log` (
  `log_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity_change` int(11) NOT NULL COMMENT 'Positive for additions, negative for deductions',
  `previous_quantity` int(11) NOT NULL,
  `new_quantity` int(11) NOT NULL,
  `action_type` enum('restock','sale','adjustment','return','damage','other') NOT NULL DEFAULT 'restock',
  `reference_id` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL COMMENT 'User ID who made the change',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_log`
--

INSERT INTO `inventory_log` (`log_id`, `product_id`, `quantity_change`, `previous_quantity`, `new_quantity`, `action_type`, `reference_id`, `notes`, `created_by`, `created_at`) VALUES
(1, 8, 10, 0, 0, 'restock', NULL, '', NULL, '2025-06-23 14:17:50'),
(2, 8, 10, 120, 130, '', '7', 'Purchase from supplier', 1, '2025-06-24 22:09:04'),
(3, 8, 1, 121, 122, '', '8', 'Purchase from supplier', 1, '2025-06-24 22:09:10'),
(4, 22, 10, 20, 30, '', '9', 'Purchase from supplier', 1, '2025-06-24 22:10:44'),
(5, 22, 10, 30, 40, '', '10', 'Purchase from supplier', 1, '2025-06-24 22:11:10'),
(6, 22, 10, 40, 50, '', '11', 'Purchase from supplier', 1, '2025-06-24 22:13:35'),
(7, 25, 10, 7, 17, '', '12', 'Purchase from supplier', 1, '2025-07-04 21:09:00'),
(8, 25, 10, 17, 27, '', '13', 'Purchase from supplier', 1, '2025-07-04 21:09:08'),
(9, 8, 1, 120, 121, '', '14', 'Purchase from supplier', 1, '2025-07-04 21:29:46'),
(10, 8, 1, 121, 122, '', '15', 'Purchase from supplier', 1, '2025-07-04 21:29:50'),
(11, 24, 1, 21, 22, '', '16', 'Purchase from supplier', 1, '2025-07-04 21:30:44'),
(12, 24, 1, 22, 23, '', '17', 'Purchase from supplier', 1, '2025-07-04 21:30:48'),
(13, 26, 2, 10, 12, '', '18', 'Purchase from supplier', 1, '2025-07-04 21:36:06'),
(14, 28, 1, 9, 10, '', '19', 'Purchase from supplier', 1, '2025-07-04 21:37:00'),
(15, 28, 1, 10, 11, '', '20', 'Purchase from supplier', 1, '2025-07-04 21:37:02'),
(16, 23, 11, 21, 32, '', '21', 'Purchase from supplier', 1, '2025-07-04 21:38:15'),
(17, 22, 1, 0, 0, 'restock', NULL, '', NULL, '2025-07-09 00:18:35'),
(18, 29, 1, 11, 12, '', '22', 'Purchase from supplier', 1, '2025-07-09 00:19:35'),
(19, 29, 1, 12, 13, '', '23', 'Purchase from supplier', 1, '2025-07-09 00:19:44'),
(20, 29, 2, 0, 0, 'restock', NULL, '', NULL, '2025-07-12 21:02:41'),
(21, 29, 5, 3, 8, '', '24', 'Purchase from supplier', 1, '2025-08-13 22:21:04'),
(22, 29, 5, 8, 13, '', '25', 'Purchase from supplier', 1, '2025-08-13 22:21:09');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `product_code` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `supplier_name` varchar(100) NOT NULL,
  `size` varchar(20) DEFAULT NULL,
  `color` varchar(30) DEFAULT NULL,
  `gender` enum('Male','Female','Unisex') DEFAULT NULL,
  `cost_price` decimal(10,2) DEFAULT NULL,
  `sale_price` decimal(10,2) DEFAULT NULL,
  `stock_quantity` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `category_id`, `supplier_id`, `product_code`, `name`, `category_name`, `supplier_name`, `size`, `color`, `gender`, `cost_price`, `sale_price`, `stock_quantity`, `description`, `image`, `created_at`, `updated_at`) VALUES
(8, NULL, NULL, 'S001', 'អាវ', 'អាវយឺត', 'test', 'L', 'ពណ៌សរ', 'Unisex', 0.01, 1.00, 114, '', 'product_1750270633.jpg', '2025-06-15 23:59:19', '2025-08-15 22:32:23'),
(22, NULL, NULL, 'P8431', 'អាវយឺត', 'សម្លៀកបំពាក់យប់', 'Sok Nov', 'L', 'ពណ៌ខ្មៅ', 'Male', 5.00, 10.00, 38, '', '685443a7adb0c.jpg', '2025-06-20 00:06:47', '2025-08-15 23:32:17'),
(23, NULL, NULL, 'K001', 'ខោជើងវែង', 'ខោ', 'test', 'L', 'ក្រហម', 'Unisex', 1.00, 2.00, 15, '', '6862d84107711.jpg', '2025-07-01 01:32:33', '2025-08-13 20:25:07'),
(24, NULL, NULL, 'K002', 'ខោជើងវែង', 'ខោ', 'test', 'M', 'សរ', 'Male', 1.00, 2.00, 22, '', '6862d87e68912.jpg', '2025-07-01 01:33:34', '2025-07-04 21:30:48'),
(25, NULL, NULL, 'S003', 'អាវយឺត LV', 'អាវយឺត', 'test', 'L', 'ពណ៌ខ្មៅ', 'Female', 500.00, 10.00, 16, '', '686384704c0cb.jpg', '2025-07-01 13:47:12', '2025-08-14 23:17:46'),
(26, NULL, NULL, 'S005', 'អាវយឺត ', 'អាវយឺត', 'test', 'M', 'សរ', 'Unisex', 5.00, 10.00, 7, '', '686384964d20d.jpg', '2025-07-01 13:47:50', '2025-08-09 23:52:25'),
(28, NULL, NULL, 'S008', 'អាវយឺត', 'អាវយឺត', 'Sok Nov', 'L', 'ក្រហម', 'Unisex', 10.00, 10.00, 8, '', '686385499077f.jpg', '2025-07-01 13:50:49', '2025-08-14 23:09:47'),
(29, NULL, NULL, 'x123', 'test1', 'សម្លៀកបំពាក់យប់', 'Supplier 2', 'M', 'ពណ៌ខ្មៅ', 'Male', 10.00, 20.00, 8, '', '686d5283f1374.jpg', '2025-07-09 00:16:51', '2025-08-13 22:21:09'),
(31, NULL, NULL, 'P002', 'អាវយឺត', 'អាវយឺត', 'Sok Nov', 'XXL', 'ក្រហម', 'Male', 5.00, 8.00, 9, '', '689dd01117aad.jpg', '2025-08-14 19:01:21', '2025-08-15 23:29:42'),
(32, NULL, NULL, 'PROD-10000', 'អាវយឺត', 'អាវយឺត', 'Supplier 2', 'XXL', 'ពណ៌សរ', 'Unisex', 5.00, 10.00, 10, '', 'PROD-10000.jpg', '2025-08-14 19:07:58', '2025-08-14 19:07:58');

-- --------------------------------------------------------

--
-- Table structure for table `purchases`
--

CREATE TABLE `purchases` (
  `purchase_id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `purchase_date` datetime NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchases`
--

INSERT INTO `purchases` (`purchase_id`, `supplier_id`, `purchase_date`, `total_amount`, `notes`, `created_by`, `created_at`) VALUES
(7, 2, '2025-06-24 17:09:04', 10.00, '0', 1, '2025-06-24 22:09:04'),
(8, 1, '2025-06-24 17:09:10', 1.00, '0', 1, '2025-06-24 22:09:10'),
(9, 1, '2025-06-24 17:10:44', 50.00, '0', 1, '2025-06-24 22:10:44'),
(10, 1, '2025-06-24 17:11:10', 50.00, '0', 1, '2025-06-24 22:11:10'),
(11, 3, '2025-06-24 17:13:35', 50.00, '0', 1, '2025-06-24 22:13:35'),
(12, 1, '2025-07-04 16:09:00', 50.00, '0', 1, '2025-07-04 21:09:00'),
(13, 1, '2025-07-04 16:09:08', 50.00, '0', 1, '2025-07-04 21:09:08'),
(14, 2, '2025-07-04 16:29:46', 5.00, '0', 1, '2025-07-04 21:29:46'),
(15, 2, '2025-07-04 16:29:50', 5.00, '0', 1, '2025-07-04 21:29:50'),
(16, 3, '2025-07-04 16:30:44', 5.00, '0', 1, '2025-07-04 21:30:44'),
(17, 3, '2025-07-04 16:30:48', 5.00, '0', 1, '2025-07-04 21:30:48'),
(18, 3, '2025-07-04 16:36:06', 2.00, '0', 1, '2025-07-04 21:36:06'),
(19, 2, '2025-07-04 16:37:00', 5.00, '0', 1, '2025-07-04 21:37:00'),
(20, 2, '2025-07-04 16:37:02', 5.00, '0', 1, '2025-07-04 21:37:02'),
(21, 1, '2025-07-04 16:38:15', 55.00, '0', 1, '2025-07-04 21:38:15'),
(22, 1, '2025-07-08 19:19:35', 1.00, '0', 1, '2025-07-09 00:19:35'),
(23, 1, '2025-07-08 19:19:44', 1.00, '0', 1, '2025-07-09 00:19:44'),
(24, 6, '2025-08-13 17:21:04', 100.00, '0', 1, '2025-08-13 22:21:04'),
(25, 6, '2025-08-13 17:21:09', 100.00, '0', 1, '2025-08-13 22:21:09');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_items`
--

CREATE TABLE `purchase_items` (
  `purchase_item_id` int(11) NOT NULL,
  `purchase_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase_items`
--

INSERT INTO `purchase_items` (`purchase_item_id`, `purchase_id`, `product_id`, `quantity`, `unit_price`, `subtotal`) VALUES
(7, 7, 8, 10, 1.00, 10.00),
(8, 8, 8, 1, 1.00, 1.00),
(9, 9, 22, 10, 5.00, 50.00),
(10, 10, 22, 10, 5.00, 50.00),
(11, 11, 22, 10, 5.00, 50.00),
(12, 12, 25, 10, 5.00, 50.00),
(13, 13, 25, 10, 5.00, 50.00),
(14, 14, 8, 1, 5.00, 5.00),
(15, 15, 8, 1, 5.00, 5.00),
(16, 16, 24, 1, 5.00, 5.00),
(17, 17, 24, 1, 5.00, 5.00),
(18, 18, 26, 2, 1.00, 2.00),
(19, 19, 28, 1, 5.00, 5.00),
(20, 20, 28, 1, 5.00, 5.00),
(21, 21, 23, 11, 5.00, 55.00),
(22, 22, 29, 1, 1.00, 1.00),
(23, 23, 29, 1, 1.00, 1.00),
(24, 24, 29, 5, 20.00, 100.00),
(25, 25, 29, 5, 20.00, 100.00);

-- --------------------------------------------------------

--
-- Table structure for table `refunds`
--

CREATE TABLE `refunds` (
  `refund_id` int(11) NOT NULL,
  `return_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL COMMENT 'cash, credit, bank_transfer, etc',
  `transaction_reference` varchar(100) DEFAULT NULL COMMENT 'Bank reference or transaction ID',
  `processed_by` varchar(100) NOT NULL,
  `processed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL,
  `status` enum('pending','completed','failed','reversed') NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Records of refunds processed for returns';

-- --------------------------------------------------------

--
-- Table structure for table `returns`
--

CREATE TABLE `returns` (
  `return_id` int(11) NOT NULL,
  `sale_id` int(11) DEFAULT NULL,
  `invoice_number` varchar(50) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `return_date` datetime DEFAULT current_timestamp(),
  `total_amount` decimal(10,2) DEFAULT NULL,
  `return_reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected','processed') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `refund_amount` decimal(10,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `returns`
--

INSERT INTO `returns` (`return_id`, `sale_id`, `invoice_number`, `customer_id`, `customer_name`, `return_date`, `total_amount`, `return_reason`, `status`, `payment_method`, `refund_amount`, `notes`, `created_by`) VALUES
(1, 8, 'INV-20250704-6867E78697C81', NULL, '', '2025-07-04 22:25:56', 0.00, 'stop', 'pending', 'cash', 0.00, '', 1),
(2, 8, 'INV-20250704-6867E78697C81', NULL, '', '2025-07-04 22:29:01', 0.00, 'stop', 'pending', 'cash', 0.00, '', 1),
(3, 8, 'INV-20250704-6867E78697C81', NULL, '', '2025-07-04 22:39:01', 0.00, 'sedfer', 'pending', 'cash', 0.00, '', 1),
(4, 10, 'INV-20250709-686E635E832BD', 0, '', '0000-00-00 00:00:00', 1.00, 'Defective Product', '', 'cash', 1.00, '', 1),
(5, 11, 'INV-20250711-68710B480D62D', 0, '', '0000-00-00 00:00:00', 10.00, 'Defective Product', '', 'cash', 10.00, '', 1),
(6, 12, 'INV-20250711-68710BBA36A30', 0, '', '0000-00-00 00:00:00', 20.00, 'No Longer Needed', '', 'cash', 20.00, '', 1),
(7, 12, 'INV-20250711-68710BBA36A30', 0, '', '0000-00-00 00:00:00', 20.00, 'Wrong Item Shipped', '', 'cash', 20.00, '', 1),
(8, 12, 'INV-20250711-68710BBA36A30', 0, '', '0000-00-00 00:00:00', 20.00, 'Wrong Item Shipped', '', 'cash', 20.00, '', 1),
(9, 13, 'INV-20250712-68726A939E490', 0, '', '0000-00-00 00:00:00', 40.00, 'Defective Product', '', 'cash', 40.00, '', 1),
(10, 28, 'INV-20250815173223-9483', 0, '', '0000-00-00 00:00:00', 1.00, 'Defective Product', '', 'cash', 1.00, '', 1);

-- --------------------------------------------------------

--
-- Table structure for table `return_items`
--

CREATE TABLE `return_items` (
  `return_item_id` int(11) NOT NULL,
  `return_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `product_code` varchar(50) DEFAULT NULL,
  `product_name` varchar(100) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `unit_price` decimal(10,2) DEFAULT NULL,
  `subtotal` decimal(10,2) DEFAULT NULL,
  `reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `return_items`
--

INSERT INTO `return_items` (`return_item_id`, `return_id`, `product_id`, `product_code`, `product_name`, `quantity`, `unit_price`, `subtotal`, `reason`) VALUES
(1, 4, 8, 'S001', 'អាវ', 1, 1.00, 1.00, ''),
(2, 5, 26, 'S005', 'អាវយឺត ', 1, 10.00, 10.00, ''),
(3, 6, 29, 'x123', 'test1', 1, 20.00, 20.00, ''),
(4, 7, 29, 'x123', 'test1', 1, 20.00, 20.00, ''),
(5, 8, 29, 'x123', 'test1', 1, 20.00, 20.00, ''),
(6, 9, 29, 'x123', 'test1', 2, 20.00, 40.00, ''),
(7, 10, 8, 'S001', 'អាវ', 1, 1.00, 1.00, '');

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `date` datetime NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `tax` decimal(10,2) NOT NULL,
  `discount` decimal(10,2) DEFAULT 0.00,
  `payment_method` enum('cash','credit','debit','transfer','other') NOT NULL DEFAULT 'cash',
  `payment_status` enum('paid','pending','partial','refunded') NOT NULL DEFAULT 'paid',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `invoice_number`, `customer_id`, `customer_name`, `date`, `total`, `tax`, `discount`, `payment_method`, `payment_status`, `notes`, `created_at`, `updated_at`) VALUES
(3, 'INV-20250702-686559902ECDA', 2, NULL, '2025-07-02 23:08:48', 23.10, 2.10, 0.00, 'cash', 'paid', '', '2025-07-02 16:08:48', '2025-07-03 09:04:20'),
(4, 'INV-20250704-6867DC76A130C', 0, NULL, '2025-07-04 20:51:50', 11.00, 1.00, 0.00, 'cash', 'paid', NULL, '2025-07-04 13:51:50', '2025-07-04 13:51:50'),
(5, 'INV-20250704-6867DCD5197A4', 0, NULL, '2025-07-04 20:53:25', 22.00, 2.00, 0.00, 'cash', 'paid', NULL, '2025-07-04 13:53:25', '2025-07-04 13:53:25'),
(6, 'INV-20250704-6867E02BBBA99', 0, NULL, '2025-07-04 21:07:39', 22.00, 2.00, 0.00, 'cash', 'paid', NULL, '2025-07-04 14:07:39', '2025-07-04 14:07:39'),
(7, 'INV-20250704-6867E03E305A5', 0, NULL, '2025-07-04 21:07:58', 55.00, 5.00, 0.00, 'cash', 'paid', NULL, '2025-07-04 14:07:58', '2025-07-04 14:07:58'),
(8, 'INV-20250704-6867E78697C81', 0, NULL, '2025-07-04 21:39:02', 6.60, 0.60, 0.00, 'cash', 'paid', NULL, '2025-07-04 14:39:02', '2025-07-04 14:39:02'),
(9, 'INV-20250708-686D538782A9E', 0, NULL, '2025-07-09 00:21:11', 66.00, 6.00, 0.00, 'cash', 'paid', NULL, '2025-07-08 17:21:11', '2025-07-08 17:21:11'),
(10, 'INV-20250709-686E635E832BD', 0, NULL, '2025-07-09 19:41:02', 1.10, 0.10, 0.00, 'cash', 'paid', NULL, '2025-07-09 12:41:02', '2025-07-09 12:41:02'),
(11, 'INV-20250711-68710B480D62D', 0, NULL, '2025-07-11 20:02:00', 11.00, 1.00, 0.00, 'cash', 'paid', NULL, '2025-07-11 13:02:00', '2025-07-11 13:02:00'),
(12, 'INV-20250711-68710BBA36A30', 0, NULL, '2025-07-11 20:03:54', 22.00, 2.00, 0.00, 'cash', 'paid', NULL, '2025-07-11 13:03:54', '2025-07-11 13:03:54'),
(13, 'INV-20250712-68726A939E490', 0, NULL, '2025-07-12 21:00:51', 44.00, 4.00, 0.00, 'cash', 'paid', NULL, '2025-07-12 14:00:51', '2025-07-12 14:00:51'),
(14, 'INV-20250713-687397E52D157', 0, NULL, '2025-07-13 18:26:29', 11.00, 1.00, 0.00, 'cash', 'paid', NULL, '2025-07-13 11:26:29', '2025-07-13 11:26:29'),
(15, 'INV-20250808-689623220EC88', 0, NULL, '2025-08-08 23:17:38', 1.10, 0.10, 0.00, 'cash', 'paid', NULL, '2025-08-08 16:17:38', '2025-08-08 16:17:38'),
(16, 'INV-20250808-689628FF1A915', 0, NULL, '2025-08-08 23:42:39', 11.00, 1.00, 0.00, 'cash', 'paid', NULL, '2025-08-08 16:42:39', '2025-08-08 16:42:39'),
(17, 'INV-20250809-6896F2A3BEB87', 0, NULL, '2025-08-09 14:02:59', 2.20, 0.20, 0.00, 'cash', 'paid', NULL, '2025-08-09 07:02:59', '2025-08-09 07:02:59'),
(18, 'INV-20250809-68977AD9DEB76', 0, NULL, '2025-08-09 23:44:09', 1.10, 0.10, 0.00, 'cash', 'paid', NULL, '2025-08-09 16:44:09', '2025-08-09 16:44:09'),
(19, 'INV-20250809-68977CAD003C1', 0, NULL, '2025-08-09 23:51:57', 1.10, 0.10, 0.00, 'cash', 'paid', NULL, '2025-08-09 16:51:57', '2025-08-09 16:51:57'),
(20, 'INV-20250809-68977CC924CD1', 0, NULL, '2025-08-09 23:52:25', 12.10, 1.10, 0.00, 'cash', 'paid', NULL, '2025-08-09 16:52:25', '2025-08-09 16:52:25'),
(21, 'INV-20250811-6899EF4C774C1', 0, NULL, '2025-08-11 20:25:32', 1.10, 0.10, 0.00, 'cash', 'paid', NULL, '2025-08-11 13:25:32', '2025-08-11 13:25:32'),
(22, 'INV-20250813-689C921079428', 0, NULL, '2025-08-13 20:24:32', 2.20, 0.20, 0.00, 'cash', 'paid', NULL, '2025-08-13 13:24:32', '2025-08-13 13:24:32'),
(23, 'INV-20250813-689C9233DB07D', 0, NULL, '2025-08-13 20:25:07', 13.20, 1.20, 0.00, 'cash', 'paid', NULL, '2025-08-13 13:25:07', '2025-08-13 13:25:07'),
(24, 'INV-20250813-689C93EAAFCF5', 0, NULL, '2025-08-13 20:32:26', 11.00, 1.00, 0.00, 'cash', 'paid', NULL, '2025-08-13 13:32:26', '2025-08-13 13:32:26'),
(25, 'INV-20250813-689C94C32C6E4', 6, NULL, '2025-08-13 20:36:03', 220.00, 20.00, 0.00, 'cash', 'paid', '', '2025-08-13 13:36:03', '2025-08-14 16:01:36'),
(26, 'INV-20250814-689E0A4B2909F', 0, NULL, '2025-08-14 23:09:47', 11.00, 1.00, 0.00, 'cash', 'paid', NULL, '2025-08-14 16:09:47', '2025-08-14 16:09:47'),
(27, 'INV-20250814-689E0C2A3DB6F', 0, NULL, '2025-08-14 23:17:46', 11.00, 1.00, 0.00, 'cash', 'paid', NULL, '2025-08-14 16:17:46', '2025-08-14 16:17:46'),
(28, 'INV-20250815173223-9483', 0, NULL, '2025-08-15 22:32:23', 1.09, 0.10, 0.01, 'cash', 'paid', NULL, '2025-08-15 15:32:23', '2025-08-15 15:32:23'),
(29, 'INV-20250815-689F6076D1019', 0, NULL, '2025-08-15 23:29:42', 8.80, 0.80, 0.00, 'cash', 'paid', NULL, '2025-08-15 16:29:42', '2025-08-15 16:29:42'),
(30, 'INV-20250815-689F61110C340', 0, NULL, '2025-08-15 23:32:17', 11.00, 1.00, 0.00, 'cash', 'paid', NULL, '2025-08-15 16:32:17', '2025-08-15 16:32:17');

-- --------------------------------------------------------

--
-- Table structure for table `sale_items`
--

CREATE TABLE `sale_items` (
  `id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sale_items`
--

INSERT INTO `sale_items` (`id`, `sale_id`, `product_id`, `product_name`, `quantity`, `price`, `total`, `created_at`) VALUES
(3, 3, 26, '', 2, 10.00, 20.00, '2025-07-02 16:08:48'),
(4, 3, 8, '', 1, 1.00, 1.00, '2025-07-02 16:08:48'),
(5, 4, 25, '', 1, 10.00, 10.00, '2025-07-04 13:51:50'),
(6, 5, 28, '', 2, 10.00, 20.00, '2025-07-04 13:53:25'),
(7, 6, 25, '', 2, 10.00, 20.00, '2025-07-04 14:07:39'),
(8, 7, 25, '', 5, 10.00, 50.00, '2025-07-04 14:07:58'),
(9, 8, 23, '', 3, 2.00, 6.00, '2025-07-04 14:39:02'),
(10, 9, 29, '', 3, 20.00, 60.00, '2025-07-08 17:21:11'),
(11, 10, 8, '', 1, 1.00, 1.00, '2025-07-09 12:41:02'),
(12, 11, 26, '', 1, 10.00, 10.00, '2025-07-11 13:02:00'),
(13, 12, 29, '', 1, 20.00, 20.00, '2025-07-11 13:03:54'),
(14, 13, 29, '', 2, 20.00, 40.00, '2025-07-12 14:00:51'),
(15, 14, 22, '', 1, 10.00, 10.00, '2025-07-13 11:26:29'),
(16, 15, 8, '', 1, 1.00, 1.00, '2025-08-08 16:17:38'),
(17, 16, 26, '', 1, 10.00, 10.00, '2025-08-08 16:42:39'),
(18, 17, 23, '', 1, 2.00, 2.00, '2025-08-09 07:02:59'),
(19, 18, 8, '', 1, 1.00, 1.00, '2025-08-09 16:44:09'),
(20, 19, 8, '', 1, 1.00, 1.00, '2025-08-09 16:51:57'),
(21, 20, 8, '', 1, 1.00, 1.00, '2025-08-09 16:52:25'),
(22, 20, 26, '', 1, 10.00, 10.00, '2025-08-09 16:52:25'),
(23, 21, 8, '', 1, 1.00, 1.00, '2025-08-11 13:25:32'),
(24, 22, 23, '', 1, 2.00, 2.00, '2025-08-13 13:24:32'),
(25, 23, 23, '', 1, 2.00, 2.00, '2025-08-13 13:25:07'),
(26, 23, 22, '', 1, 10.00, 10.00, '2025-08-13 13:25:07'),
(27, 24, 28, '', 1, 10.00, 10.00, '2025-08-13 13:32:26'),
(28, 25, 29, '', 10, 20.00, 200.00, '2025-08-13 13:36:03'),
(29, 26, 28, '', 1, 10.00, 10.00, '2025-08-14 16:09:47'),
(30, 27, 25, '', 1, 10.00, 10.00, '2025-08-14 16:17:46'),
(31, 28, 8, 'អាវ', 1, 1.00, 1.00, '2025-08-15 15:32:23'),
(32, 29, 31, '', 1, 8.00, 8.00, '2025-08-15 16:29:42'),
(33, 30, 22, '', 1, 10.00, 10.00, '2025-08-15 16:32:17');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `name`, `value`, `created_at`, `updated_at`) VALUES
(1, 'payment_methods', '[\"cash\",\"credit\",\"bank_transfer\",\"credit_card\"]', '2025-07-11 12:39:49', '2025-07-11 12:39:49');

-- --------------------------------------------------------

--
-- Table structure for table `supplier`
--

CREATE TABLE `supplier` (
  `supplier_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supplier`
--

INSERT INTO `supplier` (`supplier_id`, `name`, `phone`, `address`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Supplier 1', '098765434', 'pp', 'Active', '2025-06-11 19:33:55', '2025-07-06 23:28:08'),
(2, 'test1', '998765', 'pp', 'Active', '2025-06-11 19:36:44', '2025-06-11 19:36:44'),
(3, 'test10', '998765', 'pp', 'Active', '2025-06-11 19:36:45', '2025-06-11 20:37:47'),
(6, 'Sok Nov', '0882256288', 'Phnom​ phenhn', 'Active', '2025-07-06 18:10:34', '2025-07-06 23:27:43'),
(7, 'Supplier 2', '0987654321', 'Takeo', 'Active', '2025-07-06 23:35:18', '2025-07-06 23:35:18');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `created_at`) VALUES
(1, 'admin', 'admin@gmail.com', '$2y$10$Lutfw5s4WpLiEdzrMqGzQ.fUk6S9nQ4H3l2lXbfxBPTrcqZoiGamO', '2025-06-09 12:19:05'),
(2, 'soknov', 'soknov0@gmail.com', '$2y$10$WJcno.xbh2Z5hP7Nes/X5eST6vPa8769nkLAgyGdKtmlslh7yuaEW', '2025-06-09 22:01:41'),
(3, 'user1', 'user1@gmail.com', '$2y$10$rCwxjGPixtyB6pfF.8clQ.G4wic/7k8ciGGwLwGa3n2T/2xvAe8S.', '2025-06-09 22:04:59'),
(4, 'lem', 'livlem70@gmail.com', '$2y$10$dXiFCOzOvF1yyTzpB7ygcuV0uB8N/uocZoa7llzP6dMIg1gKhm3KS', '2025-06-09 22:51:09'),
(10, 'admin1', 'soknov623600@gmail.com', '$2y$10$NePPmLpPIb8yC878aHeZE.2r2qQzapo5HUgqZuDYVwWTPfJ1dnWTi', '2025-07-06 16:05:35'),
(11, 'admin11', 'soknov6236@gmail.com', '$2y$10$pLyjjeuhEYSZLdE2Vm/sUe7JQIiSX4GSU9Pfw.Wy4z4eNevTCeDB2', '2025-08-14 09:48:08');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory_history`
--
ALTER TABLE `inventory_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `reference_id` (`reference_id`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `inventory_log`
--
ALTER TABLE `inventory_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD UNIQUE KEY `product_code` (`product_code`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `products_ibfk_2` (`supplier_id`);

--
-- Indexes for table `purchases`
--
ALTER TABLE `purchases`
  ADD PRIMARY KEY (`purchase_id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `purchase_items`
--
ALTER TABLE `purchase_items`
  ADD PRIMARY KEY (`purchase_item_id`),
  ADD KEY `purchase_id` (`purchase_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `refunds`
--
ALTER TABLE `refunds`
  ADD PRIMARY KEY (`refund_id`),
  ADD UNIQUE KEY `return_id` (`return_id`),
  ADD KEY `processed_at` (`processed_at`);

--
-- Indexes for table `returns`
--
ALTER TABLE `returns`
  ADD PRIMARY KEY (`return_id`),
  ADD KEY `sale_id` (`sale_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `return_items`
--
ALTER TABLE `return_items`
  ADD PRIMARY KEY (`return_item_id`),
  ADD KEY `return_id` (`return_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `date` (`date`);

--
-- Indexes for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_id` (`sale_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `supplier`
--
ALTER TABLE `supplier`
  ADD PRIMARY KEY (`supplier_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `category`
--
ALTER TABLE `category`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `inventory_history`
--
ALTER TABLE `inventory_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_log`
--
ALTER TABLE `inventory_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `purchases`
--
ALTER TABLE `purchases`
  MODIFY `purchase_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `purchase_items`
--
ALTER TABLE `purchase_items`
  MODIFY `purchase_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `refunds`
--
ALTER TABLE `refunds`
  MODIFY `refund_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `returns`
--
ALTER TABLE `returns`
  MODIFY `return_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `return_items`
--
ALTER TABLE `return_items`
  MODIFY `return_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `sale_items`
--
ALTER TABLE `sale_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `supplier`
--
ALTER TABLE `supplier`
  MODIFY `supplier_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `inventory_history`
--
ALTER TABLE `inventory_history`
  ADD CONSTRAINT `inventory_history_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `inventory_log`
--
ALTER TABLE `inventory_log`
  ADD CONSTRAINT `inventory_log_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `category` (`category_id`),
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `supplier` (`supplier_id`);

--
-- Constraints for table `purchases`
--
ALTER TABLE `purchases`
  ADD CONSTRAINT `purchases_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `supplier` (`supplier_id`);

--
-- Constraints for table `purchase_items`
--
ALTER TABLE `purchase_items`
  ADD CONSTRAINT `purchase_items_ibfk_1` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`purchase_id`),
  ADD CONSTRAINT `purchase_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);

--
-- Constraints for table `refunds`
--
ALTER TABLE `refunds`
  ADD CONSTRAINT `refunds_ibfk_1` FOREIGN KEY (`return_id`) REFERENCES `returns` (`return_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `returns`
--
ALTER TABLE `returns`
  ADD CONSTRAINT `returns_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `returns_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `return_items`
--
ALTER TABLE `return_items`
  ADD CONSTRAINT `return_items_ibfk_1` FOREIGN KEY (`return_id`) REFERENCES `returns` (`return_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `return_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE SET NULL;

--
-- Constraints for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD CONSTRAINT `sale_items_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sale_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
