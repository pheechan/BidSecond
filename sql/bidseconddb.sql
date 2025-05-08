-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql210.infinityfree.com
-- Generation Time: May 08, 2025 at 04:34 AM
-- Server version: 10.6.19-MariaDB
-- PHP Version: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `if0_38762438_bidseconddb`
--

-- --------------------------------------------------------

--
-- Table structure for table `AUCTIONS`
--

CREATE TABLE `AUCTIONS` (
  `auction_id` int(11) NOT NULL,
  `seller_id` int(11) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `image` mediumblob DEFAULT NULL,
  `category` varchar(255) DEFAULT NULL,
  `description` mediumtext DEFAULT NULL,
  `start_price` decimal(10,2) DEFAULT NULL,
  `bid_amount` decimal(10,2) DEFAULT NULL,
  `min_increment` decimal(10,2) DEFAULT NULL,
  `end_time` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('pending','active','ended','cancelled','paid','shipped') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `AUCTIONS`
--

INSERT INTO `AUCTIONS` (`auction_id`, `seller_id`, `title`, `image`, `category`, `description`, `start_price`, `bid_amount`, `min_increment`, `end_time`, `status`, `created_at`) VALUES
(1, 1, 'Vintage Watch', NULL, 'Accessories', 'A classic vintage watch in excellent condition.', '100.00', '150.00', '10.00', '2025-05-08 08:07:17', 'active', '2025-05-07 19:00:00'),
(2, 1, 'Gaming Laptop', NULL, 'Electronics', 'High-performance gaming laptop with RTX 3070 GPU.', '1200.00', '1350.00', '50.00', '2025-05-13 03:00:00', 'pending', '2025-05-08 17:00:00'),
(3, 1, 'Antique Vase', NULL, 'Home Decor', 'A rare antique vase from the 18th century.', '500.00', '750.00', '25.00', '2025-05-08 08:07:12', 'ended', '2025-05-05 16:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `BIDS`
--

CREATE TABLE `BIDS` (
  `bid_id` int(11) NOT NULL,
  `auction_id` int(11) DEFAULT NULL,
  `buyer_id` int(11) DEFAULT NULL,
  `bid_amount` decimal(10,2) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `BUYER`
--

CREATE TABLE `BUYER` (
  `buyer_id` int(11) NOT NULL,
  `auction_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `BUY_HISTORY`
--

CREATE TABLE `BUY_HISTORY` (
  `user_id` int(11) NOT NULL,
  `seller_id` int(11) DEFAULT NULL,
  `auction_id` int(11) NOT NULL,
  `bid_amount` decimal(10,2) DEFAULT NULL,
  `end_time` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `INCOME`
--

CREATE TABLE `INCOME` (
  `income_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `income_amount` decimal(10,2) DEFAULT NULL,
  `income_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `PENDING_TRANSACTIONS`
--

CREATE TABLE `PENDING_TRANSACTIONS` (
  `auction_id` int(11) NOT NULL,
  `seller_id` int(11) DEFAULT NULL,
  `bid_amount` decimal(10,2) DEFAULT NULL,
  `buyer_id` int(11) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `end_time` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `payment_status` enum('unpaid','paid','shipped','cancelled') DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `SELLER`
--

CREATE TABLE `SELLER` (
  `seller_id` int(11) NOT NULL,
  `auction_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `SELLER`
--

INSERT INTO `SELLER` (`seller_id`, `auction_id`, `user_id`) VALUES
(1, 1, 8),
(1, 2, 8),
(1, 3, 8);

-- --------------------------------------------------------

--
-- Table structure for table `SELL_HISTORY`
--

CREATE TABLE `SELL_HISTORY` (
  `user_id` int(11) NOT NULL,
  `buyer_id` int(11) DEFAULT NULL,
  `auction_id` int(11) NOT NULL,
  `bid_amount` decimal(10,2) DEFAULT NULL,
  `end_time` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `SPEND`
--

CREATE TABLE `SPEND` (
  `spend_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `spend_amount` decimal(10,2) DEFAULT NULL,
  `spend_date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `TOPUP`
--

CREATE TABLE `TOPUP` (
  `topup_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `topup_amount` decimal(10,2) DEFAULT NULL,
  `topup_date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` tinyint(1) DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `USERS`
--

CREATE TABLE `USERS` (
  `user_id` int(11) NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `otp_code` varchar(6) DEFAULT NULL,
  `email_verified` tinyint(4) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `address` varchar(255) DEFAULT NULL,
  `balance` decimal(10,2) DEFAULT NULL,
  `role` varchar(50) DEFAULT 'user'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `USERS`
--

INSERT INTO `USERS` (`user_id`, `username`, `email`, `password_hash`, `otp_code`, `email_verified`, `created_at`, `address`, `balance`, `role`) VALUES
(1, 'Siwakorn', 'siwakorn.bubphasawan@outlook.com', 'lol', '915976', 0, '2025-05-07 19:17:14', NULL, NULL, 'user'),
(4, 'Phee', 'p.chantarusorn@gmail.com', 'lol', '534430', 1, '2025-05-07 21:50:37', '', NULL, 'user'),
(8, 'Jamal', 'magicakpawee@gmail.com', 'lol', '625442', 1, '2025-05-08 07:59:58', 'ur mom&#039;s crib', NULL, 'admin');

-- --------------------------------------------------------

--
-- Table structure for table `WITHDRAW`
--

CREATE TABLE `WITHDRAW` (
  `withdraw_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `withdraw_amount` decimal(10,2) DEFAULT NULL,
  `withdraw_date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `AUCTIONS`
--
ALTER TABLE `AUCTIONS`
  ADD PRIMARY KEY (`auction_id`),
  ADD KEY `seller_id` (`seller_id`);

--
-- Indexes for table `BIDS`
--
ALTER TABLE `BIDS`
  ADD PRIMARY KEY (`bid_id`),
  ADD KEY `auction_id` (`auction_id`),
  ADD KEY `buyer_id` (`buyer_id`);

--
-- Indexes for table `BUYER`
--
ALTER TABLE `BUYER`
  ADD PRIMARY KEY (`buyer_id`,`auction_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `auction_id` (`auction_id`);

--
-- Indexes for table `BUY_HISTORY`
--
ALTER TABLE `BUY_HISTORY`
  ADD PRIMARY KEY (`end_time`) USING BTREE,
  ADD KEY `seller_id` (`seller_id`);

--
-- Indexes for table `INCOME`
--
ALTER TABLE `INCOME`
  ADD PRIMARY KEY (`income_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `PENDING_TRANSACTIONS`
--
ALTER TABLE `PENDING_TRANSACTIONS`
  ADD PRIMARY KEY (`auction_id`),
  ADD KEY `seller_id` (`seller_id`),
  ADD KEY `buyer_id` (`buyer_id`);

--
-- Indexes for table `SELLER`
--
ALTER TABLE `SELLER`
  ADD PRIMARY KEY (`seller_id`,`auction_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `auction_id` (`auction_id`);

--
-- Indexes for table `SELL_HISTORY`
--
ALTER TABLE `SELL_HISTORY`
  ADD PRIMARY KEY (`end_time`) USING BTREE,
  ADD KEY `buyer_id` (`buyer_id`);

--
-- Indexes for table `SPEND`
--
ALTER TABLE `SPEND`
  ADD PRIMARY KEY (`spend_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `TOPUP`
--
ALTER TABLE `TOPUP`
  ADD PRIMARY KEY (`topup_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `USERS`
--
ALTER TABLE `USERS`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `WITHDRAW`
--
ALTER TABLE `WITHDRAW`
  ADD PRIMARY KEY (`withdraw_id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `AUCTIONS`
--
ALTER TABLE `AUCTIONS`
  MODIFY `auction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `BIDS`
--
ALTER TABLE `BIDS`
  MODIFY `bid_id` int(11) NOT NULL AUTO_INCREMENT;


--
-- AUTO_INCREMENT for table `TOPUP`
--
ALTER TABLE `TOPUP`
  MODIFY `topup_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `USERS`
--
ALTER TABLE `USERS`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `WITHDRAW`
--
ALTER TABLE `WITHDRAW`
  MODIFY `withdraw_id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
