-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 24, 2026 at 05:37 PM
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
-- Database: `diabetrack`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `activity_name` varchar(100) NOT NULL,
  `duration_minutes` int(11) NOT NULL,
  `intensity` enum('Light','Moderate','Intense') NOT NULL,
  `notes` text DEFAULT NULL,
  `logged_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `patient_id`, `activity_name`, `duration_minutes`, `intensity`, `notes`, `logged_at`) VALUES
(1, 3, 'Dancing', 14, 'Light', NULL, '2026-04-28 15:09:34'),
(2, 3, 'Exercise', 90, 'Moderate', 'jog', '2026-05-05 01:52:46'),
(3, 8, 'Dancing', 30, 'Moderate', NULL, '2026-05-14 10:30:48'),
(4, 8, 'Sports', 50, 'Light', NULL, '2026-05-14 10:31:43'),
(5, 8, 'Walking', 10, 'Intense', NULL, '2026-05-14 10:31:50'),
(6, 8, 'Dancing', 4, 'Light', NULL, '2026-05-15 09:33:36'),
(8, 8, 'Gym', 65, 'Light', NULL, '2026-05-16 13:51:45'),
(9, 8, 'Yoga', 30, 'Moderate', NULL, '2026-05-17 01:35:25'),
(10, 8, 'Yoga', 30, 'Light', NULL, '2026-05-19 12:15:57'),
(11, 8, 'Run', 30, 'Light', NULL, '2026-05-21 14:54:44'),
(12, 8, 'Yoga', 30, 'Light', NULL, '2026-05-22 01:45:21'),
(13, 8, 'Run', 30, 'Light', NULL, '2026-05-23 07:38:35'),
(14, 8, 'Run', 30, 'Moderate', NULL, '2026-05-23 13:58:06'),
(16, 8, 'Swim', 30, 'Intense', NULL, '2026-05-23 14:10:56'),
(17, 8, 'Gym', 60, 'Moderate', NULL, '2026-05-23 14:11:03'),
(18, 8, 'Swim', 30, 'Light', NULL, '2026-05-23 14:22:53'),
(19, 8, 'Yoga', 30, 'Moderate', NULL, '2026-05-24 02:03:12');

-- --------------------------------------------------------

--
-- Table structure for table `alerts`
--

CREATE TABLE `alerts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `type` enum('High Sugar','Low Sugar','Missed Dose','Carb Overload','Inactivity','Appointment') NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `alerts`
--

