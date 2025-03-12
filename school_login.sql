-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Mar 12, 2025 at 04:23 PM
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
-- Database: `school_login`
--

-- --------------------------------------------------------

--
-- Table structure for table `guests`
--

CREATE TABLE `guests` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL COMMENT 'Supports both IPv4 and IPv6 addresses',
  `user_agent` text DEFAULT NULL COMMENT 'Browser and OS information',
  `referer` varchar(255) DEFAULT NULL COMMENT 'The referring page URL',
  `page_url` varchar(255) DEFAULT NULL COMMENT 'The URL of the visited page',
  `visit_time` datetime NOT NULL COMMENT 'When the visit occurred',
  `browser` varchar(50) DEFAULT NULL COMMENT 'Detected browser name',
  `device_type` varchar(20) DEFAULT NULL COMMENT 'Mobile or Desktop',
  `os` varchar(50) DEFAULT NULL COMMENT 'Operating system',
  `session_id` varchar(255) DEFAULT NULL COMMENT 'PHP session ID if available',
  `visit_duration` int(11) DEFAULT 0 COMMENT 'Time spent on page in seconds',
  `country` varchar(100) DEFAULT NULL COMMENT 'Country based on IP (if available)',
  `region` varchar(100) DEFAULT NULL COMMENT 'Region/State based on IP (if available)',
  `city` varchar(100) DEFAULT NULL COMMENT 'City based on IP (if available)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `guests`
--

INSERT INTO `guests` (`id`, `ip_address`, `user_agent`, `referer`, `page_url`, `visit_time`, `browser`, `device_type`, `os`, `session_id`, `visit_duration`, `country`, `region`, `city`, `created_at`) VALUES
(1, '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:136.0) Gecko/20100101 Firefox/136.0', 'http://localhost/2025/school_login/index.php', '/2025/school_login/guest.php', '2025-03-12 16:10:27', 'Firefox', 'Desktop', 'Mac OS X', NULL, 0, NULL, NULL, NULL, '2025-03-12 15:10:27'),
(2, '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:136.0) Gecko/20100101 Firefox/136.0', 'http://localhost/2025/school_login/index.php', '/2025/school_login/guest.php', '2025-03-12 16:11:01', 'Firefox', 'Desktop', 'Mac OS X', NULL, 0, NULL, NULL, NULL, '2025-03-12 15:11:01'),
(3, '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:136.0) Gecko/20100101 Firefox/136.0', 'http://localhost/2025/school_login/index.php', '/2025/school_login/guest.php', '2025-03-12 16:11:13', 'Firefox', 'Desktop', 'Mac OS X', NULL, 0, NULL, NULL, NULL, '2025-03-12 15:11:13'),
(4, '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:136.0) Gecko/20100101 Firefox/136.0', 'http://localhost/2025/school_login/index.php', '/2025/school_login/guest.php', '2025-03-12 16:11:22', 'Firefox', 'Desktop', 'Mac OS X', NULL, 0, NULL, NULL, NULL, '2025-03-12 15:11:22'),
(5, '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:136.0) Gecko/20100101 Firefox/136.0', 'http://localhost/2025/school_login/index.php', '/2025/school_login/guest.php', '2025-03-12 16:12:38', 'Firefox', 'Desktop', 'Mac OS X', NULL, 0, NULL, NULL, NULL, '2025-03-12 15:12:38'),
(6, '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:136.0) Gecko/20100101 Firefox/136.0', 'http://localhost/2025/school_login/signup.php', '/2025/school_login/guest.php', '2025-03-12 16:12:57', 'Firefox', 'Desktop', 'Mac OS X', NULL, 0, NULL, NULL, NULL, '2025-03-12 15:12:57');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `academic_interest` varchar(100) DEFAULT NULL,
  `account_created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `profile_photo_original` varchar(255) DEFAULT NULL,
  `profile_photo_resized` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password_hash`, `first_name`, `last_name`, `phone_number`, `academic_interest`, `account_created_at`, `last_login`, `profile_photo_original`, `profile_photo_resized`) VALUES
(1, 'nishan', 'nishan.snav@gmail.com', '$2y$10$Ou9JvMWcviEJUUOwyK5iE.6LKWBJp2cdqeMup0WX/mgyiK/Plpyqy', 'Nishan', 'Nepali', '9769961708', 'Arts', '2025-03-12 14:34:52', '2025-03-12 15:22:46', 'uploads/profile_photos/original_67d19b8c4802a.jpeg', 'uploads/profile_photos/resized_67d19b8c4802a.jpeg'),
(2, 'suraj', 'suraj@gmail.com', '$2y$10$0T9FnWc7RVyHC7E9jTmZo.CgPUVU0FP0pa7LoRV.UVcEx0DiDXKjq', 'Suraj', 'Nepali', '937498435', 'Science', '2025-03-12 15:01:05', '2025-03-12 15:18:40', 'uploads/profile_photos/original_67d1a1b122725.jpeg', 'uploads/profile_photos/resized_67d1a1b122725.jpeg');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `guests`
--
ALTER TABLE `guests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_visit_time` (`visit_time`),
  ADD KEY `idx_ip_address` (`ip_address`),
  ADD KEY `idx_device_type` (`device_type`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `guests`
--
ALTER TABLE `guests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
