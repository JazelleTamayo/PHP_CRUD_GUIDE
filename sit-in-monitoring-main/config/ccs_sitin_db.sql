-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 10, 2026 at 05:20 AM
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
-- Database: `ccs_sitin_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `author` varchar(100) DEFAULT 'CCS Admin',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `content`, `author`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Welcome to CCS Sit-in System', 'We are excited to announce the launch of our new sit-in monitoring system! 🎉', 'CCS Admin', 1, '2026-04-10 00:57:42', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `announcement_reads`
--

CREATE TABLE `announcement_reads` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `announcement_id` int(11) NOT NULL,
  `read_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcement_reads`
--

INSERT INTO `announcement_reads` (`id`, `user_id`, `announcement_id`, `read_at`) VALUES
(1, 2, 1, '2026-04-10 01:50:05');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'system',
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `title`, `message`, `link`, `is_read`, `created_at`) VALUES
(1, 1, 'welcome', 'Welcome to CCS Sit-in System', 'Welcome! You can now reserve laboratories and track your sit-in sessions.', 'dashboard.php', 0, '2026-04-10 01:30:22'),
(2, 1, 'reminder', 'Complete Your Profile', 'Please complete your profile information for better service.', 'edit_profile.php', 0, '2026-04-10 01:30:22'),
(3, 3, 'reward', 'Sit-in Completed & Reward Earned', 'Your sit-in session has been completed. You received 1 reward point(s)! You now have 1 reward point(s). Get 3 points to earn +1 session!', 'dashboard.php', 0, '2026-04-10 03:04:11'),
(4, 2, 'welcome', 'Welcome to CCS Sit-in System', 'Welcome! You can now reserve laboratories and track your sit-in sessions.', 'dashboard.php', 0, '2026-04-10 03:04:24'),
(5, 2, 'reward', 'Sit-in Completed & Reward Earned', 'Your sit-in session has been completed. You received 1 reward point(s)! You now have 1 reward point(s). Get 3 points to earn +1 session!', 'dashboard.php', 0, '2026-04-10 03:07:16'),
(6, 1, 'reward', 'Sit-in Completed & Reward Earned', 'Your sit-in session has been completed. You received 1 reward point(s)! You now have 1 reward point(s). Get 3 points to earn +1 session!', 'dashboard.php', 0, '2026-04-10 03:08:41');

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `id_number` varchar(50) NOT NULL,
  `name` varchar(150) NOT NULL,
  `purpose` varchar(100) NOT NULL,
  `laboratory` varchar(100) NOT NULL,
  `time_in` time NOT NULL,
  `reservation_date` date NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`id`, `user_id`, `id_number`, `name`, `purpose`, `laboratory`, `time_in`, `reservation_date`, `status`, `created_at`) VALUES
(1, 2, '21418470', 'Ejhie Pacs', 'Java', '530', '03:14:00', '2026-03-27', 'rejected', '2026-03-27 02:14:37'),
(2, 2, '21418470', 'Ejhie Pacs', 'PHP', '530', '03:24:00', '2026-03-27', 'approved', '2026-03-27 02:24:35'),
(3, 2, '21418470', 'Ejhie Pacs', 'ASP.Net', 'Lab 517', '16:16:00', '2026-03-27', 'approved', '2026-03-27 03:16:49'),
(4, 1, '21526785', 'CJ Charles', 'Thesis', '524', '04:37:00', '2026-03-27', 'pending', '2026-03-27 03:38:11');

-- --------------------------------------------------------

--
-- Table structure for table `sit_in`
--

CREATE TABLE `sit_in` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `id_number` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `purpose` varchar(255) NOT NULL,
  `laboratory` varchar(100) NOT NULL,
  `login_time` time NOT NULL,
  `logout_time` time DEFAULT NULL,
  `login_date` date NOT NULL,
  `status` enum('active','completed','cancelled') DEFAULT 'active',
  `reward_points_given` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sit_in`
--

INSERT INTO `sit_in` (`id`, `user_id`, `id_number`, `name`, `purpose`, `laboratory`, `login_time`, `logout_time`, `login_date`, `status`, `reward_points_given`, `created_at`) VALUES
(1, 2, '21418470', 'Ejhie Pacs', 'C Programming', '528', '08:33:41', '09:48:58', '2026-03-27', 'completed', 0, '2026-03-27 00:33:41'),
(2, 2, '21418470', 'Ejhie Pacs', 'Python', '528', '10:26:15', '11:17:53', '2026-03-27', 'completed', 0, '2026-03-27 02:26:15'),
(3, 1, '21526785', 'CJ Charles', 'ASP.Net', '530', '11:34:02', '11:08:41', '2026-03-27', 'completed', 1, '2026-03-27 03:34:02'),
(4, 3, '21515010', 'Althea Carpentero', 'PHP', '528', '12:02:33', '11:04:11', '2026-03-27', 'completed', 1, '2026-03-27 04:02:33'),
(5, 2, '21418470', 'Ejhie Pacs', 'Java', '526', '11:05:21', '11:07:16', '2026-04-10', 'completed', 1, '2026-04-10 03:05:21'),
(6, 2, '21418470', 'Ejhie Pacs', 'Python', '528', '11:07:30', NULL, '2026-04-10', 'active', 0, '2026-04-10 03:07:30');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `id_number` varchar(20) NOT NULL,
  `course` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `year_level` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `address` text NOT NULL,
  `password` varchar(255) NOT NULL,
  `sessions` int(11) DEFAULT 30,
  `reward_points` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `id_number`, `course`, `last_name`, `first_name`, `middle_name`, `year_level`, `email`, `address`, `password`, `sessions`, `reward_points`) VALUES
(1, '21526785', 'BSIT', 'Charles', 'CJ', 'R', 3, 'charlescj1203@gmail.com', 'T.Padilla St.,Cebu City', '$2y$10$HmGIdz.M/fmbOLmQLsJ5juM9UFWZoMTaeo3wUDgvjHsb/kpysOGlS', 29, 1),
(2, '21418470', 'BSIT', 'Pacs', 'Ejhie', '', 3, 'ejhiepacquiao108@gmail.com', 'mingla', '$2y$10$JLu9vBZSyuEo4Bbumju8UOHHikODYziXPz3/ScHVjZS.blfdyXuLK', 27, 1),
(3, '21515010', 'BSIT', 'Carpentero', 'Althea', 'Buhawe', 3, 'altheakathleen@gmail.com', 'Capitol, Cebu City', '$2y$10$xbrO3fUjLynh8AT1e43tneQ41HDjZDmLe89EOHjt7wXLfG4tzFaq6', 29, 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `announcement_reads`
--
ALTER TABLE `announcement_reads`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_read` (`user_id`,`announcement_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `is_read` (`is_read`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `sit_in`
--
ALTER TABLE `sit_in`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_number` (`id_number`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `announcement_reads`
--
ALTER TABLE `announcement_reads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `sit_in`
--
ALTER TABLE `sit_in`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sit_in`
--
ALTER TABLE `sit_in`
  ADD CONSTRAINT `sit_in_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