INSERT INTO `alerts` (`id`, `user_id`, `patient_id`, `type`, `message`, `is_read`, `created_at`) VALUES
(1, 3, 3, 'High Sugar', 'Blood sugar reading of 319 mg/dL is above the safe limit (180 mg/dL).', 1, '2026-04-28 15:09:40'),
(2, 2, 3, 'High Sugar', 'Blood sugar reading of 319 mg/dL is above the safe limit (180 mg/dL).', 0, '2026-04-28 15:09:40'),
(3, 3, 3, 'High Sugar', 'Blood sugar reading of 204 mg/dL is above the safe limit (180 mg/dL).', 1, '2026-04-28 15:10:17'),
(4, 2, 3, 'High Sugar', 'Blood sugar reading of 204 mg/dL is above the safe limit (180 mg/dL).', 0, '2026-04-28 15:10:17'),
(5, 3, 3, 'Missed Dose', 'Medication \'insulin\' was not taken as scheduled.', 1, '2026-05-04 14:59:51'),
(6, 2, 3, 'Missed Dose', 'Medication \'insulin\' was not taken as scheduled.', 0, '2026-05-04 14:59:51'),
(7, 3, 3, 'High Sugar', 'Blood sugar reading of 379 mg/dL is above the safe limit (180 mg/dL).', 1, '2026-05-05 01:51:36'),
(8, 2, 3, 'High Sugar', 'Blood sugar reading of 379 mg/dL is above the safe limit (180 mg/dL).', 0, '2026-05-05 01:51:36'),
(9, 3, 3, 'High Sugar', 'Blood sugar reading of 206 mg/dL is above the safe limit (180 mg/dL).', 1, '2026-05-05 03:27:59'),
(10, 2, 3, 'High Sugar', 'Blood sugar reading of 206 mg/dL is above the safe limit (180 mg/dL).', 0, '2026-05-05 03:27:59'),
(11, 8, 8, 'High Sugar', 'Blood sugar reading of 302 mg/dL is above the safe limit (180 mg/dL).', 1, '2026-05-14 09:58:14'),
(12, 10, 8, 'High Sugar', 'Blood sugar reading of 302 mg/dL is above the safe limit (180 mg/dL).', 0, '2026-05-14 09:58:14'),
(13, 8, 8, 'High Sugar', 'Blood sugar reading of 282 mg/dL is above the safe limit (180 mg/dL).', 1, '2026-05-16 01:45:13'),
(14, 10, 8, 'High Sugar', 'Blood sugar reading of 282 mg/dL is above the safe limit (180 mg/dL).', 0, '2026-05-16 01:45:13'),
(15, 8, 8, 'Low Sugar', 'Blood sugar reading of 42 mg/dL is below the safe limit (70 mg/dL).', 1, '2026-05-16 01:45:29'),
(16, 10, 8, 'Low Sugar', 'Blood sugar reading of 42 mg/dL is below the safe limit (70 mg/dL).', 0, '2026-05-16 01:45:29'),
(17, 8, 8, 'Missed Dose', 'Medication \'insulin\' was not taken as scheduled.', 1, '2026-05-16 01:54:22'),
(18, 10, 8, 'Missed Dose', 'Medication \'insulin\' was not taken as scheduled.', 0, '2026-05-16 01:54:22'),
(19, 8, 8, 'Missed Dose', 'Medication \'biogesic\' was not taken as scheduled.', 1, '2026-05-16 01:55:30'),
(20, 10, 8, 'Missed Dose', 'Medication \'biogesic\' was not taken as scheduled.', 0, '2026-05-16 01:55:30'),
(21, 8, 8, 'Carb Overload', 'Daily carbohydrate intake has exceeded the recommended 130g limit. Total today: 134.00g.', 1, '2026-05-16 02:14:36'),
(22, 8, 8, 'High Sugar', 'Blood sugar reading of 209 mg/dL is above the safe limit (180 mg/dL).', 1, '2026-05-16 06:59:41'),
(23, 10, 8, 'High Sugar', 'Blood sugar reading of 209 mg/dL is above the safe limit (180 mg/dL).', 0, '2026-05-16 06:59:41'),
(24, 8, 8, 'High Sugar', 'Blood sugar reading of 206 mg/dL is above the safe limit (180 mg/dL).', 1, '2026-05-16 07:08:13'),
(25, 10, 8, 'High Sugar', 'Blood sugar reading of 206 mg/dL is above the safe limit (180 mg/dL).', 0, '2026-05-16 07:08:13'),
(26, 8, 8, 'High Sugar', 'Blood sugar reading of 214 mg/dL is above the safe limit (180 mg/dL).', 1, '2026-05-16 07:08:44'),
(27, 10, 8, 'High Sugar', 'Blood sugar reading of 214 mg/dL is above the safe limit (180 mg/dL).', 0, '2026-05-16 07:08:44'),
(28, 8, 8, 'Missed Dose', 'Medication \'cetrizin\' was not taken as scheduled.', 1, '2026-05-16 07:15:54'),
(29, 10, 8, 'Missed Dose', 'Medication \'cetrizin\' was not taken as scheduled.', 0, '2026-05-16 07:15:54'),
(30, 8, 8, 'Low Sugar', 'Blood sugar reading of 40 mg/dL is below the safe limit (70 mg/dL).', 1, '2026-05-16 13:59:21'),
(31, 10, 8, 'Low Sugar', 'Blood sugar reading of 40 mg/dL is below the safe limit (70 mg/dL).', 0, '2026-05-16 13:59:21'),
(32, 8, 8, 'Missed Dose', 'Medication \'cetrizin\' was not taken as scheduled.', 1, '2026-05-17 01:39:05'),
(33, 10, 8, 'Missed Dose', 'Medication \'cetrizin\' was not taken as scheduled.', 0, '2026-05-17 01:39:05'),
(34, 8, 8, 'Missed Dose', 'Medication \'n\' was not taken as scheduled.', 1, '2026-05-17 14:47:59'),
(35, 10, 8, 'Missed Dose', 'Medication \'n\' was not taken as scheduled.', 0, '2026-05-17 14:47:59'),
(36, 8, 8, 'Carb Overload', 'Daily carbohydrate intake has exceeded the recommended 130g limit. Total today: 156.00g.', 1, '2026-05-19 06:05:12'),
(37, 8, 8, 'Carb Overload', 'Daily carbohydrate intake has exceeded the recommended 130g limit. Total today: 140.00g.', 1, '2026-05-20 04:12:05'),
(38, 8, 8, 'Missed Dose', 'Medication \'insulin\' was not taken as scheduled.', 1, '2026-05-20 14:44:43'),
(39, 10, 8, 'Missed Dose', 'Medication \'insulin\' was not taken as scheduled.', 0, '2026-05-20 14:44:43'),
(40, 8, 8, 'Missed Dose', 'Medication \'insulin\' was not taken as scheduled.', 1, '2026-05-20 14:44:46'),
(41, 10, 8, 'Missed Dose', 'Medication \'insulin\' was not taken as scheduled.', 0, '2026-05-20 14:44:46'),
(42, 8, 8, 'Missed Dose', 'Medication \'insulin\' was not taken as scheduled.', 1, '2026-05-20 14:44:49'),
(43, 10, 8, 'Missed Dose', 'Medication \'insulin\' was not taken as scheduled.', 0, '2026-05-20 14:44:49'),
(44, 8, 8, 'Missed Dose', 'Medication \'insulin\' was not taken as scheduled.', 1, '2026-05-20 14:44:51'),
(45, 10, 8, 'Missed Dose', 'Medication \'insulin\' was not taken as scheduled.', 0, '2026-05-20 14:44:51'),
(46, 8, 8, 'High Sugar', 'Blood sugar reading of 263 mg/dL is above the safe limit (180 mg/dL).', 1, '2026-05-21 14:43:40'),
(47, 10, 8, 'High Sugar', 'Blood sugar reading of 263 mg/dL is above the safe limit (180 mg/dL).', 0, '2026-05-21 14:43:40'),
(48, 8, 8, 'High Sugar', 'Blood sugar reading of 257 mg/dL is above the safe limit (180 mg/dL).', 1, '2026-05-23 02:40:59'),
(49, 10, 8, 'High Sugar', 'Blood sugar reading of 257 mg/dL is above the safe limit (180 mg/dL).', 0, '2026-05-23 02:40:59'),
(50, 8, 8, 'Low Sugar', 'Blood sugar reading of 41 mg/dL is below the safe limit (70 mg/dL).', 1, '2026-05-23 02:41:05'),
(51, 10, 8, 'Low Sugar', 'Blood sugar reading of 41 mg/dL is below the safe limit (70 mg/dL).', 0, '2026-05-23 02:41:05'),
(52, 8, 8, 'High Sugar', 'Blood sugar reading of 385 mg/dL is above the safe limit (180 mg/dL).', 1, '2026-05-23 02:41:37'),
(53, 10, 8, 'High Sugar', 'Blood sugar reading of 385 mg/dL is above the safe limit (180 mg/dL).', 0, '2026-05-23 02:41:37');

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_name` varchar(100) NOT NULL,
  `appointment_date` datetime NOT NULL,
  `status` enum('Upcoming','Completed','Cancelled') NOT NULL DEFAULT 'Upcoming',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `patient_id`, `doctor_name`, `appointment_date`, `status`, `notes`, `created_at`) VALUES
(1, 3, 'Dr Santos', '2026-05-07 14:25:00', 'Cancelled', NULL, '2026-05-03 06:25:13'),
(2, 3, 'dr heaven', '2026-05-05 09:47:00', 'Upcoming', 'a', '2026-05-05 01:47:54'),
(3, 8, 'Dr Santos', '2026-05-18 06:00:00', 'Upcoming', NULL, '2026-05-17 14:57:22');

-- --------------------------------------------------------

--
-- Table structure for table `blood_sugar_logs`
--

CREATE TABLE `blood_sugar_logs` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `reading` decimal(5,2) NOT NULL,
  `reading_type` enum('Before Meal','After Meal','Fasting','Bedtime') NOT NULL,
  `status` enum('Low','Normal','High') NOT NULL,
  `notes` text DEFAULT NULL,
  `logged_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `blood_sugar_logs`
