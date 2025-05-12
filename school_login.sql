-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Mar 18, 2025 at 05:40 PM
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
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `role` enum('super_admin','event_manager','moderator') DEFAULT 'moderator',
  `account_created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `username`, `email`, `password_hash`, `full_name`, `phone_number`, `role`, `account_created_at`, `last_login`) VALUES
(1, 'nishan', 'nishan.snav@gmail.com', '$2y$10$QhcA2Z/DeCvTic681.t2Te8DFlPvsTyzYkLVAe2m7cR/Ipzdyo5lW', 'Nishan', '9769661708', 'super_admin', '2025-03-16 08:18:17', '2025-03-18 16:37:24'),
(2, 'suraj', 'suraj@gmail.com', '$2y$10$zszUKm5rIARt6ALgO6OAle5MYb6rI1xTNUf1XWy9.W.ZOtmZbm7om', 'suraj nepali', '0932840234', 'super_admin', '2025-03-16 09:43:14', '2025-03-18 03:01:47');

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT 0,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `status` enum('new','read','replied') DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `event_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `event_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `location` varchar(255) NOT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `speakers` text DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `topic` varchar(100) DEFAULT NULL,
  `tag` varchar(100) DEFAULT NULL,
  `estimated_participants` int(11) DEFAULT 0,
  `event_image_original` varchar(255) DEFAULT NULL,
  `event_image_resized` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`event_id`, `admin_id`, `title`, `description`, `event_date`, `start_time`, `end_time`, `location`, `latitude`, `longitude`, `speakers`, `department`, `topic`, `tag`, `estimated_participants`, `event_image_original`, `event_image_resized`, `created_at`, `updated_at`) VALUES
(8, 1, 'safari', 'safari is the best browser', '2025-02-25', '18:57:00', '19:02:00', 'safari', 52.5880528, -2.1300580, 'asfasdf', 'safari', 'safari', '', 333, 'http://localhost/2025/school_login/uploads/events/original_67d69ff8e1efa.jpeg', 'http://localhost/2025/school_login/uploads/events/resized_67d69ff8e1efa.jpeg', '2025-03-16 09:12:28', '2025-03-18 15:46:37'),
(9, 1, 'sdfkl', 'askdfhj', '2025-03-20', '12:02:00', '13:02:00', 'asdfklj', 52.5880528, -2.1300580, 'nishan, surja', 'suisadf', 'kladsjf', 'important', 22, 'http://localhost/2025/school_login/uploads/events/original_67d849380ee4e.jpeg', 'http://localhost/2025/school_login/uploads/events/resized_67d849380ee4e.jpeg', '2025-03-17 16:09:28', '2025-03-18 12:19:54'),
(10, 2, 'Forensic', 'askldf jaklsdfj alskfdj al;skfj as;lkdfja ;l', '2025-03-21', '22:33:00', '23:35:00', 'Briminghum', 28.5599320, 81.6266882, 'suraj nepali', 'science', 'lasjd fkl', 'seminar', 222, 'http://localhost/2025/school_login/uploads/events/original_67d8df3e5e04d.jpeg', 'http://localhost/2025/school_login/uploads/events/resized_67d8df3e5e04d.jpeg', '2025-03-18 02:49:34', '2025-03-18 02:49:34'),
(11, 1, 'Shaa', 'This is for demo prupose', '2025-03-21', '23:02:00', '23:30:00', 'Surkhet', 28.6013722, 81.6141586, 'Nishan', 'Science', 'Health', 'seminar', 500, 'http://localhost/2025/school_login/uploads/events/original_67d8ea2f15916.jpg', 'http://localhost/2025/school_login/uploads/events/resized_67d8ea2f15916.jpg', '2025-03-18 03:36:15', '2025-03-18 03:36:15'),
(12, 1, 'Checking', 'I am checking this for a while', '2025-03-27', '11:01:00', '11:45:00', 'New Delhi', 28.5885172, 81.6144673, 'Speaker', 'Science', 'New', 'important', 333, 'http://localhost/2025/school_login/uploads/events/original_67d98e9857408.png', 'http://localhost/2025/school_login/uploads/events/resized_67d98e9857408.png', '2025-03-18 15:17:44', '2025-03-18 15:17:44');

-- --------------------------------------------------------

--
-- Table structure for table `faqs`
--

CREATE TABLE `faqs` (
  `faq_id` int(11) NOT NULL,
  `question` text NOT NULL,
  `answer` text NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `is_published` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `faqs`
--

INSERT INTO `faqs` (`faq_id`, `question`, `answer`, `category`, `is_published`, `created_at`, `updated_at`) VALUES
(3, 'asdklfja sd;klfjasd f;ajs fd', 'ok', 'jkh', 1, '2025-03-18 14:45:57', '2025-03-18 14:45:57');

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
(17, '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:136.0) Gecko/20100101 Firefox/136.0', 'http://localhost/2025/school_login/guest.php', '/2025/school_login/guest.php?', '2025-03-13 05:14:07', 'Firefox', 'Desktop', 'Mac OS X', NULL, 0, NULL, NULL, NULL, '2025-03-13 04:14:07'),
(18, '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:136.0) Gecko/20100101 Firefox/136.0', 'http://localhost/2025/school_login/index.php', '/2025/school_login/guest.php', '2025-03-13 05:14:09', 'Firefox', 'Desktop', 'Mac OS X', NULL, 0, NULL, NULL, NULL, '2025-03-13 04:14:09'),
(19, '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:136.0) Gecko/20100101 Firefox/136.0', 'http://localhost/2025/school_login/signup.php', '/2025/school_login/guest.php', '2025-03-13 05:14:12', 'Firefox', 'Desktop', 'Mac OS X', NULL, 0, NULL, NULL, NULL, '2025-03-13 04:14:12'),
(20, '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:136.0) Gecko/20100101 Firefox/136.0', 'http://localhost/2025/school_login/signup.php', '/2025/school_login/guest.php', '2025-03-13 13:25:20', 'Firefox', 'Desktop', 'Mac OS X', NULL, 0, NULL, NULL, NULL, '2025-03-13 12:25:20'),
(21, '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:136.0) Gecko/20100101 Firefox/136.0', 'http://localhost/2025/school_login/signup.php', '/2025/school_login/guest.php', '2025-03-13 13:26:24', 'Firefox', 'Desktop', 'Mac OS X', NULL, 0, NULL, NULL, NULL, '2025-03-13 12:26:24');

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
(5, 'nishan', 'nishan.snav@gmail.com', '$2y$10$QQBFr4SbNxGA8wO/VlZ4ouTGZjMKgR0XzbU3QzPZ4ZyhiLYKz7hoW', 'Nishan', 'Nepali', '9769961708', 'Computer Science', '2025-03-13 12:16:05', '2025-03-18 16:38:53', 'uploads/profile_photos/original_67d2cc8561b59.jpeg', 'uploads/profile_photos/resized_67d2cc8561b59.jpeg');

-- --------------------------------------------------------

--
-- Table structure for table `user_favorites`
--

CREATE TABLE `user_favorites` (
  `favorite_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_favorites`
--

INSERT INTO `user_favorites` (`favorite_id`, `user_id`, `event_id`, `added_at`) VALUES
(9, 5, 11, '2025-03-18 03:39:25'),
(12, 5, 10, '2025-03-18 12:22:41'),
(17, 5, 8, '2025-03-18 14:44:13');

-- --------------------------------------------------------

--
-- Table structure for table `user_preferences`
--

CREATE TABLE `user_preferences` (
  `user_preference_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `default_map_location` varchar(255) DEFAULT NULL,
  `default_map_zoom` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_preferences`
--

INSERT INTO `user_preferences` (`user_preference_id`, `user_id`, `default_map_location`, `default_map_zoom`, `created_at`, `updated_at`) VALUES
(1, 5, '52.588938159851,-2.1300601959229', 17, '2025-03-18 03:18:49', '2025-03-18 14:47:09');

-- --------------------------------------------------------

--
-- Table structure for table `user_questions`
--

CREATE TABLE `user_questions` (
  `question_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `name` varchar(100) NOT NULL,
  `question` text NOT NULL,
  `is_answered` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_questions`
--

INSERT INTO `user_questions` (`question_id`, `user_id`, `email`, `name`, `question`, `is_answered`, `created_at`) VALUES
(1, 5, 'aklsdjfl@gmail.com', 'Nishan', 'kalsjdflajdfs', 0, '2025-03-18 09:40:13'),
(2, 5, 'nishan@gmail.com', 'Nishan Nepali', 'asdklfja sd;klfjasd f;ajs fd', 0, '2025-03-18 14:45:27');

-- --------------------------------------------------------

--
-- Table structure for table `user_schedules`
--

CREATE TABLE `user_schedules` (
  `schedule_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_schedules`
--

INSERT INTO `user_schedules` (`schedule_id`, `user_id`, `event_id`, `added_at`) VALUES
(16, 5, 11, '2025-03-18 10:42:51'),
(42, 5, 10, '2025-03-18 14:39:13'),
(46, 5, 9, '2025-03-18 14:44:00'),
(50, 5, 8, '2025-03-18 14:50:24');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_admin_username` (`username`),
  ADD KEY `idx_admin_email` (`email`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`event_id`),
  ADD KEY `idx_event_date` (`event_date`),
  ADD KEY `idx_event_department` (`department`),
  ADD KEY `idx_event_topic` (`topic`),
  ADD KEY `idx_event_tag` (`tag`),
  ADD KEY `idx_event_location` (`location`),
  ADD KEY `fk_events_admin` (`admin_id`);

