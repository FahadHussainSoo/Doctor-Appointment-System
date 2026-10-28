-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 24, 2025 at 07:51 AM
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
-- Database: `doctor_booking_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `status` enum('scheduled','confirmed','completed','cancelled') DEFAULT 'scheduled',
  `symptoms` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `patient_id`, `doctor_id`, `appointment_date`, `appointment_time`, `status`, `symptoms`, `notes`, `created_at`) VALUES
(1, 2, 4, '2025-10-24', '09:00:00', 'scheduled', 'This is sample symptoms', '', '2025-10-23 08:20:58'),
(2, 2, 4, '2025-10-24', '09:30:00', 'scheduled', 'Sample symptoms', '', '2025-10-24 05:05:55');

-- --------------------------------------------------------

--
-- Table structure for table `doctors`
--

CREATE TABLE `doctors` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `specialty_id` int(11) DEFAULT NULL,
  `qualification` text DEFAULT NULL,
  `experience_years` int(11) DEFAULT NULL,
  `consultation_fee` decimal(10,2) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `available_days` varchar(50) DEFAULT NULL,
  `available_time_start` time DEFAULT NULL,
  `available_time_end` time DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctors`
--

INSERT INTO `doctors` (`id`, `user_id`, `specialty_id`, `qualification`, `experience_years`, `consultation_fee`, `bio`, `available_days`, `available_time_start`, `available_time_end`, `status`, `created_at`) VALUES
(1, 2, 1, 'MD Cardiology, Board Certified', 15, 200.00, 'Dr. John Smith is a renowned cardiologist with over 15 years of experience in treating heart conditions.', 'Mon,Wed,Fri', '09:00:00', '17:00:00', 'active', '2025-10-23 08:19:00'),
(2, 3, 2, 'MD Dermatology, Board Certified', 10, 150.00, 'Dr. Sarah Johnson specializes in skin diseases and cosmetic dermatology with a patient-centered approach.', 'Tue,Thu,Sat', '10:00:00', '18:00:00', 'active', '2025-10-23 08:19:00'),
(3, 4, 3, 'MD Neurology, PhD Neuroscience', 12, 180.00, 'Dr. Michael Brown is an expert in neurological disorders with extensive research experience.', 'Mon,Tue,Wed,Thu', '08:00:00', '16:00:00', 'active', '2025-10-23 08:19:00'),
(4, 5, 4, 'MD Pediatrics, Board Certified', 8, 120.00, 'Dr. Emily Davis is dedicated to providing comprehensive healthcare for children from infancy through adolescence.', 'Mon-Fri', '09:00:00', '17:00:00', 'active', '2025-10-23 08:19:00');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `specialties`
--

CREATE TABLE `specialties` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `specialties`
--

INSERT INTO `specialties` (`id`, `name`, `description`, `icon`, `created_at`) VALUES
(1, 'Cardiology', 'Heart and cardiovascular system specialists', 'heart', '2025-10-23 08:19:00'),
(2, 'Dermatology', 'Skin, hair, and nail condition specialists', 'skin', '2025-10-23 08:19:00'),
(3, 'Neurology', 'Brain and nervous system specialists', 'brain', '2025-10-23 08:19:00'),
(4, 'Pediatrics', 'Child healthcare specialists', 'child', '2025-10-23 08:19:00'),
(5, 'Orthopedics', 'Bone and joint specialists', 'bone', '2025-10-23 08:19:00'),
(6, 'Dentistry', 'Oral health and dental care specialists', 'tooth', '2025-10-23 08:19:00'),
(7, 'Psychiatry', 'Mental health and behavioral specialists', 'mind', '2025-10-23 08:19:00'),
(8, 'Ophthalmology', 'Eye and vision care specialists', 'eye', '2025-10-23 08:19:00'),
(9, 'Cardiology', 'Heart and cardiovascular system specialists', NULL, '2025-10-24 05:03:41'),
(10, 'Dermatology', 'Skin, hair, and nail condition specialists', NULL, '2025-10-24 05:03:41'),
(11, 'Neurology', 'Brain and nervous system specialists', NULL, '2025-10-24 05:03:41'),
(12, 'Pediatrics', 'Child healthcare specialists', NULL, '2025-10-24 05:03:41'),
(13, 'Orthopedics', 'Bone and joint specialists', NULL, '2025-10-24 05:03:41');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `address` text DEFAULT NULL,
  `role` enum('patient','admin','doctor') DEFAULT 'patient',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `password`, `phone`, `date_of_birth`, `address`, `role`, `created_at`, `updated_at`) VALUES
(1, 'Admin', 'User', 'admin@medibook.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+1234567890', NULL, NULL, 'admin', '2025-10-23 08:19:00', '2025-10-23 08:19:00'),
(2, 'John', 'Smith', 'john.smith@medibook.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+1234567891', NULL, NULL, 'patient', '2025-10-23 08:19:00', '2025-10-23 08:19:00'),
(3, 'Sarah', 'Johnson', 'sarah.johnson@medibook.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+1234567892', NULL, NULL, 'doctor', '2025-10-23 08:19:00', '2025-10-24 05:44:04'),
(4, 'Michael', 'Brown', 'michael.brown@medibook.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+1234567893', NULL, NULL, 'doctor', '2025-10-23 08:19:00', '2025-10-24 05:43:56'),
(5, 'Emily', 'Davis', 'emily.davis@medibook.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+1234567894', NULL, NULL, 'doctor', '2025-10-23 08:19:00', '2025-10-24 05:43:48');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `doctor_id` (`doctor_id`);

--
-- Indexes for table `doctors`
--
ALTER TABLE `doctors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `specialty_id` (`specialty_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `appointment_id` (`appointment_id`);

--
-- Indexes for table `specialties`
--
ALTER TABLE `specialties`
  ADD PRIMARY KEY (`id`);

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
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `doctors`
--
ALTER TABLE `doctors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `specialties`
--
ALTER TABLE `specialties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`);

--
-- Constraints for table `doctors`
--
ALTER TABLE `doctors`
  ADD CONSTRAINT `doctors_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `doctors_ibfk_2` FOREIGN KEY (`specialty_id`) REFERENCES `specialties` (`id`);

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`),
  ADD CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