--

INSERT INTO `blood_sugar_logs` (`id`, `patient_id`, `reading`, `reading_type`, `status`, `notes`, `logged_at`) VALUES
(9, 3, 120.00, 'Before Meal', 'Normal', '', '2026-04-02 09:35:54'),
(10, 3, 257.00, 'Before Meal', 'High', '', '2026-04-02 09:45:06'),
(11, 3, 53.00, 'Before Meal', 'Low', '', '2026-04-02 09:45:26'),
(12, 4, 120.00, 'Before Meal', 'Normal', '', '2026-04-03 02:57:34'),
(13, 3, 120.00, 'Before Meal', 'Normal', '', '2026-04-03 08:35:46'),
(14, 3, 319.00, 'Before Meal', 'High', '', '2026-04-28 15:09:40'),
(15, 3, 204.00, 'Before Meal', 'High', '', '2026-04-28 15:10:17'),
(16, 3, 157.00, 'After Meal', 'Normal', 'felt sick', '2026-05-05 01:51:28'),
(17, 3, 379.00, 'Before Meal', 'High', '', '2026-05-05 01:51:36'),
(18, 3, 120.00, 'Before Meal', 'Normal', '', '2026-05-05 03:27:44'),
(19, 3, 206.00, 'After Meal', 'High', 'nagkaaon adobo rice', '2026-05-05 03:27:59'),
(21, 8, 120.00, 'Before Meal', 'Normal', '', '2026-05-14 09:58:03'),
(23, 8, 72.00, 'Before Meal', 'Normal', '', '2026-05-14 10:30:14'),
(24, 8, 282.00, 'Before Meal', 'High', '', '2026-05-16 01:45:13'),
(25, 8, 120.00, 'After Meal', 'Normal', '', '2026-05-16 01:45:24'),
(26, 8, 42.00, 'Fasting', 'Low', '', '2026-05-16 01:45:29'),
(27, 8, 120.00, 'After Meal', 'Normal', '', '2026-05-16 06:46:13'),
(31, 8, 120.00, 'Before Meal', 'Normal', '', '2026-05-16 07:00:07'),
(32, 8, 206.00, 'Before Meal', 'High', '', '2026-05-16 07:08:13'),
(33, 8, 214.00, 'Before Meal', 'High', '', '2026-05-16 07:08:44'),
(34, 8, 120.00, 'Before Meal', 'Normal', '', '2026-05-16 13:53:54'),
(35, 8, 40.00, 'After Meal', 'Low', '', '2026-05-16 13:59:21'),
(36, 8, 120.00, 'After Meal', 'Normal', '', '2026-05-16 14:05:23'),
(37, 8, 156.00, 'After Meal', 'Normal', '', '2026-05-17 01:32:43'),
(38, 8, 174.00, 'Before Meal', 'Normal', '', '2026-05-18 01:05:18'),
(39, 8, 120.00, 'Bedtime', 'Normal', '', '2026-05-19 12:17:02'),
(40, 8, 263.00, 'After Meal', 'High', 'ate so much puto', '2026-05-21 14:43:40'),
(41, 8, 120.00, 'Before Meal', 'Normal', '', '2026-05-23 02:32:31'),
(42, 8, 257.00, 'Before Meal', 'High', '', '2026-05-23 02:40:59'),
(43, 8, 41.00, 'Before Meal', 'Low', '', '2026-05-23 02:41:05');