--
-- Indexes for table `faqs`
--
ALTER TABLE `faqs`
  ADD PRIMARY KEY (`faq_id`),
  ADD KEY `idx_faqs_category` (`category`),
  ADD KEY `idx_faqs_is_published` (`is_published`);

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
-- Indexes for table `user_favorites`
--
ALTER TABLE `user_favorites`
  ADD PRIMARY KEY (`favorite_id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`event_id`),
  ADD KEY `event_id` (`event_id`);

--
-- Indexes for table `user_preferences`
--
ALTER TABLE `user_preferences`
  ADD PRIMARY KEY (`user_preference_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `user_questions`
--
ALTER TABLE `user_questions`
  ADD PRIMARY KEY (`question_id`),
  ADD KEY `idx_user_questions_email` (`email`),
  ADD KEY `idx_user_questions_is_answered` (`is_answered`),
  ADD KEY `idx_user_questions_user_id` (`user_id`);

--
-- Indexes for table `user_schedules`
--
ALTER TABLE `user_schedules`
  ADD PRIMARY KEY (`schedule_id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`event_id`),
  ADD KEY `event_id` (`event_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `faqs`
--
ALTER TABLE `faqs`
  MODIFY `faq_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `guests`
--
ALTER TABLE `guests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `user_favorites`
--
ALTER TABLE `user_favorites`
  MODIFY `favorite_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `user_preferences`
--
ALTER TABLE `user_preferences`
  MODIFY `user_preference_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `user_questions`
--
ALTER TABLE `user_questions`
  MODIFY `question_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `user_schedules`
--
ALTER TABLE `user_schedules`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD CONSTRAINT `contact_messages_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `fk_events_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`admin_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_favorites`
--
ALTER TABLE `user_favorites`
  ADD CONSTRAINT `user_favorites_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_favorites_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_preferences`
--
ALTER TABLE `user_preferences`
  ADD CONSTRAINT `user_preferences_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_questions`
--
ALTER TABLE `user_questions`
  ADD CONSTRAINT `user_questions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `user_schedules`
--
ALTER TABLE `user_schedules`
  ADD CONSTRAINT `user_schedules_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_schedules_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
