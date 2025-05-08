-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql210.infinityfree.com
-- Generation Time: May 08, 2025 at 02:09 AM
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
  `spend_amount` decimal(10,2) DEFAULT NULL,
  `spend_date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
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
  ADD PRIMARY KEY (`user_id`,`auction_id`),
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
  ADD PRIMARY KEY (`user_id`,`auction_id`),
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
  MODIFY `auction_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `BIDS`
--
ALTER TABLE `BIDS`
  MODIFY `bid_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `INCOME`
--
ALTER TABLE `INCOME`
  MODIFY `income_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `SPEND`
--
ALTER TABLE `SPEND`
  MODIFY `spend_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `TOPUP`
--
ALTER TABLE `TOPUP`
  MODIFY `topup_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `USERS`
--
ALTER TABLE `USERS`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `WITHDRAW`
--
ALTER TABLE `WITHDRAW`
  MODIFY `withdraw_id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
