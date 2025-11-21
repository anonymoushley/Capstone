-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 21, 2025 at 06:39 AM
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
(74, 115, 'LA CONSOLACION COLLEGE BACOLOD', 2, '2025', 95.00, 90.00, 95.00, 'Honors', '2025-11-14 04:08:27'),
(75, 116, 'SILAY INSTITUTE INC', 2, '2025', 95.00, 97.00, 95.00, 'Honors', '2025-11-19 06:47:00'),
(76, 117, 'SILAY INSTITUTE INC.', 2, '2025', 93.00, 95.00, 94.00, 'Honors', '2025-11-19 06:51:50'),
(77, 118, 'HIGH SCHOOL', 2, '2025', 90.00, 90.00, 90.00, 'Honors', '2025-11-19 07:09:50'),
(78, 119, 'STI WEST NEGROS UNIVERSITY ', 3, '2025', 92.00, 93.00, 95.00, 'Honors', '2025-11-19 07:12:31'),
(79, 120, 'CAUAYAN NHS', 1, '2025', 91.00, 92.00, 90.00, 'Honors', '2025-11-19 07:12:34'),
(80, 121, 'BATA NATIONAL HIGH SCHOOL', 6, '2025', 91.00, 92.00, 90.00, 'Honors', '2025-11-19 07:16:01'),
(81, 122, 'DOÑA MONTSERRAT LOPEZ MEMORIAL HIGH SCHOOL ', 3, '2025', 93.00, 93.00, 93.00, 'Honors', '2025-11-19 07:25:02'),
(82, 123, 'RAFAEL B. LACSON', 4, '2021', 99.00, 99.00, 99.99, 'Highest Honors', '2025-11-19 07:45:46'),
(83, 124, 'RAFAEL B LACSON MEMORIAL HIGH SCHOOL ', 6, '2025', 95.00, 95.00, 94.00, 'High Honors', '2025-11-19 08:13:24'),
(84, 125, 'DONA MONTSERAT MEMORIAL HIGH SCHOOL', 3, '2025', 93.00, 94.00, 95.00, 'High Honors', '2025-11-19 08:29:41'),
(85, 126, 'RAFAEL B LACSON MEMORIAL HIGHSCHOOL ', 3, '2025', 99.00, 99.00, 90.00, 'High Honors', '2025-11-19 08:30:07'),
(86, 127, 'DMLMHS', 6, '2025', 98.00, 98.00, 98.00, 'Highest Honors', '2025-11-19 08:30:18'),
(87, 129, 'IVFMSF', 1, '2025', 90.00, 90.00, 93.00, 'Honors', '2025-11-19 08:30:35'),
(88, 128, 'VICTORIAS NATIONAL HIGHSCHOOL ', 1, '2025', 90.00, 92.00, 96.00, 'High Honors', '2025-11-19 08:31:01'),
(89, 132, 'NOTRE DAME OF TALISAY CITY ', 1, '2025', 90.00, 90.00, 93.00, 'Honors', '2025-11-19 08:32:03'),
(90, 133, 'HINIGARAN NATIONAL HIGH SCHOOL', 3, '2025', 94.00, 94.00, 95.00, 'High Honors', '2025-11-19 08:32:18'),
(91, 130, 'E. B MAGALONA', 6, '2025', 90.00, 90.00, 94.00, 'Honors', '2025-11-19 08:32:18'),
(92, 134, 'MMHS', 2, '2025', 93.00, 94.00, 95.00, 'High Honors', '2025-11-19 08:32:24'),
(93, 135, 'STI WEST NEGROS UNIVERSITY', 2, '2025', 98.00, 98.00, 98.00, 'Highest Honors', '2025-11-19 08:33:08'),
(94, 131, 'RAFAEL B. LACSON MEMORIAL HIGH SCHOOL ', 2, '2025', 90.00, 90.00, 90.00, 'Honors', '2025-11-19 08:33:10'),
(95, 136, 'SAGAY NATIONAL HIGH SCHOOL', 3, '2025', 95.00, 96.00, 94.00, 'High Honors', '2025-11-19 08:33:22'),
(96, 137, 'LA CASTELLANA NATIONAL HIGH SCHOOL- SENIOR HIGH', 3, '2025', 95.00, 95.00, 95.00, 'High Honors', '2025-11-19 08:34:52'),
(97, 138, 'ADVENTIST ACADEMY BACOLOD', 3, '2025', 90.00, 90.00, 90.00, 'Honors', '2025-11-19 08:35:05'),
(98, 139, 'BARANGAY GUIMBALA ON NATIONAL HIGH SCHOOL ', 6, '2025', 99.99, 99.99, 99.99, 'Highest Honors', '2025-11-19 08:35:14'),
(99, 140, 'RAFAEL B. LACSON MEMORIAL HIGH SCHOOL ', 1, '2025', 91.00, 90.00, 91.00, 'Honors', '2025-11-19 08:36:17'),
(100, 141, 'SILAY INSTITUTE ', 2, '2025', 0.00, 0.00, 0.00, 'Honors', '2025-11-19 08:37:54'),
(101, 142, 'SNHS', 4, '2025', 95.00, 95.00, 95.00, 'High Honors', '2025-11-19 08:39:27'),
(102, 143, 'SHSHSHHAHSJFH', 5, '2025', 99.00, 99.00, 99.00, 'High Honors', '2025-11-19 14:35:43'),
(103, 144, 'NOTRE DAME OF TALISAY', 3, '2025', 95.00, 96.00, 95.00, 'High Honors', '2025-11-20 05:53:12'),
(104, 145, 'INOCENCIO V. FERRER MEMORIAL SCHOOL OF FISHERIES ', 2, '2022', 90.00, 90.00, 90.00, 'Honors', '2025-11-20 07:05:57'),
(105, 147, 'SUM-AG NATIONAL HIGH SCHOOL', 3, '2022', 98.00, 98.00, 98.00, 'Highest Honors', '2025-11-20 12:07:33'),
(106, 148, 'RAFAEL B. LACSON MEMORIAL HIGH SCHOOL', 2, '2025', 87.00, 88.00, 89.50, '', '2025-11-20 12:08:24'),
(107, 146, 'SILAY INSTITUTE INC.', 2, '2025', 91.00, 93.00, 93.00, '', '2025-11-20 12:10:44'),
(108, 149, '2025', 3, '2025', 94.00, 95.00, 93.00, 'High Honors', '2025-11-20 12:11:03'),
(109, 150, 'ENRIQUE B. MAGALONA NATIONAL HIGH SCHOOL ', 4, '2025', 93.00, 94.00, 93.00, 'Honors', '2025-11-20 12:23:09'),
(110, 151, 'RAFAEL B. LACSON MEMORIAL HIGHSCHOOL ', 2, '2025', 87.00, 88.00, 88.00, '', '2025-11-20 12:27:58'),
(111, 153, 'NOTRE DAME TALISAY', 2, '2025', 94.00, 0.00, 0.00, 'Honors', '2025-11-20 12:53:09'),
(112, 154, '2024', 6, '2025', 92.00, 93.00, 93.00, 'Honors', '2025-11-20 13:01:59'),
(113, 156, 'UNIVERSITY OF ST. LA SALLE - LICEO', 3, '2025', 91.00, 90.00, 90.00, 'Honors', '2025-11-20 13:23:11'),
(114, 158, 'BACOLOD CITY NATIONAL HIGH SCHOOL', 6, '2025', 88.00, 87.00, 89.00, '', '2025-11-20 13:28:44'),
(115, 155, 'NOTRE DAME OF TALISAY', 6, '2024', 86.00, 89.00, 89.00, '', '2025-11-20 13:29:13'),
(116, 157, 'ANGELA GONZGA NATIONAL HIGHSCHOOL ', 2, '2025', 82.00, 82.00, 80.00, '', '2025-11-20 13:29:35'),
(117, 159, 'DOÑA MONTSERRAT LOPEZ MEMORIAL HIGH SCHOOL ', 6, '2025', 92.00, 93.00, 93.00, 'Honors', '2025-11-20 13:49:41'),
(118, 160, 'SILAY INSTITUTE', 2, '2022', 95.00, 95.00, 96.00, 'High Honors', '2025-11-20 13:50:32'),
(119, 161, 'DOÑA MONSERRAT LOPEZ MEMORIAL HIGHSCHOOL ', 6, '2025', 93.00, 95.00, 95.00, 'High Honors', '2025-11-20 14:03:04'),
(120, 152, 'SAGAY NATIONAL HIGH SCHOOL ', 3, '2025', 93.00, 92.00, 93.00, 'Honors', '2025-11-20 16:09:14'),
(121, 162, 'VNHS', 6, '2025', 96.00, 96.00, 96.00, 'High Honors', '2025-11-21 02:37:09'),
(122, 163, 'DMLMHS ', 3, '2025', 97.00, 98.00, 97.00, 'High Honors', '2025-11-21 02:37:57'),
(123, 164, 'RBLMHS', 2, '2025', 89.00, 89.00, 89.00, '', '2025-11-21 02:38:03'),
(124, 166, 'SILAY INSTITUTE', 4, '2025', 90.00, 90.00, 90.00, 'Honors', '2025-11-21 02:38:32'),
(125, 167, 'SUM-AG NATIONAL HIGH SCHOOL', 4, '2025', 93.00, 95.00, 92.00, 'High Honors', '2025-11-21 02:40:47'),
(126, 165, 'DOÑA MONTSERRAT LOPEZ MEMORIAL HIGH SCHOOL', 7, '2025', 94.00, 95.00, 94.00, 'High Honors', '2025-11-21 02:40:55'),
(127, 169, 'DR. VICENTE F. GUSTILO MEMORIAL NATIONAL HIGH SCHOOL ', 4, '2025', 90.00, 90.00, 90.00, 'Honors', '2025-11-21 02:58:24'),
(128, 170, 'BATA NATIIONAL HIGH SCHOOL', 1, '2025', 94.40, 95.00, 97.00, 'Highest Honors', '2025-11-21 03:28:40'),
(129, 171, 'GIL MONTILLA NATIONAL HIGH SCHOOL', 6, '2025', 95.00, 95.00, 94.00, 'High Honors', '2025-11-21 03:31:11'),
(130, 172, 'ENRIQUE B. MAGALONA NATIONAL HIGHSCHOOL ', 2, '2025', 86.00, 88.00, 94.00, 'Honors', '2025-11-21 03:40:04'),
(131, 173, 'CARLOS HILADO MEMORIAL STATE UNIVERSITY', 1, '2025', 99.00, 99.00, 99.00, 'Highest Honors', '2025-11-21 03:52:41');

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
(3, 'Chairperson Talisay', 'ccstalisay', '$2y$10$6TFOGDRnthdbn38c9BTNIu3pbiw0cwmkvPXyPjGhauj0SCAFZWSem', 'Program Chair', 'BSIS', 'Talisay', '2025-08-27 06:42:14'),
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
(23, 26, '[\"691dbded77857_2024_Acer_Consumer_Option_02_3840x2400.jpg\",\"691dbded78f79_2024_Acer_Consumer_Default_3840x2400.jpg\",\"691dbded7ac17_2024_Acer_Consumer_Option_01_3840x2400.jpg\"]', '[\"691cc335369cb_2024_Acer_Consumer_Option_01_3840x2400.jpg\"]', '[\"691dcb2c69b29_Screenshot 2025-11-19 205335.png\"]', '[\"691cc3353965d_2024_Acer_Consumer_Option_01_3840x2400.jpg\"]', '[\"691cc3353a37d_2024_Acer_Consumer_Option_01_3840x2400.jpg\"]', '[\"691cc3353b48b_2024_Acer_Consumer_Option_01_3840x2400.jpg\"]', '2025-10-10 13:33:48', 'Accepted', 'Accepted', 'Accepted', 'Accepted', 'Pending', 'Pending'),
(24, 27, '68f7a72bdfa42_RobloxScreenShot20250727_074433666.png', '68f7a79b7f45a_RobloxScreenShot20250730_044554938.png', '68f7a75417db7_RobloxScreenShot20250730_043847341.png', '', '', '', '2025-10-19 10:54:19', 'Accepted', 'Accepted', 'Accepted', 'Accepted', 'Accepted', 'Accepted'),
(55, 107, '690dbb8920030_istockphoto-1463145425-612x612.jpg', '690dbb8920bb1_Your paragraph text.png', '690dbb89214d7_Your paragraph text.png', '690dbb8921ed9_Your paragraph text.png', '', '', '2025-11-07 09:27:37', 'Accepted', 'Accepted', 'Accepted', 'Accepted', 'Pending', 'Pending'),
(56, 108, '6911f1f85cba9_Your paragraph text.png', '6911f1f85dfa0_576598708_4036652966644706_7087242347509252687_n.jpg', '6911f1f85ee08_557543740_697894790058024_6966627441808617643_n.jpg', '', '', '', '2025-11-10 14:08:56', 'Accepted', 'Accepted', 'Accepted', 'Pending', 'Pending', 'Pending'),
(57, 109, '69140aea3796e_Untitled design (1).png', '69140aea38a0e_CLD.png', '69140b9670316_dg.jpg', '69140aea39c10_CLD.png', '', '', '2025-11-12 04:19:54', 'Accepted', 'Accepted', 'Accepted', 'Accepted', 'Pending', 'Pending'),
(58, 110, '691435860c5c0_557543740_697894790058024_6966627441808617643_n.jpg', '691435860d2d5_576598708_4036652966644706_7087242347509252687_n.jpg', '691435860db4d_CLD.png', '', '', '', '2025-11-12 07:21:42', 'Accepted', 'Accepted', 'Accepted', 'Pending', 'Pending', 'Pending'),
(59, 111, '69143c7f58cd7_CLD.png', '69143c7f5e29d_552771132_824798616564224_658779178776477722_n.jpg', '69143c7f5e98b_istockphoto-1463145425-612x612.jpg', '', '', '', '2025-11-12 07:51:27', 'Accepted', 'Accepted', 'Accepted', 'Pending', 'Pending', 'Pending'),
(60, 113, '6915945f88a46_CLD.png', '6915945f894d2_CLD.png', '691595ffd89ea_istockphoto-1463145425-612x612.jpg', '6915945f8b7d4_Your paragraph text.png', '', '', '2025-11-13 08:18:39', 'Accepted', 'Accepted', 'Accepted', 'Accepted', 'Pending', 'Pending'),
(61, 112, '691689a22c7f7_CLD.png', '691689a22f2d0_552771132_824798616564224_658779178776477722_n.jpg', '691689a22fc88_Your paragraph text.png', '', '', '', '2025-11-14 01:45:06', 'Accepted', 'Accepted', 'Accepted', 'Pending', 'Pending', 'Pending'),
(62, 114, '6916a9bfd9a3b_CLD.png', '6916a9bfdbda6_istockphoto-1463145425-612x612.jpg', '6916a9bfdcd6b_552771132_824798616564224_658779178776477722_n.jpg', '6916a9bfdd649_552771132_824798616564224_658779178776477722_n.jpg', '', '', '2025-11-14 04:02:07', 'Pending', 'Pending', 'Pending', 'Pending', 'Pending', 'Pending'),
(63, 115, '6916ab6d589a7_CLD.png', '6916ab6d5ac06_552771132_824798616564224_658779178776477722_n.jpg', '6916ab6d5c16f_Your paragraph text.png', '', '', '', '2025-11-14 04:09:17', 'Pending', 'Pending', 'Pending', 'Pending', 'Pending', 'Pending'),
(64, 116, '[\"691d686f16260_11.jpg\"]', '[\"691d686f16ecb_11.1.jpg\"]', '[\"691d686f17c87_12.jpg\"]', '[\"691d686f1867c_ncc.jpg\"]', '', '', '2025-11-19 06:49:19', 'Accepted', 'Accepted', 'Accepted', 'Pending', 'Pending', 'Pending'),
(65, 117, '[\"691d695a776aa_584608613_1992437458271065_7036847154996663675_n.jpg\",\"691d695a78a06_582271212_1588728849140225_1303652891952027973_n.jpg\"]', '[\"691eb83e4c8b9_582006815_1183482903749679_466432796159001139_n.jpg\"]', '[\"691d695a7c046_HD-wallpaper-techno-2-firefox-theme-technology-blue-lights-arrows.jpg\"]', '', '', '', '2025-11-19 06:53:14', 'Accepted', 'Accepted', 'Accepted', 'Pending', 'Pending', 'Pending'),
(66, 118, '[\"691d6df62f2cd_inbound6425706195230154107.png\"]', '[\"691d6df62fe89_inbound8294631763957744102.png\"]', '[\"691d6df63093c_inbound1524771795272252525.png\"]', '[\"691d6df63145c_inbound1918582161346289415.png\"]', '[\"691d6df631e5e_inbound508855739369783715.png\"]', '[\"691d6df632cad_inbound4408882892289150218.png\"]', '2025-11-19 07:12:54', 'Accepted', 'Accepted', 'Pending', 'Pending', 'Pending', 'Pending'),
(67, 120, '[\"691d6e22bc9a9_B09D6ABB-E8D7-40EC-8923-D0F873E06E83.jpeg\"]', '[\"691d6e22bdb5b_CEDD598E-D202-4FAB-91EC-3B56457A52FC.jpeg\"]', '[\"691d6e22bee00_C913A657-DAA2-42F6-95F3-C8315FA02FA9.jpeg\"]', '[\"691d6e22bf849_22A2FAFA-6B6D-4259-8789-2D597D30E709.jpeg\"]', '[\"691d6e22c0879_E8689A03-42C4-4BC5-A25E-36628D1A5E49.jpeg\"]', '[\"691d6e22c1232_32044A76-1616-4358-BD46-3556CB5B3247.jpeg\"]', '2025-11-19 07:13:38', 'Accepted', 'Pending', 'Pending', 'Pending', 'Pending', 'Pending'),
(68, 119, '[\"691d6e5dcb24c_inbound8156016921458681560.jpg\"]', '[\"691d6e5dcc14b_inbound6904322447106819315.jpg\"]', '[\"691d6e5dccb66_inbound1959014296575938555.jpg\"]', '[\"691d6e5dcd636_inbound9193513692399705802.jpg\"]', '[\"691d6e5dce727_inbound9128506093997003768.jpg\"]', '[\"691d6e5dd0e42_inbound8910459165139558613.jpg\"]', '2025-11-19 07:14:37', 'Pending', 'Pending', 'Pending', 'Pending', 'Pending', 'Pending'),
(69, 121, '[\"691d6f022400a_inbound161596284515729556.png\"]', '[\"691d6f0224b56_inbound2887572379406746534.png\"]', '[\"691d6f02258b4_inbound1145075357135460966.png\"]', '[\"691d6f0226928_inbound107579798102006138.jpg\"]', '[\"691d6f0227bc9_inbound4568679523532466996.jpg\"]', '[\"691d6f022871b_inbound8575194752389897720.jpg\"]', '2025-11-19 07:17:22', 'Accepted', 'Accepted', 'Pending', 'Pending', 'Pending', 'Pending'),
(70, 122, '[\"691d7137159db_1000012221.jpg\"]', '[\"691d7137169ae_1000012122.jpg\"]', '[\"691d713717245_1000012302.jpg\"]', '[\"691d713717f9a_1000012321.jpg\"]', '[\"691d713718ce4_1000012392.jpg\"]', '[\"691d71371aa0c_1000012435.jpg\"]', '2025-11-19 07:26:47', 'Accepted', 'Accepted', 'Pending', 'Pending', 'Pending', 'Pending'),
(71, 123, '[\"691d75c628be8_inbound6865790852400271911.jpg\"]', '[\"691d75c629af1_inbound2416001460359047474.jpg\"]', '[\"691d75c62a5d0_inbound650117608102289756.jpg\"]', '[\"691d75c62afd0_inbound6753273224771217290.jpg\"]', '[\"691d75c62b9ff_inbound5812870679264826756.jpg\"]', '[\"691d75c62d0dc_inbound3048229786511565614.jpg\"]', '2025-11-19 07:46:14', 'Accepted', 'Accepted', 'Accepted', 'Accepted', 'Pending', 'Pending'),
(72, 124, '[\"691d7c6f1d9c0_1000020679.jpg\"]', '[\"691d7c6f1ed7c_1000020678.jpg\"]', '[\"691d7c6f204fb_1000020681.jpg\"]', '[\"691d7c6f21e02_1000020680.jpg\"]', '[\"691d7c6f22c27_1000020675.jpg\"]', '[\"691d7c6f23e02_1000020674.jpg\"]', '2025-11-19 08:14:39', 'Accepted', 'Accepted', 'Pending', 'Pending', 'Pending', 'Pending'),
(73, 125, '[\"691d802dd6a1b_Save=follow # k3lly.jpg\"]', '[\"691d802dd75cf_download (1).jpg\"]', '[\"691d802dd8038_download (2).jpg\"]', '[\"691d802dd87fd_Drawing by Hillo.jpg\"]', '[\"691d802dd933c_conor-sexton-hRemch0ZDwI-unsplash.jpg\"]', '[\"691d802dd9bb2_a481b14f-a498-45d5-86d5-0658a85876c8.jpg\"]', '2025-11-19 08:30:37', 'Accepted', 'Accepted', 'Accepted', 'Accepted', 'Pending', 'Pending'),
(74, 127, '[\"691d805448b7c_received_856160213583053.jpg\"]', '[\"691d805449e5a_1763366804601.jpg\"]', '[\"691d80544aad1_1763366830518.jpg\"]', '[\"691d80544b983_1763523618230.jpg\"]', '[\"691d80544ceda_1763355699781.jpg\"]', '[\"691d80544f63f_IMG_20251115_171518 (1).jpg\"]', '2025-11-19 08:31:16', 'Accepted', 'Accepted', 'Accepted', 'Pending', 'Pending', 'Pending'),
(75, 129, '[\"691d806e19943_inbound402910389531969740.jpg\"]', '[\"691d806e1a5a9_inbound6782385736831599606.png\"]', '[\"691d806e1b15b_inbound601362949203677341.png\"]', '[\"691d806e1be05_inbound1261882714169906298.png\"]', '[\"691d806e1cab7_inbound7534912521843277700.png\"]', '[\"691d806e1dd1b_inbound2732512450855716978.png\"]', '2025-11-19 08:31:42', 'Pending', 'Pending', 'Pending', 'Pending', 'Pending', 'Pending'),
(76, 128, '[\"691d809b7dd09_IMG_20251115_172718_237.jpg\"]', '[\"691d809b7ead6_IMG_20251119_143147_768.jpg\"]', '[\"691d809b7f35c_received_857864176815457.jpeg\"]', '', '[\"691d809b7fb5a_Screenshot_20251118-222858.jpg\"]', '[\"691d809b80a3b_20251115_101922.jpg\"]', '2025-11-19 08:32:27', 'Accepted', 'Accepted', 'Accepted', 'Pending', 'Pending', 'Pending'),
(77, 133, '[\"691d80e027f51_image.jpg\"]', '[\"691d80e028c09_image.jpg\"]', '[\"691d80e029967_image.jpg\"]', '[\"691d80e02ab38_image.jpg\"]', '[\"691d80e02b700_image.jpg\"]', '[\"691d80e02d129_image.jpg\"]', '2025-11-19 08:33:36', 'Pending', 'Pending', 'Pending', 'Pending', 'Pending', 'Pending'),
(78, 126, '[\"691d80e159f4e_inbound9145958651250846182.jpg\"]', '[\"691d80e15b37e_inbound6575446449829762176.jpg\"]', '[\"691d80e15c0ee_inbound3703132067297934596.jpg\"]', '[\"691d80e15d001_inbound7481053766559125599.jpg\"]', '[\"691d80e15da48_inbound6872753351345678385.jpg\"]', '[\"691d80e15e95a_inbound2895771768682439938.jpg\"]', '2025-11-19 08:33:37', 'Pending', 'Pending', 'Pending', 'Pending', 'Pending', 'Pending'),
(79, 132, '[\"691d80e3a8eaa_1000033858.jpg\"]', '[\"691d80e3a97a7_1000032200.jpg\"]', '[\"691d80e3aead7_1000032200.jpg\"]', '', '', '', '2025-11-19 08:33:39', 'Pending', 'Pending', 'Pending', 'Pending', 'Pending', 'Pending'),
(80, 130, '[\"691d80ff11f53_1000009822.jpg\"]', '[\"691d80ff12885_1000009344.jpg\"]', '[\"691d80ff13149_1000009808.jpg\"]', '[\"691d80ff13d4e_1000009557.jpg\"]', '', '[\"691d80ff15f82_1000009437.jpg\"]', '2025-11-19 08:34:07', 'Accepted', 'Accepted', 'Accepted', 'Pending', 'Pending', 'Pending'),
(81, 134, '[\"691d8104a197e_IMG_20251031_132802.jpg\"]', '[\"691d8104a298c_IMG_20251031_132802.jpg\"]', '[\"691d8104a7a62_IMG_20251031_132802.jpg\"]', '', '', '', '2025-11-19 08:34:12', 'Pending', 'Pending', 'Pending', 'Pending', 'Pending', 'Pending'),
(82, 136, '[\"691d81408c33e_6c0e8f42-db94-4304-8b4f-74f8d009c31a.jpeg\"]', '[\"691d81408d408_Avatar Girls (1).jpeg\"]', '[\"691d81408df2e_\\u0e51.jpeg\"]', '[\"691d81408ed56_d14a1fcf-9a8f-461b-960d-256ab5132505.jpeg\"]', '[\"691d81408f679_\\ud81a\\uddb9 \\ud80c\\ude12.jpeg\"]', '[\"691d8140901f8_6c0e8f42-db94-4304-8b4f-74f8d009c31a.jpeg\"]', '2025-11-19 08:35:12', 'Pending', 'Pending', 'Pending', 'Pending', 'Pending', 'Pending'),
(83, 135, '[\"691d814d46a60_RobloxScreenShot20250609_183020395.png\"]', '[\"691d814d47a9d_RobloxScreenShot20250602_211212880.png\"]', '[\"691d814d48db9_RobloxScreenShot20250609_182836862.png\"]', '', '', '', '2025-11-19 08:35:25', 'Pending', 'Pending', 'Pending', 'Pending', 'Pending', 'Pending'),
(84, 131, '[\"691d8155c655e_inbound7577879650221455974.jpg\"]', '[\"691d8155c7308_inbound3041231238839680058.png\"]', '[\"691d8155cc4ba_inbound3337809499522403129.jpg\"]', '', '', '', '2025-11-19 08:35:33', 'Accepted', 'Accepted', 'Accepted', 'Pending', 'Pending', 'Pending'),
(85, 138, '[\"691d8289764f7_inbound4343413074929357193.jpg\"]', '[\"691d82897750f_inbound7332722617065737360.jpg\"]', '[\"691d828978588_inbound5281215535935361666.jpg\"]', '[\"691d828979e5e_inbound2173022482879270283.jpg\"]', '[\"691d82897b293_inbound2298527693868846872.jpg\"]', '[\"691d82897c73c_inbound2396083830500565584.jpg\"]', '2025-11-19 08:36:06', 'Pending', 'Pending', 'Pending', 'Pending', 'Pending', 'Pending'),
(86, 139, '[\"691d817c2e9cb_17635413282196714213074051637316.jpg\"]', '[\"691d817c2f5f8_17635413335605287109358137209435.jpg\"]', '[\"691d817c30700_17635413382491215773564706603956.jpg\"]', '[\"691d817c3155b_17635413487149094146413919739771.jpg\"]', '[\"691d817c32386_17635413553816313100023205223422.jpg\"]', '[\"691d817c34c96_17635413611562589498282003456463.jpg\"]', '2025-11-19 08:36:12', 'Accepted', 'Accepted', 'Accepted', 'Pending', 'Pending', 'Pending'),
(87, 137, '[\"691d8186231b2_17635413204861173214095400255792.jpg\"]', '[\"691d818623c80_17635413403354539072437725459249.jpg\"]', '[\"691d818626163_17635413584074512985044838637119.jpg\"]', '', '', '', '2025-11-19 08:36:22', 'Pending', 'Pending', 'Pending', 'Pending', 'Pending', 'Pending'),
(88, 140, '[\"691d824b07ee6_Screenshot_20251113-000628.png\"]', '[\"691d824b08b4b_IMG_20251117_200054.jpg\"]', '[\"691d824b0963b_IMG_20251117_200054.jpg\"]', '[\"691d824b0a0e7_att.JRSENbbF5lKMMiJeBF6RQAI_snSs_9heDhXeUKQ3T6o.png.jpeg\"]', '[\"691d824b0ac9e_Screenshot_20251113-000628.png\"]', '[\"691d824b0b758_Screenshot_20251113-000640.png\"]', '2025-11-19 08:39:39', 'Pending', 'Pending', 'Pending', 'Pending', 'Pending', 'Pending'),
(89, 141, '[\"691d8270d2a0d_inbound9030127602306966581.jpg\"]', '[\"691d8270d3dcd_inbound5586980775583061076.jpg\"]', '[\"691d8270d4bbc_inbound7759657786957409367.jpg\"]', '[\"691d8270d5e14_inbound702275390767113623.jpg\"]', '[\"691d8270d706c_inbound5291810321381981569.jpg\"]', '[\"691d8270d81f7_inbound1686416771511774489.jpg\"]', '2025-11-19 08:40:16', 'Pending', 'Pending', 'Pending', 'Pending', 'Pending', 'Pending'),
(90, 142, '[\"691d82e6cac6b_downloadgram.org_525680715_18321879256235842_544021017005555500_n_1.jpg\"]', '[\"691d82e6cbc16_1ff84e974ae10b7658330061c5a7664e.jpg\"]', '[\"691d82e6cc8d4_7d7a174d111bead64870a302311df9a3.jpg\"]', '[\"691d82e6cd1e4_8efb982e6f664cb4c7a966708faabce5.jpg\"]', '[\"691d82e6cdacf_ab341e9bacdbc8b08da218ffd1f35db8.jpg\"]', '[\"691d82e6d0d01_downloadgram.org_528017047_18322622974235842_6524294750209959628_n_1.jpg\"]', '2025-11-19 08:42:14', 'Pending', 'Pending', 'Pending', 'Pending', 'Pending', 'Pending'),
(91, 143, '[\"691dd5f4db82e_IMG_1619.jpeg\",\"691dd5f4dbee6_069555befafe271c090b991d381da513.jpeg\",\"691dd5f4dc5c7_EBBEAA10-D58D-486C-B995-5A6A281ED8FC.jpeg\"]', '[\"691dd5f4dd113_IMG_1619.jpeg\"]', '[\"691dd5f4dd82f_EBBEAA10-D58D-486C-B995-5A6A281ED8FC.jpeg\"]', '[\"691dd5f4ddf28_EBBEAA10-D58D-486C-B995-5A6A281ED8FC.jpeg\"]', '[\"691dd5f4de622_EBBEAA10-D58D-486C-B995-5A6A281ED8FC.jpeg\"]', '[\"691dd5f4dec6e_EBBEAA10-D58D-486C-B995-5A6A281ED8FC.jpeg\"]', '2025-11-19 14:36:36', 'Accepted', 'Accepted', 'Accepted', 'Pending', 'Pending', 'Pending'),
(92, 144, '[\"691ead2d9d6dd_gc.jpg\"]', '[\"691ead2d9e18b_pp.jpg\"]', '[\"691ead2d9ed31_gc.jpg\"]', '[\"691ead2d9f76d_gc.jpg\"]', '[\"691ead2da00df_gc.jpg\"]', '[\"691ead2da0d1a_gc.jpg\"]', '2025-11-20 05:54:53', 'Accepted', 'Accepted', 'Accepted', 'Accepted', 'Pending', 'Pending'),
(93, 145, '[\"691ebe89c55a2_20251120_142220.jpg\"]', '[\"691ebe89c6589_20251120_142220.jpg\"]', '[\"691ebe89c76ea_20251120_142220.jpg\"]', '[\"691ebe89c8674_20251120_142220.jpg\"]', '[\"691ebe89c95b2_20251120_142220.jpg\"]', '[\"691ebe89ca59d_20251120_142220.jpg\"]', '2025-11-20 07:08:57', 'Accepted', 'Accepted', 'Accepted', 'Accepted', 'Pending', 'Pending'),
(94, 147, '[\"691f04fc27407_1000007065.jpg\"]', '[\"691f04fc28483_1000007070.jpg\"]', '[\"691f04fc29091_1000007060.jpg\"]', '', '', '', '2025-11-20 12:09:32', 'Accepted', 'Accepted', 'Accepted', 'Pending', 'Pending', 'Pending'),
(95, 149, '[\"691f060ecda9f_inbound4137157935127823054.jpg\"]', '[\"691f060eceb49_inbound2893488716077640165.jpg\"]', '[\"691f060ed1f43_inbound9024873751940017024.jpg\"]', '', '', '', '2025-11-20 12:14:06', 'Accepted', 'Accepted', 'Accepted', 'Pending', 'Pending', 'Pending'),
(96, 153, '[\"691f0f90755d2_inbound8635272073753300261.jpg\"]', '[\"691f0f907704a_inbound6403447188175134198.jpg\"]', '[\"691f0f9077ebc_inbound6957547108029711254.jpg\"]', '', '', '', '2025-11-20 12:54:40', 'Pending', 'Pending', 'Pending', 'Pending', 'Pending', 'Pending'),
(97, 154, '[\"691f119f57fcd_inbound2365131136292118376.jpg\"]', '[\"691f119f5886c_inbound3184895441116344311.jpg\"]', '[\"691f119f594de_inbound2038403758232404159.jpg\"]', '', '', '', '2025-11-20 13:03:27', 'Accepted', 'Accepted', 'Accepted', 'Pending', 'Pending', 'Pending'),
(98, 160, '[\"691f1cfe1bf87_73b1b4ff-4961-4436-8920-050fc2c1c858.jpg\"]', '[\"691f1cfe1d194_92e44b6b-7d2e-41d9-b2f7-a1c159dc44fa.jpg\"]', '[\"691f1cfe1dd69_b1637b98-8d23-4c1f-b852-f3d5cc243d5c.jpg\"]', '', '', '', '2025-11-20 13:51:58', 'Accepted', 'Accepted', 'Accepted', 'Pending', 'Pending', 'Pending'),
(99, 152, '[\"691f3d9430356_Messenger_creation_2C8F25D0-869E-4A02-A1A6-CE1967E55759.jpeg\",\"691f3d943196a_Messenger_creation_AFD66C05-196F-4E65-9DA8-B48EEE6CE1DD.jpeg\"]', '[\"691f3d943266e_Messenger_creation_2C8F25D0-869E-4A02-A1A6-CE1967E55759.jpeg\",\"691f3d94330d7_Messenger_creation_AFD66C05-196F-4E65-9DA8-B48EEE6CE1DD.jpeg\"]', '[\"691f3eee9b8ea_IMG20251121001618.jpg\",\"691f3eee9cb19_IMG20251121001611.jpg\"]', '', '', '', '2025-11-20 16:11:00', 'Accepted', 'Accepted', 'Accepted', 'Pending', 'Pending', 'Pending'),
(100, 166, '[\"691fd250ae405_Screenshot_20251110-145658.jpg\"]', '[\"691fd250af7e1_Screenshot_20251110-150022.jpg\"]', '[\"691fd250b05d2_Screenshot_20251110-150038.jpg\"]', '[\"691fd250b1700_Screenshot_20251110-150113.jpg\"]', '[\"691fd250b4502_Screenshot_20251106-045927.jpg\"]', '', '2025-11-21 02:40:41', 'Accepted', 'Accepted', 'Accepted', 'Pending', 'Pending', 'Pending'),
(101, 163, '[\"691fd4bed3e45_inbound180121084739409648.png\"]', '[\"691fd4bed9eff_inbound6195971038534451438.jpg\"]', '[\"691fd4bed9eff_inbound6195971038534451438.jpg\"]', '[\"691fd309dc5ef_inbound763416703464872868.jpg\"]', '[]', '', '2025-11-21 02:40:41', 'Accepted', 'Accepted', 'Accepted', 'Pending', 'Pending', 'Pending'),
(102, 164, '[\"691fd1ac7a82b_inbound192644340655597699.jpg\"]', '[\"691fd1ac7b72f_inbound8013739915172521687.jpg\"]', '[\"691fd1ac7c8b4_inbound2233973624095194269.jpg\"]', '[\"691fd1ac7d378_inbound6253582996293578808.png\"]', '[\"691fd1ac7ddb8_inbound7987754590347964571.jpg\"]', '[\"691fd1ac80887_inbound6209688381036264157.jpg\"]', '2025-11-21 02:40:41', 'Accepted', 'Accepted', 'Accepted', 'Pending', 'Pending', 'Pending'),
(103, 162, '[\"691fd4bed9eff_inbound6195971038534451438.jpg\"]', '[\"691fd4bed9eff_inbound6195971038534451438.jpg\"]', '[\"691fd4bed9eff_inbound6195971038534451438.jpg\"]', '', '', '', '2025-11-21 02:40:41', 'Accepted', 'Accepted', 'Accepted', 'Pending', 'Pending', 'Pending'),
(104, 165, '[\"691fd1f997d32_orca-image-246199668.jpeg.jpeg\"]', '[\"691fd1f998e72_orca-image-246199668.jpeg.jpeg\"]', '[\"691fd1f999942_orca-image-246199668.jpeg.jpeg\"]', '[\"691fd1f99a4d4_orca-image-246199668.jpeg.jpeg\"]', '[\"691fd1f99afda_orca-image-246199668.jpeg.jpeg\"]', '[\"691fd1f99be58_orca-image-246199668.jpeg.jpeg\"]', '2025-11-21 02:43:04', 'Accepted', 'Accepted', 'Accepted', 'Pending', 'Pending', 'Pending'),
(105, 167, '[\"691fd75f779f3_1000014373.jpg\"]', '[\"691fd75f7a2f1_1000014373.jpg\"]', '[\"691fd75f7d36e_1000014373.jpg\"]', '[]', '[\"691fd7231a71c_1000014373.jpg\"]', '[\"691fd7231bb9a_1000014373.jpg\"]', '2025-11-21 02:44:45', 'Pending', 'Pending', 'Pending', 'Pending', 'Pending', 'Pending'),
(106, 169, '[\"691fd57a2cc11_319.png\"]', '[\"691fd57a2d37e_319.png\"]', '[\"691fd57a2deac_319.png\"]', '[\"691fd57a2e756_319.png\"]', '[\"691fd57a2ee81_319.png\"]', '[\"691fd57a2f583_319.png\"]', '2025-11-21 02:59:06', 'Accepted', 'Accepted', 'Accepted', 'Pending', 'Pending', 'Pending'),
(107, 146, '[\"691fdcf52907e_2238.jpg\"]', '[\"691fdd811a611_2243.jpg\"]', '[\"691fd1f997d32_orca-image-246199668.jpeg.jpeg\"]', '', '', '', '2025-11-21 03:27:40', 'Accepted', 'Accepted', 'Accepted', 'Pending', 'Pending', 'Pending'),
(108, 170, '[\"691fdcd6bc21d_583134066_1604744287199557_661647100529919907_n.jpg\"]', '[\"691fdcd6bd1e0_582584129_1323726935734159_4584024521031454945_n.jpg\"]', '[\"691fdcd6be6cc_582241480_1883789445850506_8830058529671379761_n.jpg\"]', '[\"691fdcd6bf918_Histogram1-92513160f945482e95c1afc81cb5901e.png\"]', '[\"691fdcd6c061e_582241480_1883789445850506_8830058529671379761_n.jpg\"]', '[\"691fdcd6c116d_541279345_795738682838329_4545384293914849928_n.jpg\"]', '2025-11-21 03:30:30', 'Accepted', 'Accepted', 'Accepted', 'Pending', 'Pending', 'Pending'),
(109, 171, '[\"691fdd40e81fd_inbound1867760400057288205.jpg\"]', '[\"691fdd40ec597_inbound4051183820099376292.jpg\"]', '[\"691fdd40edec8_inbound6392873955619478511.jpg\"]', '', '', '', '2025-11-21 03:32:16', 'Accepted', 'Accepted', 'Accepted', 'Pending', 'Pending', 'Pending'),
(110, 172, '[\"691fdfbd32eae_Screenshot_2025-11-16-14-40-44-28.jpg\"]', '[\"691fdfbd33dde_Screenshot_2025-11-16-16-08-13-57.jpg\"]', '[\"691fe15e51fb2_e25d882865d601537898de2b0bfb6173.jpg\"]', '[]', '[\"691fe15e59795_Screenshot_2025-11-16-16-08-13-57.jpg\"]', '', '2025-11-21 03:42:53', 'Accepted', 'Accepted', 'Accepted', 'Pending', 'Pending', 'Pending'),
(111, 173, '[\"691fe26d4cbbf_usecasescenario (2).jpg\"]', '[\"691fe26d4e7b0_usecasescenario (1).jpg\"]', '[\"691fe26d51350_usecasescenario.drawio.png\"]', '', '', '', '2025-11-21 03:54:05', 'Accepted', 'Accepted', 'Accepted', 'Pending', 'Pending', 'Pending');

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
(48, 20261017, 29, 14, 13.00, 14.00, '2025-11-13 08:52:46'),
(49, 20261051, 29, 14, 11.00, 14.00, '2025-11-20 07:15:13'),
(50, 20261013, 29, 14, 7.00, 14.00, '2025-11-20 08:14:57'),
(51, 20261022, 29, 14, 9.00, 14.00, '2025-11-20 08:57:39'),
(52, 20261089, 29, 14, 7.00, 14.00, '2025-11-20 13:37:09'),
(53, 20261029, 29, 14, 8.00, 14.00, '2025-11-20 13:47:58'),
(54, 20261123, 29, 14, 10.00, 14.00, '2025-11-21 02:58:24'),
(55, 20261118, 29, 14, 11.00, 14.00, '2025-11-21 03:08:42'),
(56, 20261119, 29, 14, 8.00, 14.00, '2025-11-21 03:09:03'),
(57, 20261120, 29, 14, 5.00, 14.00, '2025-11-21 03:09:22'),
(58, 20261122, 29, 14, 7.00, 14.00, '2025-11-21 03:11:02'),
(59, 20261054, 29, 14, 8.00, 14.00, '2025-11-21 03:43:29'),
(60, 20261078, 29, 14, 11.00, 14.00, '2025-11-21 03:46:06'),
(61, 20261067, 29, 14, 8.00, 14.00, '2025-11-21 03:46:59'),
(62, 20261127, 29, 14, 12.00, 14.00, '2025-11-21 03:50:31'),
(63, 20261128, 29, 14, 2.00, 14.00, '2025-11-21 03:57:24'),
(64, 20261129, 29, 14, 3.00, 14.00, '2025-11-21 03:59:23');

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
(29, 'A.Y. 2025-2026 Exam Day 1', 1, '2025-08-27 06:57:00', 'Published', 0, 60, 'Answer all questions to the best of your ability.', '2025-11-20 16:10:15', 3),
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
(22, 'BERMEJO', 'RUBEE MAE', 'matyasaqoe@gmail.com', '$2y$10$EtUGynpnW2fzl0WdcHyTf.dy4rmwqhgctBgPBmg3n.Tl7SJ7QZpq6', '2025-11-20 03:37:50', 3),
(23, 'LACABA', 'MICHAEL JHON', 'lacabamichaeljhon2@gmail.com', '$2y$10$LT6vI0ipVnb.TpWerhb73OzP8KZ4sGSPydfH9j/zgaUIwxJEQ.SSS', '2025-11-20 15:19:46', 3);

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  `is_archived` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `interview_schedules`
--