-- --------------------------------------------------------

--
-- Table structure for table `caregiver_links`
--

CREATE TABLE `caregiver_links` (
  `id` int(11) NOT NULL,
  `caregiver_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `relationship_to_patient` varchar(100) DEFAULT NULL,
  `status` enum('pending','accepted','declined') NOT NULL DEFAULT 'pending',
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `linked_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `caregiver_links`
--

INSERT INTO `caregiver_links` (`id`, `caregiver_id`, `patient_id`, `relationship_to_patient`, `status`, `requested_at`, `linked_at`) VALUES
(12, 2, 6, NULL, 'accepted', '2026-04-28 15:05:54', '2026-04-28 15:06:11'),
(13, 2, 3, NULL, 'accepted', '2026-05-02 15:26:02', '2026-05-02 15:41:59'),
(16, 10, 8, 'Parent', 'accepted', '2026-05-24 15:16:09', '2026-05-24 15:16:41');

-- --------------------------------------------------------

--
-- Table structure for table `caregiver_profiles`
--

CREATE TABLE `caregiver_profiles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `caregiver_profiles`
--

INSERT INTO `caregiver_profiles` (`id`, `user_id`, `contact_number`, `address`, `created_at`) VALUES
(1, 2, NULL, NULL, '2026-05-24 09:52:08'),
(2, 10, NULL, NULL, '2026-05-24 09:52:08'),
(4, 13, '09393994849', 'Philippines', '2026-05-24 10:07:43');

-- --------------------------------------------------------

--
-- Table structure for table `education_content`
--

CREATE TABLE `education_content` (
  `id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `content` text NOT NULL,
  `type` enum('Article','Tip','Video','Myth vs Fact') NOT NULL,
  `status` enum('Draft','Published') NOT NULL DEFAULT 'Published',
  `uploaded_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `meal_logs`
--

CREATE TABLE `meal_logs` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `meal_name` varchar(100) NOT NULL,
  `carbs` decimal(6,2) NOT NULL,
  `calories` decimal(7,2) DEFAULT NULL,
  `sugar` decimal(6,2) DEFAULT NULL,
  `fiber` decimal(6,2) DEFAULT NULL,
  `protein` decimal(6,2) DEFAULT NULL,
  `fat` decimal(6,2) DEFAULT NULL,
  `sodium` decimal(7,2) DEFAULT NULL,
  `glycemic_index` int(11) DEFAULT NULL,
  `meal_type` enum('Breakfast','Lunch','Dinner','Snack') NOT NULL,
  `notes` text DEFAULT NULL,
  `logged_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `meal_logs`
--

INSERT INTO `meal_logs` (`id`, `patient_id`, `meal_name`, `carbs`, `calories`, `sugar`, `fiber`, `protein`, `fat`, `sodium`, `glycemic_index`, `meal_type`, `notes`, `logged_at`) VALUES
(1, 3, 'chicken and rice', 45.00, 120.00, 12.00, 3.00, 25.00, 8.00, 420.00, NULL, 'Lunch', 'ate at the restaurant', '2026-04-05 04:08:44'),
(2, 3, 'Cornbeef and Rice', 45.00, 245.00, 8.00, 5.00, 15.00, 18.00, 950.00, NULL, 'Dinner', 'Home Cooked', '2026-04-05 04:29:01'),
(3, 3, 'Oreo', 45.00, 120.00, 124.00, 23.00, 3.00, 25.00, 267.00, NULL, 'Snack', 'store bought', '2026-04-05 04:29:45'),
(4, 3, 'chicken', 42.00, 5.00, 5.00, 5.00, 5.00, 5.00, 55.00, NULL, 'Lunch', NULL, '2026-04-28 15:09:22'),
(5, 3, 'Sinangag (Fried Rice)', 45.00, 206.00, NULL, NULL, 4.00, 7.00, NULL, NULL, 'Breakfast', NULL, '2026-05-03 06:48:57'),
(6, 3, 'Lugaw (Rice Porridge)', 28.00, 130.00, NULL, NULL, 3.00, 1.00, NULL, NULL, 'Breakfast', NULL, '2026-05-03 06:49:18'),
(7, 3, 'monggo', 45.00, 5.00, 5.00, NULL, 5.00, 5.00, NULL, NULL, 'Lunch', NULL, '2026-05-04 15:11:55'),
(8, 3, 'monggo', 45.00, 5.00, 5.00, NULL, 5.00, 5.00, NULL, NULL, 'Lunch', NULL, '2026-05-04 15:15:31'),
(9, 3, 'tqtqtq', 5.00, 5.00, 5.00, 55.00, 5.00, 5.00, 5.00, 55, 'Lunch', NULL, '2026-05-04 15:16:33'),
(10, 3, 'Adobo (Chicken)', 5.00, 285.00, 1.00, NULL, 27.00, 17.00, NULL, NULL, 'Lunch', NULL, '2026-05-05 01:52:28'),
(11, 3, 'Adobo (Chicken)', 5.00, 285.00, 1.00, NULL, 27.00, 17.00, NULL, NULL, 'Lunch', NULL, '2026-05-05 03:29:10'),
(23, 8, 'Sinangag (Fried Rice)', 45.00, 206.00, NULL, NULL, 4.00, 7.00, NULL, NULL, 'Dinner', NULL, '2026-05-16 13:49:44'),
(25, 8, 'Sinangag (Fried Rice)', 45.00, 206.00, NULL, NULL, 4.00, 7.00, NULL, NULL, 'Lunch', NULL, '2026-05-16 13:53:48'),
(26, 8, 'Sinangag (Fried Rice)', 45.00, 206.00, NULL, NULL, 4.00, 7.00, NULL, NULL, 'Breakfast', NULL, '2026-05-17 01:38:44'),
(27, 8, 'Sinangag (Fried Rice)', 45.00, 206.00, NULL, NULL, 4.00, 7.00, NULL, NULL, 'Breakfast', NULL, '2026-05-19 06:04:31'),
(28, 8, 'Pandesal', 23.00, 120.00, 3.00, NULL, 4.00, 2.00, NULL, NULL, 'Breakfast', NULL, '2026-05-19 06:04:36'),
(29, 8, 'Hard Boiled Egg', 1.00, 78.00, 1.00, NULL, 6.00, 5.00, NULL, NULL, 'Snack', NULL, '2026-05-19 06:04:40'),
(30, 8, 'Lugaw (Rice Porridge)', 28.00, 130.00, NULL, NULL, 3.00, 1.00, NULL, NULL, 'Breakfast', NULL, '2026-05-19 06:04:46'),
(34, 8, 'Kalabasa', 40.00, 320.00, 5.00, 42.00, 2.00, 8.00, 230.00, NULL, 'Lunch', 'binili sa karenderya', '2026-05-19 12:12:43'),
(35, 8, 'Sinangag (Fried Rice)', 45.00, 206.00, NULL, 45.00, 4.00, 7.00, NULL, NULL, 'Breakfast', NULL, '2026-05-19 12:13:23'),
(36, 8, 'Sinangag (Fried Rice)', 45.00, 206.00, NULL, NULL, 4.00, 7.00, NULL, NULL, 'Breakfast', NULL, '2026-05-20 04:08:05'),
(37, 8, 'Sinangag (Fried Rice)', 45.00, 206.00, NULL, NULL, 4.00, 7.00, NULL, NULL, 'Breakfast', NULL, '2026-05-20 04:11:40'),
(38, 8, 'Adobo (Chicken)', 5.00, 285.00, 1.00, NULL, 27.00, 17.00, NULL, NULL, 'Lunch', NULL, '2026-05-20 04:11:55'),
(39, 8, 'Bangus Grilled', 45.00, 175.00, NULL, NULL, 26.00, 7.00, NULL, NULL, 'Dinner', NULL, '2026-05-20 04:12:05'),
(40, 8, 'Hard Boiled Egg', 1.00, 78.00, 1.00, NULL, 6.00, 5.00, NULL, NULL, 'Snack', NULL, '2026-05-20 14:42:27'),
(41, 8, 'Bangus Grilled', 45.00, 175.00, NULL, NULL, 26.00, 7.00, NULL, NULL, 'Dinner', NULL, '2026-05-20 14:43:16'),
(42, 8, 'Adobo (Chicken)', 5.00, 285.00, 1.00, NULL, 27.00, 17.00, NULL, NULL, 'Lunch', NULL, '2026-05-21 07:11:46'),
(43, 8, 'Steamed Rice (1 cup)', 45.00, 206.00, NULL, NULL, 4.00, NULL, NULL, NULL, 'Dinner', NULL, '2026-05-23 13:59:15'),
(44, 8, 'Tinola (Chicken Soup)', 6.00, 160.00, 2.00, NULL, 22.00, 6.00, NULL, NULL, 'Lunch', NULL, '2026-05-24 02:04:38');

-- --------------------------------------------------------

--
-- Table structure for table `meal_presets`
--

CREATE TABLE `meal_presets` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `meal_name` varchar(100) NOT NULL,
  `meal_type` enum('Breakfast','Lunch','Dinner','Snack') NOT NULL DEFAULT 'Lunch',
  `carbs` decimal(6,2) NOT NULL DEFAULT 0.00,
  `calories` decimal(7,2) DEFAULT NULL,
  `sugar` decimal(6,2) DEFAULT NULL,
  `protein` decimal(6,2) DEFAULT NULL,
  `fat` decimal(6,2) DEFAULT NULL,
  `fiber` decimal(6,2) DEFAULT NULL,
  `sodium` decimal(7,2) DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `meal_presets`
--

INSERT INTO `meal_presets` (`id`, `patient_id`, `meal_name`, `meal_type`, `carbs`, `calories`, `sugar`, `protein`, `fat`, `fiber`, `sodium`, `is_default`, `created_at`) VALUES
(4, 3, 'chicken', 'Lunch', 5.00, 5.00, 55.00, 5.00, 5.00, NULL, NULL, 0, '2026-05-04 15:08:45'),
(5, 3, 'Z', 'Lunch', 5.00, 5.00, 5.00, 5.00, 5.00, NULL, NULL, 0, '2026-05-04 15:09:18'),
(8, 3, 'tqtqtq', 'Lunch', 5.00, 5.00, 5.00, 5.00, 5.00, 55.00, 5.00, 0, '2026-05-04 15:16:01'),
(9, 8, 'monggo', 'Breakfast', 45.00, 4.00, 4.00, 4.00, 4.00, 4.00, 4.00, 0, '2026-05-16 02:08:12'),
(10, 8, 'chicken inasal', 'Lunch', 45.00, 45.00, 45.00, 45.00, 45.00, 4.00, 54.00, 0, '2026-05-16 02:19:54');

-- --------------------------------------------------------

--
-- Table structure for table `medications`
--

CREATE TABLE `medications` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `dosage` varchar(50) DEFAULT NULL,
  `schedule_time` time NOT NULL,
  `frequency` enum('Daily','Twice a day','Three times a day','Weekly') DEFAULT 'Daily',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medications`
--

INSERT INTO `medications` (`id`, `patient_id`, `name`, `dosage`, `schedule_time`, `frequency`, `created_at`) VALUES
(3, 4, 'dsd', '22', '10:03:00', 'Daily', '2026-04-03 02:57:47'),
(5, 3, 'insulin', '50', '10:17:00', 'Daily', '2026-05-05 01:17:07'),
(6, 3, 'insulin', '800mg', '09:52:00', 'Twice a day', '2026-05-05 01:52:07'),
(7, 3, 'Insuget Insulin', '46', '11:29:00', 'Daily', '2026-05-05 03:28:28'),
(16, 8, 'insulin', '51', '20:00:00', 'Twice a day', '2026-05-19 00:34:40'),
(17, 8, 'insulin', '40', '08:00:00', 'Twice a day', '2026-05-19 06:03:36'),
(18, 8, 'insulin', '40', '20:00:00', 'Twice a day', '2026-05-19 06:03:36');

-- --------------------------------------------------------

--
-- Table structure for table `medication_logs`
--

CREATE TABLE `medication_logs` (
  `id` int(11) NOT NULL,
  `medication_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `status` enum('Taken','Missed') NOT NULL,
  `logged_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medication_logs`
--

INSERT INTO `medication_logs` (`id`, `medication_id`, `patient_id`, `status`, `logged_at`) VALUES
(7, 6, 3, 'Taken', '2026-05-05 03:28:33'),
(25, 17, 8, 'Taken', '2026-05-19 12:15:44'),
(26, 16, 8, 'Taken', '2026-05-19 12:15:46'),
(27, 18, 8, 'Taken', '2026-05-19 12:15:47'),
(29, 17, 8, 'Missed', '2026-05-20 14:44:46'),
(30, 16, 8, 'Missed', '2026-05-20 14:44:49'),
(31, 18, 8, 'Missed', '2026-05-20 14:44:51');

-- --------------------------------------------------------

--
-- Table structure for table `patient_profiles`
--

CREATE TABLE `patient_profiles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `diabetes_type` enum('Type 1','Type 2','Gestational','Pre-diabetes') DEFAULT NULL,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_number` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patient_profiles`
--

INSERT INTO `patient_profiles` (`id`, `user_id`, `date_of_birth`, `diabetes_type`, `emergency_contact_name`, `emergency_contact_number`) VALUES
(1, 3, NULL, NULL, NULL, NULL),
(2, 4, NULL, NULL, NULL, NULL),
(3, 5, NULL, NULL, NULL, NULL),
(4, 6, NULL, NULL, NULL, NULL),
(5, 7, NULL, NULL, NULL, NULL),
(6, 8, NULL, NULL, NULL, NULL),
(8, 11, NULL, NULL, NULL, NULL),
(9, 12, '2026-05-24', 'Type 1', 'Mary Grace Ballentes', '091928489449');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `two_fa_secret` varchar(64) DEFAULT NULL,
  `two_fa_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `role` enum('patient','caregiver','admin') NOT NULL DEFAULT 'patient',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `onboarding_complete` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `two_fa_secret`, `two_fa_enabled`, `role`, `created_at`, `onboarding_complete`) VALUES
