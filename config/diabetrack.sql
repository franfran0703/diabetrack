-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 05, 2026 at 05:07 PM
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
(13, 3, 120.00, 'Before Meal', 'Normal', '', '2026-04-03 08:35:46');

-- --------------------------------------------------------

--
-- Table structure for table `caregiver_links`
--

CREATE TABLE `caregiver_links` (
  `id` int(11) NOT NULL,
  `caregiver_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `linked_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `caregiver_links`
--

INSERT INTO `caregiver_links` (`id`, `caregiver_id`, `patient_id`, `linked_at`) VALUES
(11, 2, 3, '2026-04-05 04:27:33');

-- --------------------------------------------------------

--
-- Table structure for table `caregiver_profiles`
--

CREATE TABLE `caregiver_profiles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `relationship_to_patient` varchar(100) DEFAULT NULL COMMENT 'e.g. Son, Daughter, Spouse, Nurse',
  `contact_number` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(3, 3, 'Oreo', 45.00, 120.00, 124.00, 23.00, 3.00, 25.00, 267.00, NULL, 'Snack', 'store bought', '2026-04-05 04:29:45');

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
(4, 3, 'insulin', '45', '06:30:00', 'Daily', '2026-04-03 08:34:43');

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
(3, 4, 3, 'Taken', '2026-04-03 08:34:53');

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

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('patient','caregiver','admin') NOT NULL DEFAULT 'patient',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`) VALUES
(2, 'Francis John Ballentes', 'franciscojohn241@gmail.com', '$2y$10$CIG8wwFfeczNMPls28kV8uvB1FHbGwxQtqHT18xK4gYeczQtiKO9i', 'caregiver', '2026-03-30 14:45:06'),
(3, 'rona mae tababa', 'ronamae@gmail.com', '$2y$10$PwJ9Mcob0lnEvRHmMeN3HemKuz.x679GGMIyUkmIvB93B2OWUN3xW', 'patient', '2026-04-01 13:34:18'),
(4, 'Francis Pindoco', 'francis@gmail.com', '$2y$10$dEChX0Tym46ShlpwshYM8OVs9HDBZsHs1LlaHuYtt8EHUQOrz/LYu', 'patient', '2026-04-03 02:56:59'),
(5, 'John Cena', 'youcantseeme@gmail.com', '$2y$10$n/c/yft9Jri6MuuEFWZUrO.lvgmBMmZVKFScxsXyuJJIpgyMrP/o2', 'patient', '2026-04-03 02:58:33');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `alerts`
--
ALTER TABLE `alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `blood_sugar_logs`
--
ALTER TABLE `blood_sugar_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `caregiver_links`
--
ALTER TABLE `caregiver_links`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `caregiver_profiles`
--
ALTER TABLE `caregiver_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `education_content`
--
ALTER TABLE `education_content`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `meal_logs`
--
ALTER TABLE `meal_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `medications`
--
ALTER TABLE `medications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `medication_logs`
--
ALTER TABLE `medication_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `patient_profiles`
--
ALTER TABLE `patient_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
