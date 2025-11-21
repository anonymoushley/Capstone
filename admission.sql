-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 17, 2025 at 08:29 AM
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
-- Database: `admission`
--

-- --------------------------------------------------------

--
-- Table structure for table `academic_background`
--

CREATE TABLE `academic_background` (
  `id` int(11) NOT NULL,
  `personal_info_id` int(11) NOT NULL,
  `last_school_attended` varchar(255) NOT NULL,
  `strand_id` int(11) NOT NULL,
  `year_graduated` year(4) NOT NULL,
  `g11_1st_avg` decimal(4,2) NOT NULL,
  `g11_2nd_avg` decimal(4,2) NOT NULL,
  `g12_1st_avg` decimal(4,2) NOT NULL,
  `academic_award` enum('None','Honors','High Honors','Highest Honors') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `academic_background`
--

INSERT INTO `academic_background` (`id`, `personal_info_id`, `last_school_attended`, `strand_id`, `year_graduated`, `g11_1st_avg`, `g11_2nd_avg`, `g12_1st_avg`, `academic_award`, `created_at`) VALUES
(28, 25, 'NEGROS OCCIDENTAL HIGH SCHOOL', 1, '2015', 76.00, 65.00, 65.00, 'Honors', '2025-10-06 09:04:35'),
(29, 26, 'LA CONSOLACION COLLEGE', 2, '2025', 95.00, 96.00, 95.00, 'High Honors', '2025-10-10 13:31:17'),
(30, 27, 'DONA HORTENCIA MEMORIAL HIGH SCHOOL', 2, '2020', 90.00, 91.00, 94.00, 'High Honors', '2025-10-19 10:53:40'),
(66, 107, 'RAFAEL B LACSON MEMORIAL HIGH SCHOOL', 7, '2025', 95.00, 75.00, 85.00, 'Honors', '2025-11-07 09:26:55'),
(67, 108, 'SUM AG NATIONAL HIGH SCHOOL', 5, '2023', 95.00, 90.00, 85.00, 'High Honors', '2025-11-10 14:07:03'),
(68, 109, 'COLLEGIO STA ANA VICTORIAS', 6, '2025', 95.00, 98.00, 97.00, '', '2025-11-12 04:18:57'),
(69, 110, 'RAFAEL B LACSON MEMORIAL HIGH SCHOOL', 2, '2025', 95.00, 80.00, 85.00, 'Honors', '2025-11-12 07:21:08'),
(70, 111, 'LUIS HERVIAS NATIONAL HIGH SCHOOL', 1, '2025', 90.00, 90.00, 85.00, 'Honors', '2025-11-12 07:50:21'),
(71, 112, 'SUM AG NATIONAL HIGH SCHOOL', 5, '2025', 95.00, 96.00, 95.00, 'Highest Honors', '2025-11-12 12:21:34'),
(72, 113, 'LA CONSOLACION COLLEGE BACOLOD', 4, '2025', 90.00, 96.00, 85.00, 'High Honors', '2025-11-13 08:13:57'),
(73, 114, 'LUIS HERVIAS NATIONAL HIGH SCHOOL', 7, '2025', 90.00, 94.00, 90.00, 'Honors', '2025-11-14 04:00:30'),
(74, 115, 'LA CONSOLACION COLLEGE BACOLOD', 2, '2025', 95.00, 90.00, 95.00, 'Honors', '2025-11-14 04:08:27');

-- --------------------------------------------------------

--
-- Table structure for table `application_status`
--

CREATE TABLE `application_status` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `profile_completed` tinyint(1) DEFAULT 0,
  `exam_completed` tinyint(1) DEFAULT 0,
  `interview_completed` tinyint(1) DEFAULT 0,
  `application_completed` tinyint(1) DEFAULT 0,
  `exam_score` int(11) DEFAULT NULL,
  `exam_total_points` int(11) DEFAULT NULL,
  `interview_score` int(11) DEFAULT NULL,
  `interview_total_points` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `buildings`
--

CREATE TABLE `buildings` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `chair_id` int(11) DEFAULT NULL,
  `status` enum('active','archived') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `buildings`
--

INSERT INTO `buildings` (`id`, `name`, `chair_id`, `status`, `created_at`) VALUES
(1, 'LSAB', 3, 'active', '2025-10-19 08:56:40'),
(2, 'etgb', 3, 'active', '2025-11-08 06:54:14'),
(3, 'building alijis', 7, 'active', '2025-11-12 13:24:36');

-- --------------------------------------------------------

--
-- Table structure for table `chairperson_accounts`
--

CREATE TABLE `chairperson_accounts` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `designation` varchar(100) NOT NULL,
  `program` varchar(50) NOT NULL,
  `campus` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chairperson_accounts`
--

INSERT INTO `chairperson_accounts` (`id`, `name`, `username`, `password`, `designation`, `program`, `campus`, `created_at`) VALUES
(3, 'Chairperson Talisay', 'ccstalisay', '$2y$10$Iss2zcV/GoRJZbozB4mKdOU7BqbW38gsjejw.isYtbJLSVbBAJVUO', 'Program Chair', 'BSIS', 'Talisay', '2025-08-27 06:42:14'),
(7, 'Chairperson Alijis', 'ccsalijis', '$2y$10$Iss2zcV/GoRJZbozB4mKdOU7BqbW38gsjejw.isYtbJLSVbBAJVUO', 'Program Chair', 'BSIS', 'Alijis', '2025-11-03 16:00:59'),
(8, 'Chairperson Fortune', 'ccsfortunetowne', '$2y$10$Iss2zcV/GoRJZbozB4mKdOU7BqbW38gsjejw.isYtbJLSVbBAJVUO', 'Program Chair', 'BSIS', 'Fortune', '2025-11-03 16:00:59');

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `id` int(11) NOT NULL,
  `personal_info_id` int(11) NOT NULL,
  `g11_1st` varchar(255) NOT NULL,
  `g11_2nd` varchar(255) NOT NULL,
  `g12_1st` varchar(255) NOT NULL,
  `ncii` varchar(255) DEFAULT NULL,
  `guidance_cert` varchar(255) DEFAULT NULL,
  `additional_file` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `g11_1st_status` enum('Pending','Accepted','Rejected') DEFAULT 'Pending',
  `g11_2nd_status` enum('Pending','Accepted','Rejected') DEFAULT 'Pending',
  `g12_1st_status` enum('Pending','Accepted','Rejected') DEFAULT 'Pending',
  `ncii_status` enum('Pending','Accepted','Rejected') DEFAULT 'Pending',
  `guidance_cert_status` enum('Pending','Accepted','Rejected') DEFAULT 'Pending',
  `additional_file_status` enum('Pending','Accepted','Rejected') DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `documents`
--

INSERT INTO `documents` (`id`, `personal_info_id`, `g11_1st`, `g11_2nd`, `g12_1st`, `ncii`, `guidance_cert`, `additional_file`, `created_at`, `g11_1st_status`, `g11_2nd_status`, `g12_1st_status`, `ncii_status`, `guidance_cert_status`, `additional_file_status`) VALUES
(22, 25, '68e3866746c73_Screenshot 2024-11-04 203232.png', '68e38667488e9_Screenshot 2024-11-05 225403.png', '68e3866749010_Screenshot 2024-12-04 044614.png', '68e38667495e7_Screenshot 2025-01-09 224447.png', '68e3866749e44_Screenshot 2024-11-16 193325.png', '68e386674a405_Screenshot 2024-11-16 193734.png', '2025-10-06 09:05:43', 'Accepted', 'Accepted', 'Accepted', 'Accepted', 'Pending', 'Pending'),
(23, 26, '69018a758c0ab_Untitled design (1).png', '690b60cd6336f_368560793_10221522547884129_5779111916811399481_n.jpg', '6900cd4c22ac9_gc.jpg', '690e273323b3c_istockphoto-1463145425-612x612.jpg', '690e26ec1de15_Your paragraph text.png', '6908cf1707aa8_CLD.png', '2025-10-10 13:33:48', 'Accepted', 'Accepted', 'Accepted', 'Accepted', 'Pending', 'Pending'),
(24, 27, '68f7a72bdfa42_RobloxScreenShot20250727_074433666.png', '68f7a79b7f45a_RobloxScreenShot20250730_044554938.png', '68f7a75417db7_RobloxScreenShot20250730_043847341.png', '', '', '', '2025-10-19 10:54:19', 'Accepted', 'Accepted', 'Accepted', 'Accepted', 'Accepted', 'Accepted'),
(55, 107, '690dbb8920030_istockphoto-1463145425-612x612.jpg', '690dbb8920bb1_Your paragraph text.png', '690dbb89214d7_Your paragraph text.png', '690dbb8921ed9_Your paragraph text.png', '', '', '2025-11-07 09:27:37', 'Accepted', 'Accepted', 'Accepted', 'Accepted', 'Pending', 'Pending'),
(56, 108, '6911f1f85cba9_Your paragraph text.png', '6911f1f85dfa0_576598708_4036652966644706_7087242347509252687_n.jpg', '6911f1f85ee08_557543740_697894790058024_6966627441808617643_n.jpg', '', '', '', '2025-11-10 14:08:56', 'Accepted', 'Accepted', 'Accepted', 'Pending', 'Pending', 'Pending'),
(57, 109, '69140aea3796e_Untitled design (1).png', '69140aea38a0e_CLD.png', '69140b9670316_dg.jpg', '69140aea39c10_CLD.png', '', '', '2025-11-12 04:19:54', 'Accepted', 'Accepted', 'Accepted', 'Accepted', 'Pending', 'Pending'),
(58, 110, '691435860c5c0_557543740_697894790058024_6966627441808617643_n.jpg', '691435860d2d5_576598708_4036652966644706_7087242347509252687_n.jpg', '691435860db4d_CLD.png', '', '', '', '2025-11-12 07:21:42', 'Accepted', 'Accepted', 'Accepted', 'Pending', 'Pending', 'Pending'),
(59, 111, '69143c7f58cd7_CLD.png', '69143c7f5e29d_552771132_824798616564224_658779178776477722_n.jpg', '69143c7f5e98b_istockphoto-1463145425-612x612.jpg', '', '', '', '2025-11-12 07:51:27', 'Accepted', 'Accepted', 'Accepted', 'Pending', 'Pending', 'Pending'),
(60, 113, '6915945f88a46_CLD.png', '6915945f894d2_CLD.png', '691595ffd89ea_istockphoto-1463145425-612x612.jpg', '6915945f8b7d4_Your paragraph text.png', '', '', '2025-11-13 08:18:39', 'Accepted', 'Accepted', 'Accepted', 'Accepted', 'Pending', 'Pending'),
(61, 112, '691689a22c7f7_CLD.png', '691689a22f2d0_552771132_824798616564224_658779178776477722_n.jpg', '691689a22fc88_Your paragraph text.png', '', '', '', '2025-11-14 01:45:06', 'Accepted', 'Accepted', 'Accepted', 'Pending', 'Pending', 'Pending'),
(62, 114, '6916a9bfd9a3b_CLD.png', '6916a9bfdbda6_istockphoto-1463145425-612x612.jpg', '6916a9bfdcd6b_552771132_824798616564224_658779178776477722_n.jpg', '6916a9bfdd649_552771132_824798616564224_658779178776477722_n.jpg', '', '', '2025-11-14 04:02:07', 'Pending', 'Pending', 'Pending', 'Pending', 'Pending', 'Pending'),
(63, 115, '6916ab6d589a7_CLD.png', '6916ab6d5ac06_552771132_824798616564224_658779178776477722_n.jpg', '6916ab6d5c16f_Your paragraph text.png', '', '', '', '2025-11-14 04:09:17', 'Pending', 'Pending', 'Pending', 'Pending', 'Pending', 'Pending');

-- --------------------------------------------------------

--
-- Table structure for table `exam_answers`
--

CREATE TABLE `exam_answers` (
  `id` int(11) NOT NULL,
  `applicant_id` int(11) NOT NULL,
  `version_id` int(11) NOT NULL,
  `total_questions` int(11) DEFAULT NULL,
  `points_earned` decimal(6,2) DEFAULT 0.00,
  `points_possible` decimal(6,2) DEFAULT 0.00,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exam_answers`
--

INSERT INTO `exam_answers` (`id`, `applicant_id`, `version_id`, `total_questions`, `points_earned`, `points_possible`, `submitted_at`) VALUES
(42, 20260034, 30, 3, 2.00, 3.00, '2025-10-20 14:32:17'),
(43, 20260033, 30, 3, 3.00, 3.00, '2025-10-29 03:43:24'),
(47, 20261012, 29, 14, 12.00, 14.00, '2025-11-12 20:50:44'),
(48, 20261017, 29, 14, 13.00, 14.00, '2025-11-13 08:52:46');

-- --------------------------------------------------------

--
-- Table structure for table `exam_versions`
--

CREATE TABLE `exam_versions` (
  `id` int(11) NOT NULL,
  `version_name` varchar(255) NOT NULL,
  `is_published` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('Published','Unpublished') DEFAULT 'Unpublished',
  `is_archived` tinyint(1) DEFAULT 0,
  `time_limit` int(11) DEFAULT 60,
  `instructions` text DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `chair_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exam_versions`
--

INSERT INTO `exam_versions` (`id`, `version_name`, `is_published`, `created_at`, `status`, `is_archived`, `time_limit`, `instructions`, `published_at`, `chair_id`) VALUES
(29, 'A.Y. 2025-2026 Exam Day 1', 0, '2025-08-27 06:57:00', 'Unpublished', 0, 60, 'Answer all questions to the best of your ability.', NULL, 3),
(30, 'Sample 1', 0, '2025-09-24 06:18:02', 'Unpublished', 0, 60, 'answer all questions', NULL, 3),
(36, 'kdfjhhdkfghdf', 0, '2025-11-08 07:00:58', 'Unpublished', 1, 60, NULL, NULL, 3),
(37, 'jdjdkfhkdfddkjhfkd', 0, '2025-11-08 07:01:07', 'Unpublished', 1, 60, 'Answer all questions to the best of your ability.', NULL, 3),
(40, 'Exam Day 1', 0, '2025-11-09 16:16:09', 'Unpublished', 0, 60, 'Answer all questions to the best of your ability.', NULL, 7),
(41, 'jhjhkjh', 0, '2025-11-13 09:25:38', 'Unpublished', 1, 60, NULL, NULL, 3);

-- --------------------------------------------------------

--
-- Table structure for table `interviewers`
--

CREATE TABLE `interviewers` (
  `id` int(11) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `chairperson_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `interviewers`
--

INSERT INTO `interviewers` (`id`, `last_name`, `first_name`, `email`, `password`, `created_at`, `chairperson_id`) VALUES
(16, 'GUIAGOGO', 'MICHAEL', 'matyasaqoe@gmail.com', '$2y$10$7VozAvOTKv8knAnRkkk/ae4J31curZBe0w2f0u13EZhswUS8h/Oc6', '2025-11-07 23:39:59', 3),
(20, 'REGALADO', 'ASHLEY CARYLL', 'acregalado.chmsu@gmail.com', '$2y$10$E7bGAz4mTiK4dKnfnJpMFuyOUE4bcw0urMlW8waw63ofwvuhvCbSO', '2025-11-10 00:30:56', 3),
(21, 'TUMABING', 'DANES', 'danes.tumabing@chmsu.edu.ph', '$2y$10$Wmgi4ffRstHWEZ98cQdvbuj4X7.HZgzgwBkwUiXM.PHFZNVN8TnTS', '2025-11-13 17:17:36', 3);

-- --------------------------------------------------------

--
-- Table structure for table `interview_schedules`
--

CREATE TABLE `interview_schedules` (
  `id` int(11) NOT NULL,
  `event_date` date NOT NULL,
  `event_time` time NOT NULL,
  `venue` varchar(255) DEFAULT NULL,
  `chair_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `interview_schedules`
--

INSERT INTO `interview_schedules` (`id`, `event_date`, `event_time`, `venue`, `chair_id`, `created_at`) VALUES
(5, '2025-10-22', '05:31:00', 'LSAB - Room 311', NULL, '2025-10-21 21:31:11'),
(6, '2025-11-08', '12:00:00', 'LSAB - Room 311', 3, '2025-11-08 06:43:09'),
(9, '2025-11-29', '14:00:00', 'LSAB - Room 311', 3, '2025-11-13 09:15:12');

-- --------------------------------------------------------

--
-- Table structure for table `interview_schedule_applicants`
--

CREATE TABLE `interview_schedule_applicants` (
  `id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `applicant_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `interview_schedule_applicants`
--

INSERT INTO `interview_schedule_applicants` (`id`, `schedule_id`, `applicant_id`, `created_at`) VALUES
(5, 5, 20260034, '2025-10-21 21:31:20'),
(6, 5, 20260033, '2025-11-07 15:41:58'),
(7, 6, 20261012, '2025-11-12 20:51:53'),
(8, 9, 20261017, '2025-11-13 09:15:26');

-- --------------------------------------------------------

--
-- Table structure for table `personal_info`
--

CREATE TABLE `personal_info` (
  `id` int(11) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) NOT NULL,
  `date_of_birth` date NOT NULL,
  `age` int(11) NOT NULL,
  `sex` enum('Male','Female') NOT NULL,
  `contact_number` varchar(50) NOT NULL,
  `region` varchar(50) NOT NULL,
  `province` varchar(50) NOT NULL,
  `city` varchar(50) NOT NULL,
  `barangay` varchar(50) NOT NULL,
  `street_purok` varchar(50) NOT NULL,
  `id_picture` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `personal_info`
--

INSERT INTO `personal_info` (`id`, `last_name`, `first_name`, `middle_name`, `date_of_birth`, `age`, `sex`, `contact_number`, `region`, `province`, `city`, `barangay`, `street_purok`, `id_picture`, `created_at`) VALUES
(25, 'JARANILLA', 'KRIS', 'PANAGSAGAN', '2003-12-29', 21, 'Female', '09312312434', 'Western Visayas', 'Negros Occidental', 'City of Bacolod', 'Punta Taytay', 'Mangga', '68e385f787645.png', '2025-10-06 09:03:51'),
(26, 'REGALADO', 'ASHLEY CARYLL', '', '2003-12-10', 21, 'Female', '09704643801', 'Western Visayas', 'Negros Occidental', 'City of Bacolod', 'Punta Taytay', 'Mangga', '6908cf051e706.jpg', '2025-10-10 13:29:34'),
(27, 'GUIAGOGO', 'MARK LLOYD', 'MARFIL', '2001-12-22', 23, 'Male', '09614376716', 'Western Visayas', 'Negros Occidental', 'Pontevedra', 'Antipolo', 'Purok Lerio', '68f4c2ffa823f.png', '2025-10-19 10:52:47'),
(107, 'ROJO', 'MEG RYAN', '', '2003-06-23', 22, 'Male', '09369073906', 'Western Visayas', 'Negros Occidental', 'City of Escalante', 'Japitan', 'Gamboa', '690dbb299a60b.jpg', '2025-11-07 09:26:01'),
(108, 'Regalado', 'DENNISSE NICOLE', 'Ilon', '2005-08-18', 20, 'Female', '00999999922', 'Eastern Visayas', 'Eastern Samar', 'Oras', 'Factoria', 'Gamboa', '6911f14b7c0c8.jpg', '2025-11-10 14:06:03'),
(109, 'Casipe', 'Beverly', '', '2003-09-15', 22, 'Female', '09927786931', 'Western Visayas', 'Negros Occidental', 'City of Talisay', 'Zone 7 (Pob.)', 'Rizal Street', '69140a9b51602.jpg', '2025-11-12 04:18:35'),
(110, 'Orcajada', 'Gael Gabriel', '', '2003-09-30', 22, 'Female', '09704643801', 'Western Visayas', 'Negros Occidental', 'City of Talisay', 'Zone 7 (Pob.)', 'Gamboa', '6914353285556.jpg', '2025-11-12 07:20:18'),
(111, 'Villarna', 'Ryn Aj', 'Depositario', '2003-07-29', 22, 'Male', '00912345678', 'Western Visayas', 'Negros Occidental', 'City of Bacolod', 'Villamonte', 'Kabacawan', '69143bf98a573.jpg', '2025-11-12 07:49:13'),
(112, 'Ilon', 'Dennisse Nicole', 'REGALADO', '2005-08-18', 20, 'Female', '09123456788', 'Western Visayas', 'Negros Occidental', 'City of Bacolod', 'Sum-ag', 'San Jose I', '69147bbd2237c.jpg', '2025-11-12 12:21:17'),
(113, 'casipe', 'beverly', '', '2003-09-15', 22, 'Female', '09999765433', 'Western Visayas', 'Negros Occidental', 'City of Talisay', 'Zone 7 (Pob.)', 'Rizal Street', '6915929747929.jpg', '2025-11-13 08:11:03'),
(114, 'Dejan', 'Japheth', '', '2003-06-18', 22, 'Male', '09369073906', 'Western Visayas', 'Negros Occidental', 'City of Bacolod', 'Villamonte', 'Gamboa', '6916a928dfe47.jpg', '2025-11-14 03:59:36'),
(115, 'Gubac', 'John Patrick', '', '2003-12-29', 21, 'Male', '09369073906', 'Western Visayas', 'Negros Occidental', 'Enrique B. Magalona', 'San Jose', 'Gamboa', '6916ab185fdc2.jpg', '2025-11-14 04:07:52');

-- --------------------------------------------------------

--
-- Table structure for table `program_application`
--

CREATE TABLE `program_application` (
  `id` int(11) NOT NULL,
  `personal_info_id` int(11) NOT NULL,
  `campus` enum('Talisay','Alijis','Fortune','Binalbagan') NOT NULL,
  `college` enum('CCS') DEFAULT NULL,
  `program` enum('BSIS','BSIT') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `registration_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `program_application`
--

INSERT INTO `program_application` (`id`, `personal_info_id`, `campus`, `college`, `program`, `created_at`, `registration_id`) VALUES
(26, 25, 'Talisay', 'CCS', 'BSIS', '2025-10-06 09:04:45', NULL),
(27, 26, 'Talisay', 'CCS', 'BSIS', '2025-10-10 13:32:42', NULL),
(28, 27, 'Talisay', 'CCS', 'BSIS', '2025-10-19 10:53:49', NULL),
(59, 107, 'Alijis', 'CCS', 'BSIS', '2025-11-07 09:27:06', NULL),
(60, 108, 'Talisay', 'CCS', 'BSIS', '2025-11-10 14:07:28', NULL),
(61, 109, 'Talisay', 'CCS', 'BSIS', '2025-11-12 04:19:08', NULL),
(62, 110, 'Talisay', 'CCS', 'BSIS', '2025-11-12 07:21:19', NULL),
(63, 111, 'Talisay', 'CCS', 'BSIS', '2025-11-12 07:50:55', NULL),
(64, 112, 'Talisay', 'CCS', 'BSIS', '2025-11-12 12:21:48', NULL),
(65, 113, 'Talisay', 'CCS', 'BSIS', '2025-11-13 08:14:30', NULL),
(66, 114, 'Alijis', 'CCS', 'BSIS', '2025-11-14 04:01:19', NULL),
(67, 115, 'Alijis', 'CCS', 'BSIS', '2025-11-14 04:08:40', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

CREATE TABLE `questions` (
  `id` int(11) NOT NULL,
  `version_id` int(11) NOT NULL,
  `question_number` int(11) DEFAULT NULL,
  `question_type` varchar(50) NOT NULL,
  `question_text` text NOT NULL,
  `option_a` varchar(255) DEFAULT NULL,
  `option_b` varchar(255) DEFAULT NULL,
  `option_c` varchar(255) DEFAULT NULL,
  `option_d` varchar(255) DEFAULT NULL,
  `answer` varchar(255) NOT NULL,
  `points` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `exam_version` varchar(255) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `questions`
--

INSERT INTO `questions` (`id`, `version_id`, `question_number`, `question_type`, `question_text`, `option_a`, `option_b`, `option_c`, `option_d`, `answer`, `points`, `created_at`, `exam_version`, `image_url`) VALUES
(119, 30, 1, 'multiple', 'What is our country', 'PH', 'KOR', 'USA', 'JPN', 'PH', 1, '2025-09-24 06:38:06', '', NULL),
(120, 30, 2, 'truefalse', 'Am i Handsome', 'True', 'False', '', '', 'True', 1, '2025-10-19 09:29:50', NULL, NULL),
(121, 30, 3, 'short', 'What is my name', '', '', '', '', 'Mark Lloyd', 1, '2025-10-19 09:29:50', NULL, NULL),
(122, 29, NULL, 'multiple', 'What type of intellectual property law primarily protects original works of authorship, such as software code?', 'Trademark', 'Copyright', 'Patent', 'Trade dress', 'B', 1, '2025-11-12 09:00:36', NULL, NULL),
(123, 29, NULL, 'multiple', 'Which of the following is an example of an input device?', 'Printer ', 'Keyboard', 'Speaker', 'Monitor', 'B', 1, '2025-11-12 09:00:36', NULL, NULL),
(124, 29, NULL, 'multiple', 'The main function of the CPU is to:', 'Store data permanently', 'Display information', 'Process instructions', 'Manage printers', 'C', 1, '2025-11-12 09:00:36', NULL, NULL),
(125, 29, NULL, 'multiple', 'The “brain” of the computer is known as the:', 'Motherboard', 'RAM', 'CPU', 'Hard Drive', 'C', 1, '2025-11-12 09:00:36', NULL, NULL),
(126, 29, NULL, 'multiple', 'Which of the following is volatile memory?', 'ROM', 'Hard Disk', 'Flash Memory', 'RAM', 'D', 1, '2025-11-12 09:00:36', NULL, NULL),
(127, 29, NULL, 'multiple', 'What is the correct order of program development?', 'Coding → Designing → Testing → Analyzing', 'Testing → Coding → Analyzing → Designing', 'Analyzing → Designing → Coding → Testing', 'Designing → Coding → Testing → Analyzing', 'C', 1, '2025-11-12 09:00:36', NULL, NULL),
(128, 29, NULL, 'multiple', 'Which of the following is a looping statement?', 'if', 'while', 'switch', 'break', 'B', 1, '2025-11-12 09:00:36', NULL, NULL),
(129, 29, NULL, 'multiple', 'What is the result of 5 + 2 * 3 in most programming languages?', '21', '19', '11', '7', 'C', 1, '2025-11-12 09:00:36', NULL, NULL),
(130, 29, NULL, 'multiple', 'Which data type is best for storing a person’s name?', 'Integer', 'String', 'Boolean', 'Float', 'B', 1, '2025-11-12 11:34:22', NULL, NULL),
(131, 29, NULL, 'multiple', 'In a Flow chart, the diamond shape represents', 'Start/End', 'Input', 'Process', 'Decision', 'D', 1, '2025-11-12 11:34:22', NULL, NULL),
(132, 29, NULL, 'multiple', 'What does SQL stand for?', 'Simple Query Language', 'Sequential Query Language', 'Structured Query Language', 'Systematic Query Logic', 'C', 1, '2025-11-12 11:34:22', NULL, NULL),
(133, 29, NULL, 'multiple', 'Which command is used to retrieve data from a database?', 'SELECT', 'DELETE', 'UPDATE', 'INSERT', 'A', 1, '2025-11-12 11:34:22', NULL, NULL),
(134, 29, NULL, 'multiple', 'A primary key must be:', 'Unique and not null', 'Optional', 'Duplicated', 'Indexed only', 'A', 1, '2025-11-12 11:34:22', NULL, NULL),
(135, 29, NULL, 'multiple', 'Which database model organizes data into tables?', 'Hierarchical', 'Relational', 'Network', 'Object-oriented', 'B', 1, '2025-11-12 11:34:22', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `registration`
--

CREATE TABLE `registration` (
  `id` int(11) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `email_address` varchar(100) NOT NULL,
  `applicant_status` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `personal_info_id` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `application_submitted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `registration`
--

INSERT INTO `registration` (`id`, `last_name`, `first_name`, `email_address`, `applicant_status`, `password`, `personal_info_id`, `updated_at`, `created_at`, `application_submitted`) VALUES
(20260032, 'regalado', 'ashly', 'matyasaqoe@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$nS4T55hIgEBSa97ESJO5FudYe3TjKC7NLL2C60ELLw7SeSQcF8jeO', 25, '2025-10-06 17:05:52', '2025-10-06 17:00:14', 1),
(20260033, 'REGALADO', 'ASHLEY CARYLL', 'acregalado.chmsu@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$Iss2zcV/GoRJZbozB4mKdOU7BqbW38gsjejw.isYtbJLSVbBAJVUO', 26, '2025-10-10 21:34:20', '2025-10-10 21:25:49', 1),
(20260034, 'guiagogo', 'mark lloyd', 'marklloyd.guiagogo@chmsc.edu.ph', 'New Applicant - Same Academic Year', '$2y$10$N5o9Jb8V7DCb4VPMDdMPR.O2DQITqglTlDATFdsRIsWiOZZ33/68y', 27, '2025-11-07 22:18:22', '2025-10-19 03:50:12', 1),
(20260035, 'ROJO', 'MEG RYAN', 'regalado.ashleycaryll@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$Iss2zcV/GoRJZbozB4mKdOU7BqbW38gsjejw.isYtbJLSVbBAJVUO', 107, '2025-11-07 17:27:47', '2025-10-22 10:31:30', 1),
(20261009, 'Guiagogo', 'Mark Lloyd', 'mmguiagogo.chmsu@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$8g712s7xyPK9UZPC6MI2Mu792FagLY7hb/3ICTCsM0guAelf70zya', NULL, '2025-11-07 20:43:40', '2025-11-07 20:43:40', 0),
(20261010, 'Regalado', 'Ashley Caryll', 'ashleynteah.regalado@gmail.com', 'Transferee', '$2y$10$q..LzzcZyKwWhJhkxjzm..pa6ZiMXJxdfTBL0jqS3F.r8gHh/Wq92', 108, '2025-11-10 22:10:20', '2025-11-10 22:03:12', 1),
(20261011, 'Casipe', 'Beverly', 'bcasipe.chmsu@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$Fpa8si4U3n81hJaVXiyE1uyCVoHCLaLruh8r9ZuKll5zqmUrFA1yK', 109, '2025-11-12 12:20:02', '2025-11-12 12:11:43', 1),
(20261012, 'Villarna', 'Ryn Aj', 'ajvillarna10@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$Iss2zcV/GoRJZbozB4mKdOU7BqbW38gsjejw.isYtbJLSVbBAJVUO', 111, '2025-11-12 15:51:33', '2025-11-12 13:40:22', 1),
(20261013, 'Orcajada', 'Gael Gabriel', 'orcajadagael@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$Iss2zcV/GoRJZbozB4mKdOU7BqbW38gsjejw.isYtbJLSVbBAJVUO', 110, '2025-11-12 15:21:49', '2025-11-12 14:17:31', 1),
(20261014, 'Dejan', 'Japheth', 'jap.dej22@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$Iss2zcV/GoRJZbozB4mKdOU7BqbW38gsjejw.isYtbJLSVbBAJVUO', 114, '2025-11-14 12:02:13', '2025-11-12 14:23:12', 1),
(20261015, 'Gubac', 'John Patrick', 'jpsgubac.chmsu@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$Iss2zcV/GoRJZbozB4mKdOU7BqbW38gsjejw.isYtbJLSVbBAJVUO', 115, '2025-11-14 12:09:22', '2025-11-12 14:23:12', 1),
(20261016, 'Ilon', 'Dennisse Nicole', 'regaladodennissenicole@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$Iss2zcV/GoRJZbozB4mKdOU7BqbW38gsjejw.isYtbJLSVbBAJVUO', 112, '2025-11-14 09:45:16', '2025-11-12 20:11:39', 1),
(20261017, 'casipe', 'beverly', 'beverly.casipe@chmsc.edu.ph', 'New Applicant - Same Academic Year', '$2y$10$d0OmL6ClsrnAbsTnJWzeq.VHa0X0XCRKayLijQO4mYzHSzpA66ply', 113, '2025-11-13 16:24:15', '2025-11-13 16:05:04', 1);

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `building_id` int(11) NOT NULL,
  `chair_id` int(11) DEFAULT NULL,
  `room_number` varchar(50) NOT NULL,
  `status` enum('active','archived') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `building_id`, `chair_id`, `room_number`, `status`, `created_at`) VALUES
(1, 1, 3, '311', 'active', '2025-10-19 08:56:53'),
(2, 1, 3, '312', 'active', '2025-10-19 08:57:03'),
(3, 1, 3, '313', 'active', '2025-10-19 08:57:14'),
(4, 1, 3, '403', 'active', '2025-11-07 09:50:41'),
(5, 2, 3, '456', 'active', '2025-11-08 06:54:31'),
(6, 3, 7, '123', 'active', '2025-11-12 13:25:00');

-- --------------------------------------------------------

--
-- Table structure for table `schedules`
--

CREATE TABLE `schedules` (
  `id` int(11) NOT NULL,
  `event_date` date NOT NULL,
  `event_time` time NOT NULL,
  `venue` varchar(255) NOT NULL,
  `chair_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schedules`
--

INSERT INTO `schedules` (`id`, `event_date`, `event_time`, `venue`, `chair_id`, `created_at`) VALUES
(24, '2025-10-22', '05:30:00', 'LSAB - Room 311, LSAB - Room 313', 3, '2025-10-21 21:30:40'),
(37, '2025-11-08', '12:00:00', 'LSAB - Room 311', 3, '2025-11-08 08:09:22'),
(40, '2025-11-13', '12:00:00', 'LSAB - Room 311', 3, '2025-11-12 20:40:15'),
(41, '2025-11-13', '16:00:00', 'LSAB - Room 313', 3, '2025-11-13 02:25:40'),
(42, '2025-11-22', '08:00:00', 'LSAB - Room 311', 3, '2025-11-13 08:49:36');

-- --------------------------------------------------------

--
-- Table structure for table `schedule_applicants`
--

CREATE TABLE `schedule_applicants` (
  `id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `applicant_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schedule_applicants`
--

INSERT INTO `schedule_applicants` (`id`, `schedule_id`, `applicant_id`, `created_at`) VALUES
(17, 24, 20260034, '2025-10-21 21:30:49'),
(18, 24, 20260033, '2025-10-21 21:30:49'),
(19, 37, 20260032, '2025-11-08 08:09:32'),
(20, 40, 20261012, '2025-11-12 20:40:29'),
(21, 41, 20261011, '2025-11-13 02:26:01'),
(22, 41, 20261010, '2025-11-13 02:26:01'),
(23, 42, 20261017, '2025-11-13 08:49:55'),
(24, 42, 20261013, '2025-11-13 08:49:55');

-- --------------------------------------------------------

--
-- Table structure for table `screening_results`
--

CREATE TABLE `screening_results` (
  `id` int(11) NOT NULL,
  `personal_info_id` int(11) NOT NULL,
  `gwa_score` decimal(5,2) DEFAULT NULL,
  `stanine_result` int(11) DEFAULT NULL,
  `stanine_score` decimal(5,2) DEFAULT NULL,
  `initial_total` decimal(5,2) DEFAULT NULL,
  `exam_total_score` decimal(5,2) DEFAULT NULL,
  `exam_percentage` decimal(5,2) DEFAULT NULL,
  `interview_total_score` decimal(5,2) DEFAULT NULL,
  `interview_percentage` decimal(5,2) DEFAULT NULL,
  `plus_factor` decimal(5,2) DEFAULT NULL,
  `final_rating` decimal(5,2) DEFAULT NULL,
  `rank` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `screening_results`
--

INSERT INTO `screening_results` (`id`, `personal_info_id`, `gwa_score`, `stanine_result`, `stanine_score`, `initial_total`, `exam_total_score`, `exam_percentage`, `interview_total_score`, `interview_percentage`, `plus_factor`, `final_rating`, `rank`) VALUES
(80, 25, 68.67, 9, 9.00, 8.22, NULL, 0.00, NULL, 0.00, 2.00, 10.22, 7),
(100, 26, 95.33, 6, 6.00, 10.43, 100.00, 40.00, 100.00, 35.00, 5.00, 90.43, 1),
(102, 27, 91.67, 2, 2.00, 9.47, 66.67, 26.67, 48.00, 16.80, 5.00, 57.94, 3),
(321, 107, 85.00, NULL, NULL, 8.50, NULL, 0.00, NULL, 0.00, 5.00, 13.50, 1),
(442, 108, 90.00, 5, 5.00, 9.75, NULL, 0.00, NULL, 0.00, 0.00, 9.75, 8),
(456, 109, 96.67, 1, 1.00, 9.82, NULL, 0.00, NULL, 0.00, 5.00, 14.82, 5),
(464, 110, 86.67, 9, 9.00, 10.02, NULL, 0.00, NULL, 0.00, 3.00, 13.02, 6),
(465, 111, 88.33, 4, 4.00, 9.43, 85.71, 34.28, NULL, 0.00, 0.00, 43.71, 4),
(473, 112, 95.33, NULL, NULL, 9.53, 0.00, 0.00, NULL, 0.00, 0.00, 9.53, 9),
(2796, 113, 90.33, 9, 9.00, 10.38, 92.86, 37.14, 96.50, 33.78, 2.00, 83.30, 2),
(2956, 114, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3.00, 3.00, 2),
(2957, 115, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3.00, 3.00, 2);

-- --------------------------------------------------------

--
-- Table structure for table `socio_demographic`
--

CREATE TABLE `socio_demographic` (
  `id` int(11) NOT NULL,
  `personal_info_id` int(11) NOT NULL,
  `marital_status` enum('Single','Married','Divorced','Domestic Partnership','Others') NOT NULL,
  `religion` enum('None','Christianity','Islam','Hinduism','Others') NOT NULL,
  `orientation` enum('Heterosexual','Homosexual','Bisexual','Others') NOT NULL,
  `father_status` enum('Alive; Away','Alive; at Home','Deceased','Unknown') NOT NULL,
  `father_education` enum('No High School Diploma','High School Diploma','Bachelor''s Degree','Graduate Degree') NOT NULL,
  `father_employment` enum('Employed Full-Time','Employed Part-Time','Unemployed') NOT NULL,
  `mother_status` enum('Alive; Away','Alive; at Home','Deceased','Unknown') NOT NULL,
  `mother_education` enum('No High School Diploma','High School Diploma','Bachelor''s Degree','Graduate Degree') NOT NULL,
  `mother_employment` enum('Employed Full-Time','Employed Part-Time','Unemployed') NOT NULL,
  `siblings` enum('None','One','Two or more') NOT NULL,
  `living_with` enum('Both parents','One parent only','Relatives','Alone') NOT NULL,
  `access_computer` varchar(3) DEFAULT NULL,
  `access_internet` varchar(3) DEFAULT NULL,
  `access_mobile` varchar(3) DEFAULT NULL,
  `indigenous_group` varchar(3) NOT NULL,
  `first_gen_college` varchar(3) DEFAULT NULL,
  `was_scholar` varchar(3) DEFAULT NULL,
  `received_honors` varchar(3) DEFAULT NULL,
  `has_disability` varchar(3) DEFAULT NULL,
  `disability_detail` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `socio_demographic`
--

INSERT INTO `socio_demographic` (`id`, `personal_info_id`, `marital_status`, `religion`, `orientation`, `father_status`, `father_education`, `father_employment`, `mother_status`, `mother_education`, `mother_employment`, `siblings`, `living_with`, `access_computer`, `access_internet`, `access_mobile`, `indigenous_group`, `first_gen_college`, `was_scholar`, `received_honors`, `has_disability`, `disability_detail`, `created_at`) VALUES
(25, 25, 'Single', 'None', 'Heterosexual', 'Unknown', 'No High School Diploma', 'Unemployed', 'Alive; Away', 'No High School Diploma', 'Employed Full-Time', 'None', 'One parent only', 'Yes', 'Yes', 'Yes', 'Yes', 'No', 'No', 'No', 'No', '', '2025-10-06 09:03:51'),
(26, 26, 'Single', 'Christianity', 'Heterosexual', 'Unknown', 'No High School Diploma', 'Employed Full-Time', 'Alive; Away', 'Graduate Degree', 'Employed Full-Time', 'None', 'One parent only', 'Yes', 'Yes', 'Yes', 'No', 'Yes', 'No', 'Yes', 'No', '', '2025-10-10 13:29:34'),
(27, 27, 'Single', 'Christianity', 'Heterosexual', 'Alive; Away', 'High School Diploma', 'Employed Full-Time', 'Alive; Away', 'High School Diploma', 'Unemployed', 'One', 'Relatives', 'No', 'No', 'Yes', 'No', 'Yes', 'No', 'Yes', 'No', '', '2025-10-19 10:52:47'),
(58, 107, 'Single', 'None', 'Heterosexual', 'Alive; Away', 'No High School Diploma', 'Employed Full-Time', 'Alive; Away', 'No High School Diploma', 'Employed Full-Time', 'None', 'Both parents', 'Yes', 'Yes', 'Yes', 'No', 'Yes', 'No', 'Yes', 'Yes', 'Scoliosis', '2025-11-07 09:26:01'),
(59, 108, 'Single', 'Christianity', 'Heterosexual', 'Alive; Away', 'No High School Diploma', 'Employed Full-Time', 'Alive; Away', 'No High School Diploma', 'Employed Full-Time', 'Two or more', 'Alone', 'Yes', 'Yes', 'Yes', 'No', 'Yes', 'No', 'Yes', 'No', '', '2025-11-10 14:06:03'),
(60, 109, 'Single', 'None', 'Heterosexual', 'Unknown', 'High School Diploma', 'Unemployed', 'Unknown', 'High School Diploma', 'Unemployed', 'One', 'Relatives', 'Yes', 'Yes', 'Yes', 'No', 'No', 'No', 'Yes', 'No', '', '2025-11-12 04:18:35'),
(61, 110, 'Single', 'None', 'Homosexual', 'Alive; Away', 'No High School Diploma', 'Employed Full-Time', 'Alive; Away', 'No High School Diploma', 'Employed Full-Time', 'Two or more', 'Both parents', 'Yes', 'Yes', 'Yes', 'Yes', 'Yes', 'Yes', 'Yes', 'No', '', '2025-11-12 07:20:18'),
(62, 111, 'Single', 'None', 'Heterosexual', 'Alive; Away', 'Bachelor\'s Degree', 'Employed Part-Time', 'Alive; at Home', 'High School Diploma', 'Employed Part-Time', 'Two or more', 'Both parents', 'Yes', 'Yes', 'Yes', 'Yes', 'Yes', 'Yes', 'No', 'No', '', '2025-11-12 07:49:13'),
(63, 112, 'Single', 'Christianity', 'Heterosexual', 'Alive; at Home', 'Bachelor\'s Degree', 'Employed Full-Time', 'Alive; at Home', 'Bachelor\'s Degree', 'Employed Full-Time', 'Two or more', 'Alone', 'Yes', 'Yes', 'Yes', 'Yes', 'Yes', 'Yes', 'Yes', 'No', '', '2025-11-12 12:21:17'),
(64, 113, 'Single', 'Christianity', 'Heterosexual', 'Unknown', 'Bachelor\'s Degree', 'Employed Full-Time', 'Unknown', 'No High School Diploma', 'Employed Full-Time', 'One', 'Relatives', 'Yes', 'Yes', 'Yes', 'No', 'No', 'Yes', 'Yes', 'No', '', '2025-11-13 08:11:03'),
(65, 114, 'Single', 'Christianity', 'Heterosexual', 'Alive; Away', 'No High School Diploma', 'Employed Full-Time', 'Alive; Away', 'No High School Diploma', 'Employed Full-Time', 'None', 'Both parents', 'Yes', 'Yes', 'Yes', 'Yes', 'Yes', 'No', 'Yes', 'No', '', '2025-11-14 03:59:37'),
(66, 115, 'Single', 'None', 'Homosexual', 'Alive; Away', 'Bachelor\'s Degree', 'Employed Full-Time', 'Alive; at Home', 'High School Diploma', 'Employed Full-Time', 'None', 'Relatives', 'Yes', 'Yes', 'Yes', 'Yes', 'Yes', 'Yes', 'Yes', 'Yes', 'Scoliosis', '2025-11-14 04:07:52');

-- --------------------------------------------------------

--
-- Table structure for table `strands`
--

CREATE TABLE `strands` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `status` enum('active','archived') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `strands`
--

INSERT INTO `strands` (`id`, `name`, `status`, `created_at`, `updated_at`) VALUES
(1, 'HUMSS', 'active', '2025-10-06 09:00:00', '2025-11-08 07:04:53'),
(2, 'TVL-ICT', 'active', '2025-10-06 09:00:00', NULL),
(3, 'STEM', 'active', '2025-10-06 09:00:00', NULL),
(4, 'ABM', 'active', '2025-10-06 09:00:00', '2025-11-08 07:04:44'),
(5, 'GAS', 'active', '2025-10-06 09:00:00', '2025-11-08 07:04:49'),
(6, 'TVL-CSS', 'active', '2025-10-21 16:55:22', NULL),
(7, 'TVL-PROGRAMMING', 'active', '2025-10-21 16:55:39', '2025-11-08 06:56:23'),
(8, 'TVL-HE', 'active', '2025-10-21 16:55:58', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `uploads`
--

CREATE TABLE `uploads` (
  `id` int(11) NOT NULL,
  `applicant_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academic_background`
--
ALTER TABLE `academic_background`
  ADD PRIMARY KEY (`id`),
  ADD KEY `personal_info_id` (`personal_info_id`),
  ADD KEY `academic_background_ibfk_2` (`strand_id`);

--
-- Indexes for table `application_status`
--
ALTER TABLE `application_status`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `buildings`
--
ALTER TABLE `buildings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_buildings_chair_id` (`chair_id`);

--
-- Indexes for table `chairperson_accounts`
--
ALTER TABLE `chairperson_accounts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `personal_info_id` (`personal_info_id`);

--
-- Indexes for table `exam_answers`
--
ALTER TABLE `exam_answers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_applicant_version` (`applicant_id`,`version_id`),
  ADD KEY `applicant_id` (`applicant_id`),
  ADD KEY `version_id` (`version_id`);

--
-- Indexes for table `exam_versions`
--
ALTER TABLE `exam_versions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `version_name` (`version_name`),
  ADD KEY `fk_exam_chair` (`chair_id`);

--
-- Indexes for table `interviewers`
--
ALTER TABLE `interviewers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`email`),
  ADD KEY `fk_chairperson` (`chairperson_id`);

--
-- Indexes for table `interview_schedules`
--
ALTER TABLE `interview_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_interview_schedules_chair_id` (`chair_id`);

--
-- Indexes for table `interview_schedule_applicants`
--
ALTER TABLE `interview_schedule_applicants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_interview_schedule_applicant` (`schedule_id`,`applicant_id`),
  ADD KEY `idx_interview_schedule_id` (`schedule_id`),
  ADD KEY `idx_interview_applicant_id` (`applicant_id`);

--
-- Indexes for table `personal_info`
--
ALTER TABLE `personal_info`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `program_application`
--
ALTER TABLE `program_application`
  ADD PRIMARY KEY (`id`),
  ADD KEY `personal_info_id` (`personal_info_id`),
  ADD KEY `fk_pa_registrationid` (`registration_id`);

--
-- Indexes for table `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `version_id` (`version_id`);

--
-- Indexes for table `registration`
--
ALTER TABLE `registration`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email_address` (`email_address`),
  ADD UNIQUE KEY `Index 2` (`email_address`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `building_id` (`building_id`),
  ADD KEY `idx_rooms_chair_id` (`chair_id`);

--
-- Indexes for table `schedules`
--
ALTER TABLE `schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_schedules_chair_id` (`chair_id`);

--
-- Indexes for table `schedule_applicants`
--
ALTER TABLE `schedule_applicants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_schedule_applicant` (`schedule_id`,`applicant_id`),
  ADD KEY `schedule_id` (`schedule_id`),
  ADD KEY `applicant_id` (`applicant_id`);

--
-- Indexes for table `screening_results`
--
ALTER TABLE `screening_results`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_personal_info` (`personal_info_id`);

--
-- Indexes for table `socio_demographic`
--
ALTER TABLE `socio_demographic`
  ADD PRIMARY KEY (`id`),
  ADD KEY `personal_info_id` (`personal_info_id`);

--
-- Indexes for table `strands`
--
ALTER TABLE `strands`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `uploads`
--
ALTER TABLE `uploads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `applicant_id` (`applicant_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `academic_background`
--
ALTER TABLE `academic_background`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- AUTO_INCREMENT for table `application_status`
--
ALTER TABLE `application_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `buildings`
--
ALTER TABLE `buildings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `chairperson_accounts`
--
ALTER TABLE `chairperson_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT for table `exam_answers`
--
ALTER TABLE `exam_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `exam_versions`
--
ALTER TABLE `exam_versions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `interviewers`
--
ALTER TABLE `interviewers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `interview_schedules`
--
ALTER TABLE `interview_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `interview_schedule_applicants`
--
ALTER TABLE `interview_schedule_applicants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `personal_info`
--
ALTER TABLE `personal_info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=116;

--
-- AUTO_INCREMENT for table `program_application`
--
ALTER TABLE `program_application`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- AUTO_INCREMENT for table `questions`
--
ALTER TABLE `questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=136;

--
-- AUTO_INCREMENT for table `registration`
--
ALTER TABLE `registration`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20261018;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `schedule_applicants`
--
ALTER TABLE `schedule_applicants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `screening_results`
--
ALTER TABLE `screening_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2992;

--
-- AUTO_INCREMENT for table `socio_demographic`
--
ALTER TABLE `socio_demographic`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `strands`
--
ALTER TABLE `strands`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `uploads`
--
ALTER TABLE `uploads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `academic_background`
--
ALTER TABLE `academic_background`
  ADD CONSTRAINT `academic_background_ibfk_1` FOREIGN KEY (`personal_info_id`) REFERENCES `personal_info` (`id`),
  ADD CONSTRAINT `academic_background_ibfk_2` FOREIGN KEY (`strand_id`) REFERENCES `strands` (`id`);

--
-- Constraints for table `application_status`
--
ALTER TABLE `application_status`
  ADD CONSTRAINT `fk_application_status_user` FOREIGN KEY (`user_id`) REFERENCES `registration` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `buildings`
--
ALTER TABLE `buildings`
  ADD CONSTRAINT `fk_buildings_chair_id` FOREIGN KEY (`chair_id`) REFERENCES `chairperson_accounts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`personal_info_id`) REFERENCES `personal_info` (`id`);

--
-- Constraints for table `exam_answers`
--
ALTER TABLE `exam_answers`
  ADD CONSTRAINT `exam_answers_ibfk_1` FOREIGN KEY (`applicant_id`) REFERENCES `registration` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exam_answers_ibfk_3` FOREIGN KEY (`version_id`) REFERENCES `exam_versions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `exam_versions`
--
ALTER TABLE `exam_versions`
  ADD CONSTRAINT `fk_exam_chair` FOREIGN KEY (`chair_id`) REFERENCES `chairperson_accounts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `interviewers`
--
ALTER TABLE `interviewers`
  ADD CONSTRAINT `fk_chairperson` FOREIGN KEY (`chairperson_id`) REFERENCES `chairperson_accounts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `interview_schedules`
--
ALTER TABLE `interview_schedules`
  ADD CONSTRAINT `fk_interview_schedules_chair_id` FOREIGN KEY (`chair_id`) REFERENCES `chairperson_accounts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `program_application`
--
ALTER TABLE `program_application`
  ADD CONSTRAINT `fk_pa_registrationid` FOREIGN KEY (`registration_id`) REFERENCES `registration` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_registration` FOREIGN KEY (`registration_id`) REFERENCES `registration` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `program_application_ibfk_1` FOREIGN KEY (`personal_info_id`) REFERENCES `personal_info` (`id`);

--
-- Constraints for table `questions`
--
ALTER TABLE `questions`
  ADD CONSTRAINT `questions_ibfk_1` FOREIGN KEY (`version_id`) REFERENCES `exam_versions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `rooms`
--
ALTER TABLE `rooms`
  ADD CONSTRAINT `fk_rooms_chair_id` FOREIGN KEY (`chair_id`) REFERENCES `chairperson_accounts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `rooms_ibfk_1` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `schedules`
--
ALTER TABLE `schedules`
  ADD CONSTRAINT `fk_schedules_chair_id` FOREIGN KEY (`chair_id`) REFERENCES `chairperson_accounts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `schedule_applicants`
--
ALTER TABLE `schedule_applicants`
  ADD CONSTRAINT `schedule_applicants_ibfk_1` FOREIGN KEY (`schedule_id`) REFERENCES `schedules` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `schedule_applicants_ibfk_2` FOREIGN KEY (`applicant_id`) REFERENCES `registration` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `screening_results`
--
ALTER TABLE `screening_results`
  ADD CONSTRAINT `screening_results_ibfk_1` FOREIGN KEY (`personal_info_id`) REFERENCES `personal_info` (`id`);

--
-- Constraints for table `socio_demographic`
--
ALTER TABLE `socio_demographic`
  ADD CONSTRAINT `socio_demographic_ibfk_1` FOREIGN KEY (`personal_info_id`) REFERENCES `personal_info` (`id`);

--
-- Constraints for table `uploads`
--
ALTER TABLE `uploads`
  ADD CONSTRAINT `uploads_ibfk_1` FOREIGN KEY (`applicant_id`) REFERENCES `registration` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