(2, 'francisco', 'franciscojohn241@gmail.com', '$2y$10$CIG8wwFfeczNMPls28kV8uvB1FHbGwxQtqHT18xK4gYeczQtiKO9i', 'FCBPZMCQGXYDDXLD', 1, 'caregiver', '2026-03-30 14:45:06', 1),
(3, 'Rona Mae', 'ronamae@gmail.com', '$2y$10$5I4sCTNKbAZrI8iT4QM2ouUs/d54xSstGxdMPd92UWm/rdS9iKquq', NULL, 0, 'patient', '2026-04-01 13:34:18', 1),
(4, 'Francis Pindoco', 'francis@gmail.com', '$2y$10$dEChX0Tym46ShlpwshYM8OVs9HDBZsHs1LlaHuYtt8EHUQOrz/LYu', NULL, 0, 'patient', '2026-04-03 02:56:59', 1),
(5, 'John Cena', 'youcantseeme@gmail.com', '$2y$10$n/c/yft9Jri6MuuEFWZUrO.lvgmBMmZVKFScxsXyuJJIpgyMrP/o2', NULL, 0, 'patient', '2026-04-03 02:58:33', 1),
(6, 'Heaven Miles Santillan', 'heaven@gmail.com', '$2y$10$jmADh6XZrMN9jBkdwswm7.QiCOjt9i9nzR7255I5/AqOPeXpK4dyi', NULL, 0, 'patient', '2026-04-28 15:05:23', 1),
(7, 'francisco baltazar', 'francis22@gmail.com', '$2y$10$j5x0LDBEJPdhW2bEGj/Gve/3olrTD/.3Cf/7nE63H8lRfsgGZc9IK', NULL, 0, 'patient', '2026-05-05 03:23:38', 1),
(8, 'Francis John Ballentes', 'francisgwapo123@gmail.com', '$2y$10$/WeS5xlwNY2PanJfLf1xgeCGSXudxuKeutxZb4DqzFoPap2e8kUdy', NULL, 0, 'patient', '2026-05-09 13:11:03', 1),
(10, 'Mary Grace Ballentes', 'marygrace@gmail.com', '$2y$10$L/.3TouME..0WFcWL/SmCOFErmDI3yNPNu5scRHkvXodHX4PQUihm', NULL, 0, 'caregiver', '2026-05-09 13:13:33', 1),
(11, 'francis gwapo', 'franicis@gmail.com', '$2y$10$mF5QsnkxcrGUmn1j.AHfHepo.RVArefuKCkVAshbpcj1BLW9XIllC', NULL, 0, 'patient', '2026-05-24 10:03:28', 1),
(12, 'gwapo', 'gwapo@gmail.com', '$2y$10$Hzno0Tl1IPpYFKYoDnH9nemd6v8yrXzbKJr1Y29gEn9GSCAusFVJ2', NULL, 0, 'patient', '2026-05-24 10:05:56', 1),
(13, 'gwapa', 'gwapa@gmail.com', '$2y$10$GueFysGMKcLtnoD005NmfuFmH/8TEvDIoKfrxcOXmQ68kfiy1ZZsW', NULL, 0, 'caregiver', '2026-05-24 10:07:43', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `alerts`
--
ALTER TABLE `alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `blood_sugar_logs`
--
ALTER TABLE `blood_sugar_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `caregiver_links`
--
ALTER TABLE `caregiver_links`
  ADD PRIMARY KEY (`id`),
  ADD KEY `caregiver_id` (`caregiver_id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `caregiver_profiles`
--
ALTER TABLE `caregiver_profiles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `education_content`
--
ALTER TABLE `education_content`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `meal_logs`
--
ALTER TABLE `meal_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `meal_presets`
--
ALTER TABLE `meal_presets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `medications`
--
ALTER TABLE `medications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `medication_logs`
--
ALTER TABLE `medication_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `medication_id` (`medication_id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `patient_profiles`
--
ALTER TABLE `patient_profiles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `alerts`
--
ALTER TABLE `alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `blood_sugar_logs`
--
ALTER TABLE `blood_sugar_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `caregiver_links`
--
ALTER TABLE `caregiver_links`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `caregiver_profiles`
--
ALTER TABLE `caregiver_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `education_content`
--
ALTER TABLE `education_content`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `meal_logs`
--
ALTER TABLE `meal_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `meal_presets`
--
ALTER TABLE `meal_presets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `medications`
--
ALTER TABLE `medications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `medication_logs`
--
ALTER TABLE `medication_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `patient_profiles`
--
ALTER TABLE `patient_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `alerts`
--
ALTER TABLE `alerts`
  ADD CONSTRAINT `alerts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `alerts_ibfk_2` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `blood_sugar_logs`
--
ALTER TABLE `blood_sugar_logs`
  ADD CONSTRAINT `blood_sugar_logs_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `caregiver_links`
--
ALTER TABLE `caregiver_links`
  ADD CONSTRAINT `caregiver_links_ibfk_1` FOREIGN KEY (`caregiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `caregiver_links_ibfk_2` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `caregiver_profiles`
--
ALTER TABLE `caregiver_profiles`
  ADD CONSTRAINT `caregiver_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `education_content`
--
ALTER TABLE `education_content`
  ADD CONSTRAINT `education_content_ibfk_1` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `meal_logs`
--
ALTER TABLE `meal_logs`
  ADD CONSTRAINT `meal_logs_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `meal_presets`
--
ALTER TABLE `meal_presets`
  ADD CONSTRAINT `meal_presets_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `medications`
--
ALTER TABLE `medications`
  ADD CONSTRAINT `medications_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `medication_logs`
--
ALTER TABLE `medication_logs`
  ADD CONSTRAINT `medication_logs_ibfk_1` FOREIGN KEY (`medication_id`) REFERENCES `medications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `medication_logs_ibfk_2` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `patient_profiles`
--
ALTER TABLE `patient_profiles`
  ADD CONSTRAINT `patient_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