INSERT INTO `interview_schedules` (`id`, `event_date`, `event_time`, `venue`, `chair_id`, `created_at`, `archived`, `is_archived`) VALUES
(5, '2025-10-22', '05:31:00', 'LSAB - Room 311', 3, '2025-10-21 21:31:11', 0, 0),
(9, '2025-11-29', '14:00:00', 'LSAB - Room 311', 3, '2025-11-13 09:15:12', 0, 0),
(10, '2025-11-21', '12:00:00', 'etgb - Room 456, LSAB - Room 312', 3, '2025-11-20 07:18:37', 0, 0),
(11, '2025-11-28', '12:00:00', 'LSAB - Room 313', 3, '2025-11-20 08:58:31', 0, 0);

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
(9, 5, 20261012, '2025-11-19 19:47:00'),
(10, 5, 20261017, '2025-11-19 19:47:37'),
(11, 5, 20260034, '2025-11-19 19:47:37'),
(12, 5, 20260033, '2025-11-19 19:47:37'),
(13, 9, 20261051, '2025-11-20 07:18:49'),
(14, 5, 20261022, '2025-11-20 08:59:09'),
(15, 5, 20261078, '2025-11-21 03:59:59');

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
(115, 'Gubac', 'John Patrick', '', '2003-12-29', 21, 'Male', '09369073906', 'Western Visayas', 'Negros Occidental', 'Enrique B. Magalona', 'San Jose', 'Gamboa', '6916ab185fdc2.jpg', '2025-11-14 04:07:52'),
(116, 'Caboylo', 'Juliana Marie', 'dela cruz', '2003-07-23', 22, 'Female', '09940631136', 'Western Visayas', 'Negros Occidental', 'City of Silay', 'Bagtic', 'Brgy. Bagtic Silay City', '691d67913608e.jpg', '2025-11-19 06:45:37'),
(117, 'Broñola', 'Kyla', 'guevarra', '2002-09-08', 23, 'Female', '0483174041', 'Western Visayas', 'Negros Occidental', 'City of Silay', 'Barangay V (Pob.)', 'Purok Cabay Bodega', '691d68d2c808d.jpg', '2025-11-19 06:50:58'),
(118, 'Lanza', 'Francess', 'Chavez', '2006-12-01', 18, 'Female', '00981108530', 'Western Visayas', 'Negros Occidental', 'City of Talisay', 'Zone 10 (Pob.)', 'Rose St. ', '691d6d0abaa83.jpg', '2025-11-19 07:08:58'),
(119, 'Moises', 'Cristine Ella', 'Malutao', '2007-05-09', 18, 'Female', '09950582046', 'Western Visayas', 'Negros Occidental', 'City of Bacolod', 'Granada', 'Charito Heights Relocation Site', '691d6d5aa3793.jpg', '2025-11-19 07:10:18'),
(120, 'Melendres', 'Allyssa', 'Ignario', '2007-04-05', 18, 'Female', '09688654181', 'Western Visayas', 'Negros Occidental', 'Cauayan', 'Basak', 'Purok2', '691d6d9dbf250.jpeg', '2025-11-19 07:11:25'),
(121, 'Rico', 'Miane Marie', 'Nava', '2007-03-02', 18, 'Female', '09391825735', 'Western Visayas', 'Negros Occidental', 'City of Bacolod', 'Bata', 'Prk. Masinadyahon, Andrea Steet Zone 1', '691d6e702b3ab.jpg', '2025-11-19 07:14:56'),
(122, 'Magbanua', 'Andrei Joseph D.', 'D.', '2006-05-06', 19, 'Male', '09917799225', 'Western Visayas', 'Negros Occidental', 'City of Talisay', 'Efigenio Lizares', 'Efigenio Lizarez ', '691d7085ed945.jpg', '2025-11-19 07:23:49'),
(123, 'Cango', 'Shannen', '', '2004-06-16', 21, 'Female', '09282828282', 'Western Visayas', 'Negros Occidental', 'City of Talisay', 'Zone 9 (Pob.)', 'Industria Street', '691d756e18436.jpg', '2025-11-19 07:44:46'),
(124, 'Daquiado ', 'Glendjohn ', 'Cataylo', '2007-07-07', 18, 'Male', '09991939613', 'Western Visayas', 'Negros Occidental', 'City of Talisay', 'Zone 12-A (Pob.)', 'Lot 22 BLK 13 st.peter', '691d7bf23294e.jpg', '2025-11-19 08:12:34'),
(125, 'Charles Kalvin O.', 'Valenzuela,', 'oquias', '2004-12-01', 20, 'Male', '09940496643', 'Western Visayas', 'Negros Occidental', 'City of Silay', 'Lantad', 'Berano Bodega', '691d7fcbf053f.jpg', '2025-11-19 08:28:59'),
(126, 'SALINAS', 'THEA', 'Marquez', '2005-03-03', 20, 'Female', '09071184859', 'Western Visayas', 'Negros Occidental', 'City of Talisay', 'Zone 7 (Pob.)', 'Blk 2 Lot 37', '691d7fe4ab9f7.heic', '2025-11-19 08:29:24'),
(127, 'Francis Jude C.', 'Dojoles,', 'Cabrera', '2004-11-17', 21, 'Male', '09916773796', 'Western Visayas', 'Negros Occidental', 'Moises Padilla', 'Crossing Magallon', 'Sitio Dap-Dap Brgy Lantad', '691d7ff055f6a.jpg', '2025-11-19 08:29:36'),
(128, 'Cruz', 'Edrei Josh', '', '2004-10-26', 21, 'Male', '09686357647', 'Western Visayas', 'Negros Occidental', 'City of Victorias', 'Barangay IX', 'Toreno Heights Phase 2 ', '691d80073a58a.jpg', '2025-11-19 08:29:59'),
(129, 'Janshen', 'Senope', 'Villoso', '2004-05-01', 21, 'Male', '00123456789', 'Western Visayas', 'Negros Occidental', 'City of Talisay', 'Zone 2 (Pob.)', 'Mabini', '691d800b835fe.jpg', '2025-11-19 08:30:03'),
(130, 'Astodillo', 'Rainer', 'Cahilo', '2004-09-18', 21, 'Male', '09070034857', 'Western Visayas', 'Negros Occidental', 'Enrique B. Magalona', 'Tuburan', 'Zone 3', '691d8035ba6b7.jpg', '2025-11-19 08:30:45'),
(131, 'Bretaña', 'Mia Allysa', 'Tolosa', '2004-06-09', 21, 'Female', '09917345286', 'Western Visayas', 'Negros Occidental', 'City of Talisay', 'Zone 3 (Pob.)', 'Purok Greenshell ', '691d80503f9e6.jpg', '2025-11-19 08:31:12'),
(132, 'Jhey Ree', 'Ebro', 'Ceñidoza', '2005-02-19', 20, 'Male', '09380473138', 'Western Visayas', 'Negros Occidental', 'City of Bacolod', 'Banago', 'Sto. Domingo ', '691d805ab1863.jpg', '2025-11-19 08:31:22'),
(133, 'Setias', 'Caryl mae', 'Truces', '2004-04-16', 21, 'Female', '09859735401', 'Western Visayas', 'Negros Occidental', 'Hinigaran', 'Miranda', 'BULOBITO-ON', '691d805c96d08.jpg', '2025-11-19 08:31:24'),
(134, 'Maming', 'Arlene', 'Baclason', '2005-01-17', 20, 'Female', '09094262914', 'Western Visayas', 'Negros Occidental', 'Manapla', 'Punta Mesa', 'Hda. Lourdes ', '691d806996c71.jpg', '2025-11-19 08:31:37'),
(135, 'Mationg', 'Kheishia Faith', 'Gustilo', '2004-10-09', 21, 'Female', '09930637977', 'Western Visayas', 'Negros Occidental', 'City of Bacolod', 'Bata', 'Prk. Katilingban', '691d80906cfef.jpg', '2025-11-19 08:32:16'),
(136, 'Margaha', 'Rubelyn', 'Flores', '2005-01-19', 20, 'Female', '09056189675', 'Western Visayas', 'Negros Occidental', 'City of Sagay', 'Paraiso', 'Housing Phase 1, Blk-16-B-Lot-27', '691d809b495d2.jpg', '2025-11-19 08:32:27'),
(137, 'Sunshine', 'Presquito', '', '2003-12-03', 21, 'Female', '09304801625', 'Western Visayas', 'Negros Occidental', 'La Castellana', 'Robles (Pob.)', 'Gomez Street', '691d80ebeb01c.jpg', '2025-11-19 08:33:47'),
(138, 'Zacarias', 'Leo', 'Taboclaon', '2002-11-17', 23, 'Male', '09691080024', 'Western Visayas', 'Negros Occidental', 'City of Bacolod', 'Bata', 'Purok Pag-Isa', '691d80ed28e81.jpg', '2025-11-19 08:33:49'),
(139, 'Jason A.', 'Saldua,', 'Alvarez', '2005-01-19', 20, 'Male', '09460056944', 'Western Visayas', 'Negros Occidental', 'City of Silay', 'Guimbala-on', 'Hda. Tionko', '691d812aaf726.jpg', '2025-11-19 08:34:50'),
(140, 'Oñate', 'Angela', 'maguate', '2004-11-07', 21, 'Female', '09933864528', 'Western Visayas', 'Negros Occidental', 'City of Talisay', 'Zone 5 (Pob.)', 'Jayme Street, Purok Punao ', '691d813e9c1fa.jpeg', '2025-11-19 08:35:10'),
(141, 'Viñas', 'Katrina Grace', 'Pesanon', '2003-12-21', 21, 'Female', '09939212387', 'Western Visayas', 'Negros Occidental', 'City of Silay', 'Bagtic', 'Hda. Bagacay', '691d819c7c242.jpg', '2025-11-19 08:36:44'),
(142, 'Toroy', 'Mariah Jella', 'mAGULIMAN', '2003-08-28', 22, 'Female', '0951976006', 'Western Visayas', 'Negros Occidental', 'City of Bacolod', 'Punta Taytay', 'Prk. Mahinangpanon', '691d81fc53fc0.jpg', '2025-11-19 08:38:20'),
(143, 'Regalado', 'Ashley', '', '2001-03-19', 24, 'Male', '09263636363', 'Western Visayas', 'Negros Occidental', 'Enrique B. Magalona', 'Poblacion II', 'Purok lerio', '691dd5a96ee28.jpeg', '2025-11-19 14:35:21'),
(144, 'porrAS', 'GUENEVERE', 'celebre', '2006-05-16', 19, 'Female', '09676625252', 'Western Visayas', 'Negros Occidental', 'City of Talisay', 'Concepcion', 'SITIO CASA, CELEBRE RESIDENCE', '691eac4cbb71c.jpg', '2025-11-20 05:51:08'),
(145, 'Bagahansol', 'Jasmin', 'flores', '2003-10-08', 22, 'Female', '00992460608', 'Western Visayas', 'Negros Occidental', 'City of Talisay', 'Zone 1 (Pob.)', 'Bangga Dose', '691ebd8279b35.jpg', '2025-11-20 07:04:34'),
(146, 'Komori', 'Yohei', 'Balaguis ', '2006-03-11', 19, 'Male', '09387563898', 'Western Visayas', 'Negros Occidental', 'City of Silay', 'Lantad', 'Sitio Beraño', '691f0116eaa9b.jpg', '2025-11-20 11:52:54'),
(147, 'Alfonso', 'karen kaye', 'Tiongco', '2003-09-11', 22, 'Female', '09453010692', 'Western Visayas', 'Negros Occidental', 'City of Bacolod', 'Sum-ag', 'Prk. Kasanagan', '691f04433fe54.jpg', '2025-11-20 12:06:27'),
(148, 'Penetrante', 'Frederick jr.', 'Rojo', '2007-05-03', 18, 'Male', '09511221139', 'Western Visayas', 'Negros Occidental', 'City of Talisay', 'Zone 12-A (Pob.)', 'Blk. 24 Lot 10, Carmela Valley Homes', '691f044e5f038.jpeg', '2025-11-20 12:06:38'),
(149, 'Alvarez', 'Joanna Marie', 'Miraflor ', '2007-03-20', 18, 'Female', '09948212535', 'Western Visayas', 'Negros Occidental', 'Murcia', 'Santa Rosa', 'Purok. Puncian', '691f0511e9e7f.png', '2025-11-20 12:09:53'),
(150, 'Erasmo', 'Dhanice', 'Marinduque ', '2006-11-19', 19, 'Female', '09811794304', 'Western Visayas', 'Negros Occidental', 'Enrique B. Magalona', 'Alicante', 'Prk. Masagana, T-92', '691f07cf9f0dc.jpg', '2025-11-20 12:21:35'),
(151, 'Trecho', 'john Michael', 'Santillan', '2004-05-31', 21, 'Male', '09451762928', 'Western Visayas', 'Negros Occidental', 'City of Talisay', 'Zone 12-A (Pob.)', 'Fatima Subdivision ', '691f0907e44e1.jpg', '2025-11-20 12:26:47'),
(152, 'Dence', 'Kherbymhar', 'Fernandez ', '2006-11-18', 19, 'Female', '09931632249', 'Western Visayas', 'Negros Occidental', 'City of Sagay', 'Paraiso', 'Paraiso Heights (Phase 2) Block 25 Lot 41', '691f0dfccdfd4.jpeg', '2025-11-20 12:47:45'),
(153, 'Trejeros', 'Paul jhared', 'Gajeto', '2009-08-24', 16, 'Male', '09534478412', 'Western Visayas', 'Negros Occidental', 'City of Talisay', 'Concepcion', 'HDA CATAYWA', '691f0ef39b849.webp', '2025-11-20 12:52:03'),
(154, 'Alamis', 'John paul', 'Colipapa', '2003-01-13', 22, 'Male', '09133456789', 'Western Visayas', 'Negros Occidental', 'City of Talisay', 'Zone 4-A (Pob.)', 'Doña Enrica', '691f111c3878f.jpg', '2025-11-20 13:01:16'),
(155, 'flores', 'jacquilyn', 'Obat', '2009-04-14', 16, 'Female', '0970465433', 'Western Visayas', 'Negros Occidental', 'City of Talisay', 'Zone 12-A (Pob.)', 'Carmela valley homes Block 30 lot9', '691f145c1f5a5.jpg', '2025-11-20 13:15:08'),
(156, 'Dechimo', 'Zara Jaztine', 'Junto', '2007-01-20', 18, 'Female', '09682058509', 'Western Visayas', 'Negros Occidental', 'Valladolid', 'Palaka', 'Purok 1', '691f15f083b63.png', '2025-11-20 13:21:52'),
(157, 'Gatuslao', 'Lord Cedrick', 'Cervantes ', '2009-01-08', 16, 'Male', '00985982299', 'Western Visayas', 'Negros Occidental', 'City of Talisay', 'Cabatangan', 'Hope gk', '691f16b919764.jpg', '2025-11-20 13:25:13'),
(158, 'Villar', 'John michael', '', '2007-01-10', 18, 'Male', '09950589314', 'Western Visayas', 'Negros Occidental', 'City of Bacolod', 'Taculing', 'Sharina Heights', '691f1741ca338.jpg', '2025-11-20 13:27:29'),
(159, 'pallorina', 'Aira', 'Condesa', '2006-11-15', 19, 'Female', '09852115213', 'Western Visayas', 'Negros Occidental', 'City of Silay', 'Mambulac', 'Barra Lakturan ', '691f1c2c5e843.jpg', '2025-11-20 13:48:28'),
(160, 'CAYAO', 'NICO', '', '2003-03-11', 22, 'Male', '09319790472', 'Western Visayas', 'Negros Occidental', 'City of Silay', 'Rizal', 'Hda. aurora 2 brgy. rizal', '691f1c64c5a86.png', '2025-11-20 13:49:24'),
(161, 'Sumicad', 'Ma.Mae', 'Tejamo', '2007-04-29', 18, 'Female', '09317607414', 'Western Visayas', 'Negros Occidental', 'Enrique B. Magalona', 'Latasan', 'So. San Francisco Brgy. Latasan E.B.Magalona', '691f1e7d0f3eb.png', '2025-11-20 13:58:21'),
(162, 'Polinar', 'John Michael', 'Leonico', '2006-01-26', 19, 'Male', '09162653377', 'Western Visayas', 'Negros Occidental', 'City of Victorias', 'Barangay XIII', 'Sitio So-ol ', '691fcff12f44c.jpg', '2025-11-21 02:35:29'),
(163, 'DusaraN', 'Xervy', 'Burlan', '2006-09-27', 19, 'Male', '09668026781', 'Western Visayas', 'Negros Occidental', 'City of Silay', 'Barangay VI Pob.', 'Crossing Malisbog ', '691fd009dbde0.webp', '2025-11-21 02:35:53'),
(164, 'Bibanco', 'Justine', 'Mongado', '2000-11-04', 25, 'Male', '09321456789', 'Western Visayas', 'Negros Occidental', 'City of Talisay', 'Cabatangan', 'Hope village', '691fd0332bf8c.jpg', '2025-11-21 02:36:35'),
(165, 'Robles', 'Paul Benedict', 'Llada', '2005-06-09', 20, 'Male', '00193925552', 'Western Visayas', 'Negros Occidental', 'City of Silay', 'Guinhalaran', 'Carmela phase 4 ', '691fd03367b40.jpeg', '2025-11-21 02:36:35'),
(166, 'Hilo', 'Carlo', '', '2006-04-28', 19, 'Male', '09459660363', 'Western Visayas', 'Negros Occidental', 'City of Silay', 'Barangay IV (Pob.)', 'Hda. Panaogao 3 Marland ', '691fd077eddc6.jpg', '2025-11-21 02:37:43'),
(167, 'Bivoso', 'Meryl', 'Batiles', '2004-09-05', 21, 'Female', '09956255413', 'Western Visayas', 'Negros Occidental', 'City of Bago', 'Dulao', 'Purok Ramos', '691fd0d38f8de.jpg', '2025-11-21 02:39:15'),
(168, 'alegora', 'jian louise', 'Berueda', '2005-08-20', 20, 'Female', '09459667388', 'Western Visayas', 'Negros Occidental', 'City of Bago', 'Tabunan', 'Purok Kabatuhan', '691fd140bfb3b.jpg', '2025-11-21 02:41:04'),
(169, 'Perez', 'jet Eliseo', '', '2005-09-09', 20, 'Male', '00966924530', 'Western Visayas', 'Negros Occidental', 'City of Cadiz', 'Barangay 3 Pob.', 'Purok Sea Breeze ', '691fd51de8ca1.jpg', '2025-11-21 02:57:33'),
(170, 'Subaldo', 'aidan', '-', '2007-06-14', 18, 'Male', '09671742973', 'Western Visayas', 'Negros Occidental', 'City of Silay', 'Barangay V (Pob.)', 'Purok Tinapok', '691fdbf989d21.jpg', '2025-11-21 03:26:49'),
(171, 'Ferrer', 'Janfrel', 'Aplaon', '2004-12-14', 20, 'Male', '09813447335', 'Western Visayas', 'Negros Occidental', 'City of Sipalay', 'Maricalum', 'Buenafe', '691fdc9e2dcf0.jpg', '2025-11-21 03:29:34'),
(172, 'Labto', 'Kenneth jhey', 'Majan', '2007-06-04', 18, 'Male', '09507781544', 'Western Visayas', 'Negros Occidental', 'Enrique B. Magalona', 'Alicante', 'N/A', '691fdebe30986.jpg', '2025-11-21 03:38:38'),
(173, 'Sotela', 'Michael', 'tumlos', '2006-12-26', 18, 'Male', '09640923124', 'Western Visayas', 'Negros Occidental', 'Enrique B. Magalona', 'Madalag', 'purok 3', '691fe198c01dd.jpg', '2025-11-21 03:50:48');

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
(26, 25, 'Talisay', 'CCS', 'BSIS', '2025-10-06 09:04:45', 20260032),
(27, 26, 'Talisay', 'CCS', 'BSIS', '2025-10-10 13:32:42', 20260033),
(28, 27, 'Talisay', 'CCS', 'BSIS', '2025-10-19 10:53:49', 20260034),
(59, 107, 'Alijis', 'CCS', 'BSIS', '2025-11-07 09:27:06', 20260035),
(60, 108, 'Talisay', 'CCS', 'BSIS', '2025-11-10 14:07:28', 20261010),
(61, 109, 'Talisay', 'CCS', 'BSIS', '2025-11-12 04:19:08', 20261011),
(62, 110, 'Talisay', 'CCS', 'BSIS', '2025-11-12 07:21:19', 20261013),
(63, 111, 'Talisay', 'CCS', 'BSIS', '2025-11-12 07:50:55', 20261012),
(64, 112, 'Talisay', 'CCS', 'BSIS', '2025-11-12 12:21:48', 20261016),
(65, 113, 'Talisay', 'CCS', 'BSIS', '2025-11-13 08:14:30', 20261017),
(66, 114, 'Alijis', 'CCS', 'BSIS', '2025-11-14 04:01:19', 20261014),
(67, 115, 'Alijis', 'CCS', 'BSIS', '2025-11-14 04:08:40', 20261015),
(68, 116, 'Talisay', 'CCS', 'BSIS', '2025-11-19 06:47:13', 20261021),
(69, 117, 'Talisay', 'CCS', 'BSIS', '2025-11-19 06:52:00', 20261022),
(70, 118, 'Talisay', 'CCS', 'BSIS', '2025-11-19 07:10:02', 20261024),
(71, 120, 'Talisay', '', 'BSIS', '2025-11-19 07:12:47', 20261025),
(72, 119, 'Talisay', '', 'BSIS', '2025-11-19 07:13:17', 20261023),
(73, 121, 'Talisay', '', 'BSIS', '2025-11-19 07:16:13', 20261026),
(74, 122, 'Talisay', 'CCS', 'BSIS', '2025-11-19 07:25:16', 20261028),
(75, 123, 'Talisay', 'CCS', 'BSIS', '2025-11-19 07:45:53', 20261029),
(76, 124, 'Talisay', 'CCS', 'BSIS', '2025-11-19 08:13:35', 20261030),
(77, 125, 'Talisay', 'CCS', 'BSIS', '2025-11-19 08:29:49', 20261034),
(78, 126, 'Talisay', '', 'BSIS', '2025-11-19 08:30:15', 20261031),
(79, 127, 'Talisay', '', 'BSIS', '2025-11-19 08:30:30', 20261036),
(80, 129, 'Talisay', 'CCS', 'BSIS', '2025-11-19 08:30:45', 20261032),
(81, 128, 'Talisay', 'CCS', 'BSIS', '2025-11-19 08:31:12', 20261033),
(82, 132, 'Talisay', 'CCS', 'BSIS', '2025-11-19 08:32:14', 20261035),
(83, 133, 'Talisay', '', 'BSIS', '2025-11-19 08:32:25', 20261041),
(84, 130, 'Talisay', 'CCS', 'BSIS', '2025-11-19 08:32:33', 20261037),
(85, 134, 'Talisay', '', 'BSIS', '2025-11-19 08:32:35', 20261044),
(86, 135, 'Talisay', 'CCS', 'BSIS', '2025-11-19 08:33:15', 20261042),
(87, 131, 'Talisay', 'CCS', 'BSIS', '2025-11-19 08:33:25', 20261045),
(88, 136, 'Talisay', 'CCS', 'BSIS', '2025-11-19 08:33:39', 20261040),
(89, 137, 'Talisay', '', 'BSIS', '2025-11-19 08:35:03', 20261038),
(90, 139, 'Talisay', 'CCS', 'BSIS', '2025-11-19 08:35:21', 20261039),
(91, 138, 'Talisay', 'CCS', 'BSIS', '2025-11-19 08:35:33', 20261046),
(92, 140, 'Talisay', '', 'BSIS', '2025-11-19 08:36:31', 20261043),
(93, 141, 'Talisay', 'CCS', 'BSIS', '2025-11-19 08:38:06', 20261047),
(94, 142, 'Talisay', 'CCS', 'BSIS', '2025-11-19 08:39:53', 20261048),
(95, 143, 'Talisay', 'CCS', 'BSIS', '2025-11-19 14:35:51', 20261020),
(96, 144, 'Talisay', 'CCS', 'BSIS', '2025-11-20 05:53:27', 20261050),
(97, 145, 'Talisay', 'CCS', 'BSIS', '2025-11-20 07:06:11', 20261051),
(98, 147, 'Talisay', 'CCS', 'BSIS', '2025-11-20 12:07:58', 20261053),
(99, 148, 'Talisay', 'CCS', 'BSIS', '2025-11-20 12:08:34', 20261062),
(100, 146, 'Talisay', 'CCS', 'BSIS', '2025-11-20 12:10:53', 20261054),
(101, 149, 'Talisay', '', 'BSIS', '2025-11-20 12:11:14', 20261066),
(102, 150, 'Talisay', '', 'BSIS', '2025-11-20 12:23:18', 20261074),
(103, 151, 'Talisay', 'CCS', 'BSIS', '2025-11-20 12:28:08', 20261076),
(104, 153, 'Talisay', 'CCS', 'BSIS', '2025-11-20 12:53:30', 20261080),
(105, 154, 'Talisay', '', 'BSIS', '2025-11-20 13:02:07', 20261089),
(106, 156, 'Talisay', 'CCS', 'BSIS', '2025-11-20 13:23:28', 20261097),
(107, 158, 'Talisay', 'CCS', 'BSIS', '2025-11-20 13:28:55', 20261099),
(108, 157, 'Talisay', 'CCS', 'BSIS', '2025-11-20 13:30:10', 20261091),
(109, 155, 'Talisay', 'CCS', 'BSIS', '2025-11-20 13:30:21', 20261088),
(110, 159, 'Talisay', '', 'BSIS', '2025-11-20 13:50:00', 20261107),
(111, 160, 'Talisay', 'CCS', 'BSIS', '2025-11-20 13:50:46', 20261112),
(112, 161, 'Talisay', '', 'BSIS', '2025-11-20 14:03:19', 20261115),
(113, 152, 'Talisay', 'CCS', 'BSIS', '2025-11-20 16:09:25', 20261067),
(114, 162, 'Talisay', 'CCS', 'BSIS', '2025-11-21 02:37:56', 20261119),
(115, 163, 'Talisay', 'CCS', 'BSIS', '2025-11-21 02:38:29', 20261118),
(116, 164, 'Talisay', '', 'BSIS', '2025-11-21 02:38:29', 20261117),
(117, 166, 'Talisay', 'CCS', 'BSIS', '2025-11-21 02:38:50', 20261123),
(118, 167, 'Talisay', 'CCS', 'BSIS', '2025-11-21 02:41:04', 20261121),
(119, 165, 'Talisay', '', 'BSIS', '2025-11-21 02:41:13', 20261122),
(120, 169, 'Talisay', '', 'BSIS', '2025-11-21 02:58:33', 20261120),
(121, 170, 'Talisay', 'CCS', 'BSIS', '2025-11-21 03:28:55', 20261078),
(122, 171, 'Talisay', 'CCS', 'BSIS', '2025-11-21 03:31:30', 20261127),
(123, 172, 'Talisay', 'CCS', 'BSIS', '2025-11-21 03:40:14', 20261128),
(124, 173, 'Talisay', '', 'BSIS', '2025-11-21 03:52:57', 20261129);

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
(20260033, 'REGALADO', 'ASHLEY CARYLL', 'acregalado.chmsu@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$hzYCC1P4wKOhj0gSV3SVuuc9Ur0G5jHxfDVBtNyTYa9ZNGMCpjVgC', 26, '2025-11-20 04:17:05', '2025-10-10 21:25:49', 1),
(20260034, 'guiagogo', 'mark lloyd', 'marklloyd.guiagogo@chmsc.edu.ph', 'New Applicant - Same Academic Year', '$2y$10$N5o9Jb8V7DCb4VPMDdMPR.O2DQITqglTlDATFdsRIsWiOZZ33/68y', 27, '2025-11-07 22:18:22', '2025-10-19 03:50:12', 1),
(20260035, 'ROJO', 'MEG RYAN', 'regalado.ashleycaryll@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$Iss2zcV/GoRJZbozB4mKdOU7BqbW38gsjejw.isYtbJLSVbBAJVUO', 107, '2025-11-07 17:27:47', '2025-10-22 10:31:30', 1),
(20261009, 'Guiagogo', 'Mark Lloyd', 'mmguiagogo.chmsu@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$8g712s7xyPK9UZPC6MI2Mu792FagLY7hb/3ICTCsM0guAelf70zya', NULL, '2025-11-07 20:43:40', '2025-11-07 20:43:40', 0),
(20261010, 'Regalado', 'Ashley Caryll', 'ashleynteah.regalado@gmail.com', 'Transferee', '$2y$10$q..LzzcZyKwWhJhkxjzm..pa6ZiMXJxdfTBL0jqS3F.r8gHh/Wq92', 108, '2025-11-10 22:10:20', '2025-11-10 22:03:12', 1),
(20261011, 'Casipe', 'Beverly', 'bcasipe.chmsu@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$Fpa8si4U3n81hJaVXiyE1uyCVoHCLaLruh8r9ZuKll5zqmUrFA1yK', 109, '2025-11-12 12:20:02', '2025-11-12 12:11:43', 1),
(20261012, 'Villarna', 'Ryn Aj', 'ajvillarna10@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$Iss2zcV/GoRJZbozB4mKdOU7BqbW38gsjejw.isYtbJLSVbBAJVUO', 111, '2025-11-12 15:51:33', '2025-11-12 13:40:22', 1),
(20261013, 'Orcajada', 'Gael Gabriel', 'orcajadagael@gmail.com', 'New Applicant - Same Academic Year', '$2a$12$Rzm3g4ZtteBonqF8LzWSve4Iu2tuQy/V5dbGGgnNvkPYKzlGXZWq2', 110, '2025-11-20 16:13:55', '2025-11-12 14:17:31', 1),
(20261014, 'Dejan', 'Japheth', 'jap.dej22@gmail.com', 'New Applicant - Same Academic Year', '$2a$12$Rzm3g4ZtteBonqF8LzWSve4Iu2tuQy/V5dbGGgnNvkPYKzlGXZWq2', 114, '2025-11-20 16:13:04', '2025-11-12 14:23:12', 1),
(20261015, 'Gubac', 'John Patrick', 'jpsgubac.chmsu@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$Iss2zcV/GoRJZbozB4mKdOU7BqbW38gsjejw.isYtbJLSVbBAJVUO', 115, '2025-11-14 12:09:22', '2025-11-12 14:23:12', 1),
(20261016, 'Ilon', 'Dennisse Nicole', 'regaladodennissenicole@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$Iss2zcV/GoRJZbozB4mKdOU7BqbW38gsjejw.isYtbJLSVbBAJVUO', 112, '2025-11-14 09:45:16', '2025-11-12 20:11:39', 1),
(20261017, 'casipe', 'beverly', 'beverly.casipe@chmsc.edu.ph', 'New Applicant - Same Academic Year', '$2y$10$d0OmL6ClsrnAbsTnJWzeq.VHa0X0XCRKayLijQO4mYzHSzpA66ply', 113, '2025-11-13 16:24:15', '2025-11-13 16:05:04', 1),
(20261018, 'Ortega', 'Kert', 'kertortega99@gmail.com', 'Transferee', '$2y$10$M6C7DdHGNl7b7snPbq.C7uRZrYwPDZdNQ1aXx9ESvqxUHH9zYqUk.', NULL, '2025-11-19 13:03:29', '2025-11-19 13:03:29', 0),
(20261019, 'Doromal', 'Rovie Alfred', 'rsdoromal.chmsu@gmail.com', 'New Applicant - Previous Academic Year', '$2y$10$BzBrKDHfDmxz9HC5mSj2VuZwUtbBuMhRE7MjVpHzSzilanuvAKTyu', NULL, '2025-11-19 13:07:52', '2025-11-19 13:07:52', 0),
(20261020, 'Regalado', 'Ashley', 'bsis2d.regalado.ashleycaryll@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$kkXMzx22IkzKFPnr5l9Re.OS3RppaD2z3YyTF/Q/YKSFltAkBCpEm', 143, '2025-11-19 22:38:16', '2025-11-19 13:33:07', 1),
(20261021, 'Caboylo', 'Juliana Marie', 'julianacaboylo.chmsu@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$h77bTCR3pXSNERB1jfXI6eHNfKCxFPPWi3Yl1b2uxPy1rnxOkUkuW', 116, '2025-11-19 14:49:27', '2025-11-19 14:25:28', 1),
(20261022, 'Broñola', 'Kyla', 'kylamaygbronola@gmail.com', 'New Applicant - Same Academic Year', '$2a$12$Rzm3g4ZtteBonqF8LzWSve4Iu2tuQy/V5dbGGgnNvkPYKzlGXZWq2', 117, '2025-11-20 16:53:19', '2025-11-19 14:47:26', 1),
(20261023, 'Moises', 'Cristine Ella', 'cristineellamoises@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$jmblIMaxUZFHfeztWYJDw.OkZsJSqkoR5mlPRy7FaIIraghkS7a32', 119, '2025-11-19 15:14:48', '2025-11-19 15:04:22', 1),
(20261024, 'Lanza', 'Francess', 'francesslanza@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$6Dd5SSxoNulzK30wvgt0tudWMyCL4F4cHFiKiMtMCzkdob9qKJ.rW', 118, '2025-11-19 15:13:06', '2025-11-19 15:04:22', 1),
(20261025, 'Melendres', 'Allyssa', 'lysabog@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$Ys9DOkdCN4gRtoHKT.s1rO9rSKJL2pf8/s21bWCAOvPLdRzB3hslW', 120, '2025-11-19 15:13:52', '2025-11-19 15:05:51', 1),
(20261026, 'Rico', 'Miane Marie', 'miane.rico02@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$3pArVYF1FCRqgUBx7u.ole5TGGd54TrSLv2.HqLcdi76a/EhdP2PS', 121, '2025-11-19 15:17:28', '2025-11-19 15:11:06', 1),
(20261027, 'Tumabing', 'Danes', 'dtumabing@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$S8RC4Qn85xcthShovf7EBOLZTLHyGO6elLJTRjcokqtoG4CF1nH26', NULL, '2025-11-19 15:12:58', '2025-11-19 15:12:58', 0),
(20261028, 'Magbanua', 'Andrei Joseph D.', 'andreimagbanua92@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$.gxl4T/q.qaODyMuyF/Cd.8VjPyCY8NXqkSEqBt6qp74t50fLtoVa', 122, '2025-11-19 15:26:55', '2025-11-19 15:19:58', 1),
(20261029, 'Cango', 'Shannen', 'scango.chmsu@gmail.com', 'New Applicant - Previous Academic Year', '$2a$12$Rzm3g4ZtteBonqF8LzWSve4Iu2tuQy/V5dbGGgnNvkPYKzlGXZWq2', 123, '2025-11-20 16:52:41', '2025-11-19 15:39:21', 1),
(20261030, 'Glen', 'Enjo', 'kingenjo777@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$Lc4AYK95tVAnWzuUum6h0uqYQfJbRSpRi5Rdy3yTP9uw05jBHgVTO', 124, '2025-11-19 16:14:47', '2025-11-19 16:09:27', 1),
(20261031, 'SALINAS', 'THEA', 'salinas.thea2021@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$6j8V5AmeYGXaPf5qqruEa.WgOq4NB4iD7BExNOtd9aWPRxHaETpXO', 126, '2025-11-19 16:36:23', '2025-11-19 16:25:42', 1),
(20261032, 'Janshen', 'Senope', 'janshensenop3@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$NAEFVNSnUbGvhvwyha6Mlu9zT.f56AsGKBaiaeTEt4jAFuDtpMc9.', 129, '2025-11-19 16:31:47', '2025-11-19 16:26:20', 1),
(20261033, 'Cruz', 'Edrei Josh', 'ejcruz.chmsu@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$3Ozz1bStYH4FboUiZiI5xeYqRUy1rveQE41piJnV0ADc/IDODSGaq', 128, '2025-11-19 16:35:15', '2025-11-19 16:26:35', 1),
(20261034, 'Charles Kalvin O.', 'Valenzuela,', 'ckovalenzuela.chmsu@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$XpUCFgJLS.lbkVIl.JQTU.ZsZgZv8zdSo1kS20e2MXS8K20N8ymFq', 125, '2025-11-19 16:30:46', '2025-11-19 16:26:41', 1),
(20261035, 'Jhey Ree', 'Ebro', 'jheyreeebro19.chmsu@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$Wo2mkp9GczpjNvItqlRMR.GlOgK98ohGJg3kSHjWozU.x4Dl6pwIi', 132, '2025-11-19 16:38:18', '2025-11-19 16:26:43', 1),
(20261036, 'Francis Jude C.', 'Dojoles,', 'francisjudecabrera17@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$IyS7w3Up6pJ6pS81zkzN8.NiE6xMZozqdmgh0IqlsC.dySqzY1OpG', 127, '2025-11-19 16:31:22', '2025-11-19 16:26:45', 1),
(20261037, 'Astodillo', 'Rainer', 'rainerc.astodillo.chmsu@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$/Y/pu4Vcu4J03DZa2slgVO95EWLdxWNdu21tfJq6RJSLk8OHwlDYO', 130, '2025-11-19 16:35:38', '2025-11-19 16:26:52', 1),
(20261038, 'Sunshine', 'Presquito', 'sunshinevillalon@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$64DsaSv11gCWRg5NabyOO.ym8Uny529F5PQVWqX2T2ufOlcS8feDy', 137, '2025-11-19 16:36:39', '2025-11-19 16:26:54', 1),
(20261039, 'Jason A.', 'Saldua,', 'salduajason19@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$K7dTpMeqDNcH5aP2/0sC4OObWc7Et/6h56FidtmtK3.dQghPKeWW2', 139, '2025-11-19 16:36:21', '2025-11-19 16:27:00', 1),
(20261040, 'Rubelyn F.', 'Margaha,', 'rubelynmargaha4@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$rBXNbxBDrTU1HMfX8akKZ.TZ0FsYfnt1heUAKOd1Yd7s0Da4JxnXq', 136, '2025-11-19 16:37:18', '2025-11-19 16:27:11', 1),
(20261041, 'Caryl Mae', 'Setias,', 'carylsetias0416@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$LXxW0nxUvvL4S1NjxUujruPRSjNmwnI0d93Synh1xRDB7LjfvYbxi', 133, '2025-11-19 16:37:40', '2025-11-19 16:27:15', 1),
(20261042, 'Mationg', 'Kheishia Faith', 'kfgmationg@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$D1.ZHeQA4gyZLf/1Ifbjb.BF2OmZQkFABNMIfSHg1WvfhOC6JBnMi', 135, '2025-11-19 16:35:29', '2025-11-19 16:27:34', 1),
(20261043, 'Oñate', 'Angela', 'amonate.chmsu@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$.IUpJ5W7zfxJ/D2h4.uHbOZXPs2o9e6RncN6YcNxsO3nBmHW.Q5OK', 140, '2025-11-19 16:40:09', '2025-11-19 16:27:36', 1),
(20261044, 'Arlene', 'Maming', 'mamingarlene9@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$8/Y.XlcKcBwyLilULo6IquflP5ctZIL9gJcJ1k2eozamITntCNa1C', 134, '2025-11-19 16:37:16', '2025-11-19 16:27:39', 1),
(20261045, 'Bretaña', 'Mia Allysa', 'bretanamiaallysa.chmsu@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$osrveqSVNJtGAIoLTuvUV.wm9ffGcxSeDVigtwa.tfKVUp/vWsXFe', 131, '2025-11-19 16:35:49', '2025-11-19 16:27:44', 1),
(20261046, 'Zacarias', 'Leo', 'leozcrs17@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$p4p7ThobOUnu5uFbNxdZ/.6sRwY8f9S3H56Js61L0B7BZDaXLr3oG', 138, '2025-11-19 16:36:21', '2025-11-19 16:29:55', 1),
(20261047, 'Viñas', 'Katrina Grace', 'kgpvinas.chmsu@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$DPp5RY8R7F3g6W3P5RN2W.0SMTHrUefsyTfDGLCj5WuOe.KpMXKim', 141, '2025-11-19 16:40:24', '2025-11-19 16:32:30', 1),
(20261048, 'Toroy', 'Mariah Jella', 'toroymariahjella@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$HfwKevWIfX4I/J7F2/wdUeCCzqvNkyKvAGFk19WpyYxrjWEjQUACW', 142, '2025-11-19 16:42:22', '2025-11-19 16:33:19', 1),
(20261049, 'dela cruz', 'juan', 'sherylmarinog@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$SAN6DAhzxdPAd0SBBC7WOu5gRMMx1EYNxl.0LkL8RjOhR/FEXJF1.', NULL, '2025-11-19 19:11:39', '2025-11-19 19:11:39', 0),
(20261050, 'porrAS', 'GUENEVERE', 'porrasguenevere@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$c/zBMh8P5/jA.cqPSLuas.oge58ICLg2fI0B/ljH07g5QHxnQJLj2', 144, '2025-11-20 13:55:02', '2025-11-20 13:44:24', 1),
(20261051, 'Bagahansol', 'Jasmin', 'jfbagahansol.chmsu@gmail.com', 'New Applicant - Previous Academic Year', '$2y$10$op8OGmFUb/6HgjQwuTooY.x/DM3XmnRCFZxZYBkfyfeYz3/9IiopG', 145, '2025-11-20 15:09:10', '2025-11-20 14:57:05', 1),
(20261052, 'igancio', 'joshua', 'joshua.ignacio506@gmail.com', 'New Applicant - Previous Academic Year', '$2y$10$We2MdX9IZw9GarPxg4QSH.zq9qwSph.uvhnOMNq3Ji31VYLSuFCI6', NULL, '2025-11-20 15:58:14', '2025-11-20 15:58:14', 0),
(20261053, 'Alfonso', 'karen kaye', 'alfonsokarenkaye@gmail.com', 'Transferee', '$2y$10$HEv5NNf/6q96AyP3ghIM8OAUbt7cjlYOvANqU8p1m/yuE8oLgI/xS', 147, '2025-11-20 20:10:10', '2025-11-20 19:46:25', 1),
(20261054, 'Komori', 'Yohei', 'raze2658@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$Ndp1K4L5m6gktSKIBc7ukeNn3bS0Q6p42yNtxeck3Pq1fifm76iGO', 146, '2025-11-21 11:33:58', '2025-11-20 19:49:20', 1),
(20261055, 'Duma-op', 'Aleah', 'aleahdumaop5@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$6qSZ4SvvNwu1Ay9oVSB.h.g6G6NF0ShRKgxlofY86SFFZ.xzIX9j.', NULL, '2025-11-20 19:50:05', '2025-11-20 19:50:05', 0),
(20261056, 'Galono', 'Danica', 'nixxa659@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$LIVvTkoWsXn5NktUEihRn.5i33yZN4AXYLSgn9Nvga5XjWY9ZN.Nq', NULL, '2025-11-20 19:50:12', '2025-11-20 19:50:12', 0),
(20261057, 'Trespeces', 'Luijan', 'trespeceslj@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$i8QPJ5wupSG70USmmxf1HOBxnr/Gk4Lml7I1pH/8DcU1tZ037xKV6', NULL, '2025-11-20 19:50:35', '2025-11-20 19:50:35', 0),
(20261058, 'bolar', 'ahron', 'ahronbolar14@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$uF/tk0VoHIfrncidBG2l2OBcHZagUtubQBmi5karl123lMc7gEXcS', NULL, '2025-11-20 19:50:47', '2025-11-20 19:50:47', 0),
(20261059, 'Afloro', 'Franziene', 'franzieneafloro8@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$rPt/EUzkoB5nSeGuqv8VRurXJm5y3FpCuHh9Gyf1dn1nWsZS1hFMq', NULL, '2025-11-20 19:53:06', '2025-11-20 19:53:06', 0),
(20261060, 'Binaohan', 'Nick justine', 'ninobazan095@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$b8EkWgfnQEL/dYW8RQxA8eSX5C0mgP3.RDqIKY/R.CQx5GQYT/g6m', NULL, '2025-11-20 19:53:15', '2025-11-20 19:53:15', 0),
(20261061, 'Talanquines', 'Angel', 'talanquinesangel41@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$KzHZDTDhXYYzauAuBCmd2eKEA0wn5rI0Ivy1H.0RCLY6pY8UeDV8m', NULL, '2025-11-20 19:55:03', '2025-11-20 19:55:03', 0),
(20261062, 'Penetrante', 'Frederick jr.', 'frederickpenetrante272@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$AZeNMvjVqf7GVLKehCeIX.FmUTdE3FDo40mpOxP3vTWEmf0YVIEHi', 148, '2025-11-20 20:06:38', '2025-11-20 19:55:36', 0),
(20261063, 'Pabillo', 'Analou Clare', 'clarejamison6@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$ypMj890RCVv08tJPEGUFmOCA2cfcor2U3U7cnEmbQCR6UOJdVULw6', NULL, '2025-11-20 19:56:22', '2025-11-20 19:56:22', 0),
(20261064, 'Layaging', 'Jeca', 'iamjecalayaging@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$hioZePgtImdn3CQgvm7EUegNzB/PR4QzZTXhDnRaXiS6AwUfJmhoq', NULL, '2025-11-20 19:58:50', '2025-11-20 19:58:50', 0),
(20261065, 'Andres', 'Marie', 'marietumayan04@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$TsNipwHstTW/2q/zLifCEugLszo/y6k06AnmuoI3LHbw2ZHl8GbJ.', NULL, '2025-11-20 20:00:14', '2025-11-20 20:00:14', 0),
(20261066, 'Alvarez', 'Joanna Marie', 'alvarezjoanna141@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$Gj7fddcQ.bE6oFb6AjxgvuDfHY9epTOW.DAqQjH2ySou0L/e8bi52', 149, '2025-11-20 20:14:14', '2025-11-20 20:00:14', 1),
(20261067, 'Dence', 'Kherbymhar', 'kherbymhar@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$AD8v2BpwUFz/Cz9jLFvD9OrDp0z44TuQlQmIF1KZxp8tcoAXhVRGC', 152, '2025-11-21 00:17:12', '2025-11-20 20:02:10', 1),
(20261068, 'Silorio', 'Jean Karess', 'jeankaresssilorio@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$VwY/hMYCKikeXGGQQKtxlu52szRIEea5hGOF26h5WrQ3x17MkVAd6', NULL, '2025-11-20 20:05:01', '2025-11-20 20:05:01', 0),
(20261069, 'Quijano', 'Hilary', 'quijanohilary157@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$InJ3Kw8VpH.RCQze.BuFFuyjqc1.IdWUwYXWDjH9eZ9LkSoM6yO9y', NULL, '2025-11-20 20:05:16', '2025-11-20 20:05:16', 0),
(20261070, 'Tongcua', 'Jane Clowe', 'tongcuajaneclowe@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$Nl9rtJWbI2YQjabbQ79JZOYA5NQzFkjfTU59SyciTERTGUUQoJOmu', NULL, '2025-11-20 20:08:22', '2025-11-20 20:08:22', 0),
(20261071, 'Beñales', 'Darius', 'benales122703@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$wHxq29ggA1yAusbfyUYTWek/0FVMiyihyaUDxEzkJ6vBYyAZ9.FIK', NULL, '2025-11-20 20:10:32', '2025-11-20 20:10:32', 0),
(20261072, 'Mario', 'Ven joseph', 'venjosephmario2006@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$tuiJLt491ydZ.xRefzibpeqw7NWo9EQ9HPiWfpX0vWBf.SD4bXpdy', NULL, '2025-11-20 20:10:47', '2025-11-20 20:10:47', 0),
(20261073, 'Carampatana', 'Ciara', 'ciaracarampatana06@gmail.com', 'Transferee', '$2y$10$5jZYjX6itnqd7cr6DGFQD.cYTAKhsqAdV.6wuOOn.FB49xoi/6OvO', NULL, '2025-11-20 20:12:31', '2025-11-20 20:12:31', 0),
(20261074, 'Erasmo', 'Dhanice', 'dhaniceerasmo19@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$2KNoc6wYmCpRDHsGI8rP/eiEZOvDO.5g1CrvvFsRDUmTXVRoQZWVC', 150, '2025-11-20 20:21:35', '2025-11-20 20:16:36', 0),
(20261075, 'bianes', 'Juliana Marie', 'julianabianes497@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$96RSqcaOuY81iw0UzPvdPugZsUukMEJ0Pz7YLQs/E2fIQj03whJIO', NULL, '2025-11-20 20:18:36', '2025-11-20 20:18:36', 0),
(20261076, 'Trecho', 'john Michael', 'mikk863@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$zZe2YnnL.z.2v1L7XYAsPOXCoqRF1apv75LSmh4YmqHpuzGIeh.Gy', 151, '2025-11-20 20:26:47', '2025-11-20 20:18:46', 0),
(20261077, 'Quiñones', 'Jess', 'sian281606@gmail.com', 'New Applicant - Previous Academic Year', '$2y$10$jV6.5kLOzxKzy57VF683aeyyJTsmstPdG.R49o.Q9To0TW6/JGQJ2', NULL, '2025-11-20 20:20:04', '2025-11-20 20:20:04', 0),
(20261078, 'Subaldo', 'aidan', 'aidansubaldo061407@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$Ur5nimuJAWR/Fk.YJln2C./hz1SD9yya6qTaOU1PwIJWKxzP3nj4O', 170, '2025-11-21 11:31:14', '2025-11-20 20:25:40', 1),
(20261079, 'Lim', 'Dominic', 'jdlim1230@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$Z8Ds3BavR4tmzHRZQf4Fv.6Mz6rn1BJcnSi.XOyU11OvtbopsCo3y', NULL, '2025-11-20 20:31:39', '2025-11-20 20:31:39', 0),
(20261080, 'Trejeros', 'Paul jhared', 'pauljharedtrejeros814@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$fICbGH4IXSVmUluV/hd30.K7rMm2ztMufeT7xC28j2VHBhfwFK9hS', 153, '2025-11-20 20:55:57', '2025-11-20 20:36:25', 1),
(20261081, 'Valiente', 'Earl Vincent', 'earl2000valiente@gmail.com', 'New Applicant - Previous Academic Year', '$2y$10$Lff7dOUn.yPSu8vXNlpeD.DC/a4oOJFL5fHhO7nJHeat0UjxemJmy', NULL, '2025-11-20 20:38:57', '2025-11-20 20:38:57', 0),
(20261082, 'Laroza', 'Marinel', 'marinellaroza45@gmail.com', 'Transferee', '$2y$10$B63q2u5MPHK.Fubi4Idd1eadD6PXjuCxqO.C9IVAT2Z3iHSZWdfC2', NULL, '2025-11-20 20:45:16', '2025-11-20 20:45:16', 0),
(20261083, 'Sael', 'Jason', 'saeljason@gmail.com', 'Transferee', '$2y$10$nC6A5.URiDTqCPZaTjw8du/loQKATleq061lOsEWbyeXknb8FgdFu', NULL, '2025-11-20 20:46:29', '2025-11-20 20:46:29', 0),
(20261084, 'ESCOBAR', 'Zyann', 'zyann31escobar2008@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$jbcOpX9XFi8bPFbM7033X.V5bnJfJsPw9pAwH56/ylkAhVxD1/SVi', NULL, '2025-11-20 20:46:38', '2025-11-20 20:46:38', 0),
(20261085, 'Libo-on', 'Jonathan', 'liboonjonathan1@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$wUjIHSmXmy7/xcX74yqifehT/WaqE/WZmvtZKsvbJqjor/1L706Ou', NULL, '2025-11-20 20:46:39', '2025-11-20 20:46:39', 0),
(20261086, 'Plaverte', 'melody', 'plavertemelody16@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$iXgZqFbVctwluVepn69iIOuTgglN3fxdJf5DqXzkUuK/8ozPvThXK', NULL, '2025-11-20 20:47:05', '2025-11-20 20:47:05', 0),
(20261087, 'ESCOBAR', 'ZYANN', 'celinezillions10@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$kD9GDK5YkG0PRfJPOQqVCOfjaWpOMjQMAvMV0L4MfFk8uLKMhCdjy', NULL, '2025-11-20 20:52:53', '2025-11-20 20:52:53', 0),
(20261088, 'flores', 'jacquilyn', 'nhielarquisola18@gmail.com', 'New Applicant - Previous Academic Year', '$2y$10$J28WgNtXKtHEySm4i65IXeSHxk1F6tVeIC56BfpD6Gi9kebXHorSq', 155, '2025-11-20 21:36:06', '2025-11-20 20:53:04', 1),
(20261089, 'Alamis', 'John paul', 'jcalamis.chmsu@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$j7Ee70It4sILHXLlXmrGq.19hOPDKym5MTXRsavEQSCPxEF1kNiW2', 154, '2025-11-20 21:03:35', '2025-11-20 20:56:42', 1),
(20261090, 'CORNELIO', 'Rosemarie', 'neyney0783@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$648kTfWr4WPrk0/VcMFZP.UOdglBb1pJWSo64UECZXLQZ7UAuligG', NULL, '2025-11-20 20:59:59', '2025-11-20 20:59:59', 0),
(20261091, 'Gatuslao', 'Lord Cedrick', 'marktreyes202@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$owGrdJAXZLawqFETGEXIVO5wO3U2nMdI/l7DvnfUBwswybGk0DDSy', 157, '2025-11-20 21:25:13', '2025-11-20 21:04:32', 0),
(20261092, 'Talavera', 'Noela May', 'noelatalavera141@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$s5zXXJ3xvPOQCeAINuQOEO.vQYL4ruQm57J9MnaIPpqcVfDur1W/e', NULL, '2025-11-20 21:04:40', '2025-11-20 21:04:40', 0),
(20261093, 'Montojo', 'Angela', 'yumicom331@gmail.com', 'Transferee', '$2y$10$LYjWXS3aHO4C30K5j6rS2OVw3xHSx88yF3eKv94ijAL4ItIF0i5iy', NULL, '2025-11-20 21:10:05', '2025-11-20 21:10:05', 0),
(20261094, 'Sumicad', 'Ma. Mae', 'mariamaesumicad@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$mTrtVXwY5IGBgMzAGaoVDuvsKI5Uy4//6VgtLgIV7Mt.40Y1Ja0P2', NULL, '2025-11-20 21:11:23', '2025-11-20 21:11:23', 0),
(20261095, 'Jaud', 'daniela', 'danielajaud.b@gmail.com', 'Transferee', '$2y$10$kMBGXhxvCmFldWZIiBeJ.efPNPRhGl.y9Y7kHd9XTFxJajaqPNO9y', NULL, '2025-11-20 21:17:29', '2025-11-20 21:17:29', 0),
(20261096, 'afloro', 'Franziene', 'franzieneafloro868@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$dF7VxlcEEeQqhpF2n6fMk.9odbGeLY7wQb0QAG84cdHCzbOqN3R2y', NULL, '2025-11-20 21:17:38', '2025-11-20 21:17:38', 0),
(20261097, 'Dechimo', 'Zara Jaztine', 'zarajaztined99@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$Do3DpSKx4LzmbLMtWAQ2yOfsXu.dHDvUAayPxRUFsGvgbgd79RnI.', 156, '2025-11-20 21:21:52', '2025-11-20 21:17:53', 0),
(20261098, 'Abaluna', 'Zhel ann', 'zhelannabaluna@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$kK8JjZPy39Y43foKHMSV9e1Wmfu2Jbm8cTZAXxbYRO6QihWckomRC', NULL, '2025-11-20 21:19:25', '2025-11-20 21:19:25', 0),
(20261099, 'Villar', 'John michael', 'd7084970@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$vcagWXWvLs11T6mKKb.xaOet87dBgHA4gX3ju/RNBXNlEn6ZBQBke', 158, '2025-11-20 21:27:29', '2025-11-20 21:19:34', 0),
(20261100, 'Tang', 'Hershey lianne', 'hesheyliannetang@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$mnZbxpqpa519/inLYK/cY.b63KLrDcZ57XDAVByezbSjJc//.Vd2a', NULL, '2025-11-20 21:20:17', '2025-11-20 21:20:17', 0),
(20261101, 'Tresvalles', 'John Mark', 'johnmarktresvalles5@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$wUQvH1SMM8bshakaC6k60.gyR3gll6aqIbBOZgG3Gzn06SyuPsS5G', NULL, '2025-11-20 21:20:26', '2025-11-20 21:20:26', 0),
(20261102, 'Casaalan', 'Andrei eliazar', 'andreicasaalan@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$jnbNq/5ChN3Uwqw3L3tsHupvzwEgdLG/f2xg3.HDtomSve3j7kYR2', NULL, '2025-11-20 21:20:46', '2025-11-20 21:20:46', 0),
(20261103, 'FIORCLEA', 'Cortez', 'fiorcella831@gmail.com', 'Transferee', '$2y$10$psz0D.4HNGF5Ppkv2r60P.AaVAKIyocamNQesml8e8lboU4PufBjq', NULL, '2025-11-20 21:20:57', '2025-11-20 21:20:57', 0),
(20261104, 'Superficial', 'Saira', 'superficialsaira65@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$cHD8awOFajtKipVLqzzBweySltpI45kNeWHSWUL7UNP3y4/mGhJqG', NULL, '2025-11-20 21:24:36', '2025-11-20 21:24:36', 0),
(20261105, 'Busilacan', 'ANNA LIZA', 'busilacanannaliza@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$EZF7k3dmbr1Z1tCct/dTHewz22Y8cE9WQYVCJWON2RbvJGY03FrZi', NULL, '2025-11-20 21:25:37', '2025-11-20 21:25:37', 0),
(20261106, 'Ayes', 'joebeniel', 'joebeniela@gmail.com', 'New Applicant - Previous Academic Year', '$2y$10$I6mUqcQl0r8QjP.Jrq5m.e8M5Dq6EVzZcB7q1uGmULdOQ.ayyT/Qu', NULL, '2025-11-20 21:26:08', '2025-11-20 21:26:08', 0),
(20261107, 'pallorina', 'Aira', 'airapallorina0615@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$PA/0oGoB6RqiKjJBtCyv8O8rwo15BD/44O54Fm7y4X6Eon0R7W8wC', 159, '2025-11-20 21:48:28', '2025-11-20 21:26:50', 0),
(20261108, 'Aquillo', 'Eloiza', 'eloizaaquillo27@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$1/lSgGpM1jmZ4y.nUUgGfe90.LgHou.JhA2H1MTV4s07vp4TTwxFW', NULL, '2025-11-20 21:29:26', '2025-11-20 21:29:26', 0),
(20261109, 'Alejandrino', 'trishia', 'alejandrinotrishia07@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$.OO7dRSeZrgbWr1PNHGuxevFYmkaS5KmBIpNYgxjnRqyzpa5lq4oG', NULL, '2025-11-20 21:29:45', '2025-11-20 21:29:45', 0),
(20261110, 'Pabon', 'Andrea', 'andreapabonnobleza@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$DaTkNoGSKwYwv.0bDL1aZ.4sHPgUnfqbxLMu2DofOyuXR2J/rmkTO', NULL, '2025-11-20 21:38:57', '2025-11-20 21:38:57', 0),
(20261111, 'Loteyro', 'billy joe', 'loteyrobilly@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$xWmeqAS77/lTg4IMttk22eurAkiOA2YUbLhY0HgDHUkBG9GEDxnQ.', NULL, '2025-11-20 21:40:45', '2025-11-20 21:40:45', 0),
(20261112, 'CAYAO', 'NICO', 'nscayao.chmsu@gmail.com', 'New Applicant - Previous Academic Year', '$2y$10$Qe8L8vtTusNpQDaHN7upReOG34qWe3cUBCdD6NMsAgEPaofKz.5Z.', 160, '2025-11-20 21:52:09', '2025-11-20 21:42:38', 1),
(20261113, 'Demegillo', 'jarkia', 'jk.dmgll@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$qMmYrtzIH2ZrJsz.J1PWZOKKhjnhVUPhBpGcLvQVYtvIjkXJ/XaKa', NULL, '2025-11-20 21:50:00', '2025-11-20 21:50:00', 0),
(20261114, 'SAMSON', 'LESLIE', 'samsonleslie1821@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$kMq92wLbTEvj9dgYVxagl.6fw0M8vDEKJpBhqBWybj.3tQP2l2t2e', NULL, '2025-11-20 21:50:31', '2025-11-20 21:50:31', 0),
(20261115, 'Sumicad', 'Ma.Mae', 'mariahmaesumicad@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$tJhHA23qftP2IwvN20R5h.fGR6WRfFLGwDEkuQDlkBHJ6SB/1RxCW', 161, '2025-11-20 21:58:21', '2025-11-20 21:52:19', 0),
(20261116, 'Donesa', 'Den Marie', 'denmariedonesa95@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$IoljyrI90qcyaIKmsN8zM.lH2WA5GXsYhvX2VhE7dd3PUa2abrrzi', NULL, '2025-11-20 23:00:30', '2025-11-20 23:00:30', 0),
(20261117, 'Bibanco', 'Justine', 'justinebibanco11@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$xdeNge8wPx0rMFlj/IweEemnER5h6djkIihcPvuJYIVh4hG.QlBfW', 164, '2025-11-21 10:44:23', '2025-11-21 10:28:51', 1),
(20261118, 'Dusara', 'Xervy', 'dusaranx@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$nYo4qiMP7GMeT4rFRJ38W.pLQq7M6qI0BIZRYhUEJmEtGOJFyndiG', 163, '2025-11-21 10:42:48', '2025-11-21 10:28:54', 1),
(20261119, 'Polinar', 'John Michael', 'johnpolinar26@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$GMiV9LH306iPK6/Iiwdu3OhrvrfDPJwSso8tqJHT3NGCkLP4QS7Vi', 162, '2025-11-21 10:42:36', '2025-11-21 10:29:39', 1),
(20261120, 'Perez', 'jet Eliseo', 'jeteliseoperez@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$iEfx3FyVKJLyezMHpm1JzueBPpYRc73IsqQEms6KdoR/0t8E1vdsi', 169, '2025-11-21 10:59:14', '2025-11-21 10:29:45', 1),
(20261121, 'Bivoso', 'Meryl', 'merylbivoso@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$cZkCVsm.Du3q8iKU7pQu3uStssKdth8EI7r9LBmAT1/wiNF0GZElm', 167, '2025-11-21 11:06:30', '2025-11-21 10:30:42', 1),
(20261122, 'Robles', 'Paul Benedict', 'paulrobles373@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$ShCZsFM19UltRuRuI6rB9OsyIwGltmVxJ3FQWzWHYLJtTZLUNZpKy', 165, '2025-11-21 10:36:35', '2025-11-21 10:31:46', 0),
(20261123, 'Hilo', 'Carlo', 'carlohilo0000@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$km9MbuYAPD.aY1eaA61Q/u6S1HAf5Dy2IRu6kdhstSjN.1/6qXzVu', 166, '2025-11-21 10:45:56', '2025-11-21 10:32:33', 1),
(20261124, 'Bello', 'SHERELYN', 'bellosherelyn@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$y2Yjawlxb3MkwYTNlL0vjeJS9rcf5th5V8vhUvAEQliANygzDbc.y', NULL, '2025-11-21 10:41:40', '2025-11-21 10:33:56', 1),
(20261125, 'alegora', 'jian louise', 'jialegora@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$aL3ui0eAhIkN8KEaXP/Viunj9hrQY6y15q8cTvlAWf.z6GAlSGpEa', 168, '2025-11-21 10:41:04', '2025-11-21 10:34:58', 0),
(20261126, 'Perez', 'jet Eliseo', 'jeteliseop@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$Wz7j2HrBvh0E5dqCPc931OrDJtOjAiPW6bbJ4Xqne03Xn/rRbkLWK', NULL, '2025-11-21 10:40:15', '2025-11-21 10:40:15', 0),
(20261127, 'Ferrer', 'Janfrel', 'janfrelferrer@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$rRpudMcWVp6snrFS53pExupVa1C0f9FLb3treXblyVl3ANssmkNWy', 171, '2025-11-21 11:32:37', '2025-11-21 11:23:00', 1),
(20261128, 'Labto', 'Kenneth jhey', 'labtokenneth@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$KB8Z4lbFdS6e2/gV24oQ.uyJ1ZszON5e1KkIDN6r5zDNkKp7kqaW6', 172, '2025-11-21 11:50:12', '2025-11-21 11:26:23', 1),
(20261129, 'Sotela', 'Michael', 'sotelamichael6@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$LxrrgAIQlkVKDk9egKCXB.uJ811GxbFe5Kn/S/vUVyawcwcnOuskO', 173, '2025-11-21 11:54:30', '2025-11-21 11:26:42', 1),
(20261130, 'Espiñe', 'Josephine', 'josephine82507@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$2LuhC9r9BFw21Agn4TxFtumczutUNFJn6yoxgzC8D1N9fWoW/nSfa', NULL, '2025-11-21 12:46:44', '2025-11-21 12:46:44', 0),
(20261131, 'Silos', 'KHELSEY JAMAICA', 'siloskhelseyjamaica@gmail.com', 'New Applicant - Same Academic Year', '$2y$10$iycZmHSNpPPHMyi0jEvRjesrO6HTmm/lSkQQoBkjxYM3lWwdhKHUG', NULL, '2025-11-21 12:46:56', '2025-11-21 12:46:56', 0);

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  `is_archived` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schedules`
--

INSERT INTO `schedules` (`id`, `event_date`, `event_time`, `venue`, `chair_id`, `created_at`, `archived`, `is_archived`) VALUES
(44, '2025-11-20', '12:00:00', 'etgb - Room 456, LSAB - Room 312', 3, '2025-11-19 17:33:25', 0, 1),
(45, '2025-11-21', '12:00:00', 'LSAB - Room 312', 3, '2025-11-20 06:00:16', 0, 0),
(46, '2025-11-22', '12:00:00', 'LSAB - Room 311', 3, '2025-11-20 06:03:27', 0, 0),
(47, '2025-11-21', '08:00:00', 'LSAB - Room 311', 3, '2025-11-20 06:44:24', 0, 0),
(48, '2025-11-21', '08:00:00', 'LSAB - Room 311', 3, '2025-11-20 06:44:38', 0, 0),
(49, '2025-11-28', '12:00:00', 'LSAB - Room 313', 3, '2025-11-20 07:10:54', 0, 0),
(50, '2025-11-21', '08:00:00', 'LSAB - Room 313', 3, '2025-11-20 07:27:37', 0, 0),
(51, '2025-11-22', '08:00:00', 'LSAB - Room 311', 3, '2025-11-20 08:03:08', 0, 0),
(52, '2025-11-25', '08:00:00', 'LSAB - Room 403', 3, '2025-11-21 02:53:10', 0, 0),
(53, '2025-11-27', '08:00:00', 'LSAB - Room 313', 3, '2025-11-21 03:03:37', 0, 0),
(54, '2025-11-26', '09:00:00', 'LSAB - Room 311', 3, '2025-11-21 03:37:15', 0, 0),
(55, '2025-11-26', '09:00:00', 'LSAB - Room 311', 3, '2025-11-21 03:55:49', 0, 0);

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
(25, 44, 20261011, '2025-11-19 17:33:35'),
(26, 44, 20261017, '2025-11-19 17:33:35'),
(27, 44, 20260034, '2025-11-19 17:33:35'),
(28, 44, 20261016, '2025-11-19 17:33:35'),
(29, 44, 20260032, '2025-11-19 17:33:35'),
(30, 44, 20261013, '2025-11-19 17:33:35'),
(31, 44, 20260033, '2025-11-19 17:33:35'),
(32, 44, 20261010, '2025-11-19 17:33:35'),
(33, 44, 20261012, '2025-11-19 17:33:35'),
(34, 45, 20261037, '2025-11-20 06:00:38'),
(35, 45, 20261045, '2025-11-20 06:00:38'),
(36, 46, 20261050, '2025-11-20 06:03:35'),
(38, 47, 20261022, '2025-11-20 06:45:06'),
(39, 48, 20261036, '2025-11-20 06:45:24'),
(40, 49, 20261051, '2025-11-20 07:11:13'),
(41, 51, 20261021, '2025-11-20 08:04:54'),
(43, 50, 20261089, '2025-11-20 13:29:51'),
(44, 50, 20261053, '2025-11-20 13:29:51'),
(45, 50, 20261029, '2025-11-20 13:29:51'),
(46, 50, 20261020, '2025-11-20 13:29:51'),
(47, 52, 20261123, '2025-11-21 02:53:33'),
(48, 52, 20261122, '2025-11-21 02:53:33'),
(49, 53, 20261118, '2025-11-21 03:04:28'),
(50, 53, 20261120, '2025-11-21 03:04:28'),
(51, 53, 20261119, '2025-11-21 03:04:28'),
(52, 54, 20261067, '2025-11-21 03:37:36'),
(53, 54, 20261127, '2025-11-21 03:37:36'),
(54, 54, 20261054, '2025-11-21 03:37:36'),
(55, 54, 20261078, '2025-11-21 03:37:36'),
(60, 55, 20261128, '2025-11-21 03:56:05'),
(61, 55, 20261129, '2025-11-21 03:56:05');

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
(80, 25, 68.67, 9, 9.00, 8.22, NULL, 0.00, NULL, 0.00, 2.00, 10.22, 23),
(100, 26, 95.33, 5, 5.00, 10.28, 100.00, 40.00, 100.00, 35.00, 5.00, 90.28, 1),
(102, 27, 91.67, 5, 5.00, 9.92, 66.67, 26.67, 48.00, 16.80, 5.00, 58.39, 6),
(321, 107, 85.00, NULL, NULL, 8.50, NULL, 0.00, NULL, 0.00, 5.00, 13.50, 1),
(442, 108, 90.00, 5, 5.00, 9.75, NULL, 0.00, NULL, 0.00, 0.00, 9.75, 24),
(456, 109, 96.67, 1, 1.00, 9.82, NULL, 0.00, NULL, 0.00, 5.00, 14.82, 20),
(464, 110, 86.67, 9, 9.00, 10.02, 50.00, 20.00, NULL, 0.00, 3.00, 33.02, 11),
(465, 111, 88.33, 5, 5.00, 9.58, 85.71, 34.28, 99.00, 34.65, 0.00, 78.51, 4),
(473, 112, 95.33, NULL, NULL, 9.53, 0.00, 0.00, NULL, 0.00, 0.00, 9.53, 25),
(2796, 113, 90.33, 5, 5.00, 9.78, 92.86, 37.14, 96.50, 33.78, 2.00, 82.70, 2),
(2956, 114, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3.00, 3.00, 2),
(2957, 115, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3.00, 3.00, 2),
(3253, 130, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3.00, 3.00, 28),
(3254, 131, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3.00, 3.00, 28),
(3255, 117, 94.00, 5, 5.00, 10.15, 64.29, 25.72, NULL, 0.00, 3.00, 38.87, 7),
(3256, 116, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3.00, 3.00, 28),
(3257, 123, 99.33, 5, 5.00, 10.68, 57.14, 22.86, NULL, 0.00, 2.00, 35.54, 9),
(3258, 125, 94.00, 7, 7.00, 10.45, NULL, 0.00, NULL, 0.00, 5.00, 15.45, 19),
(3259, 128, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 58),
(3260, 124, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3.00, 3.00, 28),
(3261, 127, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3.00, 3.00, 28),
(3262, 129, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 58),
(3263, 139, 99.99, 4, 4.00, 10.60, NULL, 0.00, NULL, 0.00, 3.00, 13.60, 22),
(3264, 132, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 58),
(3265, 118, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3.00, 3.00, 28),
(3266, 122, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3.00, 3.00, 28),
(3267, 134, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3.00, 3.00, 28),
(3268, 136, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3.00, 3.00, 28),
(3269, 135, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3.00, 3.00, 28),
(3270, 120, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 58),
(3271, 119, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3.00, 3.00, 28),
(3272, 140, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 58),
(3273, 143, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 58),
(3274, 121, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3.00, 3.00, 28),
(3275, 126, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3.00, 3.00, 28),
(3276, 133, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3.00, 3.00, 28),
(3277, 137, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3.00, 3.00, 28),
(3278, 142, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 58),
(3279, 141, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3.00, 3.00, 28),
(3280, 138, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3.00, 3.00, 28),
(8831, 144, 95.33, 9, 9.00, 10.88, NULL, 0.00, NULL, 0.00, 5.00, 15.88, 18),
(8948, 145, 90.00, 5, 5.00, 9.75, 78.57, 31.43, 96.00, 33.60, 5.00, 79.78, 3),
(9303, 154, 92.67, 3, 3.00, 9.72, 50.00, 20.00, NULL, 0.00, 3.00, 32.72, 12),
(9304, 147, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3.00, 3.00, 28),
(9305, 149, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3.00, 3.00, 28),
(9306, 156, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3.00, 3.00, 28),
(9307, 150, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 58),
(9308, 155, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3.00, 3.00, 28),
(9309, 157, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3.00, 3.00, 28),
(9310, 146, NULL, NULL, NULL, NULL, 57.14, 22.86, NULL, NULL, 3.00, 25.86, 14),
(9311, 148, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3.00, 3.00, 28),
(9312, 151, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3.00, 3.00, 28),
(9313, 153, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3.00, 3.00, 28),
(9314, 158, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3.00, 3.00, 28),
(9419, 160, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3.00, 3.00, 28),
(9420, 159, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3.00, 3.00, 28),
(9421, 161, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3.00, 3.00, 28),
(9476, 152, NULL, NULL, NULL, NULL, 57.14, 22.86, NULL, NULL, 3.00, 25.86, 14),
(9763, 166, NULL, NULL, NULL, NULL, 71.43, 28.57, NULL, NULL, 0.00, 28.57, 13),
(9819, 164, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3.00, 3.00, 28),
(9820, 167, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 58),
(9821, 163, NULL, NULL, NULL, NULL, 78.57, 31.43, NULL, NULL, 3.00, 34.43, 10),
(9823, 169, NULL, NULL, NULL, NULL, 35.71, 14.28, NULL, NULL, 0.00, 14.28, 21),
(9824, 162, NULL, NULL, NULL, NULL, 57.14, 22.86, NULL, NULL, 3.00, 25.86, 14),
(9825, 165, NULL, NULL, NULL, NULL, 50.00, 20.00, NULL, NULL, 3.00, 23.00, 17),
(10202, 170, NULL, NULL, NULL, NULL, 78.57, 31.43, 90.50, 31.68, 0.00, 63.11, 5),
(10266, 171, NULL, NULL, NULL, NULL, 85.71, 34.28, NULL, NULL, 3.00, 37.28, 8),
(10332, 172, NULL, NULL, NULL, NULL, 14.29, 5.72, NULL, NULL, 3.00, 8.72, 26),
(10727, 173, NULL, NULL, NULL, NULL, 21.43, 8.57, NULL, NULL, 0.00, 8.57, 27);

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
(66, 115, 'Single', 'None', 'Homosexual', 'Alive; Away', 'Bachelor\'s Degree', 'Employed Full-Time', 'Alive; at Home', 'High School Diploma', 'Employed Full-Time', 'None', 'Relatives', 'Yes', 'Yes', 'Yes', 'Yes', 'Yes', 'Yes', 'Yes', 'Yes', 'Scoliosis', '2025-11-14 04:07:52'),
(67, 116, 'Single', 'Christianity', 'Heterosexual', 'Deceased', 'High School Diploma', 'Employed Full-Time', 'Alive; at Home', 'High School Diploma', 'Employed Part-Time', 'Two or more', 'One parent only', 'Yes', 'Yes', 'Yes', 'No', 'Yes', 'Yes', 'No', 'No', '', '2025-11-19 06:45:37'),
(68, 117, 'Single', 'Christianity', 'Heterosexual', 'Alive; at Home', 'Bachelor\'s Degree', 'Employed Full-Time', 'Alive; Away', 'High School Diploma', 'Unemployed', 'Two or more', 'One parent only', 'Yes', 'Yes', 'Yes', 'No', 'No', 'No', 'Yes', 'No', '', '2025-11-19 06:50:58'),
(69, 118, 'Single', 'None', 'Heterosexual', 'Alive; Away', 'High School Diploma', 'Employed Part-Time', 'Alive; at Home', 'High School Diploma', 'Employed Full-Time', 'One', 'One parent only', 'No', 'No', 'Yes', 'No', 'Yes', 'No', 'Yes', 'No', '', '2025-11-19 07:08:58'),
(70, 119, 'Single', 'Christianity', 'Heterosexual', 'Alive; at Home', 'No High School Diploma', 'Unemployed', 'Alive; at Home', 'High School Diploma', 'Unemployed', 'Two or more', 'Both parents', 'No', 'Yes', 'Yes', 'No', 'No', 'No', 'Yes', 'No', '', '2025-11-19 07:10:18'),
(71, 120, 'Single', 'Christianity', 'Heterosexual', 'Alive; at Home', 'High School Diploma', 'Employed Part-Time', 'Alive; at Home', 'No High School Diploma', 'Employed Part-Time', 'Two or more', 'Both parents', 'Yes', 'Yes', 'Yes', 'No', 'No', 'Yes', 'Yes', 'No', '', '2025-11-19 07:11:25'),
(72, 121, 'Single', 'Christianity', 'Others', 'Deceased', 'No High School Diploma', 'Unemployed', 'Alive; at Home', 'High School Diploma', 'Employed Full-Time', 'One', 'One parent only', 'Yes', 'Yes', 'Yes', 'No', 'Yes', 'No', 'Yes', 'No', '', '2025-11-19 07:14:56'),
(73, 122, 'Single', 'Christianity', 'Others', 'Alive; Away', 'Bachelor\'s Degree', 'Employed Full-Time', 'Alive; Away', 'Bachelor\'s Degree', 'Employed Full-Time', 'None', 'Both parents', 'Yes', 'Yes', 'Yes', 'No', 'No', 'No', 'Yes', 'No', '', '2025-11-19 07:23:49'),
(74, 123, 'Single', 'Christianity', 'Bisexual', 'Deceased', 'Bachelor\'s Degree', 'Unemployed', 'Alive; at Home', 'Bachelor\'s Degree', 'Employed Full-Time', 'One', 'Both parents', 'Yes', 'Yes', 'Yes', 'No', 'No', 'No', 'Yes', 'No', '', '2025-11-19 07:44:46'),
(75, 124, 'Single', 'Christianity', 'Bisexual', 'Alive; Away', 'Graduate Degree', 'Employed Full-Time', 'Alive; at Home', 'Graduate Degree', 'Employed Part-Time', 'One', 'One parent only', 'Yes', 'Yes', 'Yes', 'No', 'No', 'No', 'Yes', 'No', '', '2025-11-19 08:12:34'),
(76, 125, 'Single', 'Others', 'Others', 'Unknown', 'No High School Diploma', 'Unemployed', 'Unknown', 'No High School Diploma', 'Unemployed', 'None', 'Relatives', 'Yes', 'Yes', 'Yes', 'No', 'Yes', 'No', 'Yes', 'No', '', '2025-11-19 08:29:00'),
(77, 126, 'Single', 'Christianity', 'Heterosexual', 'Alive; at Home', 'High School Diploma', 'Employed Full-Time', 'Alive; at Home', 'Graduate Degree', 'Employed Part-Time', 'Two or more', 'Relatives', 'Yes', 'Yes', 'Yes', 'No', 'No', 'No', 'Yes', 'No', '', '2025-11-19 08:29:24'),
(78, 127, 'Single', 'Christianity', 'Others', 'Alive; Away', 'Bachelor\'s Degree', 'Employed Full-Time', 'Alive; at Home', 'Bachelor\'s Degree', 'Employed Full-Time', 'Two or more', 'Both parents', 'No', 'No', 'Yes', 'Yes', 'Yes', 'Yes', 'Yes', 'No', '', '2025-11-19 08:29:36'),
(79, 128, 'Single', 'Others', 'Heterosexual', 'Alive; at Home', 'No High School Diploma', 'Employed Part-Time', 'Alive; at Home', 'Bachelor\'s Degree', 'Unemployed', 'None', 'Alone', 'Yes', 'Yes', 'Yes', 'No', 'No', 'No', 'Yes', 'No', '', '2025-11-19 08:29:59'),
(80, 129, 'Single', 'Christianity', 'Others', 'Alive; Away', 'Bachelor\'s Degree', 'Employed Full-Time', 'Alive; at Home', 'Bachelor\'s Degree', 'Unemployed', 'None', 'Relatives', 'No', 'No', 'Yes', 'No', 'Yes', 'Yes', 'Yes', 'No', '', '2025-11-19 08:30:03'),
(81, 130, 'Single', 'Christianity', 'Heterosexual', 'Alive; Away', 'Graduate Degree', 'Employed Full-Time', 'Alive; at Home', 'Graduate Degree', 'Unemployed', 'Two or more', 'One parent only', 'Yes', 'Yes', 'Yes', 'No', 'No', 'No', 'Yes', 'Yes', 'Orthopedic', '2025-11-19 08:30:45'),
(82, 131, 'Single', 'Christianity', 'Heterosexual', 'Alive; at Home', 'No High School Diploma', 'Employed Full-Time', 'Deceased', 'High School Diploma', 'Unemployed', 'Two or more', 'One parent only', 'Yes', 'Yes', 'Yes', 'No', 'No', 'No', 'Yes', 'No', '', '2025-11-19 08:31:12'),
(83, 132, 'Single', 'Christianity', 'Heterosexual', 'Deceased', 'Bachelor\'s Degree', 'Employed Full-Time', 'Deceased', 'Bachelor\'s Degree', 'Employed Full-Time', 'Two or more', 'Relatives', 'Yes', 'Yes', 'Yes', 'No', 'No', 'No', 'Yes', 'No', '', '2025-11-19 08:31:22'),
(84, 133, 'Single', 'Christianity', 'Others', 'Alive; at Home', 'Bachelor\'s Degree', 'Employed Part-Time', 'Alive; at Home', 'Bachelor\'s Degree', 'Employed Part-Time', 'Two or more', 'Both parents', 'Yes', 'Yes', 'Yes', 'No', 'Yes', 'No', 'Yes', 'No', '', '2025-11-19 08:31:24'),
(85, 134, 'Single', 'None', 'Others', 'Alive; at Home', 'Bachelor\'s Degree', 'Employed Full-Time', 'Alive; at Home', 'High School Diploma', 'Unemployed', 'One', 'Both parents', 'Yes', 'No', 'Yes', 'No', 'No', 'No', 'Yes', 'No', '', '2025-11-19 08:31:37'),
(86, 135, 'Single', 'Others', 'Heterosexual', 'Alive; at Home', 'Bachelor\'s Degree', 'Employed Full-Time', 'Alive; at Home', 'Bachelor\'s Degree', 'Employed Part-Time', 'None', 'Both parents', 'Yes', 'Yes', 'Yes', 'No', 'Yes', 'Yes', 'Yes', 'No', '', '2025-11-19 08:32:16'),
(87, 136, 'Single', 'Others', 'Bisexual', 'Alive; at Home', 'High School Diploma', 'Unemployed', 'Alive; at Home', 'No High School Diploma', 'Unemployed', 'Two or more', 'Both parents', 'Yes', 'Yes', 'Yes', 'No', 'Yes', 'No', 'Yes', 'No', '', '2025-11-19 08:32:27'),
(88, 137, 'Single', 'Christianity', 'Heterosexual', 'Alive; at Home', 'Bachelor\'s Degree', 'Unemployed', 'Alive; at Home', 'High School Diploma', 'Unemployed', 'Two or more', 'Both parents', 'Yes', 'Yes', 'Yes', 'No', 'No', 'No', 'Yes', 'No', '', '2025-11-19 08:33:47'),
(89, 138, 'Single', 'Christianity', 'Heterosexual', 'Unknown', 'No High School Diploma', 'Employed Full-Time', 'Alive; at Home', 'High School Diploma', 'Employed Full-Time', 'Two or more', 'One parent only', 'Yes', 'Yes', 'Yes', 'No', 'No', 'No', 'Yes', 'No', '', '2025-11-19 08:33:49'),
(90, 139, 'Single', 'Christianity', 'Heterosexual', 'Alive; at Home', 'High School Diploma', 'Employed Part-Time', 'Alive; at Home', 'High School Diploma', 'Employed Part-Time', 'Two or more', 'Both parents', 'No', 'Yes', 'Yes', 'No', 'No', 'No', 'Yes', 'No', '', '2025-11-19 08:34:50'),
(91, 140, 'Single', 'Others', 'Heterosexual', 'Alive; at Home', 'High School Diploma', 'Employed Part-Time', 'Deceased', 'High School Diploma', 'Unemployed', 'None', 'Relatives', 'Yes', 'Yes', 'Yes', 'No', 'No', 'No', 'Yes', 'No', '', '2025-11-19 08:35:10'),
(92, 141, 'Single', 'Others', 'Heterosexual', 'Alive; at Home', 'Graduate Degree', 'Employed Full-Time', 'Alive; at Home', 'No High School Diploma', 'Employed Part-Time', 'Two or more', 'Both parents', 'Yes', 'Yes', 'Yes', 'No', 'Yes', 'No', 'Yes', 'No', '', '2025-11-19 08:36:44'),
(93, 142, 'Single', 'Christianity', 'Heterosexual', 'Alive; at Home', 'Bachelor\'s Degree', 'Unemployed', 'Alive; at Home', 'Bachelor\'s Degree', 'Employed Part-Time', 'Two or more', 'Both parents', 'Yes', 'Yes', 'Yes', 'No', 'No', 'No', 'Yes', 'No', '', '2025-11-19 08:38:20'),
(94, 143, 'Single', 'None', 'Heterosexual', 'Deceased', 'No High School Diploma', 'Employed Full-Time', 'Alive; Away', 'No High School Diploma', 'Employed Full-Time', 'None', 'Both parents', 'Yes', 'Yes', 'Yes', 'Yes', 'Yes', 'Yes', 'Yes', 'No', '', '2025-11-19 14:35:21'),
(95, 144, 'Single', 'Christianity', 'Heterosexual', 'Alive; Away', 'Bachelor\'s Degree', 'Employed Full-Time', 'Alive; at Home', 'High School Diploma', 'Unemployed', 'Two or more', 'One parent only', 'No', 'No', 'Yes', 'No', 'No', 'Yes', 'Yes', 'No', '', '2025-11-20 05:51:08'),
(96, 145, 'Single', 'Christianity', 'Heterosexual', 'Alive; at Home', 'High School Diploma', 'Employed Full-Time', 'Alive; at Home', 'Graduate Degree', 'Unemployed', 'Two or more', 'Both parents', 'Yes', 'Yes', 'Yes', 'No', 'No', 'No', 'Yes', 'No', '', '2025-11-20 07:04:34'),
(97, 146, 'Single', 'Christianity', 'Heterosexual', 'Alive; Away', 'High School Diploma', 'Employed Part-Time', 'Alive; Away', 'High School Diploma', 'Employed Full-Time', 'Two or more', 'Relatives', 'Yes', 'Yes', 'Yes', 'No', 'Yes', 'No', 'Yes', 'No', '', '2025-11-20 11:52:54'),
(98, 147, 'Single', 'Christianity', 'Heterosexual', 'Alive; at Home', 'High School Diploma', 'Unemployed', 'Alive; at Home', 'Graduate Degree', 'Unemployed', 'Two or more', 'Both parents', 'Yes', 'Yes', 'Yes', 'No', 'No', 'No', 'Yes', 'No', '', '2025-11-20 12:06:27'),
(99, 148, 'Single', 'Christianity', 'Heterosexual', 'Alive; Away', 'Graduate Degree', 'Unemployed', 'Alive; at Home', 'Graduate Degree', 'Unemployed', 'Two or more', 'One parent only', 'Yes', 'Yes', 'Yes', 'No', 'No', 'No', 'No', 'No', '', '2025-11-20 12:06:38'),
(100, 149, 'Single', 'Others', 'Heterosexual', 'Alive; at Home', 'No High School Diploma', 'Employed Full-Time', 'Alive; at Home', 'High School Diploma', 'Unemployed', 'Two or more', 'Both parents', 'Yes', 'Yes', 'Yes', 'No', 'No', 'No', 'Yes', 'No', '', '2025-11-20 12:09:53'),
(101, 150, 'Single', 'Others', 'Heterosexual', 'Alive; at Home', 'No High School Diploma', 'Employed Full-Time', 'Alive; at Home', 'No High School Diploma', 'Employed Full-Time', 'Two or more', 'Both parents', 'No', 'Yes', 'Yes', 'No', 'Yes', 'No', 'Yes', 'No', '', '2025-11-20 12:21:35'),
(102, 151, 'Single', 'Christianity', 'Heterosexual', 'Alive; at Home', 'Graduate Degree', 'Employed Full-Time', 'Alive; at Home', 'Graduate Degree', 'Employed Full-Time', 'None', 'Both parents', 'No', 'Yes', 'Yes', 'No', 'No', 'No', 'No', 'No', '', '2025-11-20 12:26:47'),
(103, 152, 'Single', 'Others', 'Others', 'Alive; at Home', 'Bachelor\'s Degree', 'Employed Full-Time', 'Alive; at Home', 'Bachelor\'s Degree', 'Employed Full-Time', 'Two or more', 'Alone', 'Yes', 'Yes', 'Yes', 'No', 'No', 'No', 'Yes', 'No', '', '2025-11-20 12:47:45'),
(104, 153, 'Single', 'Christianity', 'Others', 'Alive; at Home', 'High School Diploma', 'Employed Full-Time', 'Alive; at Home', 'High School Diploma', 'Employed Part-Time', 'One', 'Both parents', 'No', 'No', 'Yes', 'Yes', 'No', 'Yes', 'Yes', 'No', '', '2025-11-20 12:52:03'),
(105, 154, 'Single', 'Christianity', 'Others', 'Alive; Away', 'High School Diploma', 'Unemployed', 'Alive; Away', 'Graduate Degree', 'Unemployed', 'One', 'Alone', 'Yes', 'Yes', 'Yes', 'No', 'No', 'Yes', 'Yes', 'No', '', '2025-11-20 13:01:16'),
(106, 155, 'Single', 'None', 'Heterosexual', 'Alive; at Home', 'High School Diploma', 'Employed Full-Time', 'Alive; at Home', 'High School Diploma', 'Employed Part-Time', 'None', 'Both parents', 'Yes', 'Yes', 'Yes', 'No', 'No', 'No', 'No', 'No', '', '2025-11-20 13:15:08'),
(107, 156, 'Single', 'Christianity', 'Homosexual', 'Unknown', 'High School Diploma', 'Unemployed', 'Alive; Away', 'Bachelor\'s Degree', 'Employed Full-Time', 'One', 'Relatives', 'Yes', 'Yes', 'Yes', 'No', 'No', 'No', 'Yes', 'No', '', '2025-11-20 13:21:52'),
(108, 157, 'Single', 'Christianity', 'Heterosexual', 'Unknown', 'No High School Diploma', 'Unemployed', 'Unknown', 'No High School Diploma', 'Unemployed', 'None', 'Relatives', 'No', 'Yes', 'Yes', 'No', 'Yes', 'No', 'No', 'No', '', '2025-11-20 13:25:13'),
(109, 158, 'Single', 'None', 'Heterosexual', 'Alive; Away', 'High School Diploma', 'Unemployed', 'Alive; at Home', 'High School Diploma', 'Employed Full-Time', 'None', 'One parent only', 'Yes', 'Yes', 'Yes', 'No', 'Yes', 'No', 'Yes', 'No', '', '2025-11-20 13:27:29'),
(110, 159, 'Single', 'Others', 'Heterosexual', 'Alive; at Home', 'High School Diploma', 'Employed Full-Time', 'Alive; at Home', 'High School Diploma', 'Unemployed', 'Two or more', 'Both parents', 'No', 'Yes', 'Yes', 'No', 'No', 'No', 'Yes', 'No', '', '2025-11-20 13:48:28'),
(111, 160, 'Single', 'Others', 'Others', 'Alive; at Home', 'High School Diploma', 'Employed Part-Time', 'Alive; at Home', 'Bachelor\'s Degree', 'Unemployed', 'Two or more', 'Both parents', 'Yes', 'Yes', 'Yes', 'No', 'No', 'No', 'Yes', 'No', '', '2025-11-20 13:49:24'),
(112, 161, 'Single', 'Christianity', 'Heterosexual', 'Alive; at Home', 'No High School Diploma', 'Employed Part-Time', 'Alive; Away', 'No High School Diploma', 'Employed Full-Time', 'Two or more', 'One parent only', 'No', 'No', 'Yes', 'No', 'No', 'No', 'Yes', 'No', '', '2025-11-20 13:58:21'),
(113, 162, 'Single', 'Christianity', 'Heterosexual', 'Alive; Away', 'High School Diploma', 'Employed Full-Time', 'Alive; Away', 'High School Diploma', 'Employed Part-Time', 'None', 'Relatives', 'No', 'No', 'Yes', 'No', 'No', 'No', 'Yes', 'No', '', '2025-11-21 02:35:29'),
(114, 163, 'Single', 'Christianity', 'Heterosexual', 'Deceased', 'Bachelor\'s Degree', 'Employed Part-Time', 'Alive; at Home', 'High School Diploma', 'Employed Full-Time', 'One', 'One parent only', 'Yes', 'Yes', 'Yes', 'No', 'No', 'No', 'Yes', 'No', '', '2025-11-21 02:35:53'),
(115, 164, 'Single', 'Christianity', 'Heterosexual', 'Alive; at Home', 'Bachelor\'s Degree', 'Employed Full-Time', 'Alive; Away', 'Bachelor\'s Degree', 'Employed Full-Time', 'One', 'One parent only', 'Yes', 'Yes', 'Yes', 'No', 'No', 'No', 'No', 'No', '', '2025-11-21 02:36:35'),
(116, 165, 'Single', 'Others', 'Heterosexual', 'Alive; at Home', 'High School Diploma', 'Unemployed', 'Alive; at Home', 'No High School Diploma', 'Employed Full-Time', 'None', 'Both parents', 'Yes', 'Yes', 'Yes', 'No', 'Yes', 'No', 'No', 'No', '', '2025-11-21 02:36:35'),
(117, 166, 'Single', 'Christianity', 'Homosexual', 'Alive; Away', 'Graduate Degree', 'Unemployed', 'Alive; Away', 'High School Diploma', 'Employed Part-Time', 'One', 'Both parents', 'Yes', 'No', 'Yes', 'No', 'Yes', 'No', 'Yes', 'Yes', 'Cleft Palate', '2025-11-21 02:37:43'),
(118, 167, 'Single', 'Christianity', 'Heterosexual', 'Alive; at Home', 'High School Diploma', 'Employed Full-Time', 'Alive; at Home', 'High School Diploma', 'Unemployed', 'Two or more', 'Both parents', 'Yes', 'Yes', 'Yes', 'No', 'Yes', 'No', 'Yes', 'No', '', '2025-11-21 02:39:15'),
(119, 168, 'Single', 'None', 'Heterosexual', 'Alive; at Home', 'Bachelor\'s Degree', 'Unemployed', 'Alive; at Home', 'Bachelor\'s Degree', 'Employed Full-Time', 'One', 'Both parents', 'Yes', 'Yes', 'Yes', 'No', 'No', 'No', 'Yes', 'No', '', '2025-11-21 02:41:04'),
(120, 169, 'Single', 'Christianity', 'Heterosexual', 'Alive; at Home', 'High School Diploma', 'Employed Full-Time', 'Alive; at Home', 'High School Diploma', 'Employed Full-Time', 'Two or more', 'Both parents', 'No', 'Yes', 'Yes', 'No', 'No', 'No', 'Yes', 'No', '', '2025-11-21 02:57:33'),
(121, 170, 'Single', 'Christianity', 'Heterosexual', 'Deceased', 'No High School Diploma', 'Unemployed', 'Alive; Away', 'High School Diploma', 'Employed Full-Time', 'One', 'Relatives', 'Yes', 'Yes', 'Yes', 'No', 'Yes', 'No', 'Yes', 'No', '', '2025-11-21 03:26:49'),
(122, 171, 'Single', 'Christianity', 'Heterosexual', 'Alive; at Home', 'High School Diploma', 'Employed Part-Time', 'Alive; at Home', 'High School Diploma', 'Employed Full-Time', 'Two or more', 'Both parents', 'Yes', 'Yes', 'Yes', 'No', 'No', 'No', 'Yes', 'No', '', '2025-11-21 03:29:34'),
(123, 172, 'Single', 'Christianity', 'Heterosexual', 'Deceased', 'No High School Diploma', 'Unemployed', 'Alive; at Home', 'No High School Diploma', 'Unemployed', 'Two or more', 'One parent only', 'No', 'Yes', 'Yes', 'No', 'No', 'No', 'Yes', 'No', '', '2025-11-21 03:38:38'),
(124, 173, 'Single', 'Christianity', 'Homosexual', 'Alive; at Home', 'High School Diploma', 'Employed Full-Time', 'Alive; at Home', 'High School Diploma', 'Employed Full-Time', 'Two or more', 'Both parents', 'No', 'Yes', 'Yes', 'No', 'No', 'No', 'No', 'No', '', '2025-11-21 03:50:48');

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
  ADD KEY `idx_interview_schedules_chair_id` (`chair_id`),
  ADD KEY `idx_interview_schedules_is_archived` (`is_archived`);

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
  ADD KEY `idx_schedules_chair_id` (`chair_id`),
  ADD KEY `idx_schedules_is_archived` (`is_archived`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=132;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=112;

--
-- AUTO_INCREMENT for table `exam_answers`
--
ALTER TABLE `exam_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT for table `exam_versions`
--
ALTER TABLE `exam_versions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `interviewers`
--
ALTER TABLE `interviewers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `interview_schedules`
--
ALTER TABLE `interview_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `interview_schedule_applicants`
--
ALTER TABLE `interview_schedule_applicants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `personal_info`
--
ALTER TABLE `personal_info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=174;

--
-- AUTO_INCREMENT for table `program_application`
--
ALTER TABLE `program_application`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=125;

--
-- AUTO_INCREMENT for table `questions`
--
ALTER TABLE `questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=136;

--
-- AUTO_INCREMENT for table `registration`
--
ALTER TABLE `registration`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20261132;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `schedule_applicants`
--
ALTER TABLE `schedule_applicants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT for table `screening_results`
--
ALTER TABLE `screening_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10862;

--
-- AUTO_INCREMENT for table `socio_demographic`
--
ALTER TABLE `socio_demographic`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=125;

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
