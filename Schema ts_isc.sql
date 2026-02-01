-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 18, 2025 at 08:30 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ts_isc`
--

-- --------------------------------------------------------

--
-- Table structure for table `tbl_department`
--

CREATE TABLE `tbl_department` (
  `department_id` int(11) NOT NULL,
  `department_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_department`
--

INSERT INTO `tbl_department` (`department_id`, `department_name`, `created_at`) VALUES
(1, 'Sales', '2025-09-22 06:41:18'),
(2, 'Finance', '2025-09-22 06:41:18'),
(3, 'Human Resource', '2025-09-22 06:41:18'),
(4, 'Warehouse', '2025-09-22 06:41:18'),
(5, 'Production', '2025-09-22 06:41:18'),
(6, 'Shipping', '2025-09-22 06:41:18'),
(7, 'Engineering', '2025-09-22 06:41:18');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_department_routing`
--

CREATE TABLE `tbl_department_routing` (
  `routing_id` int(11) NOT NULL,
  `category` varchar(100) NOT NULL,
  `target_department_id` int(11) NOT NULL,
  `auto_assign_technician` tinyint(1) DEFAULT 0,
  `priority_boost` enum('none','low','medium','high') DEFAULT 'none',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_department_routing`
--

INSERT INTO `tbl_department_routing` (`routing_id`, `category`, `target_department_id`, `auto_assign_technician`, `priority_boost`, `created_at`) VALUES
(1, 'System access or login issues', 7, 1, 'high', '2025-11-06 20:36:22'),
(2, 'Network or router troubleshooting', 7, 1, 'high', '2025-11-06 20:36:22'),
(3, 'Hardware or software installation', 7, 1, 'medium', '2025-11-06 20:36:22'),
(4, 'Malfunctioning PCs or peripherals', 7, 1, 'medium', '2025-11-06 20:36:22'),
(5, 'Email configuration errors', 7, 1, 'high', '2025-11-06 20:36:22'),
(6, 'Coordination with other departments', 7, 0, 'low', '2025-11-06 20:36:22'),
(7, 'ERP entry errors', 2, 1, 'high', '2025-11-06 20:36:22'),
(8, 'Billing or reconciliation disputes', 2, 1, 'medium', '2025-11-06 20:36:22'),
(9, 'Payment verification issues', 2, 1, 'high', '2025-11-06 20:36:22'),
(10, 'Report generation errors', 2, 1, 'medium', '2025-11-06 20:36:22'),
(11, 'Financial data synchronization', 2, 1, 'high', '2025-11-06 20:36:22'),
(12, 'Warranty validation errors', 7, 1, 'medium', '2025-11-06 20:36:22'),
(13, 'Delayed ticket for servicing items', 7, 1, 'low', '2025-11-06 20:36:22'),
(14, 'Product serial verification', 7, 1, 'medium', '2025-11-06 20:36:22'),
(15, 'Approval for replacement items', 7, 1, 'high', '2025-11-06 20:36:22'),
(16, 'Onboarding or offboarding system access', 3, 1, 'high', '2025-11-06 20:36:22'),
(17, 'Employee account creation', 3, 1, 'medium', '2025-11-06 20:36:22'),
(18, 'Password recovery', 3, 1, '', '2025-11-06 20:36:22'),
(19, 'Attendance record discrepancies', 3, 1, 'medium', '2025-11-06 20:36:22'),
(20, 'Inventory record inconsistencies', 4, 1, 'medium', '2025-11-06 20:36:22'),
(21, 'Missing stock entries', 4, 1, 'high', '2025-11-06 20:36:22'),
(22, 'Damaged item reports', 4, 1, 'medium', '2025-11-06 20:36:22'),
(23, 'Delayed shipment arrivals', 4, 1, 'high', '2025-11-06 20:36:22'),
(24, 'Batch tagging errors', 5, 1, 'medium', '2025-11-06 20:36:22'),
(25, 'System synchronization lag', 5, 1, 'high', '2025-11-06 20:36:22'),
(26, 'Staff scheduling module malfunction', 5, 1, 'medium', '2025-11-06 20:36:22'),
(27, 'Equipment maintenance', 5, 1, 'low', '2025-11-06 20:36:22'),
(28, 'Customer inquiry updates', 1, 1, 'medium', '2025-11-06 20:36:22'),
(29, 'Warranty record assistance', 1, 1, 'low', '2025-11-06 20:36:22'),
(30, 'System-generated report errors', 1, 1, 'medium', '2025-11-06 20:36:22'),
(31, 'Customer profile updates', 1, 1, 'low', '2025-11-06 20:36:22'),
(32, 'Wrong delivery or update issues', 6, 1, 'high', '2025-11-06 20:36:22'),
(33, 'Missing items', 6, 1, '', '2025-11-06 20:36:22'),
(34, 'Delivery confirmation requests', 6, 1, 'medium', '2025-11-06 20:36:22'),
(35, 'Logistics coordination', 6, 1, 'low', '2025-11-06 20:36:22');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_faq`
--

CREATE TABLE `tbl_faq` (
  `faq_id` int(11) NOT NULL,
  `type` enum('account','technical','general','operations','other') DEFAULT 'other',
  `question` varchar(255) NOT NULL,
  `answer` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_technician`
--

CREATE TABLE `tbl_technician` (
  `technician_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `position` enum('junior','advanced') DEFAULT 'junior',
  `specialization` enum('software','hardware','operation') DEFAULT 'software',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `active_tickets` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_technician`
--

INSERT INTO `tbl_technician` (`technician_id`, `name`, `email`, `password`, `position`, `specialization`, `status`, `created_at`, `active_tickets`) VALUES
(1, 'Test Pilot', 'pilot@company.com', '1234', 'junior', 'software', 'active', '2025-09-22 06:36:43', 6),
(2, 'Captain', 'capt@company.com', '1234', 'advanced', 'operation', 'active', '2025-09-22 12:14:49', 9),
(3, 'Commander', 'commander@company.com', '1234', 'advanced', 'hardware', 'active', '2025-09-30 09:57:50', 2),
(4, 'Private', 'private@company.com', '1234', 'junior', 'hardware', 'active', '2025-09-30 09:57:50', 4),
(5, 'Vice Captain', 'vicecap@company.com', '1234', 'junior', 'operation', 'active', '2025-09-30 09:58:34', 9),
(6, 'Admiral', 'admiral@company.com', '1234', 'advanced', 'software', 'active', '2025-09-30 09:59:27', 4);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_ticket`
--

CREATE TABLE `tbl_ticket` (
  `ticket_id` int(11) NOT NULL,
  `reference_id` varchar(50) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `type` varchar(100) NOT NULL,
  `category` varchar(100) NOT NULL,
  `priority` enum('low','regular','high','urgent') DEFAULT NULL,
  `urgency` enum('low','medium','high','urgent') DEFAULT 'low',
  `description` text DEFAULT NULL,
  `attachments` longblob DEFAULT NULL,
  `assigned_technician_id` int(11) DEFAULT NULL,
  `status` enum('unassigned','pending','followup','complete') DEFAULT 'unassigned',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `sla_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_ticket`
--

INSERT INTO `tbl_ticket` (`ticket_id`, `reference_id`, `user_id`, `title`, `type`, `category`, `priority`, `urgency`, `description`, `attachments`, `assigned_technician_id`, `evaluator_id`, `evaluated_at`, `evaluation_notes`, `status`, `created_at`, `sla_date`) VALUES
(21, 'TCK-ALI001', 415, 'Network Connectivity', 'IT', 'Network', 'high', 'high', NULL, NULL, 2, NULL, NULL, NULL, 'complete', '2025-10-10 03:24:29', NULL),
(23, 'TCK-CAR001', 417, 'Database Backup', 'IT', 'Database', '', 'low', NULL, NULL, 4, NULL, NULL, NULL, 'complete', '2025-07-17 03:24:29', NULL),
(25, 'TCK-SIM002', 354, 'Printer Issue', 'IT', 'Hardware', 'low', 'low', NULL, NULL, 5, NULL, NULL, NULL, 'complete', '2025-09-17 03:24:29', NULL),
(26, 'TCK-ALI002', 415, 'Email Configuration', 'IT', 'Software', '', 'low', NULL, NULL, 1, NULL, NULL, NULL, 'complete', '2025-08-17 03:24:29', NULL),
(27, 'TCK-BOB002', 416, 'Security Audit', 'IT', 'Security', 'high', 'high', NULL, NULL, 2, NULL, NULL, NULL, 'pending', '2025-06-17 03:24:29', NULL),
(28, 'TCK-CAR002', 417, 'Cloud Migration', 'IT', 'Cloud', '', 'low', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2025-04-17 03:24:29', NULL),
(30, 'TCK-ALI001', 421, 'Network Connectivity', 'IT', 'Network', 'high', 'high', NULL, NULL, 2, NULL, NULL, NULL, 'complete', '2025-10-10 09:47:54', NULL),
(32, 'TCK-CAR001', 423, 'Database Backup', 'IT', 'Database', '', 'low', NULL, NULL, 4, NULL, NULL, NULL, 'complete', '2025-07-17 09:47:54', NULL),
(34, 'TCK-SIM002', 354, 'Printer Issue', 'IT', 'Hardware', 'low', 'low', NULL, NULL, 5, NULL, NULL, NULL, 'complete', '2025-09-17 09:47:54', NULL),
(35, 'TCK-ALI002', 421, 'Email Configuration', 'IT', 'Software', '', 'low', NULL, NULL, 1, NULL, NULL, NULL, 'complete', '2025-08-17 09:47:54', NULL),
(36, 'TCK-BOB002', 422, 'Security Audit', 'IT', 'Security', 'high', 'high', NULL, NULL, 2, NULL, NULL, NULL, 'pending', '2025-06-17 09:47:54', NULL),
(37, 'TCK-CAR002', 423, 'Cloud Migration', 'IT', 'Cloud', '', 'low', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2025-04-17 09:47:54', NULL),
(38, 'TCK-MG001', 435, 'Printer Not Working', 'IT', 'Software', 'high', 'high', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2025-04-07 09:56:35', '2025-04-13'),
(39, 'TCK-LT002', 572, 'Cloud Migration Issues', 'IT', 'Cloud', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2024-11-19 09:56:35', '2024-11-26'),
(40, 'TCK-MD003', 573, 'Email Configuration', 'IT', 'Security', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2025-01-21 09:56:35', '2025-01-28'),
(41, 'TCK-ES004', 561, 'Hardware Replacement', 'IT', 'Security', 'high', 'high', NULL, NULL, 3, NULL, NULL, NULL, 'complete', '2025-09-16 09:56:35', '2025-09-20'),
(42, 'TCK-GW005', 633, 'System Optimization', 'IT', 'Hardware', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', '2025-08-18 09:56:35', '2025-08-19'),
(43, 'TCK-JR006', 543, 'VPN Connection Problems', 'IT', 'Database', 'low', 'low', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2025-04-17 09:56:35', '2025-04-18'),
(44, 'TCK-MG007', 435, 'Network Security', 'IT', 'Software', '', 'low', NULL, NULL, 5, NULL, NULL, NULL, 'complete', '2025-02-06 09:56:35', '2025-02-09'),
(45, 'TCK-DR008', 624, 'File Server Access', 'IT', 'Other', 'low', 'low', NULL, NULL, 1, NULL, NULL, NULL, 'complete', '2025-01-11 09:56:35', '2025-01-14'),
(46, 'TCK-LJ009', 560, 'System Downtime', 'IT', 'Other', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', '2024-11-14 09:56:35', '2024-11-18'),
(47, 'TCK-RM010', 446, 'Server Maintenance Needed', 'IT', 'Security', '', 'low', NULL, NULL, 2, NULL, NULL, NULL, 'pending', '2025-01-09 09:56:35', '2025-01-16'),
(48, 'TCK-MN011', 532, 'Hardware Replacement', 'IT', 'Software', 'high', 'high', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2025-01-12 09:56:35', '2025-01-17'),
(49, 'TCK-LP012', 443, 'User Permissions', 'IT', 'Cloud', 'low', 'low', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2025-05-23 09:56:35', '2025-05-27'),
(50, 'TCK-LJ013', 560, 'Server Maintenance Needed', 'IT', 'Security', '', 'low', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2025-07-25 09:56:35', '2025-07-31'),
(51, 'TCK-GR014', 522, 'Database Backup Failure', 'IT', 'Other', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2025-07-23 09:56:35', '2025-07-29'),
(52, 'TCK-LP015', 443, 'Data Recovery', 'IT', 'Other', 'low', 'low', NULL, NULL, 2, NULL, NULL, NULL, 'pending', '2025-02-20 09:56:35', '2025-02-22'),
(53, 'TCK-SW016', 636, 'Software Installation', 'IT', 'Other', 'high', 'high', NULL, NULL, 2, NULL, NULL, NULL, 'pending', '2025-06-05 09:56:35', '2025-06-07'),
(54, 'TCK-EY017', 635, 'Mobile Sync Issues', 'IT', 'Software', 'high', 'high', NULL, NULL, 2, NULL, NULL, NULL, 'pending', '2025-06-19 09:56:35', '2025-06-21'),
(55, 'TCK-LA018', 464, 'Application Crashing', 'IT', 'Cloud', '', 'low', NULL, NULL, 1, NULL, NULL, NULL, 'complete', '2024-11-12 09:56:35', '2024-11-17'),
(56, 'TCK-DR019', 624, 'Email Configuration', 'IT', 'Software', 'high', 'high', NULL, NULL, 1, NULL, NULL, NULL, 'complete', '2025-01-11 09:56:35', '2025-01-18'),
(57, 'TCK-MN020', 541, 'Antivirus Update', 'IT', 'Performance', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2025-09-23 09:56:35', '2025-09-25'),
(58, 'TCK-LT021', 572, 'Data Import Error', 'IT', 'Hardware', 'high', 'high', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2025-03-18 09:56:35', '2025-03-24'),
(59, 'TCK-JJ022', 567, 'Printer Not Working', 'IT', 'Cloud', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2025-04-10 09:56:35', '2025-04-13'),
(60, 'TCK-GW023', 633, 'VPN Connection Problems', 'IT', 'Network', 'high', 'high', NULL, NULL, 2, NULL, NULL, NULL, 'pending', '2024-12-24 09:56:35', '2024-12-31'),
(61, 'TCK-LA024', 464, 'File Server Access', 'IT', 'Performance', 'high', 'high', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2025-05-30 09:56:35', '2025-06-04'),
(62, 'TCK-JJ025', 567, 'Network Security', 'IT', 'Database', 'high', 'high', NULL, NULL, 2, NULL, NULL, NULL, 'pending', '2025-09-11 09:56:35', '2025-09-17'),
(63, 'TCK-LW026', 479, 'Mobile Sync Issues', 'IT', 'Network', 'low', 'low', NULL, NULL, 5, NULL, NULL, NULL, 'complete', '2024-12-19 09:56:35', '2024-12-20'),
(64, 'TCK-PT027', 451, 'Network Security', 'IT', 'Hardware', 'high', 'high', NULL, NULL, 5, NULL, NULL, NULL, 'complete', '2025-01-31 09:56:35', '2025-02-04'),
(65, 'TCK-LA028', 464, 'System Freezing', 'IT', 'Database', 'high', 'high', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2025-02-03 09:56:35', '2025-02-08'),
(66, 'TCK-PT029', 440, 'System Updates', 'IT', 'Performance', 'low', 'low', NULL, NULL, 3, NULL, NULL, NULL, 'complete', '2025-01-10 09:56:35', '2025-01-13'),
(67, 'TCK-BW030', 672, 'System Downtime', 'IT', 'Hardware', 'low', 'low', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2025-05-13 09:56:35', '2025-05-19'),
(68, 'TCK-KT031', 474, 'VPN Connection Problems', 'IT', 'Security', 'high', 'high', NULL, NULL, 2, NULL, NULL, NULL, 'pending', '2025-06-24 09:56:35', '2025-07-01'),
(69, 'TCK-JR032', 536, 'Security Audit Required', 'IT', 'Security', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'complete', '2025-02-04 09:56:35', '2025-02-05'),
(70, 'TCK-DJ033', 514, 'Network Security', 'IT', 'Performance', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2025-06-02 09:56:35', '2025-06-04'),
(71, 'TCK-MN034', 541, 'System Optimization', 'IT', 'Security', 'high', 'high', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2025-09-04 09:56:35', '2025-09-11'),
(72, 'TCK-DA035', 566, 'Printer Not Working', 'IT', 'Hardware', '', 'low', NULL, NULL, 1, NULL, NULL, NULL, 'pending', '2025-04-24 09:56:35', '2025-04-26'),
(73, 'TCK-ES036', 561, 'Application Crashing', 'IT', 'Other', 'low', 'low', NULL, NULL, 2, NULL, NULL, NULL, 'pending', '2025-01-04 09:56:35', '2025-01-06'),
(75, 'TCK-MD038', 573, 'Database Backup Failure', 'IT', 'Cloud', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2025-04-04 09:56:35', '2025-04-05'),
(76, 'TCK-LA039', 464, 'Security Audit Required', 'IT', 'Hardware', '', 'low', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2025-01-29 09:56:35', '2025-01-30'),
(77, 'TCK-JJ040', 567, 'Network Speed Slow', 'IT', 'Performance', 'low', 'low', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2025-08-19 09:56:35', '2025-08-22'),
(78, 'TCK-MA041', 492, 'Security Audit Required', 'IT', 'Hardware', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2025-04-27 09:56:35', '2025-05-01'),
(79, 'TCK-SW042', 636, 'File Corruption', 'IT', 'Software', 'high', 'high', NULL, NULL, 2, NULL, NULL, NULL, 'pending', '2025-03-01 09:56:35', '2025-03-08'),
(80, 'TCK-CD043', 591, 'User Permissions', 'IT', 'Hardware', 'low', 'low', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2025-06-04 09:56:35', '2025-06-11'),
(81, 'TCK-LJ044', 560, 'User Permissions', 'IT', 'Performance', 'high', 'high', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2025-04-01 09:56:35', '2025-04-06'),
(82, 'TCK-BA045', 549, 'System Optimization', 'IT', 'Database', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', '2025-09-17 09:56:35', '2025-09-23'),
(83, 'TCK-RM046', 446, 'System Updates', 'IT', 'Network', 'low', 'low', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2025-02-23 09:56:35', '2025-03-02'),
(84, 'TCK-JR047', 536, 'Login Issues', 'IT', 'Cloud', '', 'low', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2025-08-16 09:56:35', '2025-08-22'),
(85, 'TCK-AH048', 453, 'Security Audit Required', 'IT', 'Performance', 'high', 'high', NULL, NULL, 2, NULL, NULL, NULL, 'pending', '2024-12-01 09:56:35', '2024-12-08'),
(86, 'TCK-ST049', 513, 'Network Connectivity Issues', 'IT', 'Other', 'low', 'low', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2025-06-15 09:56:35', '2025-06-21'),
(87, 'TCK-EY050', 635, 'User Permissions', 'IT', 'Hardware', '', 'low', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2025-07-29 09:56:35', '2025-08-03'),
(88, 'TCK-LT051', 572, 'Antivirus Update', 'IT', 'Cloud', 'high', 'high', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2025-05-13 09:56:35', '2025-05-18'),
(89, 'TCK-LJ052', 560, 'System Optimization', 'IT', 'Network', 'high', 'high', NULL, NULL, 2, NULL, NULL, NULL, 'complete', '2025-07-05 09:56:35', '2025-07-10'),
(90, 'TCK-MN053', 541, 'Hardware Replacement', 'IT', 'Hardware', 'high', 'high', NULL, NULL, 3, NULL, NULL, NULL, 'complete', '2025-04-24 09:56:35', '2025-05-01'),
(91, 'TCK-SJ054', 580, 'User Permissions', 'IT', 'Other', 'high', 'high', NULL, NULL, 3, NULL, NULL, NULL, 'complete', '2025-04-30 09:56:35', '2025-05-04'),
(92, 'TCK-LW055', 479, 'Mobile Sync Issues', 'IT', 'Performance', 'low', 'low', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2025-08-25 09:56:35', '2025-08-29'),
(93, 'TCK-MJ056', 534, 'Login Issues', 'IT', 'Cloud', 'low', 'low', NULL, NULL, 2, NULL, NULL, NULL, 'complete', '2025-08-31 09:56:35', '2025-09-05'),
(94, 'TCK-JR057', 536, 'Server Maintenance Needed', 'IT', 'Database', '', 'low', NULL, NULL, 2, NULL, NULL, NULL, 'pending', '2025-09-04 09:56:35', '2025-09-05'),
(95, 'TCK-DR058', 656, 'Network Connectivity Issues', 'IT', 'Hardware', 'high', 'high', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2025-06-10 09:56:35', '2025-06-12'),
(96, 'TCK-SJ059', 580, 'System Downtime', 'IT', 'Database', 'low', 'low', NULL, NULL, 2, NULL, NULL, NULL, 'pending', '2025-07-23 09:56:35', '2025-07-26'),
(97, 'TCK-DR060', 624, 'Security Audit Required', 'IT', 'Security', 'high', 'high', NULL, NULL, 1, NULL, NULL, NULL, 'pending', '2024-12-28 09:56:35', '2025-01-03'),
(98, 'TCK-DA061', 566, 'Network Connectivity Issues', 'IT', 'Other', 'low', 'low', NULL, NULL, 4, NULL, NULL, NULL, 'complete', '2025-02-18 09:56:35', '2025-02-24'),
(99, 'TCK-LR062', 666, 'Network Security', 'IT', 'Software', '', 'low', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2025-01-04 09:56:35', '2025-01-10'),
(100, 'TCK-MA063', 492, 'Antivirus Update', 'IT', 'Software', 'high', 'high', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2024-12-21 09:56:35', '2024-12-28'),
(101, 'TCK-CL064', 637, 'Application Crashing', 'IT', 'Software', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2025-04-18 09:56:35', '2025-04-25'),
(102, 'TCK-SS065', 585, 'Data Recovery', 'IT', 'Performance', 'high', 'high', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2025-08-07 09:56:35', '2025-08-12'),
(103, 'TCK-LT066', 572, 'VPN Connection Problems', 'IT', 'Software', 'low', 'low', NULL, NULL, 1, NULL, NULL, NULL, 'pending', '2025-01-30 09:56:35', '2025-02-05'),
(104, 'TCK-SS067', 585, 'Database Backup Failure', 'IT', 'Performance', 'high', 'high', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2025-01-21 09:56:35', '2025-01-25'),
(105, 'TCK-EL068', 542, 'Firewall Configuration', 'IT', 'Network', '', 'low', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2025-06-03 09:56:35', '2025-06-04'),
(106, 'TCK-CF069', 601, 'Network Security', 'IT', 'Other', '', 'low', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2025-05-26 09:56:35', '2025-05-27'),
(107, 'TCK-MD070', 573, 'Firewall Configuration', 'IT', 'Other', '', 'low', NULL, NULL, 5, NULL, NULL, NULL, 'complete', '2025-09-07 09:56:35', '2025-09-13'),
(108, 'TCK-MN071', 541, 'Backup Configuration', 'IT', 'Software', 'high', 'high', NULL, NULL, 2, NULL, NULL, NULL, 'pending', '2024-11-01 09:56:35', '2024-11-04'),
(109, 'TCK-BR072', 592, 'Software Installation', 'IT', 'Hardware', 'high', 'high', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2025-03-22 09:56:35', '2025-03-26'),
(110, 'TCK-LS073', 550, 'System Updates', 'IT', 'Cloud', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'complete', '2025-05-05 09:56:35', '2025-05-09'),
(111, 'TCK-SW074', 636, 'Mobile Sync Issues', 'IT', 'Database', '', 'low', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2025-03-27 09:56:35', '2025-04-01'),
(112, 'TCK-KT075', 474, 'User Permissions', 'IT', 'Other', '', 'low', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2025-07-01 09:56:35', '2025-07-03'),
(113, 'TCK-MN076', 541, 'IT Support Request', 'IT', 'Cloud', '', 'low', NULL, NULL, 1, NULL, NULL, NULL, 'pending', '2025-02-04 09:56:35', '2025-02-08'),
(114, 'TCK-PT077', 451, 'Email Configuration', 'IT', 'Other', '', 'low', NULL, NULL, 2, NULL, NULL, NULL, 'pending', '2025-05-28 09:56:35', '2025-06-04'),
(115, 'TCK-LS078', 550, 'System Downtime', 'IT', 'Cloud', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2024-11-11 09:56:35', '2024-11-13'),
(116, 'TCK-CF079', 601, 'Password Reset', 'IT', 'Database', '', 'low', NULL, NULL, 1, NULL, NULL, NULL, 'pending', '2025-01-23 09:56:35', '2025-01-27'),
(117, 'TCK-MN080', 541, 'System Downtime', 'IT', 'Hardware', 'high', 'high', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2025-07-01 09:56:35', '2025-07-08'),
(118, 'TCK-CL081', 637, 'Data Recovery', 'IT', 'Network', 'low', 'low', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2025-07-11 09:56:35', '2025-07-13'),
(119, 'TCK-MJ082', 534, 'Security Audit Required', 'IT', 'Network', 'low', 'low', NULL, NULL, 2, NULL, NULL, NULL, 'pending', '2025-02-24 09:56:35', '2025-02-28'),
(120, 'TCK-MA083', 492, 'IT Support Request', 'IT', 'Performance', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', '2025-04-26 09:56:35', '2025-04-29'),
(121, 'TCK-EL084', 542, 'Login Issues', 'IT', 'Network', 'high', 'high', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2025-01-02 09:56:35', '2025-01-09'),
(122, 'TCK-LW085', 553, 'Firewall Configuration', 'IT', 'Hardware', 'high', 'high', NULL, NULL, 1, NULL, NULL, NULL, 'pending', '2025-02-14 09:56:35', '2025-02-20'),
(123, 'TCK-ML086', 504, 'System Updates', 'IT', 'Network', 'high', 'high', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2025-05-15 09:56:35', '2025-05-21'),
(124, 'TCK-EL087', 542, 'Backup Configuration', 'IT', 'Network', 'low', 'low', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2025-07-10 09:56:35', '2025-07-15'),
(125, 'TCK-GW088', 633, 'Network Speed Slow', 'IT', 'Security', '', 'low', NULL, NULL, 5, NULL, NULL, NULL, 'complete', '2025-01-25 09:56:35', '2025-01-30'),
(126, 'TCK-GR089', 522, 'IT Support Request', 'IT', 'Performance', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'complete', '2025-03-14 09:56:35', '2025-03-19'),
(127, 'TCK-MD090', 573, 'Network Security', 'IT', 'Database', 'high', 'high', NULL, NULL, 5, NULL, NULL, NULL, 'complete', '2025-06-15 09:56:35', '2025-06-20'),
(128, 'TCK-MA091', 492, 'Database Backup Failure', 'IT', 'Performance', 'low', 'low', NULL, NULL, 4, NULL, NULL, NULL, 'complete', '2025-04-06 09:56:35', '2025-04-10'),
(129, 'TCK-JR092', 543, 'Network Connectivity Issues', 'IT', 'Hardware', 'high', 'high', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2025-01-25 09:56:35', '2025-01-31'),
(130, 'TCK-MD093', 573, 'Application Crashing', 'IT', 'Performance', 'low', 'low', NULL, NULL, 3, NULL, NULL, NULL, 'complete', '2025-05-10 09:56:35', '2025-05-13'),
(131, 'TCK-LJ094', 560, 'Firewall Configuration', 'IT', 'Database', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2024-12-06 09:56:35', '2024-12-07'),
(132, 'TCK-LR095', 666, 'Password Reset', 'IT', 'Cloud', '', 'low', NULL, NULL, 2, NULL, NULL, NULL, 'pending', '2025-06-28 09:56:35', '2025-07-01'),
(133, 'TCK-LW096', 553, 'VPN Connection Problems', 'IT', 'Database', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', '2025-02-28 09:56:35', '2025-03-01'),
(134, 'TCK-MD097', 573, 'Data Import Error', 'IT', 'Database', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2025-10-07 09:56:35', '2025-10-13'),
(135, 'TCK-DA098', 566, 'Mobile Sync Issues', 'IT', 'Other', 'low', 'low', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2025-07-27 09:56:35', '2025-07-29'),
(136, 'TCK-MN099', 541, 'Antivirus Update', 'IT', 'Database', 'high', 'high', NULL, NULL, 1, NULL, NULL, NULL, 'pending', '2025-02-25 09:56:35', '2025-02-28'),
(137, 'TCK-SS100', 585, 'Email Configuration', 'IT', 'Network', 'high', 'high', NULL, NULL, 1, NULL, NULL, NULL, 'pending', '2025-01-01 09:56:35', '2025-01-03'),
(138, 'TCK-GW101', 633, 'Firewall Configuration', 'IT', 'Cloud', 'high', 'high', NULL, NULL, 1, NULL, NULL, NULL, 'pending', '2024-11-27 09:56:35', '2024-12-01'),
(139, 'TCK-RM102', 446, 'Software Installation', 'IT', 'Hardware', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2025-01-25 09:56:35', '2025-02-01'),
(140, 'TCK-MN103', 541, 'Cloud Migration Issues', 'IT', 'Software', 'high', 'high', NULL, NULL, 3, NULL, NULL, NULL, 'complete', '2025-08-16 09:56:35', '2025-08-20'),
(141, 'TCK-PW104', 600, 'VPN Connection Problems', 'IT', 'Security', 'high', 'high', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2025-09-27 09:56:35', '2025-09-30'),
(142, 'TCK-AR105', 677, 'Hardware Replacement', 'IT', 'Security', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2024-12-09 09:56:35', '2024-12-14'),
(143, 'TCK-EY106', 635, 'System Freezing', 'IT', 'Database', '', 'low', NULL, NULL, 3, NULL, NULL, NULL, 'complete', '2024-10-23 09:56:35', '2024-10-27'),
(144, 'TCK-MA107', 492, 'Email Configuration', 'IT', 'Network', '', 'low', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2025-02-08 09:56:35', '2025-02-10'),
(145, 'TCK-MN108', 532, 'Software Installation', 'IT', 'Software', 'high', 'high', NULL, NULL, 3, NULL, NULL, NULL, 'complete', '2025-09-11 09:56:35', '2025-09-14'),
(146, 'TCK-GT109', 501, 'Mobile Sync Issues', 'IT', 'Other', 'high', 'high', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2025-03-22 09:56:35', '2025-03-25'),
(147, 'TCK-MN110', 532, 'Antivirus Update', 'IT', 'Software', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', '2025-08-14 09:56:35', '2025-08-21'),
(148, 'TCK-GW111', 633, 'Firewall Configuration', 'IT', 'Security', 'high', 'high', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2025-06-27 09:56:35', '2025-07-03'),
(149, 'TCK-GW112', 633, 'Email Configuration', 'IT', 'Software', 'high', 'high', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2025-01-19 09:56:35', '2025-01-24'),
(150, 'TCK-BW113', 672, 'Printer Not Working', 'IT', 'Cloud', 'low', 'low', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2025-03-19 09:56:35', '2025-03-20'),
(151, 'TCK-JR114', 543, 'Server Maintenance Needed', 'IT', 'Cloud', 'high', 'high', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2025-07-19 09:56:35', '2025-07-22'),
(152, 'TCK-KT115', 474, 'System Freezing', 'IT', 'Cloud', 'low', 'low', NULL, NULL, 5, NULL, NULL, NULL, 'complete', '2025-05-10 09:56:35', '2025-05-12'),
(153, 'TCK-DJ116', 514, 'Mobile Sync Issues', 'IT', 'Database', '', 'low', NULL, NULL, 4, NULL, NULL, NULL, 'complete', '2025-07-30 09:56:35', '2025-07-31'),
(154, 'TCK-DR117', 624, 'System Updates', 'IT', 'Performance', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2025-09-19 09:56:35', '2025-09-23'),
(155, 'TCK-PW118', 600, 'Antivirus Update', 'IT', 'Database', 'low', 'low', NULL, NULL, 2, NULL, NULL, NULL, 'complete', '2025-08-09 09:56:35', '2025-08-11'),
(156, 'TCK-KT119', 474, 'Security Audit Required', 'IT', 'Database', '', 'low', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2025-06-17 09:56:35', '2025-06-22'),
(157, 'TCK-ES120', 561, 'Printer Not Working', 'IT', 'Performance', 'low', 'low', NULL, NULL, 2, NULL, NULL, NULL, 'pending', '2025-10-08 09:56:35', '2025-10-11'),
(158, 'TCK-LR121', 666, 'System Downtime', 'IT', 'Hardware', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2024-12-12 09:56:35', '2024-12-17'),
(159, 'TCK-JJ122', 567, 'System Freezing', 'IT', 'Hardware', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2025-01-05 09:56:35', '2025-01-12'),
(160, 'TCK-MN123', 541, 'User Permissions', 'IT', 'Security', 'high', 'high', NULL, NULL, 1, NULL, NULL, NULL, 'complete', '2025-06-06 09:56:35', '2025-06-10'),
(161, 'TCK-TG124', 486, 'Network Security', 'IT', 'Security', 'high', 'high', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2024-12-27 09:56:35', '2025-01-03'),
(162, 'TCK-LW125', 553, 'Firewall Configuration', 'IT', 'Other', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', '2025-04-09 09:56:35', '2025-04-10'),
(163, 'TCK-TG126', 486, 'System Downtime', 'IT', 'Other', 'low', 'low', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2025-10-14 09:56:35', '2025-10-15'),
(164, 'TCK-ST127', 513, 'Database Backup Failure', 'IT', 'Software', 'high', 'high', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2025-10-04 09:56:35', '2025-10-08'),
(165, 'TCK-PT128', 451, 'Application Crashing', 'IT', 'Software', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'complete', '2024-11-11 09:56:35', '2024-11-12'),
(166, 'TCK-BW129', 672, 'Network Speed Slow', 'IT', 'Database', '', 'low', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2024-10-30 09:56:35', '2024-11-02'),
(167, 'TCK-DR130', 624, 'Database Performance', 'IT', 'Network', '', 'low', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2024-12-05 09:56:35', '2024-12-08'),
(168, 'TCK-GW131', 633, 'IT Support Request', 'IT', 'Database', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2024-10-24 09:56:35', '2024-10-26'),
(169, 'TCK-LT132', 572, 'Password Reset', 'IT', 'Network', 'high', 'high', NULL, NULL, 5, NULL, NULL, NULL, 'complete', '2025-04-01 09:56:35', '2025-04-06'),
(170, 'TCK-BW133', 672, 'System Downtime', 'IT', 'Other', 'high', 'high', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2024-12-09 09:56:35', '2024-12-14'),
(171, 'TCK-DJ134', 514, 'User Permissions', 'IT', 'Database', '', 'low', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2025-08-31 09:56:35', '2025-09-02'),
(172, 'TCK-PW135', 600, 'Cloud Migration Issues', 'IT', 'Software', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2025-09-07 09:56:35', '2025-09-11'),
(173, 'TCK-MN136', 532, 'Hardware Replacement', 'IT', 'Network', 'high', 'high', NULL, NULL, 2, NULL, NULL, NULL, 'pending', '2024-11-18 09:56:35', '2024-11-20'),
(174, 'TCK-GW137', 633, 'Cloud Migration Issues', 'IT', 'Cloud', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2024-10-20 09:56:35', '2024-10-21'),
(175, 'TCK-ST138', 513, 'Mobile Sync Issues', 'IT', 'Software', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'complete', '2024-12-25 09:56:35', '2024-12-27'),
(176, 'TCK-KT139', 474, 'Website Not Loading', 'IT', 'Software', 'low', 'low', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2024-11-22 09:56:35', '2024-11-28'),
(177, 'TCK-NT140', 493, 'System Freezing', 'IT', 'Security', '', 'low', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2025-02-21 09:56:35', '2025-02-24'),
(178, 'TCK-MN141', 532, 'File Corruption', 'IT', 'Other', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', '2024-12-07 09:56:35', '2024-12-14'),
(179, 'TCK-PW142', 600, 'IT Support Request', 'IT', 'Hardware', 'low', 'low', NULL, NULL, 4, NULL, NULL, NULL, 'complete', '2025-08-07 09:56:35', '2025-08-10'),
(180, 'TCK-GW143', 633, 'Network Security', 'IT', 'Database', 'high', 'high', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2024-11-10 09:56:35', '2024-11-11'),
(181, 'TCK-MJ144', 534, 'Data Recovery', 'IT', 'Cloud', 'low', 'low', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2025-01-21 09:56:35', '2025-01-23'),
(182, 'TCK-EL145', 542, 'File Server Access', 'IT', 'Database', '', 'low', NULL, NULL, 2, NULL, NULL, NULL, 'complete', '2024-11-28 09:56:35', '2024-11-29'),
(183, 'TCK-MD146', 573, 'Password Reset', 'IT', 'Cloud', 'high', 'high', NULL, NULL, 1, NULL, NULL, NULL, 'pending', '2025-04-13 09:56:35', '2025-04-17'),
(184, 'TCK-MJ147', 534, 'Cloud Migration Issues', 'IT', 'Other', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2025-03-28 09:56:35', '2025-03-31'),
(185, 'TCK-LW148', 553, 'Website Not Loading', 'IT', 'Network', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', '2025-03-20 09:56:35', '2025-03-21'),
(186, 'TCK-LP149', 443, 'Network Speed Slow', 'IT', 'Software', 'high', 'high', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2025-07-13 09:56:35', '2025-07-20'),
(187, 'TCK-WA150', 603, 'Antivirus Update', 'IT', 'Network', '', 'low', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2024-11-27 09:56:35', '2024-11-30'),
(188, 'TCK-TG151', 486, 'Server Maintenance Needed', 'IT', 'Database', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', '2024-11-08 09:56:35', '2024-11-15'),
(189, 'TCK-PW152', 600, 'Remote Access Setup', 'IT', 'Software', 'low', 'low', NULL, NULL, 1, NULL, NULL, NULL, 'pending', '2025-05-01 09:56:35', '2025-05-02'),
(190, 'TCK-JJ153', 567, 'Database Backup Failure', 'IT', 'Other', '', 'low', NULL, NULL, 1, NULL, NULL, NULL, 'pending', '2024-12-10 09:56:35', '2024-12-11'),
(191, 'TCK-PT154', 451, 'IT Support Request', 'IT', 'Security', 'high', 'high', NULL, NULL, 1, NULL, NULL, NULL, 'complete', '2025-05-28 09:56:35', '2025-05-31'),
(192, 'TCK-AH155', 453, 'Cloud Migration Issues', 'IT', 'Performance', 'low', 'low', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2025-06-14 09:56:35', '2025-06-17'),
(193, 'TCK-LP156', 443, 'System Updates', 'IT', 'Software', 'low', 'low', NULL, NULL, 3, NULL, NULL, NULL, 'complete', '2025-01-18 09:56:35', '2025-01-25'),
(194, 'TCK-JJ157', 567, 'Website Not Loading', 'IT', 'Performance', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2025-01-26 09:56:35', '2025-01-28'),
(195, 'TCK-GT158', 501, 'File Server Access', 'IT', 'Security', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2025-09-12 09:56:35', '2025-09-14'),
(196, 'TCK-LR159', 666, 'Antivirus Update', 'IT', 'Database', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2025-03-30 09:56:35', '2025-04-03'),
(197, 'TCK-RM160', 446, 'Cloud Migration Issues', 'IT', 'Performance', '', 'low', NULL, NULL, 1, NULL, NULL, NULL, 'pending', '2024-12-18 09:56:35', '2024-12-24'),
(198, 'TCK-BW161', 672, 'Cloud Migration Issues', 'IT', 'Other', 'low', 'low', NULL, NULL, 3, NULL, NULL, NULL, 'complete', '2025-02-13 09:56:35', '2025-02-19'),
(199, 'TCK-BA162', 549, 'Data Import Error', 'IT', 'Other', '', 'low', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2025-01-04 09:56:35', '2025-01-06'),
(200, 'TCK-LS163', 550, 'VPN Connection Problems', 'IT', 'Performance', 'high', 'high', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2025-09-04 09:56:35', '2025-09-10'),
(201, 'TCK-MN164', 541, 'Login Issues', 'IT', 'Security', 'low', 'low', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2024-11-27 09:56:35', '2024-11-30'),
(202, 'TCK-PT165', 440, 'Network Security', 'IT', 'Security', '', 'low', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2025-09-21 09:56:35', '2025-09-23'),
(203, 'TCK-SJ166', 580, 'System Downtime', 'IT', 'Software', 'low', 'low', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2025-08-23 09:56:35', '2025-08-28'),
(204, 'TCK-GW167', 633, 'Software Installation', 'IT', 'Software', 'high', 'high', NULL, NULL, 1, NULL, NULL, NULL, 'pending', '2025-04-29 09:56:35', '2025-05-03'),
(205, 'TCK-JR168', 543, 'Network Security', 'IT', 'Software', 'low', 'low', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2025-06-26 09:56:35', '2025-06-29'),
(206, 'TCK-ES169', 561, 'Website Not Loading', 'IT', 'Database', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2024-10-29 09:56:35', '2024-10-30'),
(207, 'TCK-KT170', 474, 'Antivirus Update', 'IT', 'Other', 'high', 'high', NULL, NULL, 5, NULL, NULL, NULL, 'complete', '2024-10-24 09:56:35', '2024-10-29'),
(208, 'TCK-AR171', 677, 'Email Configuration', 'IT', 'Performance', '', 'low', NULL, NULL, 1, NULL, NULL, NULL, 'complete', '2025-02-21 09:56:35', '2025-02-25'),
(209, 'TCK-LW172', 553, 'Password Reset', 'IT', 'Other', 'high', 'high', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2024-12-11 09:56:35', '2024-12-15'),
(210, 'TCK-CL173', 637, 'Database Performance', 'IT', 'Hardware', 'low', 'low', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2025-10-05 09:56:35', '2025-10-10'),
(211, 'TCK-BW174', 672, 'Backup Configuration', 'IT', 'Other', '', 'low', NULL, NULL, 1, NULL, NULL, NULL, 'pending', '2025-08-29 09:56:35', '2025-09-04'),
(212, 'TCK-AR175', 677, 'Network Speed Slow', 'IT', 'Database', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'complete', '2025-06-01 09:56:35', '2025-06-03'),
(213, 'TCK-MG176', 435, 'Application Crashing', 'IT', 'Security', '', 'low', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2025-04-13 09:56:35', '2025-04-14'),
(214, 'TCK-LS177', 550, 'Website Not Loading', 'IT', 'Performance', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'complete', '2025-07-23 09:56:35', '2025-07-28'),
(215, 'TCK-AH178', 453, 'VPN Connection Problems', 'IT', 'Software', '', 'low', NULL, NULL, 1, NULL, NULL, NULL, 'pending', '2025-01-08 09:56:35', '2025-01-12'),
(216, 'TCK-LJ179', 560, 'Network Connectivity Issues', 'IT', 'Other', '', 'low', NULL, NULL, 4, NULL, NULL, NULL, 'complete', '2024-10-28 09:56:35', '2024-10-29'),
(217, 'TCK-RM180', 446, 'Hardware Replacement', 'IT', 'Software', 'high', 'high', NULL, NULL, 1, NULL, NULL, NULL, 'pending', '2025-09-13 09:56:35', '2025-09-16'),
(218, 'TCK-MN181', 541, 'VPN Connection Problems', 'IT', 'Hardware', 'high', 'high', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2024-11-10 09:56:35', '2024-11-12'),
(219, 'TCK-SS182', 585, 'System Updates', 'IT', 'Cloud', 'low', 'low', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2025-01-14 09:56:35', '2025-01-20'),
(220, 'TCK-KT183', 474, 'Firewall Configuration', 'IT', 'Performance', 'high', 'high', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2025-01-30 09:56:35', '2025-02-06'),
(221, 'TCK-MJ184', 534, 'Network Connectivity Issues', 'IT', 'Other', 'low', 'low', NULL, NULL, 1, NULL, NULL, NULL, 'complete', '2025-08-05 09:56:35', '2025-08-12'),
(222, 'TCK-EY185', 635, 'Data Import Error', 'IT', 'Hardware', 'high', 'high', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2025-06-16 09:56:35', '2025-06-23'),
(223, 'TCK-BW186', 672, 'Software Installation', 'IT', 'Security', '', 'low', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2025-01-14 09:56:35', '2025-01-17'),
(224, 'TCK-GW187', 633, 'Cloud Migration Issues', 'IT', 'Network', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', '2025-02-26 09:56:35', '2025-03-05'),
(225, 'TCK-SS188', 585, 'Backup Configuration', 'IT', 'Hardware', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2025-04-01 09:56:35', '2025-04-05'),
(226, 'TCK-LA189', 464, 'Login Issues', 'IT', 'Hardware', '', 'low', NULL, NULL, 5, NULL, NULL, NULL, 'complete', '2025-06-19 09:56:35', '2025-06-22'),
(227, 'TCK-CL190', 637, 'Password Reset', 'IT', 'Network', 'low', 'low', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2024-10-24 09:56:35', '2024-10-28'),
(228, 'TCK-CL191', 637, 'VPN Connection Problems', 'IT', 'Database', 'high', 'high', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2024-10-18 09:56:35', '2024-10-23'),
(229, 'TCK-GW192', 633, 'File Corruption', 'IT', 'Other', 'low', 'low', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2024-12-13 09:56:35', '2024-12-19'),
(230, 'TCK-MN193', 541, 'Software Installation', 'IT', 'Performance', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2025-08-27 09:56:35', '2025-09-03'),
(231, 'TCK-ST194', 513, 'Network Speed Slow', 'IT', 'Other', 'high', 'high', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2025-07-27 09:56:35', '2025-07-29'),
(232, 'TCK-ES195', 561, 'System Downtime', 'IT', 'Hardware', 'high', 'high', NULL, NULL, 2, NULL, NULL, NULL, 'pending', '2025-06-16 09:56:35', '2025-06-19'),
(233, 'TCK-GW196', 633, 'Remote Access Setup', 'IT', 'Software', 'high', 'high', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2025-03-08 09:56:35', '2025-03-09'),
(234, 'TCK-S197', 354, 'IT Support Request', 'IT', 'Performance', 'high', 'high', NULL, NULL, 4, NULL, NULL, NULL, 'complete', '2025-01-11 09:56:35', '2025-01-18'),
(235, 'TCK-LS198', 550, 'Database Performance', 'IT', 'Database', 'low', 'low', NULL, NULL, 4, NULL, NULL, NULL, 'complete', '2025-02-07 09:56:35', '2025-02-10'),
(236, 'TCK-WA199', 603, 'System Optimization', 'IT', 'Software', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2024-12-28 09:56:35', '2025-01-02'),
(237, 'TCK-LR200', 666, 'Network Connectivity Issues', 'IT', 'Cloud', '', 'low', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2024-11-11 09:56:35', '2024-11-13'),
(238, 'TCK-DT001', 460, 'System Optimization', 'IT', 'Software', '', 'low', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2025-09-27 23:19:33', '2025-09-29'),
(239, 'TCK-DF002', 586, 'Login Issues', 'IT', 'Database', '', 'low', NULL, NULL, 5, NULL, NULL, NULL, 'complete', '2024-12-23 23:19:33', '2024-12-27'),
(241, 'TCK-LW004', 634, 'Database Performance', 'IT', 'Network', 'high', 'high', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2025-09-01 23:19:33', '2025-09-08'),
(242, 'TCK-MW005', 702, 'Network Speed Slow', 'IT', 'Database', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'complete', '2025-09-02 23:19:33', '2025-09-08'),
(243, 'TCK-JW006', 485, 'Printer Not Working', 'IT', 'Network', 'high', 'high', NULL, NULL, 1, NULL, NULL, NULL, 'pending', '2025-04-12 23:19:33', '2025-04-18'),
(244, 'TCK-JM007', 826, 'Database Backup Failure', 'IT', 'Software', 'high', 'high', NULL, NULL, 4, NULL, NULL, NULL, 'complete', '2025-04-28 23:19:33', '2025-05-04'),
(245, 'TCK-JW008', 485, 'Security Audit Required', 'IT', 'Software', 'high', 'high', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2024-12-03 23:19:33', '2024-12-07'),
(246, 'TCK-TJ009', 829, 'Password Reset', 'IT', 'Hardware', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2025-01-21 23:19:33', '2025-01-25'),
(247, 'TCK-CH010', 523, 'Network Security', 'IT', 'Software', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', '2025-03-22 23:19:33', '2025-03-24'),
(248, 'TCK-JC011', 735, 'Database Backup Failure', 'IT', 'Software', 'high', 'high', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2025-01-25 23:19:33', '2025-01-27'),
(249, 'TCK-DT012', 772, 'Network Connectivity Issues', 'IT', 'Network', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'complete', '2024-10-23 23:19:33', '2024-10-25'),
(250, 'TCK-RF013', 863, 'Network Security', 'IT', 'Security', 'low', 'low', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2024-11-22 23:19:33', '2024-11-26'),
(251, 'TCK-JW014', 485, 'Data Recovery', 'IT', 'Database', 'high', 'high', NULL, NULL, 1, NULL, NULL, NULL, 'pending', '2025-06-19 23:19:33', '2025-06-26'),
(252, 'TCK-LW015', 553, 'Email Configuration', 'IT', 'Security', 'high', 'high', NULL, NULL, 1, NULL, NULL, NULL, 'complete', '2025-04-07 23:19:33', '2025-04-10'),
(253, 'TCK-CL016', 708, 'File Corruption', 'IT', 'Other', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2025-08-04 23:19:33', '2025-08-10'),
(255, 'TCK-SS018', 569, 'Software Installation', 'IT', 'Other', 'high', 'high', NULL, NULL, 4, NULL, NULL, NULL, 'complete', '2025-03-13 23:19:33', '2025-03-21'),
(256, 'TCK-CL019', 637, 'Data Import Error', 'IT', 'Network', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', '2025-02-21 23:19:33', '2025-02-27'),
(257, 'TCK-LT020', 588, 'Login Issues', 'IT', 'Software', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2025-04-03 23:19:33', '2025-04-05'),
(258, 'TCK-ST021', 799, 'Database Backup Failure', 'IT', 'Hardware', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'complete', '2025-05-13 23:19:33', '2025-05-17'),
(259, 'TCK-MW022', 796, 'Server Maintenance Needed', 'IT', 'Other', '', 'low', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2025-05-07 23:19:33', '2025-05-12'),
(260, 'TCK-JW023', 538, 'System Downtime', 'IT', 'Security', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'complete', '2025-08-03 23:19:33', '2025-08-05'),
(261, 'TCK-JC024', 735, 'Database Backup Failure', 'IT', 'Network', 'high', 'high', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2024-11-13 23:19:33', '2024-11-19'),
(262, 'TCK-HT025', 505, 'Server Maintenance Needed', 'IT', 'Performance', 'low', 'low', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2025-10-07 23:19:33', '2025-10-13'),
(263, 'TCK-DT026', 460, 'System Optimization', 'IT', 'Cloud', 'low', 'low', NULL, NULL, 3, NULL, NULL, NULL, 'complete', '2025-04-30 23:19:33', '2025-05-05'),
(264, 'TCK-PJ027', 914, 'System Optimization', 'IT', 'Hardware', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2024-11-13 23:19:33', '2024-11-18'),
(265, 'TCK-NT028', 452, 'Network Speed Slow', 'IT', 'Cloud', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'complete', '2025-04-28 23:19:33', '2025-05-05'),
(266, 'TCK-DL029', 593, 'Network Speed Slow', 'IT', 'Security', '', 'low', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2025-02-07 23:19:33', '2025-02-09'),
(267, 'TCK-LD030', 888, 'Remote Access Setup', 'IT', 'Performance', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', '2025-03-04 23:19:33', '2025-03-09'),
(268, 'TCK-SB031', 454, 'System Freezing', 'IT', 'Other', '', 'low', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2025-09-11 23:19:33', '2025-09-19'),
(269, 'TCK-WJ032', 860, 'Cloud Migration Issues', 'IT', 'Database', 'high', 'high', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2025-08-04 23:19:33', '2025-08-08'),
(270, 'TCK-TT033', 525, 'Network Connectivity Issues', 'IT', 'Security', '', 'low', NULL, NULL, 1, NULL, NULL, NULL, 'pending', '2025-05-14 23:19:33', '2025-05-19'),
(271, 'TCK-SG034', 621, 'Website Not Loading', 'IT', 'Software', '', 'low', NULL, NULL, 2, NULL, NULL, NULL, 'pending', '2025-06-18 23:19:33', '2025-06-21'),
(272, 'TCK-DF035', 586, 'Email Configuration', 'IT', 'Hardware', 'high', 'high', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2024-10-21 23:19:33', '2024-10-28'),
(273, 'TCK-TG036', 486, 'Network Security', 'IT', 'Cloud', '', 'low', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2025-07-23 23:19:33', '2025-07-26'),
(274, 'TCK-PJ037', 914, 'Remote Access Setup', 'IT', 'Security', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', '2025-05-04 23:19:33', '2025-05-12'),
(275, 'TCK-BM038', 886, 'Application Crashing', 'IT', 'Hardware', 'high', 'high', NULL, NULL, 2, NULL, NULL, NULL, 'pending', '2025-01-15 23:19:33', '2025-01-20'),
(276, 'TCK-MN039', 909, 'Software Installation', 'IT', 'Security', 'low', 'low', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2024-11-07 23:19:33', '2024-11-15'),
(277, 'TCK-TG040', 486, 'System Optimization', 'IT', 'Other', 'high', 'high', NULL, NULL, 1, NULL, NULL, NULL, 'pending', '2024-12-02 23:19:33', '2024-12-05'),
(278, 'TCK-SB041', 454, 'Data Recovery', 'IT', 'Network', 'high', 'high', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2025-08-08 23:19:33', '2025-08-10'),
(279, 'TCK-KY042', 859, 'Printer Not Working', 'IT', 'Cloud', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2025-04-05 23:19:33', '2025-04-13'),
(280, 'TCK-DT043', 587, 'Mobile Sync Issues', 'IT', 'Hardware', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2024-12-28 23:19:33', '2025-01-05'),
(281, 'TCK-JJ044', 567, 'VPN Connection Problems', 'IT', 'Software', 'high', 'high', NULL, NULL, 2, NULL, NULL, NULL, 'complete', '2025-03-30 23:19:33', '2025-04-07'),
(282, 'TCK-DT045', 587, 'Login Issues', 'IT', 'Cloud', 'high', 'high', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2025-10-12 23:19:33', '2025-10-18'),
(283, 'TCK-WJ046', 860, 'Printer Not Working', 'IT', 'Software', 'low', 'low', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2025-03-29 23:19:33', '2025-04-05'),
(284, 'TCK-CL047', 637, 'System Downtime', 'IT', 'Cloud', 'low', 'low', NULL, NULL, 1, NULL, NULL, NULL, 'complete', '2025-01-09 23:19:33', '2025-01-16'),
(285, 'TCK-SG048', 621, 'Server Maintenance Needed', 'IT', 'Cloud', 'low', 'low', NULL, NULL, 2, NULL, NULL, NULL, 'pending', '2025-09-12 23:19:33', '2025-09-16'),
(286, 'TCK-PJ049', 914, 'Application Crashing', 'IT', 'Hardware', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2025-05-03 23:19:33', '2025-05-08'),
(287, 'TCK-TG050', 486, 'Backup Configuration', 'IT', 'Database', '', 'low', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2025-02-21 23:19:33', '2025-02-24'),
(288, 'TCK-DT051', 460, 'Network Security', 'IT', 'Network', 'high', 'high', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2025-05-09 23:19:33', '2025-05-11'),
(289, 'TCK-MW052', 796, 'Server Maintenance Needed', 'IT', 'Performance', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2025-07-14 23:19:33', '2025-07-16'),
(290, 'TCK-DF053', 586, 'IT Support Request', 'IT', 'Security', 'high', 'high', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2025-08-31 23:19:33', '2025-09-03'),
(291, 'TCK-LW054', 553, 'Data Recovery', 'IT', 'Other', 'low', 'low', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2025-03-30 23:19:33', '2025-04-06'),
(292, 'TCK-MW055', 702, 'Login Issues', 'IT', 'Software', '', 'low', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2025-01-17 23:19:33', '2025-01-19'),
(293, 'TCK-BM056', 886, 'Network Speed Slow', 'IT', 'Security', '', 'low', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2025-04-01 23:19:33', '2025-04-03'),
(294, 'TCK-JH057', 527, 'VPN Connection Problems', 'IT', 'Hardware', 'high', 'high', NULL, NULL, 1, NULL, NULL, NULL, 'pending', '2025-06-25 23:19:33', '2025-06-28'),
(295, 'TCK-LW058', 634, 'System Updates', 'IT', 'Hardware', 'high', 'high', NULL, NULL, 2, NULL, NULL, NULL, 'pending', '2024-12-30 23:19:33', '2025-01-03'),
(296, 'TCK-DT059', 772, 'Login Issues', 'IT', 'Cloud', 'low', 'low', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2024-10-27 23:19:33', '2024-10-30'),
(297, 'TCK-SS060', 569, 'IT Support Request', 'IT', 'Network', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2024-11-20 23:19:33', '2024-11-24'),
(298, 'TCK-PJ061', 914, 'Application Crashing', 'IT', 'Network', 'high', 'high', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2025-05-09 23:19:33', '2025-05-13'),
(299, 'TCK-RF062', 863, 'Hardware Replacement', 'IT', 'Hardware', 'high', 'high', NULL, NULL, 2, NULL, NULL, NULL, 'complete', '2025-06-25 23:19:33', '2025-06-29'),
(300, 'TCK-LT063', 588, 'Firewall Configuration', 'IT', 'Database', 'high', 'high', NULL, NULL, 3, NULL, NULL, NULL, 'complete', '2025-09-11 23:19:33', '2025-09-16'),
(301, 'TCK-CH064', 523, 'Printer Not Working', 'IT', 'Software', '', 'low', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2024-11-18 23:19:33', '2024-11-26'),
(302, 'TCK-DT065', 772, 'Login Issues', 'IT', 'Performance', 'low', 'low', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2025-01-24 23:19:33', '2025-01-27'),
(303, 'TCK-MW066', 702, 'System Optimization', 'IT', 'Software', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2024-10-27 23:19:33', '2024-11-04'),
(304, 'TCK-JC067', 735, 'User Permissions', 'IT', 'Performance', 'high', 'high', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2025-03-20 23:19:33', '2025-03-24'),
(305, 'TCK-CH068', 523, 'Network Security', 'IT', 'Database', 'high', 'high', NULL, NULL, 1, NULL, NULL, NULL, 'complete', '2025-01-23 23:19:33', '2025-01-31'),
(306, 'TCK-SS069', 569, 'System Downtime', 'IT', 'Software', 'high', 'high', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2024-12-22 23:19:33', '2024-12-29'),
(307, 'TCK-RG070', 620, 'System Freezing', 'IT', 'Security', 'low', 'low', NULL, NULL, 2, NULL, NULL, NULL, 'pending', '2025-09-19 23:19:33', '2025-09-24'),
(308, 'TCK-WJ071', 860, 'Network Connectivity Issues', 'IT', 'Other', 'high', 'high', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2025-09-07 23:19:33', '2025-09-11'),
(309, 'TCK-LW072', 634, 'Printer Not Working', 'IT', 'Software', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2025-06-23 23:19:33', '2025-06-26'),
(310, 'TCK-TT073', 525, 'Cloud Migration Issues', 'IT', 'Performance', 'low', 'low', NULL, NULL, 1, NULL, NULL, NULL, 'pending', '2025-05-26 23:19:33', '2025-06-01'),
(311, 'TCK-AH074', 805, 'Data Recovery', 'IT', 'Cloud', '', 'low', NULL, NULL, 1, NULL, NULL, NULL, 'pending', '2025-07-19 23:19:33', '2025-07-22'),
(313, 'TCK-NT076', 452, 'Website Not Loading', 'IT', 'Cloud', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2025-06-06 23:19:33', '2025-06-13'),
(314, 'TCK-ST077', 799, 'File Server Access', 'IT', 'Cloud', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'complete', '2025-07-10 23:19:33', '2025-07-12'),
(315, 'TCK-ST078', 799, 'File Server Access', 'IT', 'Software', 'low', 'low', NULL, NULL, 5, NULL, NULL, NULL, 'complete', '2025-02-07 23:19:33', '2025-02-12'),
(316, 'TCK-MW079', 702, 'System Updates', 'IT', 'Database', 'high', 'high', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2025-06-28 23:19:33', '2025-07-05'),
(317, 'TCK-JM080', 788, 'Server Maintenance Needed', 'IT', 'Database', 'high', 'high', NULL, NULL, 3, NULL, NULL, NULL, 'complete', '2024-12-16 23:19:33', '2024-12-21'),
(318, 'TCK-LT081', 588, 'User Permissions', 'IT', 'Security', 'high', 'high', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2025-02-11 23:19:33', '2025-02-14'),
(319, 'TCK-LW082', 634, 'IT Support Request', 'IT', 'Performance', 'high', 'high', NULL, NULL, 4, NULL, NULL, NULL, 'complete', '2025-10-07 23:19:33', '2025-10-10'),
(320, 'TCK-PJ083', 914, 'Antivirus Update', 'IT', 'Other', 'low', 'low', NULL, NULL, 1, NULL, NULL, NULL, 'pending', '2024-10-26 23:19:33', '2024-11-03'),
(321, 'TCK-JA084', 433, 'Hardware Replacement', 'IT', 'Security', 'low', 'low', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2025-07-29 23:19:33', '2025-08-03'),
(322, 'TCK-LW085', 553, 'System Updates', 'IT', 'Other', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2025-10-07 23:19:33', '2025-10-10'),
(323, 'TCK-JH086', 527, 'IT Support Request', 'IT', 'Database', 'high', 'high', NULL, NULL, 2, NULL, NULL, NULL, 'pending', '2025-06-08 23:19:33', '2025-06-15'),
(324, 'TCK-JH087', 527, 'Hardware Replacement', 'IT', 'Cloud', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2025-06-01 23:19:33', '2025-06-09'),
(326, 'TCK-KG089', 912, 'Hardware Replacement', 'IT', 'Other', 'low', 'low', NULL, NULL, 1, NULL, NULL, NULL, 'pending', '2025-02-07 23:19:33', '2025-02-13'),
(327, 'TCK-JM090', 788, 'Security Audit Required', 'IT', 'Cloud', 'high', 'high', NULL, NULL, 3, NULL, NULL, NULL, 'complete', '2025-02-07 23:19:33', '2025-02-15'),
(328, 'TCK-JJ091', 567, 'System Freezing', 'IT', 'Software', 'low', 'low', NULL, NULL, 2, NULL, NULL, NULL, 'pending', '2024-12-29 23:19:33', '2025-01-05'),
(329, 'TCK-DL092', 593, 'Application Crashing', 'IT', 'Hardware', 'low', 'low', NULL, NULL, 3, NULL, NULL, NULL, 'complete', '2025-06-19 23:19:33', '2025-06-22'),
(330, 'TCK-DT093', 460, 'Email Configuration', 'IT', 'Security', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'complete', '2025-09-09 23:19:33', '2025-09-13'),
(331, 'TCK-LT094', 588, 'VPN Connection Problems', 'IT', 'Security', 'low', 'low', NULL, NULL, 5, NULL, NULL, NULL, 'complete', '2024-12-18 23:19:33', '2024-12-26'),
(332, 'TCK-JM095', 788, 'System Optimization', 'IT', 'Performance', 'high', 'high', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2025-07-04 23:19:33', '2025-07-11'),
(333, 'TCK-MM096', 463, 'IT Support Request', 'IT', 'Database', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2025-03-24 23:19:33', '2025-03-27'),
(334, 'TCK-KG097', 912, 'Password Reset', 'IT', 'Hardware', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2025-01-30 23:19:33', '2025-02-03'),
(335, 'TCK-BM098', 886, 'Network Connectivity Issues', 'IT', 'Cloud', 'low', 'low', NULL, NULL, 2, NULL, NULL, NULL, 'complete', '2024-11-03 23:19:33', '2024-11-09'),
(336, 'TCK-DF099', 586, 'Data Import Error', 'IT', 'Other', 'high', 'high', NULL, NULL, 2, NULL, NULL, NULL, 'pending', '2024-11-01 23:19:33', '2024-11-04'),
(337, 'TCK-KG100', 912, 'IT Support Request', 'IT', 'Performance', '', 'low', NULL, NULL, 2, NULL, NULL, NULL, 'pending', '2025-01-01 23:19:33', '2025-01-04'),
(338, 'TCK-MM101', 463, 'Cloud Migration Issues', 'IT', 'Network', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'complete', '2024-12-01 23:19:33', '2024-12-06'),
(340, 'TCK-HT103', 505, 'File Server Access', 'IT', 'Software', 'high', 'high', NULL, NULL, 3, NULL, NULL, NULL, 'complete', '2025-05-30 23:19:33', '2025-06-06'),
(341, 'TCK-PJ104', 914, 'Application Crashing', 'IT', 'Database', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2024-12-09 23:19:33', '2024-12-11'),
(342, 'TCK-RY105', 865, 'System Downtime', 'IT', 'Other', 'high', 'high', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2025-06-14 23:19:33', '2025-06-18'),
(343, 'TCK-JC106', 735, 'System Optimization', 'IT', 'Performance', '', 'low', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2025-02-10 23:19:33', '2025-02-12'),
(344, 'TCK-S107', 354, 'System Downtime', 'IT', 'Network', '', 'low', NULL, NULL, 1, NULL, NULL, NULL, 'complete', '2025-02-09 23:19:33', '2025-02-15');
INSERT INTO `tbl_ticket` (`ticket_id`, `reference_id`, `user_id`, `title`, `type`, `category`, `priority`, `urgency`, `description`, `attachments`, `assigned_technician_id`, `evaluator_id`, `evaluated_at`, `evaluation_notes`, `status`, `created_at`, `sla_date`) VALUES
(345, 'TCK-SS108', 683, 'File Corruption', 'IT', 'Hardware', '', 'low', NULL, NULL, 3, NULL, NULL, NULL, 'complete', '2025-04-14 23:19:33', '2025-04-19'),
(346, 'TCK-SB109', 454, 'Application Crashing', 'IT', 'Database', 'low', 'low', NULL, NULL, 2, NULL, NULL, NULL, 'pending', '2025-02-18 23:19:33', '2025-02-23'),
(347, 'TCK-DT110', 460, 'Server Maintenance Needed', 'IT', 'Cloud', 'low', 'low', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2024-12-20 23:19:33', '2024-12-23'),
(348, 'TCK-RF111', 863, 'System Optimization', 'IT', 'Database', '', 'low', NULL, NULL, 1, NULL, NULL, NULL, 'pending', '2025-04-16 23:19:33', '2025-04-19'),
(349, 'TCK-JM112', 826, 'Data Import Error', 'IT', 'Network', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'complete', '2025-02-05 23:19:33', '2025-02-13'),
(350, 'TCK-RG113', 620, 'Network Connectivity Issues', 'IT', 'Database', 'low', 'low', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2025-07-02 23:19:33', '2025-07-10'),
(351, 'TCK-SB114', 454, 'System Freezing', 'IT', 'Security', 'high', 'high', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2024-11-10 23:19:33', '2024-11-12'),
(352, 'TCK-KY115', 859, 'Server Maintenance Needed', 'IT', 'Other', 'low', 'low', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2025-09-28 23:19:33', '2025-10-05'),
(353, 'TCK-TG116', 486, 'Mobile Sync Issues', 'IT', 'Network', 'low', 'low', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2024-11-27 23:19:33', '2024-12-05'),
(354, 'TCK-JM117', 826, 'System Optimization', 'IT', 'Other', '', 'low', NULL, NULL, 2, NULL, NULL, NULL, 'complete', '2025-10-07 23:19:33', '2025-10-15'),
(355, 'TCK-MM118', 463, 'Backup Configuration', 'IT', 'Cloud', 'low', 'low', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2024-10-23 23:19:33', '2024-10-28'),
(356, 'TCK-JH119', 527, 'File Corruption', 'IT', 'Database', 'low', 'low', NULL, NULL, 1, NULL, NULL, NULL, 'complete', '2025-06-11 23:19:33', '2025-06-18'),
(357, 'TCK-JM120', 826, 'Backup Configuration', 'IT', 'Other', '', 'low', NULL, NULL, 1, NULL, NULL, NULL, 'complete', '2025-01-05 23:19:33', '2025-01-08'),
(358, 'TCK-JW121', 485, 'File Server Access', 'IT', 'Security', 'low', 'low', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2024-10-23 23:19:33', '2024-10-31'),
(359, 'TCK-DT122', 460, 'Data Import Error', 'IT', 'Security', 'high', 'high', NULL, NULL, 3, NULL, NULL, NULL, 'complete', '2024-11-21 23:19:33', '2024-11-27'),
(360, 'TCK-LW123', 634, 'File Server Access', 'IT', 'Hardware', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', '2025-03-30 23:19:33', '2025-04-05'),
(361, 'TCK-ST124', 799, 'Network Speed Slow', 'IT', 'Hardware', 'high', 'high', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2025-02-24 23:19:33', '2025-03-01'),
(362, 'TCK-LW125', 553, 'System Optimization', 'IT', 'Security', 'high', 'high', NULL, NULL, 2, NULL, NULL, NULL, 'pending', '2025-04-13 23:19:33', '2025-04-18'),
(363, 'TCK-RG126', 620, 'Printer Not Working', 'IT', 'Hardware', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2025-08-08 23:19:33', '2025-08-12'),
(364, 'TCK-RF127', 863, 'Firewall Configuration', 'IT', 'Other', '', 'low', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2025-06-10 23:19:33', '2025-06-13'),
(365, 'TCK-ST128', 799, 'Hardware Replacement', 'IT', 'Database', 'low', 'low', NULL, NULL, 2, NULL, NULL, NULL, 'complete', '2025-02-05 23:19:33', '2025-02-10'),
(366, 'TCK-JJ129', 567, 'Data Recovery', 'IT', 'Network', '', 'low', NULL, NULL, 2, NULL, NULL, NULL, 'pending', '2025-04-26 23:19:33', '2025-05-01'),
(367, 'TCK-DR130', 624, 'Website Not Loading', 'IT', 'Database', '', 'low', NULL, NULL, 2, NULL, NULL, NULL, 'pending', '2025-05-31 23:19:33', '2025-06-03'),
(368, 'TCK-ET131', 874, 'System Optimization', 'IT', 'Other', '', 'low', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2025-03-06 23:19:33', '2025-03-10'),
(369, 'TCK-HT132', 505, 'Data Import Error', 'IT', 'Performance', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', '2025-09-09 23:19:33', '2025-09-13'),
(370, 'TCK-JW133', 485, 'Software Installation', 'IT', 'Security', 'high', 'high', NULL, NULL, 1, NULL, NULL, NULL, 'pending', '2024-12-15 23:19:33', '2024-12-17'),
(371, 'TCK-TG134', 486, 'System Optimization', 'IT', 'Other', 'high', 'high', NULL, NULL, 1, NULL, NULL, NULL, 'complete', '2025-01-20 23:19:33', '2025-01-23'),
(372, 'TCK-RY135', 865, 'VPN Connection Problems', 'IT', 'Other', 'low', 'low', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2025-04-11 23:19:33', '2025-04-14'),
(373, 'TCK-MW136', 796, 'Server Maintenance Needed', 'IT', 'Performance', 'low', 'low', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2025-07-10 23:19:33', '2025-07-18'),
(374, 'TCK-TJ137', 829, 'Network Speed Slow', 'IT', 'Hardware', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2025-03-13 23:19:33', '2025-03-21'),
(375, 'TCK-JW138', 485, 'System Optimization', 'IT', 'Network', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', '2025-06-28 23:19:33', '2025-07-04'),
(376, 'TCK-RY139', 865, 'Network Speed Slow', 'IT', 'Security', '', 'low', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2025-08-22 23:19:33', '2025-08-29'),
(377, 'TCK-JC140', 735, 'Network Connectivity Issues', 'IT', 'Network', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2025-07-11 23:19:33', '2025-07-13'),
(378, 'TCK-JW141', 485, 'Security Audit Required', 'IT', 'Hardware', 'high', 'high', NULL, NULL, 3, NULL, NULL, NULL, 'complete', '2025-08-25 23:19:33', '2025-08-29'),
(379, 'TCK-JJ142', 567, 'Hardware Replacement', 'IT', 'Security', '', 'low', NULL, NULL, 1, NULL, NULL, NULL, 'complete', '2024-11-08 23:19:33', '2024-11-13'),
(380, 'TCK-BM143', 886, 'Security Audit Required', 'IT', 'Software', '', 'low', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2024-11-28 23:19:33', '2024-12-03'),
(381, 'TCK-LM144', 787, 'User Permissions', 'IT', 'Database', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2025-01-05 23:19:33', '2025-01-12'),
(382, 'TCK-ET145', 874, 'Server Maintenance Needed', 'IT', 'Cloud', '', 'low', NULL, NULL, 2, NULL, NULL, NULL, 'pending', '2025-08-15 23:19:33', '2025-08-20'),
(383, 'TCK-JC146', 735, 'Network Connectivity Issues', 'IT', 'Database', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2025-04-25 23:19:33', '2025-04-29'),
(384, 'TCK-DR147', 624, 'Software Installation', 'IT', 'Security', 'high', 'high', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2025-07-29 23:19:33', '2025-08-01'),
(385, 'TCK-LW148', 634, 'Login Issues', 'IT', 'Network', 'high', 'high', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2025-09-02 23:19:33', '2025-09-09'),
(386, 'TCK-MW149', 796, 'VPN Connection Problems', 'IT', 'Network', 'high', 'high', NULL, NULL, 1, NULL, NULL, NULL, 'complete', '2025-05-23 23:19:33', '2025-05-28'),
(387, 'TCK-SS150', 569, 'Database Performance', 'IT', 'Security', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2025-06-02 23:19:33', '2025-06-05'),
(388, 'TCK-KY151', 859, 'Server Maintenance Needed', 'IT', 'Database', '', 'low', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2025-08-21 23:19:33', '2025-08-25'),
(389, 'TCK-MM152', 463, 'Security Audit Required', 'IT', 'Security', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', '2025-08-17 23:19:33', '2025-08-23'),
(390, 'TCK-BM153', 886, 'Software Installation', 'IT', 'Performance', 'high', 'high', NULL, NULL, 1, NULL, NULL, NULL, 'pending', '2025-03-27 23:19:33', '2025-04-02'),
(391, 'TCK-JW154', 485, 'Software Installation', 'IT', 'Software', '', 'low', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2025-04-06 23:19:33', '2025-04-13'),
(392, 'TCK-AH155', 805, 'Website Not Loading', 'IT', 'Security', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', '2025-04-20 23:19:33', '2025-04-22'),
(393, 'TCK-JA156', 433, 'Network Security', 'IT', 'Software', '', 'low', NULL, NULL, 2, NULL, NULL, NULL, 'complete', '2025-04-08 23:19:33', '2025-04-14'),
(394, 'TCK-AH157', 805, 'Website Not Loading', 'IT', 'Software', 'high', 'high', NULL, NULL, 3, NULL, NULL, NULL, 'complete', '2025-07-19 23:19:33', '2025-07-24'),
(395, 'TCK-JJ158', 567, 'Network Security', 'IT', 'Database', 'high', 'high', NULL, NULL, 2, NULL, NULL, NULL, 'complete', '2025-05-25 23:19:33', '2025-06-02'),
(396, 'TCK-DT159', 460, 'Remote Access Setup', 'IT', 'Security', 'high', 'high', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2025-03-29 23:19:33', '2025-04-04'),
(397, 'TCK-RY160', 865, 'Antivirus Update', 'IT', 'Other', 'high', 'high', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2025-03-10 23:19:33', '2025-03-14'),
(398, 'TCK-NT161', 452, 'Database Performance', 'IT', 'Network', 'high', 'high', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2025-03-29 23:19:33', '2025-04-04'),
(399, 'TCK-RG162', 620, 'Printer Not Working', 'IT', 'Network', '', 'low', NULL, NULL, 3, NULL, NULL, NULL, 'complete', '2025-06-11 23:19:33', '2025-06-17'),
(400, 'TCK-LM163', 787, 'Website Not Loading', 'IT', 'Database', 'high', 'high', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2025-04-16 23:19:33', '2025-04-22'),
(401, 'TCK-ST164', 799, 'VPN Connection Problems', 'IT', 'Network', 'low', 'low', NULL, NULL, 3, NULL, NULL, NULL, 'complete', '2025-03-30 23:19:33', '2025-04-02'),
(402, 'TCK-JC165', 735, 'System Freezing', 'IT', 'Performance', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2025-01-15 23:19:33', '2025-01-18'),
(403, 'TCK-SS166', 683, 'Mobile Sync Issues', 'IT', 'Network', 'high', 'high', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2025-01-24 23:19:33', '2025-01-27'),
(404, 'TCK-JM167', 826, 'Network Speed Slow', 'IT', 'Security', 'high', 'high', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2025-07-10 23:19:33', '2025-07-12'),
(405, 'TCK-MW168', 702, 'Backup Configuration', 'IT', 'Database', 'high', 'high', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2025-08-19 23:19:33', '2025-08-21'),
(406, 'TCK-LT169', 588, 'Password Reset', 'IT', 'Hardware', 'low', 'low', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2025-10-08 23:19:33', '2025-10-12'),
(407, 'TCK-S170', 354, 'Server Maintenance Needed', 'IT', 'Database', 'high', 'high', NULL, NULL, 1, NULL, NULL, NULL, 'pending', '2025-08-27 23:19:33', '2025-08-30'),
(408, 'TCK-KG171', 912, 'Remote Access Setup', 'IT', 'Cloud', '', 'low', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2024-12-02 23:19:33', '2024-12-04'),
(409, 'TCK-MM172', 463, 'Application Crashing', 'IT', 'Network', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'complete', '2024-12-04 23:19:33', '2024-12-06'),
(410, 'TCK-MW173', 702, 'Website Not Loading', 'IT', 'Software', 'high', 'high', NULL, NULL, 1, NULL, NULL, NULL, 'pending', '2025-09-04 23:19:33', '2025-09-11'),
(411, 'TCK-JM174', 826, 'Database Backup Failure', 'IT', 'Other', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', '2025-09-06 23:19:33', '2025-09-08'),
(412, 'TCK-LW175', 553, 'Printer Not Working', 'IT', 'Software', 'high', 'high', NULL, NULL, 2, NULL, NULL, NULL, 'pending', '2025-02-15 23:19:33', '2025-02-21'),
(413, 'TCK-NT176', 452, 'Antivirus Update', 'IT', 'Database', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'complete', '2025-04-20 23:19:33', '2025-04-23'),
(414, 'TCK-KY177', 859, 'Network Security', 'IT', 'Hardware', 'low', 'low', NULL, NULL, 1, NULL, NULL, NULL, 'complete', '2025-09-17 23:19:33', '2025-09-23'),
(415, 'TCK-KY178', 859, 'Security Audit Required', 'IT', 'Software', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', '2025-06-03 23:19:33', '2025-06-05'),
(416, 'TCK-DJ179', 896, 'Security Audit Required', 'IT', 'Hardware', 'high', 'high', NULL, NULL, 1, NULL, NULL, NULL, 'complete', '2025-06-23 23:19:33', '2025-07-01'),
(417, 'TCK-DT180', 460, 'System Freezing', 'IT', 'Performance', 'low', 'low', NULL, NULL, 1, NULL, NULL, NULL, 'complete', '2025-07-05 23:19:33', '2025-07-11'),
(418, 'TCK-JC181', 735, 'Antivirus Update', 'IT', 'Performance', 'low', 'low', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2025-05-16 23:19:33', '2025-05-21'),
(419, 'TCK-JM182', 788, 'System Updates', 'IT', 'Network', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2024-11-09 23:19:33', '2024-11-17'),
(420, 'TCK-JW183', 538, 'Data Import Error', 'IT', 'Cloud', 'high', 'high', NULL, NULL, 2, NULL, NULL, NULL, 'pending', '2025-09-01 23:19:33', '2025-09-07'),
(421, 'TCK-JJ184', 567, 'File Corruption', 'IT', 'Database', '', 'low', NULL, NULL, 5, NULL, NULL, NULL, 'complete', '2024-12-19 23:19:33', '2024-12-23'),
(422, 'TCK-SS185', 683, 'Remote Access Setup', 'IT', 'Database', 'low', 'low', NULL, NULL, 1, NULL, NULL, NULL, 'pending', '2025-03-20 23:19:33', '2025-03-22'),
(423, 'TCK-KY186', 859, 'User Permissions', 'IT', 'Cloud', '', 'low', NULL, NULL, 2, NULL, NULL, NULL, 'pending', '2025-04-09 23:19:33', '2025-04-17'),
(424, 'TCK-SS187', 569, 'Software Installation', 'IT', 'Software', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'complete', '2025-04-03 23:19:33', '2025-04-10'),
(425, 'TCK-MJ188', 472, 'File Corruption', 'IT', 'Network', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2025-02-17 23:19:33', '2025-02-25'),
(426, 'TCK-BM189', 886, 'Data Recovery', 'IT', 'Network', 'high', 'high', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2025-02-28 23:19:33', '2025-03-05'),
(427, 'TCK-CL190', 637, 'System Freezing', 'IT', 'Software', 'low', 'low', NULL, NULL, 2, NULL, NULL, NULL, 'pending', '2025-04-09 23:19:33', '2025-04-12'),
(428, 'TCK-DL191', 593, 'Password Reset', 'IT', 'Other', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2025-09-10 23:19:33', '2025-09-14'),
(429, 'TCK-TG192', 486, 'File Corruption', 'IT', 'Performance', 'low', 'low', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2025-07-27 23:19:33', '2025-08-03'),
(430, 'TCK-MJ193', 472, 'Software Installation', 'IT', 'Software', 'high', 'high', NULL, NULL, 1, NULL, NULL, NULL, 'complete', '2025-05-25 23:19:33', '2025-05-27'),
(431, 'TCK-JM194', 788, 'User Permissions', 'IT', 'Cloud', 'high', 'high', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2025-09-19 23:19:33', '2025-09-23'),
(432, 'TCK-DT195', 772, 'Data Recovery', 'IT', 'Software', 'high', 'high', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2025-07-13 23:19:33', '2025-07-21'),
(433, 'TCK-LW196', 553, 'Mobile Sync Issues', 'IT', 'Performance', 'high', 'high', NULL, NULL, 1, NULL, NULL, NULL, 'pending', '2025-04-20 23:19:33', '2025-04-23'),
(434, 'TCK-MW197', 796, 'Database Backup Failure', 'IT', 'Network', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'complete', '2025-06-26 23:19:33', '2025-06-28'),
(435, 'TCK-WJ198', 860, 'Email Configuration', 'IT', 'Cloud', 'high', 'high', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2025-02-05 23:19:33', '2025-02-13'),
(436, 'TCK-PJ199', 914, 'Password Reset', 'IT', 'Network', '', 'low', NULL, NULL, 1, NULL, NULL, NULL, 'complete', '2025-01-21 23:19:33', '2025-01-26'),
(437, 'TCK-DJ200', 896, 'File Server Access', 'IT', 'Database', 'high', 'high', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2025-03-14 23:19:33', '2025-03-21'),
(438, 'TCK-LW001', 1157, 'Firewall Configuration', 'IT', 'Other', 'high', 'high', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2025-03-08 22:19:21', '2025-03-16'),
(439, 'TCK-DJ002', 1170, 'Mobile Sync Issues', 'IT', 'Network', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'complete', '2025-09-19 22:19:21', '2025-09-26'),
(440, 'TCK-LW003', 1149, 'System Freezing', 'IT', 'Cloud', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', '2025-05-04 22:19:21', '2025-05-08'),
(441, 'TCK-RW004', 813, 'Remote Access Setup', 'IT', 'Software', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2025-07-14 22:19:21', '2025-07-17'),
(442, 'TCK-AW005', 885, 'Application Crashing', 'IT', 'Performance', 'low', 'low', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2025-09-18 22:19:21', '2025-09-21'),
(443, 'TCK-MM006', 1080, 'Security Audit Required', 'IT', 'Hardware', 'low', 'low', NULL, NULL, 3, NULL, NULL, NULL, 'complete', '2025-10-09 22:19:21', '2025-10-14'),
(444, 'TCK-JM007', 1070, 'File Server Access', 'IT', 'Other', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', '2025-08-04 22:19:21', '2025-08-09'),
(445, 'TCK-DJ008', 1170, 'System Updates', 'IT', 'Software', '', 'low', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2025-06-05 22:19:21', '2025-06-11'),
(446, 'TCK-DM009', 903, 'File Corruption', 'IT', 'Other', '', 'low', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2025-08-30 22:19:21', '2025-09-06'),
(447, 'TCK-BP010', 631, 'Data Import Error', 'IT', 'Network', 'high', 'high', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2025-09-03 22:19:21', '2025-09-07'),
(448, 'TCK-LY011', 939, 'Database Backup Failure', 'IT', 'Network', 'low', 'low', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2025-07-02 22:19:21', '2025-07-07'),
(449, 'TCK-MJ012', 857, 'Database Performance', 'IT', 'Cloud', '', 'low', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2025-04-10 22:19:21', '2025-04-17'),
(450, 'TCK-KR013', 976, 'Printer Not Working', 'IT', 'Software', '', 'low', NULL, NULL, 5, NULL, NULL, NULL, 'complete', '2025-09-21 22:19:21', '2025-09-27'),
(451, 'TCK-MB014', 1034, 'Network Security', 'IT', 'Security', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2025-06-27 22:19:21', '2025-06-29'),
(452, 'TCK-MT015', 908, 'Antivirus Update', 'IT', 'Performance', 'low', 'low', NULL, NULL, 5, NULL, NULL, NULL, 'complete', '2025-05-13 22:19:21', '2025-05-21'),
(453, 'TCK-SJ016', 430, 'Firewall Configuration', 'IT', 'Other', 'low', 'low', NULL, NULL, 1, NULL, NULL, NULL, 'pending', '2024-11-28 22:19:21', '2024-12-03'),
(454, 'TCK-DH017', 595, 'Data Import Error', 'IT', 'Security', 'low', 'low', NULL, NULL, 1, NULL, NULL, NULL, 'complete', '2024-11-12 22:19:21', '2024-11-17'),
(455, 'TCK-MA018', 915, 'Application Crashing', 'IT', 'Cloud', 'high', 'high', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2025-09-01 22:19:21', '2025-09-08'),
(456, 'TCK-RS019', 790, 'Login Issues', 'IT', 'Other', 'low', 'low', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2025-06-17 22:19:21', '2025-06-25'),
(457, 'TCK-MH020', 1148, 'System Downtime', 'IT', 'Database', 'high', 'high', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2025-08-14 22:19:21', '2025-08-16'),
(458, 'TCK-AW021', 885, 'System Downtime', 'IT', 'Security', 'high', 'high', NULL, NULL, 1, NULL, NULL, NULL, 'pending', '2025-10-11 22:19:21', '2025-10-15'),
(459, 'TCK-BP022', 631, 'Website Not Loading', 'IT', 'Performance', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2024-11-10 22:19:21', '2024-11-12'),
(460, 'TCK-RP023', 1098, 'Mobile Sync Issues', 'IT', 'Cloud', 'low', 'low', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2025-05-20 22:19:21', '2025-05-26'),
(461, 'TCK-MJ024', 857, 'Remote Access Setup', 'IT', 'Security', 'low', 'low', NULL, NULL, 3, NULL, NULL, NULL, 'complete', '2025-09-21 22:19:21', '2025-09-28'),
(462, 'TCK-CR025', 1002, 'IT Support Request', 'IT', 'Other', 'high', 'high', NULL, NULL, 5, NULL, NULL, NULL, 'complete', '2024-11-16 22:19:21', '2024-11-19'),
(463, 'TCK-DJ026', 1170, 'Software Installation', 'IT', 'Other', 'high', 'high', NULL, NULL, 2, NULL, NULL, NULL, 'pending', '2025-03-08 22:19:21', '2025-03-13'),
(464, 'TCK-SB027', 454, 'User Permissions', 'IT', 'Performance', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'complete', '2025-05-28 22:19:21', '2025-06-03'),
(465, 'TCK-PT028', 808, 'Password Reset', 'IT', 'Network', 'high', 'high', NULL, NULL, 3, NULL, NULL, NULL, 'complete', '2025-04-21 22:19:21', '2025-04-25'),
(466, 'TCK-JM029', 1070, 'Server Maintenance Needed', 'IT', 'Software', 'low', 'low', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2025-09-09 22:19:21', '2025-09-16'),
(467, 'TCK-MT030', 908, 'Firewall Configuration', 'IT', 'Security', 'high', 'high', NULL, NULL, 2, NULL, NULL, NULL, 'pending', '2025-02-25 22:19:21', '2025-03-01'),
(468, 'TCK-PR031', 1131, 'System Optimization', 'IT', 'Database', 'low', 'low', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2024-12-26 22:19:21', '2024-12-28'),
(469, 'TCK-RJ032', 1171, 'Security Audit Required', 'IT', 'Hardware', 'high', 'high', NULL, NULL, 2, NULL, NULL, NULL, 'pending', '2025-07-14 22:19:21', '2025-07-16'),
(470, 'TCK-MH033', 849, 'Printer Not Working', 'IT', 'Network', 'high', 'high', NULL, NULL, 4, NULL, NULL, NULL, 'complete', '2025-03-10 22:19:21', '2025-03-17'),
(471, 'TCK-MJ034', 857, 'Data Import Error', 'IT', 'Database', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'complete', '2025-08-12 22:19:21', '2025-08-15'),
(472, 'TCK-MM035', 1080, 'Antivirus Update', 'IT', 'Security', 'high', 'high', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2025-09-28 22:19:21', '2025-10-01'),
(473, 'TCK-AW036', 885, 'Network Connectivity Issues', 'IT', 'Network', 'low', 'low', NULL, NULL, 1, NULL, NULL, NULL, 'pending', '2025-09-03 22:19:21', '2025-09-11'),
(474, 'TCK-PR037', 1131, 'Firewall Configuration', 'IT', 'Database', 'high', 'high', NULL, NULL, 2, NULL, NULL, NULL, 'pending', '2025-01-24 22:19:21', '2025-02-01'),
(475, 'TCK-GL038', 921, 'Printer Not Working', 'IT', 'Hardware', 'low', 'low', NULL, NULL, 1, NULL, NULL, NULL, 'pending', '2024-11-22 22:19:21', '2024-11-30'),
(476, 'TCK-MA039', 915, 'File Server Access', 'IT', 'Performance', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'complete', '2025-09-03 22:19:21', '2025-09-09'),
(477, 'TCK-CW040', 584, 'Email Configuration', 'IT', 'Hardware', '', 'low', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2024-10-22 22:19:21', '2024-10-27'),
(478, 'TCK-MA041', 915, 'Email Configuration', 'IT', 'Network', 'high', 'high', NULL, NULL, 3, NULL, NULL, NULL, 'complete', '2024-10-31 22:19:21', '2024-11-04'),
(479, 'TCK-CT042', 738, 'Database Backup Failure', 'IT', 'Network', '', 'low', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2025-07-12 22:19:21', '2025-07-17'),
(480, 'TCK-KH043', 913, 'Application Crashing', 'IT', 'Other', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'complete', '2025-08-02 22:19:21', '2025-08-07'),
(481, 'TCK-RW044', 813, 'Network Security', 'IT', 'Network', 'low', 'low', NULL, NULL, 1, NULL, NULL, NULL, 'pending', '2025-09-03 22:19:21', '2025-09-05'),
(482, 'TCK-RJ045', 1171, 'Security Audit Required', 'IT', 'Performance', 'low', 'low', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2025-03-06 22:19:21', '2025-03-13'),
(483, 'TCK-MH046', 1148, 'Mobile Sync Issues', 'IT', 'Cloud', 'high', 'high', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2025-03-03 22:19:21', '2025-03-11'),
(484, 'TCK-KH047', 913, 'Mobile Sync Issues', 'IT', 'Network', '', 'low', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2025-08-12 22:19:21', '2025-08-14'),
(485, 'TCK-MM048', 1080, 'Software Installation', 'IT', 'Network', 'high', 'high', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2024-11-25 22:19:21', '2024-11-30'),
(486, 'TCK-AW049', 885, 'System Downtime', 'IT', 'Network', '', 'low', NULL, NULL, 4, NULL, NULL, NULL, 'complete', '2025-08-07 22:19:21', '2025-08-09'),
(487, 'TCK-RJ050', 1171, 'System Freezing', 'IT', 'Database', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2024-11-16 22:19:21', '2024-11-24'),
(488, 'TCK-RP051', 1098, 'Data Recovery', 'IT', 'Security', 'low', 'low', NULL, NULL, 1, NULL, NULL, NULL, 'complete', '2025-06-30 22:19:21', '2025-07-06'),
(489, 'TCK-LW052', 1157, 'Data Recovery', 'IT', 'Security', 'low', 'low', NULL, NULL, 3, NULL, NULL, NULL, 'complete', '2025-09-09 22:19:21', '2025-09-11'),
(490, 'TCK-SJ053', 430, 'File Corruption', 'IT', 'Other', 'low', 'low', NULL, NULL, 3, NULL, NULL, NULL, 'complete', '2024-11-29 22:19:21', '2024-12-05'),
(491, 'TCK-MH054', 1051, 'Mobile Sync Issues', 'IT', 'Cloud', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2025-06-23 22:19:21', '2025-06-27'),
(492, 'TCK-JT055', 940, 'Cloud Migration Issues', 'IT', 'Other', 'high', 'high', NULL, NULL, 2, NULL, NULL, NULL, 'pending', '2025-06-28 22:19:21', '2025-07-04'),
(493, 'TCK-CW056', 973, 'Website Not Loading', 'IT', 'Other', 'low', 'low', NULL, NULL, 2, NULL, NULL, NULL, 'pending', '2025-03-12 22:19:21', '2025-03-18'),
(494, 'TCK-KH057', 913, 'Database Performance', 'IT', 'Performance', 'high', 'high', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2024-10-31 22:19:21', '2024-11-02'),
(495, 'TCK-BP058', 631, 'Remote Access Setup', 'IT', 'Hardware', 'low', 'low', NULL, NULL, 2, NULL, NULL, NULL, 'pending', '2025-06-04 22:19:21', '2025-06-07'),
(496, 'TCK-PR059', 1131, 'Data Import Error', 'IT', 'Security', '', 'low', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2025-03-20 22:19:21', '2025-03-24'),
(497, 'TCK-GL060', 921, 'Backup Configuration', 'IT', 'Other', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', '2025-09-16 22:19:21', '2025-09-24'),
(498, 'TCK-RW061', 813, 'System Freezing', 'IT', 'Network', 'low', 'low', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2025-04-26 22:19:21', '2025-05-02'),
(499, 'TCK-CR062', 1002, 'Database Performance', 'IT', 'Performance', '', 'low', NULL, NULL, 1, NULL, NULL, NULL, 'pending', '2025-01-31 22:19:21', '2025-02-07'),
(500, 'TCK-JM063', 1070, 'Mobile Sync Issues', 'IT', 'Database', 'high', 'high', NULL, NULL, 2, NULL, NULL, NULL, 'pending', '2024-11-18 22:19:21', '2024-11-21'),
(501, 'TCK-KJ064', 782, 'Data Import Error', 'IT', 'Software', '', 'low', NULL, NULL, 1, NULL, NULL, NULL, 'pending', '2024-11-13 22:19:21', '2024-11-15'),
(502, 'TCK-MG065', 435, 'Data Recovery', 'IT', 'Performance', 'high', 'high', NULL, NULL, 1, NULL, NULL, NULL, 'pending', '2024-12-12 22:19:21', '2024-12-15'),
(503, 'TCK-LW066', 479, 'Database Backup Failure', 'IT', 'Hardware', '', 'low', NULL, NULL, 2, NULL, NULL, NULL, 'complete', '2024-11-14 22:19:21', '2024-11-19'),
(504, 'TCK-RS067', 450, 'Email Configuration', 'IT', 'Hardware', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', '2025-06-08 22:19:21', '2025-06-11'),
(505, 'TCK-SB068', 454, 'VPN Connection Problems', 'IT', 'Network', 'high', 'high', NULL, NULL, 4, NULL, NULL, NULL, 'complete', '2025-09-08 22:19:21', '2025-09-16'),
(506, 'TCK-MH069', 1169, 'System Downtime', 'IT', 'Hardware', 'low', 'low', NULL, NULL, 2, NULL, NULL, NULL, 'pending', '2025-08-12 22:19:21', '2025-08-19'),
(507, 'TCK-AW070', 779, 'Data Recovery', 'IT', 'Hardware', 'high', 'high', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2025-04-02 22:19:21', '2025-04-04'),
(508, 'TCK-RS071', 790, 'Remote Access Setup', 'IT', 'Other', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'complete', '2024-11-24 22:19:21', '2024-12-02'),
(509, 'TCK-CT072', 738, 'VPN Connection Problems', 'IT', 'Other', 'high', 'high', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2025-03-16 22:19:21', '2025-03-22'),
(510, 'TCK-CT073', 738, 'System Freezing', 'IT', 'Other', 'low', 'low', NULL, NULL, 2, NULL, NULL, NULL, 'pending', '2024-10-28 22:19:21', '2024-11-02'),
(511, 'TCK-BP074', 631, 'Server Maintenance Needed', 'IT', 'Database', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', '2025-08-22 22:19:21', '2025-08-30'),
(512, 'TCK-LW075', 1157, 'Backup Configuration', 'IT', 'Performance', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'complete', '2025-01-20 22:19:21', '2025-01-23'),
(513, 'TCK-LW076', 1157, 'Remote Access Setup', 'IT', 'Network', 'high', 'high', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2025-03-14 22:19:21', '2025-03-22'),
(514, 'TCK-MT077', 908, 'Antivirus Update', 'IT', 'Network', 'high', 'high', NULL, NULL, 2, NULL, NULL, NULL, 'complete', '2024-11-27 22:19:21', '2024-12-03'),
(515, 'TCK-CW078', 584, 'System Downtime', 'IT', 'Hardware', '', 'low', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2024-12-02 22:19:21', '2024-12-07'),
(516, 'TCK-MH079', 849, 'File Corruption', 'IT', 'Software', '', 'low', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2025-07-06 22:19:21', '2025-07-08'),
(517, 'TCK-HW080', 897, 'Antivirus Update', 'IT', 'Software', 'high', 'high', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2024-11-11 22:19:21', '2024-11-15'),
(518, 'TCK-RP081', 1098, 'Network Speed Slow', 'IT', 'Performance', 'high', 'high', NULL, NULL, 2, NULL, NULL, NULL, 'pending', '2025-09-16 22:19:21', '2025-09-23'),
(519, 'TCK-MH082', 849, 'Data Recovery', 'IT', 'Cloud', 'high', 'high', NULL, NULL, 2, NULL, NULL, NULL, 'pending', '2025-04-08 22:19:21', '2025-04-16'),
(520, 'TCK-GL083', 921, 'Network Connectivity Issues', 'IT', 'Other', 'high', 'high', NULL, NULL, 3, NULL, NULL, NULL, 'complete', '2024-11-28 22:19:21', '2024-12-03'),
(521, 'TCK-HW084', 897, 'Server Maintenance Needed', 'IT', 'Software', 'high', 'high', NULL, NULL, 4, NULL, NULL, NULL, 'complete', '2024-12-19 22:19:21', '2024-12-27'),
(522, 'TCK-BP085', 631, 'Password Reset', 'IT', 'Other', 'low', 'low', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2025-06-23 22:19:21', '2025-06-26'),
(523, 'TCK-LS086', 720, 'Backup Configuration', 'IT', 'Network', 'high', 'high', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2025-01-06 22:19:21', '2025-01-08'),
(525, 'TCK-RS088', 790, 'User Permissions', 'IT', 'Cloud', 'low', 'low', NULL, NULL, 2, NULL, NULL, NULL, 'pending', '2024-11-04 22:19:21', '2024-11-09'),
(526, 'TCK-MA089', 915, 'System Downtime', 'IT', 'Other', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2025-04-14 22:19:21', '2025-04-18'),
(527, 'TCK-GL090', 921, 'File Server Access', 'IT', 'Performance', '', 'low', NULL, NULL, 5, NULL, NULL, NULL, 'complete', '2025-08-21 22:19:21', '2025-08-25'),
(528, 'TCK-LH091', 1097, 'System Optimization', 'IT', 'Software', 'high', 'high', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2025-04-19 22:19:21', '2025-04-23'),
(529, 'TCK-MH092', 1148, 'Printer Not Working', 'IT', 'Cloud', 'high', 'high', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2024-11-06 22:19:21', '2024-11-09'),
(530, 'TCK-MJ093', 857, 'Password Reset', 'IT', 'Performance', 'high', 'high', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2025-05-05 22:19:21', '2025-05-09'),
(531, 'TCK-DH094', 595, 'Email Configuration', 'IT', 'Database', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2024-11-12 22:19:21', '2024-11-20'),
(532, 'TCK-LW095', 479, 'Data Import Error', 'IT', 'Database', 'high', 'high', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2025-05-22 22:19:21', '2025-05-27'),
(533, 'TCK-CT096', 738, 'Printer Not Working', 'IT', 'Hardware', 'high', 'high', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2025-08-19 22:19:21', '2025-08-21'),
(534, 'TCK-LS097', 720, 'Printer Not Working', 'IT', 'Cloud', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2025-09-17 22:19:21', '2025-09-24'),
(535, 'TCK-CT098', 738, 'User Permissions', 'IT', 'Software', 'high', 'high', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2025-02-05 22:19:21', '2025-02-09'),
(536, 'TCK-MB099', 1034, 'System Downtime', 'IT', 'Other', '', 'low', NULL, NULL, 2, NULL, NULL, NULL, 'pending', '2025-04-26 22:19:21', '2025-04-30'),
(537, 'TCK-KJ100', 782, 'Cloud Migration Issues', 'IT', 'Performance', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', '2025-07-29 22:19:21', '2025-08-03'),
(538, 'TCK-JT101', 940, 'Website Not Loading', 'IT', 'Security', 'low', 'low', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2025-02-26 22:19:21', '2025-03-06'),
(539, 'TCK-MH102', 1169, 'User Permissions', 'IT', 'Cloud', 'low', 'low', NULL, NULL, 1, NULL, NULL, NULL, 'pending', '2025-06-01 22:19:21', '2025-06-08'),
(540, 'TCK-HW103', 897, 'Email Configuration', 'IT', 'Other', '', 'low', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2024-11-16 22:19:21', '2024-11-19'),
(541, 'TCK-LW104', 1157, 'Application Crashing', 'IT', 'Database', 'low', 'low', NULL, NULL, 4, NULL, NULL, NULL, 'complete', '2024-12-28 22:19:21', '2025-01-03'),
(542, 'TCK-CR105', 1002, 'VPN Connection Problems', 'IT', 'Cloud', 'high', 'high', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2025-01-19 22:19:21', '2025-01-25'),
(543, 'TCK-DA106', 1164, 'System Updates', 'IT', 'Network', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'complete', '2024-11-09 22:19:21', '2024-11-17'),
(544, 'TCK-MH107', 849, 'IT Support Request', 'IT', 'Performance', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2025-07-07 22:19:21', '2025-07-15'),
(545, 'TCK-MH108', 1169, 'Network Connectivity Issues', 'IT', 'Network', 'high', 'high', NULL, NULL, 2, NULL, NULL, NULL, 'complete', '2025-02-15 22:19:21', '2025-02-18'),
(546, 'TCK-DJ109', 1049, 'Network Connectivity Issues', 'IT', 'Software', 'high', 'high', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2025-09-30 22:19:21', '2025-10-08'),
(547, 'TCK-MH110', 1051, 'Password Reset', 'IT', 'Hardware', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', '2025-06-20 22:19:21', '2025-06-23'),
(548, 'TCK-LS111', 720, 'Mobile Sync Issues', 'IT', 'Cloud', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2025-08-09 22:19:21', '2025-08-11'),
(549, 'TCK-AW112', 779, 'Server Maintenance Needed', 'IT', 'Other', 'high', 'high', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2025-02-16 22:19:21', '2025-02-22'),
(550, 'TCK-JM113', 1070, 'File Server Access', 'IT', 'Database', 'low', 'low', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2024-12-10 22:19:21', '2024-12-15'),
(551, 'TCK-RS114', 450, 'Login Issues', 'IT', 'Other', 'high', 'high', NULL, NULL, 2, NULL, NULL, NULL, 'complete', '2025-04-09 22:19:21', '2025-04-15'),
(552, 'TCK-BR115', 724, 'Application Crashing', 'IT', 'Other', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2024-10-31 22:19:21', '2024-11-02'),
(553, 'TCK-LW116', 1149, 'Mobile Sync Issues', 'IT', 'Cloud', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'complete', '2024-11-12 22:19:21', '2024-11-19'),
(554, 'TCK-HW117', 897, 'Hardware Replacement', 'IT', 'Network', 'low', 'low', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2025-08-30 22:19:21', '2025-09-04'),
(555, 'TCK-KH118', 913, 'System Optimization', 'IT', 'Software', 'low', 'low', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2025-08-30 22:19:21', '2025-09-07'),
(556, 'TCK-RJ119', 851, 'Hardware Replacement', 'IT', 'Hardware', 'high', 'high', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2024-11-03 22:19:21', '2024-11-05'),
(557, 'TCK-MH120', 1051, 'Mobile Sync Issues', 'IT', 'Cloud', 'high', 'high', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2025-02-23 22:19:21', '2025-02-26'),
(558, 'TCK-MH121', 1169, 'Printer Not Working', 'IT', 'Hardware', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2025-02-26 22:19:21', '2025-02-28'),
(559, 'TCK-DJ122', 1170, 'Password Reset', 'IT', 'Database', 'high', 'high', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2024-11-02 22:19:21', '2024-11-08'),
(560, 'TCK-HW123', 897, 'Network Security', 'IT', 'Cloud', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2025-04-27 22:19:21', '2025-04-29'),
(561, 'TCK-ML124', 441, 'Application Crashing', 'IT', 'Hardware', '', 'low', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2025-09-08 22:19:21', '2025-09-15'),
(562, 'TCK-ML125', 441, 'IT Support Request', 'IT', 'Cloud', 'high', 'high', NULL, NULL, 2, NULL, NULL, NULL, 'pending', '2025-01-16 22:19:21', '2025-01-24'),
(563, 'TCK-BR126', 724, 'Hardware Replacement', 'IT', 'Cloud', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2025-08-11 22:19:21', '2025-08-19'),
(564, 'TCK-DJ127', 1170, 'Network Speed Slow', 'IT', 'Security', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2025-05-30 22:19:21', '2025-06-06'),
(565, 'TCK-DJ128', 1049, 'Network Security', 'IT', 'Software', '', 'low', NULL, NULL, 5, NULL, NULL, NULL, 'complete', '2025-10-02 22:19:21', '2025-10-10'),
(566, 'TCK-MH129', 1169, 'User Permissions', 'IT', 'Cloud', '', 'low', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2024-11-24 22:19:21', '2024-11-27'),
(567, 'TCK-RW130', 813, 'User Permissions', 'IT', 'Cloud', '', 'low', NULL, NULL, 4, NULL, NULL, NULL, 'complete', '2025-07-24 22:19:21', '2025-07-28'),
(568, 'TCK-MG131', 435, 'Backup Configuration', 'IT', 'Database', 'high', 'high', NULL, NULL, 2, NULL, NULL, NULL, 'complete', '2025-05-09 22:19:21', '2025-05-13'),
(569, 'TCK-PT132', 808, 'Password Reset', 'IT', 'Network', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'complete', '2025-01-15 22:19:21', '2025-01-17'),
(570, 'TCK-RW133', 813, 'Data Import Error', 'IT', 'Other', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2025-09-20 22:19:21', '2025-09-26'),
(571, 'TCK-MH134', 1148, 'Login Issues', 'IT', 'Other', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2025-03-22 22:19:21', '2025-03-29'),
(572, 'TCK-MH135', 849, 'Software Installation', 'IT', 'Network', 'high', 'high', NULL, NULL, 1, NULL, NULL, NULL, 'pending', '2024-12-02 22:19:21', '2024-12-07'),
(573, 'TCK-MJ136', 857, 'Cloud Migration Issues', 'IT', 'Performance', '', 'low', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2024-10-28 22:19:21', '2024-11-02'),
(574, 'TCK-MH137', 1051, 'User Permissions', 'IT', 'Other', 'low', 'low', NULL, NULL, 2, NULL, NULL, NULL, 'complete', '2025-08-20 22:19:21', '2025-08-25'),
(575, 'TCK-DJ138', 1049, 'User Permissions', 'IT', 'Software', 'low', 'low', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2025-06-05 22:19:21', '2025-06-12'),
(576, 'TCK-BP139', 631, 'Software Installation', 'IT', 'Security', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'complete', '2025-07-18 22:19:21', '2025-07-23'),
(577, 'TCK-DJ140', 1170, 'Network Connectivity Issues', 'IT', 'Security', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2024-11-24 22:19:21', '2024-11-30'),
(578, 'TCK-JT141', 940, 'Server Maintenance Needed', 'IT', 'Software', 'high', 'high', NULL, NULL, 3, NULL, NULL, NULL, 'complete', '2025-03-07 22:19:21', '2025-03-10'),
(579, 'TCK-KR142', 976, 'Password Reset', 'IT', 'Software', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2024-11-19 22:19:21', '2024-11-23'),
(580, 'TCK-BP143', 631, 'System Optimization', 'IT', 'Security', 'high', 'high', NULL, NULL, 1, NULL, NULL, NULL, 'pending', '2025-09-21 22:19:21', '2025-09-24'),
(581, 'TCK-PT144', 808, 'Network Speed Slow', 'IT', 'Software', 'low', 'low', NULL, NULL, 1, NULL, NULL, NULL, 'pending', '2024-11-11 22:19:21', '2024-11-15'),
(582, 'TCK-PR145', 1131, 'Website Not Loading', 'IT', 'Database', 'high', 'high', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2025-09-16 22:19:21', '2025-09-23'),
(583, 'TCK-WJ146', 860, 'Cloud Migration Issues', 'IT', 'Network', '', 'low', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2025-05-23 22:19:21', '2025-05-25'),
(584, 'TCK-MJ147', 857, 'Backup Configuration', 'IT', 'Security', 'high', 'high', NULL, NULL, 1, NULL, NULL, NULL, 'complete', '2024-12-16 22:19:21', '2024-12-23'),
(585, 'TCK-MH148', 1051, 'User Permissions', 'IT', 'Database', 'high', 'high', NULL, NULL, 2, NULL, NULL, NULL, 'complete', '2025-07-23 22:19:21', '2025-07-25'),
(586, 'TCK-PT149', 808, 'Software Installation', 'IT', 'Software', 'low', 'low', NULL, NULL, 1, NULL, NULL, NULL, 'complete', '2025-08-15 22:19:21', '2025-08-19'),
(587, 'TCK-DM150', 903, 'Hardware Replacement', 'IT', 'Hardware', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', '2025-08-06 22:19:21', '2025-08-14'),
(589, 'TCK-LW152', 1157, 'Data Import Error', 'IT', 'Performance', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', '2025-05-12 22:19:21', '2025-05-18'),
(590, 'TCK-LS153', 720, 'File Server Access', 'IT', 'Performance', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'complete', '2025-02-23 22:19:21', '2025-02-26'),
(591, 'TCK-PT154', 808, 'File Server Access', 'IT', 'Cloud', 'high', 'high', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2024-11-25 22:19:21', '2024-11-30'),
(592, 'TCK-KR155', 976, 'Software Installation', 'IT', 'Hardware', 'low', 'low', NULL, NULL, 1, NULL, NULL, NULL, 'pending', '2025-09-27 22:19:21', '2025-09-30'),
(593, 'TCK-BP156', 631, 'Application Crashing', 'IT', 'Performance', '', 'low', NULL, NULL, 2, NULL, NULL, NULL, 'pending', '2025-02-20 22:19:21', '2025-02-23'),
(594, 'TCK-LH157', 1097, 'File Server Access', 'IT', 'Other', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', '2025-08-11 22:19:21', '2025-08-16'),
(595, 'TCK-AA158', 1085, 'System Downtime', 'IT', 'Security', 'low', 'low', NULL, NULL, 1, NULL, NULL, NULL, 'pending', '2025-07-04 22:19:21', '2025-07-11'),
(596, 'TCK-MA159', 915, 'Network Speed Slow', 'IT', 'Database', 'low', 'low', NULL, NULL, 1, NULL, NULL, NULL, 'pending', '2025-07-26 22:19:21', '2025-07-31'),
(597, 'TCK-CR160', 1002, 'Mobile Sync Issues', 'IT', 'Database', 'high', 'high', NULL, NULL, 4, NULL, NULL, NULL, 'complete', '2024-12-28 22:19:21', '2025-01-01'),
(598, 'TCK-MH161', 1169, 'Remote Access Setup', 'IT', 'Network', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'complete', '2025-05-24 22:19:21', '2025-05-28'),
(599, 'TCK-ML162', 441, 'Software Installation', 'IT', 'Database', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', '2025-03-21 22:19:21', '2025-03-29'),
(600, 'TCK-AW163', 779, 'Software Installation', 'IT', 'Network', '', 'low', NULL, NULL, 1, NULL, NULL, NULL, 'complete', '2025-04-10 22:19:21', '2025-04-14'),
(601, 'TCK-LW164', 479, 'Firewall Configuration', 'IT', 'Software', 'high', 'high', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2025-06-14 22:19:21', '2025-06-16'),
(602, 'TCK-LW165', 1149, 'Software Installation', 'IT', 'Other', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2024-12-17 22:19:21', '2024-12-20'),
(603, 'TCK-MT166', 908, 'Database Backup Failure', 'IT', 'Cloud', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', '2025-09-11 22:19:21', '2025-09-16'),
(604, 'TCK-MJ167', 857, 'Software Installation', 'IT', 'Cloud', '', 'low', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2025-08-05 22:19:21', '2025-08-10'),
(605, 'TCK-PT168', 808, 'Application Crashing', 'IT', 'Hardware', 'high', 'high', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2024-12-12 22:19:21', '2024-12-18'),
(606, 'TCK-JT169', 940, 'Antivirus Update', 'IT', 'Database', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2025-01-08 22:19:21', '2025-01-16'),
(607, 'TCK-AW170', 885, 'System Freezing', 'IT', 'Other', 'low', 'low', NULL, NULL, 2, NULL, NULL, NULL, 'pending', '2025-03-14 22:19:21', '2025-03-20'),
(608, 'TCK-PS171', 652, 'Network Connectivity Issues', 'IT', 'Other', 'high', 'high', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2025-08-05 22:19:21', '2025-08-13'),
(609, 'TCK-CT172', 738, 'Security Audit Required', 'IT', 'Other', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'complete', '2025-01-30 22:19:21', '2025-02-04'),
(610, 'TCK-PS173', 652, 'Application Crashing', 'IT', 'Software', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2025-02-14 22:19:21', '2025-02-17'),
(611, 'TCK-JT174', 940, 'Data Import Error', 'IT', 'Software', 'high', 'high', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2025-07-19 22:19:21', '2025-07-26'),
(612, 'TCK-PR175', 1131, 'Network Security', 'IT', 'Database', '', 'low', NULL, NULL, 2, NULL, NULL, NULL, 'pending', '2025-08-27 22:19:21', '2025-08-31'),
(613, 'TCK-JM176', 1070, 'Software Installation', 'IT', 'Network', 'high', 'high', NULL, NULL, 2, NULL, NULL, NULL, 'pending', '2025-10-10 22:19:21', '2025-10-14'),
(614, 'TCK-KH177', 913, 'System Optimization', 'IT', 'Performance', 'high', 'high', NULL, NULL, 1, NULL, NULL, NULL, 'complete', '2025-04-03 22:19:21', '2025-04-11'),
(615, 'TCK-MJ178', 857, 'Application Crashing', 'IT', 'Network', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2025-10-01 22:19:21', '2025-10-09'),
(616, 'TCK-RW179', 813, 'Application Crashing', 'IT', 'Cloud', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2025-03-29 22:19:21', '2025-04-04'),
(617, 'TCK-MH180', 1148, 'File Corruption', 'IT', 'Cloud', '', 'low', NULL, NULL, 1, NULL, NULL, NULL, 'pending', '2025-07-12 22:19:21', '2025-07-20'),
(618, 'TCK-SJ181', 430, 'Backup Configuration', 'IT', 'Hardware', 'high', 'high', NULL, NULL, 1, NULL, NULL, NULL, 'pending', '2025-06-12 22:19:21', '2025-06-20'),
(619, 'TCK-AA182', 1085, 'Login Issues', 'IT', 'Other', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2025-02-11 22:19:21', '2025-02-19'),
(620, 'TCK-PR183', 1131, 'Cloud Migration Issues', 'IT', 'Performance', 'low', 'low', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2025-08-25 22:19:21', '2025-08-28'),
(621, 'TCK-CW184', 584, 'Firewall Configuration', 'IT', 'Other', 'low', 'low', NULL, NULL, 3, NULL, NULL, NULL, 'complete', '2025-08-22 22:19:21', '2025-08-26'),
(622, 'TCK-SB185', 454, 'File Server Access', 'IT', 'Cloud', 'high', 'high', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2025-08-15 22:19:21', '2025-08-21'),
(623, 'TCK-CW186', 584, 'Application Crashing', 'IT', 'Performance', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2025-05-03 22:19:21', '2025-05-11'),
(624, 'TCK-DM187', 903, 'System Updates', 'IT', 'Database', 'high', 'high', NULL, NULL, 3, NULL, NULL, NULL, 'pending', '2025-09-13 22:19:21', '2025-09-20'),
(625, 'TCK-RW188', 813, 'Email Configuration', 'IT', 'Security', 'high', 'high', NULL, NULL, 4, NULL, NULL, NULL, 'complete', '2025-01-03 22:19:21', '2025-01-06'),
(626, 'TCK-RS189', 450, 'VPN Connection Problems', 'IT', 'Other', 'low', 'low', NULL, NULL, 2, NULL, NULL, NULL, 'pending', '2025-08-22 22:19:21', '2025-08-28'),
(627, 'TCK-RS190', 790, 'Remote Access Setup', 'IT', 'Network', 'high', 'high', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2025-06-22 22:19:21', '2025-06-27'),
(628, 'TCK-BP191', 631, 'File Server Access', 'IT', 'Performance', 'high', 'high', NULL, NULL, 4, NULL, NULL, NULL, 'pending', '2025-10-07 22:19:21', '2025-10-09'),
(629, 'TCK-LH192', 1097, 'Network Security', 'IT', 'Cloud', 'low', 'low', NULL, NULL, 2, NULL, NULL, NULL, 'pending', '2025-08-14 22:19:21', '2025-08-16'),
(630, 'TCK-MG193', 435, 'Data Recovery', 'IT', 'Performance', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2024-12-08 22:19:21', '2024-12-10'),
(631, 'TCK-BP194', 631, 'System Freezing', 'IT', 'Software', 'low', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', '2025-03-02 22:19:21', '2025-03-10'),
(632, 'TCK-SJ195', 430, 'VPN Connection Problems', 'IT', 'Database', '', 'low', NULL, NULL, NULL, NULL, NULL, NULL, 'unassigned', '2025-04-29 22:19:21', '2025-05-03'),
(633, 'TCK-LS196', 720, 'Hardware Replacement', 'IT', 'Database', 'low', 'low', NULL, NULL, 5, NULL, NULL, NULL, 'pending', '2025-07-28 22:19:21', '2025-08-01'),
(634, 'TCK-RW197', 813, 'Security Audit Required', 'IT', 'Other', 'low', 'low', NULL, NULL, 2, NULL, NULL, NULL, 'pending', '2024-12-17 22:19:21', '2024-12-25'),
(635, 'TCK-CW198', 584, 'Security Audit Required', 'IT', 'Performance', 'low', 'low', NULL, NULL, 3, NULL, NULL, NULL, 'complete', '2025-03-06 22:19:21', '2025-03-13'),
(636, 'TCK-RJ199', 851, 'System Downtime', 'IT', 'Database', '', 'low', NULL, NULL, 5, NULL, NULL, NULL, 'complete', '2025-09-12 22:19:21', '2025-09-17'),
(637, 'TCK-MT200', 908, 'System Freezing', 'IT', 'Software', 'high', 'high', NULL, NULL, 1, NULL, NULL, NULL, 'pending', '2025-03-03 22:19:21', '2025-03-06'),
(651, 'TCK-0BBC6', 354, 'Warranty', 'Sales', 'Warranty record assistance', 'low', '', 'Need to know my cable\'s warranty coverage', 0x30, 2, NULL, NULL, NULL, 'pending', '2025-11-17 12:26:15', NULL),
(652, 'TCK-AF1A1', 428, 'Incorrect Serial', 'Engineering', 'Product serial verification', 'low', '', 'Incorrect Serial Code', 0x30, 2, NULL, NULL, NULL, 'pending', '2025-11-17 16:18:44', NULL),
(653, 'TCK-56A26', 412, 'ERP Entry not logging', 'Finance', 'ERP entry errors', 'low', '', '1234', '', 2, 1190, NULL, NULL, 'pending', '2025-11-18 03:52:47', NULL),
(654, 'TCK-054D8', 412, 'Broken Payment Verification', 'Finance', 'Payment verification issues', 'regular', 'medium', 'Helpasds', '', 2, 1190, NULL, NULL, 'pending', '2025-11-18 04:26:17', NULL),
(655, 'TCK-C7F3D', 1190, 'Help me', 'Facilities', 'Lighting', 'low', '', 'Help', '', 5, NULL, NULL, NULL, 'pending', '2025-11-18 04:33:32', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_ticket_checklist`
--

CREATE TABLE `tbl_ticket_checklist` (
  `item_id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `is_technician` tinyint(1) DEFAULT 0,
  `description` varchar(255) NOT NULL,
  `is_completed` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `completed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_ticket_checklist`
--

INSERT INTO `tbl_ticket_checklist` (`item_id`, `ticket_id`, `created_by`, `is_technician`, `description`, `is_completed`, `created_at`, `completed_at`) VALUES
(1, 652, 5, 1, 'Test 2', 0, '2025-11-18 11:12:10', NULL),
(2, 260, 1190, 0, '1234', 1, '2025-11-18 12:33:53', '2025-11-18 12:36:07');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_ticket_comment`
--

CREATE TABLE `tbl_ticket_comment` (
  `comment_id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `commenter_id` int(11) NOT NULL,
  `is_technician` tinyint(1) DEFAULT 0,
  `role` varchar(50) NOT NULL,
  `comment_text` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_ticket_comment`
--

INSERT INTO `tbl_ticket_comment` (`comment_id`, `ticket_id`, `commenter_id`, `is_technician`, `role`, `comment_text`, `created_at`) VALUES
(1, 652, 5, 1, 'technician', 'test', '2025-11-18 11:08:37'),
(2, 260, 1190, 0, 'department_head', '1234', '2025-11-18 12:33:52');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_ticket_escalation`
--

CREATE TABLE `tbl_ticket_escalation` (
  `escalation_id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `prev_technician_id` int(11) DEFAULT NULL,
  `new_technician_id` int(11) DEFAULT NULL,
  `prev_department_id` int(11) DEFAULT NULL,
  `new_department_id` int(11) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `escalator_id` int(11) DEFAULT NULL,
  `escalation_type` enum('manual','system') DEFAULT 'system',
  `escalation_timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `sla_status` enum('on-time','overdue','escalated') DEFAULT 'on-time'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_ticket_escalation`
--

INSERT INTO `tbl_ticket_escalation` (`escalation_id`, `ticket_id`, `prev_technician_id`, `new_technician_id`, `prev_department_id`, `new_department_id`, `reason`, `escalator_id`, `escalation_type`, `escalation_timestamp`, `sla_status`) VALUES
(1, 439, NULL, NULL, 7, 5, 'zxczc', 412, 'system', '2025-10-21 00:25:39', 'escalated'),
(2, 439, NULL, NULL, 5, 7, 'zzzz', 412, 'system', '2025-10-21 00:34:26', 'escalated'),
(3, 439, NULL, NULL, 7, 5, 'zxc', 412, 'system', '2025-10-21 00:37:11', 'escalated'),
(4, 439, NULL, NULL, 5, 6, 'xxxx', 412, 'system', '2025-10-21 00:37:19', 'escalated'),
(5, 439, NULL, NULL, 6, 5, 'zxczxc', 412, 'system', '2025-10-21 00:48:16', 'escalated'),
(7, 19, NULL, NULL, 1, 4, 'test', 412, 'system', '2025-11-06 20:40:21', 'escalated'),
(8, 19, NULL, NULL, 4, 4, 'test', 412, 'system', '2025-11-06 20:40:36', 'escalated'),
(9, 19, NULL, NULL, 4, 6, 'test', 412, 'system', '2025-11-06 20:40:48', 'escalated'),
(10, 19, NULL, NULL, 6, 7, 'test', 412, 'system', '2025-11-06 20:40:53', 'escalated'),
(11, 19, NULL, NULL, 7, 4, 'test', 412, 'system', '2025-11-06 20:41:09', 'escalated'),
(12, 19, NULL, NULL, 4, 4, 'test', 412, 'system', '2025-11-06 20:41:14', 'escalated'),
(13, 19, NULL, NULL, 4, 5, 'zxc', 412, 'system', '2025-11-06 20:41:31', 'escalated'),
(14, 518, NULL, NULL, 1, 4, 'test escalate', 412, 'system', '2025-11-06 20:42:31', 'escalated'),
(15, 654, 2, 2, 2, 2, 'Priority not Correct', 1190, 'manual', '2025-11-18 06:25:00', 'escalated'),
(16, 654, 2, 2, 2, 2, 'Wrong Department', 1190, 'manual', '2025-11-18 06:26:01', 'escalated'),
(17, 654, 2, 2, 2, 1, 'Wrong Priority and department. Escalated', 1190, 'manual', '2025-11-18 06:34:58', 'escalated'),
(18, 654, 2, 2, 2, 1, 'Broken', 1190, 'manual', '2025-11-18 06:38:04', 'escalated'),
(19, 654, 2, 2, 2, 2, 'Broken', 1190, 'manual', '2025-11-18 06:38:19', 'escalated'),
(20, 654, 2, 2, 2, 2, 'Beyond scope', 1190, 'manual', '2025-11-18 06:44:29', 'escalated'),
(21, 654, 2, 2, 2, 2, 'Beyond scope', 1190, 'manual', '2025-11-18 06:44:49', 'escalated'),
(22, 654, 2, 2, 2, 1, 'Test', 1190, 'manual', '2025-11-18 06:51:50', 'escalated'),
(23, 654, 2, 2, 2, 1, 'Testing', 1190, 'manual', '2025-11-18 06:59:34', 'escalated');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_ticket_logs`
--

CREATE TABLE `tbl_ticket_logs` (
  `log_id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_role` enum('user','technician','system','department_head','admin') NOT NULL DEFAULT 'user',
  `action_type` varchar(50) NOT NULL,
  `action_details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_ticket_logs`
--

INSERT INTO `tbl_ticket_logs` (`log_id`, `ticket_id`, `user_id`, `user_role`, `action_type`, `action_details`, `ip_address`, `user_agent`, `created_at`) VALUES
(4, 439, 412, 'user', 'reply', 'Added reply: test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-21 00:48:05'),
(5, 439, 412, 'user', 'escalate', 'Escalated ticket to department 5 - zxczxc', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-21 00:48:16'),
(6, 211, 412, 'user', 'reply', 'Added reply: zxzxczxczcx', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-21 00:55:24'),
(7, 211, 412, 'user', 'reply', 'Added reply: asdasd', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-21 01:01:36'),
(8, 211, 412, 'user', 'reply', 'Added reply: test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-21 01:10:02'),
(10, 439, 412, 'user', 'complete', 'Ticket completed by user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-21 01:19:24'),
(11, 439, 412, 'user', 'reopen', 'Ticket reopened by user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-21 01:19:31'),
(12, 439, 412, 'user', 'complete', 'Ticket completed by user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-21 01:19:35'),
(13, 439, 412, 'user', 'reopen', 'Ticket reopened by user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-21 01:19:45'),
(14, 439, 412, 'user', 'complete', 'Ticket completed by user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-21 01:19:47'),
(15, 543, 412, 'user', 'reply', 'Added reply: test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-21 01:21:12'),
(16, 543, 412, 'user', 'escalate', 'Escalated ticket to department 5 - test escalate', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-21 01:21:32'),
(17, 543, 412, 'user', 'complete', 'Ticket completed by user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-21 01:21:35'),
(18, 543, 412, 'user', 'reply', 'Added reply: zxc', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-21 18:23:00'),
(19, 543, 412, 'user', 'reply', 'Added reply: czcz', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-21 18:23:11'),
(20, 543, 412, 'user', 'reopen', 'Ticket reopened by user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-21 18:23:14'),
(21, 543, 412, 'user', 'reply', 'Added reply: czcz', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-21 18:23:17'),
(22, 543, 412, 'user', 'complete', 'Ticket completed by user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-21 18:23:20'),
(23, 74, 412, 'user', 'reply', 'Added reply: test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-21 20:09:25'),
(24, 518, 412, 'user', 'reply', 'Added reply: zxzczcx', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-22 00:33:58'),
(25, 518, 354, 'user', 'reply', 'Added reply: test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-22 00:40:52'),
(26, 19, 354, 'user', 'reply', 'Added reply: test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-22 00:41:34'),
(27, 518, 354, 'user', 'reply', 'Added reply: tewt', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-22 00:56:06'),
(28, 629, 354, 'user', 'reply', 'Added reply: zxc', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-22 00:56:12'),
(29, 19, 412, 'user', 'reply', 'Added reply: test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-22 01:26:36'),
(30, 518, NULL, 'technician', 'reply', 'Added reply: test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-22 01:28:12'),
(31, 518, 412, 'user', 'reply', 'Added reply: test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-22 23:40:37'),
(32, 636, 412, 'user', 'reply', 'Added reply: test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 00:18:54'),
(36, 518, 412, 'user', 'reply', 'Added reply: test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-24 00:31:29'),
(42, 518, 412, 'user', 'reply', 'Added reply: testing', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-28 02:28:44'),
(43, 518, 412, 'user', 'reply', 'Added reply: ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-28 02:28:56'),
(44, 518, 412, 'user', 'reply', 'Added reply: test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-28 02:29:00'),
(45, 518, 412, 'user', 'reply', 'Added reply: test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-28 02:29:04'),
(46, 518, 412, 'user', 'reply', 'Added reply: ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-28 02:29:11'),
(47, 518, 412, 'user', 'reply', 'Added reply: test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-06 20:39:41'),
(48, 19, 412, 'user', 'escalate', 'Escalated ticket to department 4 - test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-06 20:40:21'),
(49, 19, 412, 'user', 'escalate', 'Escalated ticket to department 4 - test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-06 20:40:36'),
(50, 19, 412, 'user', 'escalate', 'Escalated ticket to department 6 - test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-06 20:40:48'),
(51, 19, 412, 'user', 'escalate', 'Escalated ticket to department 7 - test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-06 20:40:53'),
(52, 19, 412, 'user', 'escalate', 'Escalated ticket to department 4 - test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-06 20:41:09'),
(53, 19, 412, 'user', 'escalate', 'Escalated ticket to department 4 - test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-06 20:41:14'),
(54, 19, 412, 'user', 'escalate', 'Escalated ticket to department 5 - zxc', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-06 20:41:31'),
(55, 19, 412, 'user', 'reply', 'Added reply: test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-06 20:41:49'),
(56, 19, 412, 'user', 'reply', 'Added reply: zxc', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-06 20:41:53'),
(57, 518, 412, 'user', 'escalate', 'Escalated ticket to department 4 - test escalate', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-06 20:42:31'),
(58, 628, 354, 'user', 'reply', 'Added reply: .', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-06 20:45:08'),
(60, 574, 1187, 'user', 'reply', 'Added reply: test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-16 09:40:30'),
(61, 574, 1187, 'user', 'complete', 'Ticket completed by user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-16 09:40:33'),
(65, 260, 1190, 'department_head', 'reply', 'User ID 1190 replied: \"Power\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 04:33:57'),
(66, 260, 1190, 'user', 'complete', 'Ticket completed by user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 04:34:05'),
(67, 654, 1190, 'department_head', 'escalate', 'Ticket escalated to technician ID 2. Reason: Beyond scope', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 06:10:15'),
(68, 654, 1190, 'department_head', 'escalate', 'Ticket escalated to technician ID 2 with new priority high. Reason: Priority not Correct', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 06:25:00'),
(69, 654, 1190, 'department_head', 'escalate', 'Ticket escalated to technician ID 2 with new priority regular. Reason: Wrong Department', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 06:26:01'),
(70, 654, 1190, 'department_head', 'escalate', 'Ticket escalated to technician ID 2 with new priority medium. Reason: Wrong Priority and department. Escalated', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 06:34:58'),
(71, 654, 1190, 'department_head', 'escalate', 'Ticket escalated to technician ID 2 with new priority low. Reason: Broken', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 06:38:04'),
(72, 654, 1190, 'department_head', 'escalate', 'Ticket escalated to technician ID 2 with new priority medium. Reason: Broken', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 06:38:19'),
(73, 654, 1190, 'department_head', 'escalate', 'Ticket escalated to technician ID 2 with new priority high. Reason: Beyond scope', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 06:44:49'),
(74, 654, 1190, 'department_head', 'escalate', 'Ticket escalated to technician ID 2 with new priority medium. Reason: Test', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 06:51:50'),
(75, 654, 1190, 'department_head', 'escalate', 'Ticket escalated to technician ID 2 with new priority regular. Reason: Testing', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 06:59:34'),
(76, 655, NULL, 'technician', 'reply', 'Technician ID 5 replied: \"Hello test\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 07:18:38'),
(77, 655, NULL, 'technician', 'reply', 'Technician ID 5 replied: \"Resend\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 07:19:26'),
(78, 655, NULL, 'technician', 'reply', 'Technician ID 5 replied: \"Testing\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 07:20:56'),
(79, 655, NULL, 'technician', 'reply', 'Technician ID 5 replied: \"Testing 2\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 07:24:02'),
(80, 655, NULL, 'technician', 'reply', 'Technician ID 5 replied: \"Testing 2\" (with attachment)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-18 07:26:00');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_ticket_reply`
--

CREATE TABLE `tbl_ticket_reply` (
  `reply_id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `replied_by` enum('user','technician','system') NOT NULL,
  `replier_id` int(11) DEFAULT NULL,
  `reply_text` text DEFAULT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_ticket_reply`
--

INSERT INTO `tbl_ticket_reply` (`reply_id`, `ticket_id`, `replied_by`, `replier_id`, `reply_text`, `attachment_path`, `created_at`) VALUES
(85, 260, 'user', 1190, 'Power', NULL, '2025-11-18 04:33:57'),
(86, 260, 'system', 0, 'Ticket has been completed.', NULL, '2025-11-18 04:34:05'),
(87, 655, 'technician', 5, 'Hello test', NULL, '2025-11-18 07:18:38'),
(88, 655, 'technician', 5, 'Resend', NULL, '2025-11-18 07:19:26'),
(89, 655, 'technician', 5, 'Testing', NULL, '2025-11-18 07:20:56'),
(90, 655, 'technician', 5, 'Testing 2', NULL, '2025-11-18 07:24:02'),
(91, 655, 'technician', 5, 'Testing 2', '../uploads/replies/1763450760_Screenshot_2025-06-28_132958.png', '2025-11-18 07:26:00');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_user`
--

CREATE TABLE `tbl_user` (
  `user_id` int(11) NOT NULL,
  `user_type` enum('internal','external') NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `name` varchar(150) DEFAULT NULL,
  `company` varchar(150) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `user_role` enum('customer','department_head','admin') DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `phone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_user`
--

INSERT INTO `tbl_user` (`user_id`, `user_type`, `department_id`, `name`, `company`, `email`, `password`, `status`, `user_role`, `created_at`, `phone`) VALUES
(354, 'external', 1, 'Simon', 'Organization', 'simon@organization.com', '1234', 'active', 'customer', '2025-09-22 06:45:54', NULL),
(412, 'internal', 5, 'Laura', NULL, 'laura@company.com', '1234', 'active', 'admin', '2025-09-22 06:44:39', NULL),
(413, 'internal', 1, 'John Manager', NULL, 'john.manager@company.com', '1234', 'active', 'customer', '2025-10-17 03:24:29', NULL),
(414, 'internal', 7, 'Sarah Tech', NULL, 'sarah.tech@company.com', '1234', 'active', 'customer', '2025-10-17 03:24:29', NULL),
(415, 'external', 2, 'Alice Johnson', 'ABC Corp', 'alice.johnson@client.com', '1234', 'active', 'customer', '2025-10-17 03:24:29', NULL),
(416, 'external', 3, 'Bob Wilson', 'XYZ Ltd', 'bob.wilson@enterprise.com', '1234', 'active', 'customer', '2025-10-17 03:24:29', NULL),
(417, 'external', 4, 'Carol Davis', 'Startup Inc', 'carol.davis@startup.com', '1234', 'active', 'customer', '2025-10-17 03:24:29', NULL),
(418, 'external', 5, 'David Brown', 'Tech Solutions', 'david.brown@tech.com', '1234', 'active', 'customer', '2025-10-17 03:24:29', NULL),
(419, 'internal', 1, 'John Manager', NULL, 'john.manager@company.com', '1234', 'active', 'customer', '2025-10-17 09:47:54', NULL),
(420, 'internal', 7, 'Sarah Tech', NULL, 'sarah.tech@company.com', '1234', 'active', 'customer', '2025-10-17 09:47:54', NULL),
(421, 'external', 6, 'Alice Johnson', 'ABC Corp', 'alice.johnson@client.com', '1234', 'active', 'customer', '2025-10-17 09:47:54', NULL),
(422, 'external', 7, 'Bob Wilson', 'XYZ Ltd', 'bob.wilson@enterprise.com', '1234', 'active', 'customer', '2025-10-17 09:47:54', NULL),
(423, 'external', 1, 'Carol Davis', 'Startup Inc', 'carol.davis@startup.com', '1234', 'active', 'customer', '2025-10-17 09:47:54', NULL),
(424, 'external', 2, 'David Brown', 'Tech Solutions', 'david.brown@tech.com', '1234', 'active', 'customer', '2025-10-17 09:47:54', NULL),
(425, 'internal', 1, 'John Manager', NULL, 'john.manager@company.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(426, 'internal', 7, 'Sarah Tech', NULL, 'sarah.tech@company.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(427, 'internal', 1, 'Mike Admin', NULL, 'mike.admin@company.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(428, 'internal', 3, 'Lisa Support', NULL, 'lisa.support@company.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(429, 'external', 4, 'Edward Williams', 'Advanced Networks', 'edward.williams@advancednetworks.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(430, 'external', 5, 'Susan Johnson', NULL, 'susan.johnson@gmail.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(431, 'external', 6, 'Ronald Ramirez', 'Data Dynamics', 'ronald.ramirez@datadynamics.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(432, 'external', 5, 'Donald White', 'Professional Services', 'donald.white@professionalservices.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(433, 'external', 7, 'Jennifer Anderson', 'Advanced Networks', 'jennifer.anderson@advancednetworks.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(434, 'external', 5, 'Helen Williams', 'Smart Solutions', 'helen.williams@smartsolutions.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(435, 'external', 7, 'Michelle Gonzalez', NULL, 'michelle.gonzalez@gmail.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(436, 'external', 7, 'Margaret Harris', 'ABC Corporation', 'margaret.harris@abcoration.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(437, 'external', 1, 'Mary Jones', 'Tech Solutions Inc', 'mary.jones@techsolutions.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(438, 'external', 2, 'Susan Wilson', 'Tech Solutions Inc', 'susan.wilson@techsolutions.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(439, 'external', 2, 'Helen Ramirez', 'Enterprise Plus', 'helen.ramirez@enterpriseplus.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(440, 'external', 3, 'Patricia Taylor', NULL, 'patricia.taylor@gmail.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(441, 'external', 7, 'Margaret Lewis', 'NextGen Systems', 'margaret.lewis@nextgensystems.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(442, 'external', 4, 'Ronald Jones', 'Cloud Systems', 'ronald.jones@cloudsystems.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(443, 'external', 5, 'Laura Perez', 'Startup Hub', 'laura.perez@startuphub.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(444, 'external', 7, 'Paul Perez', NULL, 'paul.perez@gmail.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(445, 'external', 5, 'Deborah Lee', 'Modern Enterprises', 'deborah.lee@modernenterprises.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(446, 'external', 6, 'Ruth Martin', 'Global Enterprises', 'ruth.martin@globalenterprises.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(447, 'external', 7, 'Dorothy Miller', 'Global Enterprises', 'dorothy.miller@globalenterprises.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(448, 'external', 7, 'Andrew Young', 'Digital Works', 'andrew.young@digitalworks.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(449, 'external', 5, 'Elizabeth Flores', 'Global Enterprises', 'elizabeth.flores@globalenterprises.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(450, 'external', 7, 'Robert Smith', NULL, 'robert.smith@gmail.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(451, 'external', 7, 'Patricia Torres', 'ABC Corporation', 'patricia.torres@abcoration.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(452, 'external', 7, 'Nancy Taylor', NULL, 'nancy.taylor@gmail.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(453, 'external', 7, 'Anthony Hill', 'Data Dynamics', 'anthony.hill@datadynamics.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(454, 'external', 7, 'Susan Brown', NULL, 'susan.brown@gmail.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(455, 'external', 6, 'Mark Wright', 'Advanced Networks', 'mark.wright@advancednetworks.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(456, 'external', 7, 'Sharon Wright', 'Quality Tech', 'sharon.wright@qualitytech.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(457, 'external', 1, 'Richard Moore', 'Advanced Networks', 'richard.moore@advancednetworks.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(458, 'external', 5, 'Christopher Perez', 'Innovation Labs', 'christopher.perez@innovationlabs.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(459, 'external', 7, 'Margaret Nguyen', 'Digital Works', 'margaret.nguyen@digitalworks.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(460, 'external', 2, 'Donald Torres', 'Cloud Systems', 'donald.torres@cloudsystems.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(461, 'external', 7, 'Michael Lewis', 'Elite Solutions', 'michael.lewis@elitesolutions.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(462, 'external', 3, 'Sharon Davis', 'Enterprise Plus', 'sharon.davis@enterpriseplus.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(463, 'external', 5, 'Michelle Martin', 'Advanced Networks', 'michelle.martin@advancednetworks.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(464, 'external', 4, 'Lisa Allen', NULL, 'lisa.allen@gmail.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(465, 'external', 4, 'Elizabeth Moore', 'Network Solutions', 'elizabeth.moore@networksolutions.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(466, 'external', 2, 'Karen Garcia', 'Quality Tech', 'karen.garcia@qualitytech.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(467, 'external', 2, 'Jennifer Lee', 'Future Technologies', 'jennifer.lee@futuretechnologies.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(468, 'external', 7, 'Laura Walker', NULL, 'laura.walker@gmail.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(469, 'external', 7, 'Kevin Hill', 'ABC Corporation', 'kevin.hill@abcoration.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(470, 'external', 7, 'Linda Jones', 'Digital Works', 'linda.jones@digitalworks.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(471, 'external', 5, 'Dorothy Perez', 'Cloud Systems', 'dorothy.perez@cloudsystems.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(472, 'external', 7, 'Mary Johnson', 'Modern Enterprises', 'mary.johnson@modernenterprises.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(473, 'external', 7, 'Richard Moore', 'Tech Solutions Inc', 'richard.moore@techsolutions.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(474, 'external', 7, 'Kevin Torres', 'Data Dynamics', 'kevin.torres@datadynamics.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(475, 'external', 7, 'Sandra Miller', 'Elite Solutions', 'sandra.miller@elitesolutions.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(476, 'external', 7, 'Susan Harris', 'Tech Solutions Inc', 'susan.harris@techsolutions.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(477, 'external', 7, 'Carol Flores', 'Advanced Networks', 'carol.flores@advancednetworks.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(478, 'external', 5, 'Karen Nguyen', 'XYZ Ltd', 'karen.nguyen@xyz.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(479, 'external', 5, 'Linda White', 'Professional Services', 'linda.white@professionalservices.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(480, 'external', 5, 'Deborah Nguyen', 'Tech Solutions Inc', 'deborah.nguyen@techsolutions.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(481, 'external', 6, 'Edward Smith', 'Professional Services', 'edward.smith@professionalservices.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(482, 'external', 6, 'Andrew Robinson', 'Global Enterprises', 'andrew.robinson@globalenterprises.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(483, 'external', 7, 'Robert Perez', 'Future Technologies', 'robert.perez@futuretechnologies.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(484, 'external', 5, 'Joshua Hernandez', 'Quality Tech', 'joshua.hernandez@qualitytech.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(485, 'external', 7, 'Jessica Williams', 'Future Technologies', 'jessica.williams@futuretechnologies.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(486, 'external', 7, 'Thomas Gonzalez', 'Premium Services', 'thomas.gonzalez@premiumservices.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(487, 'external', 7, 'John Young', 'Advanced Networks', 'john.young@advancednetworks.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(488, 'external', 7, 'Ronald Ramirez', 'Enterprise Plus', 'ronald.ramirez@enterpriseplus.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(489, 'external', 7, 'Dorothy Nguyen', 'Innovation Labs', 'dorothy.nguyen@innovationlabs.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(490, 'external', 1, 'Mary Wilson', 'Advanced Networks', 'mary.wilson@advancednetworks.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(491, 'external', 2, 'Lisa Clark', NULL, 'lisa.clark@gmail.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(492, 'external', 2, 'Matthew Allen', NULL, 'matthew.allen@gmail.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(493, 'external', 2, 'Nancy Torres', 'Enterprise Plus', 'nancy.torres@enterpriseplus.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(494, 'external', 5, 'Kevin Hill', 'Cloud Systems', 'kevin.hill@cloudsystems.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(495, 'external', 3, 'Elizabeth Sanchez', 'Enterprise Plus', 'elizabeth.sanchez@enterpriseplus.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(496, 'external', 4, 'Karen Nguyen', NULL, 'karen.nguyen@gmail.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(497, 'external', 7, 'Patricia Anderson', 'Modern Enterprises', 'patricia.anderson@modernenterprises.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(498, 'external', 7, 'Lisa Lee', 'Network Solutions', 'lisa.lee@networksolutions.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(499, 'external', 5, 'Paul Garcia', NULL, 'paul.garcia@gmail.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(500, 'external', 7, 'Jessica Young', NULL, 'jessica.young@gmail.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(501, 'external', 7, 'George Thomas', NULL, 'george.thomas@gmail.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(502, 'external', 7, 'Christopher Garcia', 'Digital Works', 'christopher.garcia@digitalworks.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(503, 'external', 6, 'Margaret Wright', 'Quality Tech', 'margaret.wright@qualitytech.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(504, 'external', 5, 'Matthew Lopez', 'Elite Solutions', 'matthew.lopez@elitesolutions.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(505, 'external', 7, 'Helen Thomas', 'Digital Works', 'helen.thomas@digitalworks.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(506, 'external', 7, 'Robert Garcia', NULL, 'robert.garcia@gmail.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(507, 'external', 7, 'Nancy Jones', NULL, 'nancy.jones@gmail.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(508, 'external', 7, 'Joshua Lopez', 'Advanced Networks', 'joshua.lopez@advancednetworks.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(509, 'external', 1, 'Laura Martinez', NULL, 'laura.martinez@gmail.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(510, 'external', 7, 'Robert Davis', NULL, 'robert.davis@gmail.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(511, 'external', 3, 'Elizabeth Perez', NULL, 'elizabeth.perez@gmail.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(512, 'external', 7, 'Deborah Robinson', 'Elite Solutions', 'deborah.robinson@elitesolutions.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(513, 'external', 7, 'Sharon Torres', NULL, 'sharon.torres@gmail.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(514, 'external', 7, 'David Jones', 'Global Enterprises', 'david.jones@globalenterprises.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(515, 'external', 5, 'Steven Davis', 'ABC Corporation', 'steven.davis@abcoration.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(516, 'external', 7, 'Carol Martinez', 'Innovation Labs', 'carol.martinez@innovationlabs.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(517, 'external', 7, 'Linda Anderson', 'Advanced Networks', 'linda.anderson@advancednetworks.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(518, 'external', 7, 'Elizabeth Jones', NULL, 'elizabeth.jones@gmail.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(519, 'external', 2, 'David Brown', 'Premium Services', 'david.brown@premiumservices.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(520, 'external', 3, 'Kimberly Thomas', 'XYZ Ltd', 'kimberly.thomas@xyz.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(521, 'external', 5, 'George Garcia', NULL, 'george.garcia@gmail.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(522, 'external', 3, 'George Robinson', NULL, 'george.robinson@gmail.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(523, 'external', 5, 'Charles Harris', 'XYZ Ltd', 'charles.harris@xyz.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(524, 'external', 4, 'Edward Martinez', 'NextGen Systems', 'edward.martinez@nextgensystems.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(525, 'external', 5, 'Thomas Torres', 'Enterprise Plus', 'thomas.torres@enterpriseplus.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(526, 'external', 7, 'Lisa Davis', 'Quality Tech', 'lisa.davis@qualitytech.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(527, 'external', 7, 'John Hill', NULL, 'john.hill@gmail.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(528, 'external', 7, 'Mary Wright', 'NextGen Systems', 'mary.wright@nextgensystems.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(529, 'external', 6, 'John Clark', NULL, 'john.clark@gmail.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(530, 'external', 4, 'Richard Lee', 'Advanced Networks', 'richard.lee@advancednetworks.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(531, 'external', 4, 'Kenneth Torres', 'Cloud Systems', 'kenneth.torres@cloudsystems.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(532, 'external', 7, 'Mary Nguyen', 'Smart Solutions', 'mary.nguyen@smartsolutions.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(533, 'external', 7, 'Jennifer Clark', 'Network Solutions', 'jennifer.clark@networksolutions.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(534, 'external', 4, 'Michelle Johnson', 'Professional Services', 'michelle.johnson@professionalservices.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(535, 'external', 7, 'Jennifer Wright', 'Tech Solutions Inc', 'jennifer.wright@techsolutions.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(536, 'external', 1, 'Jessica Rodriguez', 'Future Technologies', 'jessica.rodriguez@futuretechnologies.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(537, 'external', 7, 'Charles Wright', 'Digital Works', 'charles.wright@digitalworks.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(538, 'external', 2, 'Joshua Walker', 'XYZ Ltd', 'joshua.walker@xyz.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(539, 'external', 3, 'Margaret Lopez', 'Smart Solutions', 'margaret.lopez@smartsolutions.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(540, 'external', 7, 'Sandra Lee', NULL, 'sandra.lee@gmail.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(541, 'external', 7, 'Matthew Nguyen', 'Data Dynamics', 'matthew.nguyen@datadynamics.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(542, 'external', 7, 'Elizabeth Lewis', 'Smart Solutions', 'elizabeth.lewis@smartsolutions.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(543, 'external', 5, 'John Robinson', 'Tech Solutions Inc', 'john.robinson@techsolutions.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(544, 'external', 7, 'Donna Garcia', 'Premium Services', 'donna.garcia@premiumservices.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(545, 'external', 3, 'Michelle King', 'NextGen Systems', 'michelle.king@nextgensystems.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(546, 'external', 7, 'Linda Jackson', NULL, 'linda.jackson@gmail.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(547, 'external', 7, 'Deborah Nguyen', 'Global Enterprises', 'deborah.nguyen@globalenterprises.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(548, 'external', 4, 'Joseph Allen', 'Modern Enterprises', 'joseph.allen@modernenterprises.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(549, 'external', 7, 'Barbara Allen', NULL, 'barbara.allen@gmail.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(550, 'external', 5, 'Laura Smith', 'Data Dynamics', 'laura.smith@datadynamics.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(551, 'external', 6, 'Laura Rodriguez', 'XYZ Ltd', 'laura.rodriguez@xyz.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(552, 'external', 5, 'Carol Flores', 'Modern Enterprises', 'carol.flores@modernenterprises.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(553, 'external', 5, 'Laura Walker', NULL, 'laura.walker@gmail.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(554, 'external', 7, 'George Hernandez', 'Quality Tech', 'george.hernandez@qualitytech.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(555, 'external', 7, 'Kimberly Johnson', 'Quality Tech', 'kimberly.johnson@qualitytech.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(556, 'external', 5, 'Ronald Lewis', 'Digital Works', 'ronald.lewis@digitalworks.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(557, 'external', 6, 'Barbara Davis', 'Smart Solutions', 'barbara.davis@smartsolutions.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(558, 'external', 7, 'David Moore', 'Premium Services', 'david.moore@premiumservices.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(559, 'external', 5, 'Kimberly Moore', 'Future Technologies', 'kimberly.moore@futuretechnologies.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(560, 'external', 7, 'Lisa Jones', 'Innovation Labs', 'lisa.jones@innovationlabs.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(561, 'external', 7, 'Elizabeth Smith', NULL, 'elizabeth.smith@gmail.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(562, 'external', 5, 'Daniel Harris', 'Innovation Labs', 'daniel.harris@innovationlabs.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(563, 'external', 7, 'Susan Hill', 'Elite Solutions', 'susan.hill@elitesolutions.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(564, 'external', 7, 'Joseph Scott', 'Digital Works', 'joseph.scott@digitalworks.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(565, 'external', 1, 'Paul Scott', 'XYZ Ltd', 'paul.scott@xyz.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(566, 'external', 2, 'Donald Anderson', 'NextGen Systems', 'donald.anderson@nextgensystems.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(567, 'external', 7, 'James Jones', 'Data Dynamics', 'james.jones@datadynamics.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(568, 'external', 7, 'John Clark', 'Premium Services', 'john.clark@premiumservices.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(569, 'external', 7, 'Susan Smith', NULL, 'susan.smith@gmail.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(570, 'external', 5, 'Helen Allen', 'Cloud Systems', 'helen.allen@cloudsystems.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(571, 'external', 7, 'Matthew Walker', 'Premium Services', 'matthew.walker@premiumservices.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(572, 'external', 7, 'Linda Thomas', 'Professional Services', 'linda.thomas@professionalservices.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(573, 'external', 7, 'Michelle Davis', 'ABC Corporation', 'michelle.davis@abcoration.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(574, 'external', 7, 'Edward Johnson', 'Data Dynamics', 'edward.johnson@datadynamics.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(575, 'external', 7, 'Joseph Gonzalez', 'NextGen Systems', 'joseph.gonzalez@nextgensystems.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(576, 'external', 3, 'Elizabeth Harris', 'Elite Solutions', 'elizabeth.harris@elitesolutions.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(577, 'external', 7, 'Carol Jackson', NULL, 'carol.jackson@gmail.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(578, 'external', 7, 'Christopher Gonzalez', 'Professional Services', 'christopher.gonzalez@professionalservices.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(579, 'external', 4, 'Laura Martinez', 'Cloud Systems', 'laura.martinez@cloudsystems.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(580, 'external', 4, 'Sandra Jones', 'Global Enterprises', 'sandra.jones@globalenterprises.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(581, 'external', 5, 'Dorothy Young', 'Innovation Labs', 'dorothy.young@innovationlabs.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(582, 'external', 5, 'Ronald Thomas', NULL, 'ronald.thomas@gmail.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(583, 'external', 3, 'Steven Allen', 'NextGen Systems', 'steven.allen@nextgensystems.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(584, 'external', 6, 'Charles Walker', 'NextGen Systems', 'charles.walker@nextgensystems.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(585, 'external', 7, 'Sandra Scott', 'Innovation Labs', 'sandra.scott@innovationlabs.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(586, 'external', 7, 'Deborah Flores', 'XYZ Ltd', 'deborah.flores@xyz.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(587, 'external', 5, 'Donna Taylor', 'Global Enterprises', 'donna.taylor@globalenterprises.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(588, 'external', 7, 'Laura Torres', 'ABC Corporation', 'laura.torres@abcoration.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(589, 'external', 7, 'Michael Flores', 'Modern Enterprises', 'michael.flores@modernenterprises.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(590, 'external', 5, 'Andrew Torres', 'ABC Corporation', 'andrew.torres@abcoration.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(591, 'external', 4, 'Charles Davis', 'Modern Enterprises', 'charles.davis@modernenterprises.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(592, 'external', 7, 'Barbara Ramirez', 'Startup Hub', 'barbara.ramirez@startuphub.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(593, 'external', 7, 'David Lee', 'Digital Works', 'david.lee@digitalworks.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(594, 'external', 7, 'Linda Perez', 'Enterprise Plus', 'linda.perez@enterpriseplus.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(595, 'external', 7, 'Daniel Harris', 'Quality Tech', 'daniel.harris@qualitytech.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(596, 'external', 7, 'Barbara Garcia', 'Future Technologies', 'barbara.garcia@futuretechnologies.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(597, 'external', 7, 'Matthew Moore', 'Advanced Networks', 'matthew.moore@advancednetworks.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(598, 'external', 7, 'Jennifer Jackson', NULL, 'jennifer.jackson@gmail.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(599, 'external', 1, 'Richard Moore', 'Professional Services', 'richard.moore@professionalservices.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(600, 'external', 5, 'Patricia Walker', 'Quality Tech', 'patricia.walker@qualitytech.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(601, 'external', 5, 'Carol Flores', 'Cloud Systems', 'carol.flores@cloudsystems.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(602, 'external', 7, 'Jessica Perez', 'Enterprise Plus', 'jessica.perez@enterpriseplus.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(603, 'external', 6, 'William Anderson', 'ABC Corporation', 'william.anderson@abcoration.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(604, 'external', 6, 'Mark Ramirez', 'Network Solutions', 'mark.ramirez@networksolutions.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(605, 'external', 7, 'Sarah Brown', NULL, 'sarah.brown@gmail.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(606, 'external', 7, 'Karen Young', 'Data Dynamics', 'karen.young@datadynamics.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(607, 'external', 7, 'Linda Moore', 'Premium Services', 'linda.moore@premiumservices.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(608, 'external', 2, 'Steven White', NULL, 'steven.white@gmail.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(609, 'external', 2, 'Kimberly Clark', 'Professional Services', 'kimberly.clark@professionalservices.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(610, 'external', 2, 'Susan Allen', 'Network Solutions', 'susan.allen@networksolutions.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(611, 'external', 3, 'Kenneth Thompson', 'Cloud Systems', 'kenneth.thompson@cloudsystems.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(612, 'external', 3, 'Brian Walker', 'Startup Hub', 'brian.walker@startuphub.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(613, 'external', 7, 'David White', NULL, 'david.white@gmail.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(614, 'external', 7, 'Karen Martinez', 'Quality Tech', 'karen.martinez@qualitytech.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(615, 'external', 4, 'Michael Williams', 'Elite Solutions', 'michael.williams@elitesolutions.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(616, 'external', 5, 'John Davis', 'Elite Solutions', 'john.davis@elitesolutions.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(617, 'external', 4, 'David Perez', 'Enterprise Plus', 'david.perez@enterpriseplus.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(618, 'external', 5, 'Christopher Walker', 'Cloud Systems', 'christopher.walker@cloudsystems.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(619, 'external', 7, 'Richard Smith', 'Future Technologies', 'richard.smith@futuretechnologies.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(620, 'external', 6, 'Richard Gonzalez', NULL, 'richard.gonzalez@gmail.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(621, 'external', 7, 'Steven Garcia', NULL, 'steven.garcia@gmail.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(622, 'external', 6, 'Steven Rodriguez', 'Tech Solutions Inc', 'steven.rodriguez@techsolutions.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(623, 'external', 7, 'Patricia King', 'ABC Corporation', 'patricia.king@abcoration.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(624, 'external', 1, 'Deborah Ramirez', 'Tech Solutions Inc', 'deborah.ramirez@techsolutions.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(625, 'external', 7, 'Michael Brown', 'Network Solutions', 'michael.brown@networksolutions.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(626, 'external', 2, 'Donald Lopez', 'Digital Works', 'donald.lopez@digitalworks.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(627, 'external', 7, 'Brian Perez', 'Elite Solutions', 'brian.perez@elitesolutions.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(628, 'external', 3, 'Barbara Lee', 'Innovation Labs', 'barbara.lee@innovationlabs.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(629, 'external', 7, 'Charles Anderson', 'Innovation Labs', 'charles.anderson@innovationlabs.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(630, 'external', 7, 'Joshua Wilson', 'Data Dynamics', 'joshua.wilson@datadynamics.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(631, 'external', 4, 'Betty Perez', 'ABC Corporation', 'betty.perez@abcoration.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(632, 'external', 7, 'Carol Allen', 'Elite Solutions', 'carol.allen@elitesolutions.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(633, 'external', 6, 'George Wright', 'Elite Solutions', 'george.wright@elitesolutions.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(634, 'external', 5, 'Linda Wilson', NULL, 'linda.wilson@gmail.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(635, 'external', 6, 'Edward Young', 'Smart Solutions', 'edward.young@smartsolutions.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(636, 'external', 7, 'Sandra Walker', NULL, 'sandra.walker@gmail.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(637, 'external', 4, 'Christopher Lewis', 'Modern Enterprises', 'christopher.lewis@modernenterprises.com', '1234', 'active', 'customer', '2025-10-17 09:56:34', NULL),
(638, 'external', 7, 'Joshua Young', 'Smart Solutions', 'joshua.young@smartsolutions.com', '1234', 'active', 'customer', '2025-10-17 09:56:35', NULL),
(639, 'external', 7, 'Matthew Anderson', NULL, 'matthew.anderson@gmail.com', '1234', 'active', 'customer', '2025-10-17 09:56:35', NULL),
(640, 'external', 1, 'Steven Davis', 'Global Enterprises', 'steven.davis@globalenterprises.com', '1234', 'active', 'customer', '2025-10-17 09:56:35', NULL),
(641, 'external', 7, 'Richard Thomas', 'Innovation Labs', 'richard.thomas@innovationlabs.com', '1234', 'active', 'customer', '2025-10-17 09:56:35', NULL),
(642, 'external', 2, 'Robert Perez', 'Elite Solutions', 'robert.perez@elitesolutions.com', '1234', 'active', 'customer', '2025-10-17 09:56:35', NULL),
(643, 'external', 3, 'Patricia Lewis', NULL, 'patricia.lewis@gmail.com', '1234', 'active', 'customer', '2025-10-17 09:56:35', NULL),
(644, 'external', 6, 'Joshua Taylor', 'Elite Solutions', 'joshua.taylor@elitesolutions.com', '1234', 'active', 'customer', '2025-10-17 09:56:35', NULL),
(645, 'external', 5, 'Helen Anderson', 'Cloud Systems', 'helen.anderson@cloudsystems.com', '1234', 'active', 'customer', '2025-10-17 09:56:35', NULL),
(646, 'external', 3, 'Daniel Lee', 'Global Enterprises', 'daniel.lee@globalenterprises.com', '1234', 'active', 'customer', '2025-10-17 09:56:35', NULL),
(647, 'external', 7, 'Sarah Young', 'NextGen Systems', 'sarah.young@nextgensystems.com', '1234', 'active', 'customer', '2025-10-17 09:56:35', NULL),
(648, 'external', 7, 'Kenneth Miller', NULL, 'kenneth.miller@gmail.com', '1234', 'active', 'customer', '2025-10-17 09:56:35', NULL),
(649, 'external', 4, 'Joseph Martinez', 'ABC Corporation', 'joseph.martinez@abcoration.com', '1234', 'active', 'customer', '2025-10-17 09:56:35', NULL),
(650, 'external', 5, 'Paul Rodriguez', 'Quality Tech', 'paul.rodriguez@qualitytech.com', '1234', 'active', 'customer', '2025-10-17 09:56:35', NULL),
(651, 'external', 3, 'Anthony Martin', 'Premium Services', 'anthony.martin@premiumservices.com', '1234', 'active', 'customer', '2025-10-17 09:56:35', NULL),
(652, 'external', 7, 'Paul Sanchez', 'XYZ Ltd', 'paul.sanchez@xyz.com', '1234', 'active', 'customer', '2025-10-17 09:56:35', NULL),
(653, 'external', 6, 'Jessica Gonzalez', 'Quality Tech', 'jessica.gonzalez@qualitytech.com', '1234', 'active', 'customer', '2025-10-17 09:56:35', NULL),
(654, 'external', 7, 'Lisa Ramirez', 'NextGen Systems', 'lisa.ramirez@nextgensystems.com', '1234', 'active', 'customer', '2025-10-17 09:56:35', NULL),
(655, 'external', 1, 'Margaret Nguyen', 'Advanced Networks', 'margaret.nguyen@advancednetworks.com', '1234', 'active', 'customer', '2025-10-17 09:56:35', NULL),
(656, 'external', 2, 'Donna Robinson', 'Cloud Systems', 'donna.robinson@cloudsystems.com', '1234', 'active', 'customer', '2025-10-17 09:56:35', NULL),
(657, 'external', 3, 'Paul White', 'XYZ Ltd', 'paul.white@xyz.com', '1234', 'active', 'customer', '2025-10-17 09:56:35', NULL),
(658, 'external', 7, 'Sarah Davis', 'Tech Solutions Inc', 'sarah.davis@techsolutions.com', '1234', 'active', 'customer', '2025-10-17 09:56:35', NULL),
(659, 'external', 4, 'Laura Wright', 'Global Enterprises', 'laura.wright@globalenterprises.com', '1234', 'active', 'customer', '2025-10-17 09:56:35', NULL),
(660, 'external', 5, 'James Taylor', 'Advanced Networks', 'james.taylor@advancednetworks.com', '1234', 'active', 'customer', '2025-10-17 09:56:35', NULL),
(661, 'external', 6, 'Carol Wilson', NULL, 'carol.wilson@gmail.com', '1234', 'active', 'customer', '2025-10-17 09:56:35', NULL),
(662, 'external', 6, 'Kenneth Miller', 'Advanced Networks', 'kenneth.miller@advancednetworks.com', '1234', 'active', 'customer', '2025-10-17 09:56:35', NULL),
(663, 'external', 7, 'Kenneth Johnson', 'ABC Corporation', 'kenneth.johnson@abcoration.com', '1234', 'active', 'customer', '2025-10-17 09:56:35', NULL),
(664, 'external', 7, 'Susan Martinez', 'Data Dynamics', 'susan.martinez@datadynamics.com', '1234', 'active', 'customer', '2025-10-17 09:56:35', NULL),
(665, 'external', 3, 'Jessica Martin', 'Premium Services', 'jessica.martin@premiumservices.com', '1234', 'active', 'customer', '2025-10-17 09:56:35', NULL),
(666, 'external', 7, 'Linda Ramirez', 'Cloud Systems', 'linda.ramirez@cloudsystems.com', '1234', 'active', 'customer', '2025-10-17 09:56:35', NULL),
(667, 'external', 1, 'Richard Anderson', 'ABC Corporation', 'richard.anderson@abcoration.com', '1234', 'active', 'customer', '2025-10-17 09:56:35', NULL),
(668, 'external', 7, 'Matthew Scott', 'Modern Enterprises', 'matthew.scott@modernenterprises.com', '1234', 'active', 'customer', '2025-10-17 09:56:35', NULL),
(669, 'external', 2, 'Ruth Gonzalez', 'Advanced Networks', 'ruth.gonzalez@advancednetworks.com', '1234', 'active', 'customer', '2025-10-17 09:56:35', NULL),
(670, 'external', 3, 'Edward Rodriguez', 'Elite Solutions', 'edward.rodriguez@elitesolutions.com', '1234', 'active', 'customer', '2025-10-17 09:56:35', NULL),
(671, 'external', 4, 'Linda Davis', 'Enterprise Plus', 'linda.davis@enterpriseplus.com', '1234', 'active', 'customer', '2025-10-17 09:56:35', NULL),
(672, 'external', 5, 'Brian Walker', 'Digital Works', 'brian.walker@digitalworks.com', '1234', 'active', 'customer', '2025-10-17 09:56:35', NULL),
(673, 'external', 7, 'Helen Jackson', NULL, 'helen.jackson@gmail.com', '1234', 'active', 'customer', '2025-10-17 09:56:35', NULL),
(674, 'external', 6, 'Thomas Martinez', 'XYZ Ltd', 'thomas.martinez@xyz.com', '1234', 'active', 'customer', '2025-10-17 09:56:35', NULL),
(675, 'external', 2, 'John Ramirez', 'Quality Tech', 'john.ramirez@qualitytech.com', '1234', 'active', 'customer', '2025-10-17 09:56:35', NULL),
(676, 'external', 7, 'Kevin White', 'Enterprise Plus', 'kevin.white@enterpriseplus.com', '1234', 'active', 'customer', '2025-10-17 09:56:35', NULL),
(677, 'external', 7, 'Anthony Robinson', 'ABC Corporation', 'anthony.robinson@abcoration.com', '1234', 'active', 'customer', '2025-10-17 09:56:35', NULL),
(678, 'external', 5, 'Jennifer Wright', 'Cloud Systems', 'jennifer.wright@cloudsystems.com', '1234', 'active', 'customer', '2025-10-17 09:56:35', NULL),
(679, 'internal', 1, 'John Manager', NULL, 'john.manager@company.com', '1234', 'active', 'customer', '2025-10-19 23:19:32', NULL),
(680, 'internal', 7, 'Sarah Tech', NULL, 'sarah.tech@company.com', '1234', 'active', 'customer', '2025-10-19 23:19:32', NULL),
(681, 'internal', 1, 'Mike Admin', NULL, 'mike.admin@company.com', '1234', 'active', 'customer', '2025-10-19 23:19:32', NULL),
(682, 'internal', 7, 'Lisa Support', NULL, 'lisa.support@company.com', '1234', 'active', 'customer', '2025-10-19 23:19:32', NULL),
(683, 'external', 6, 'Sharon Sanchez', NULL, 'sharon.sanchez@gmail.com', '1234', 'active', 'customer', '2025-10-19 23:19:32', NULL),
(684, 'external', 1, 'John Torres', 'Global Enterprises', 'john.torres@globalenterprises.com', '1234', 'active', 'customer', '2025-10-19 23:19:32', NULL),
(685, 'external', 5, 'Matthew Robinson', 'Data Dynamics', 'matthew.robinson@datadynamics.com', '1234', 'active', 'customer', '2025-10-19 23:19:32', NULL),
(686, 'external', 2, 'Ruth Hernandez', NULL, 'ruth.hernandez@gmail.com', '1234', 'active', 'customer', '2025-10-19 23:19:32', NULL),
(687, 'external', 7, 'Richard Nguyen', 'Network Solutions', 'richard.nguyen@networksolutions.com', '1234', 'active', 'customer', '2025-10-19 23:19:32', NULL),
(688, 'external', 3, 'Linda Harris', NULL, 'linda.harris@gmail.com', '1234', 'active', 'customer', '2025-10-19 23:19:32', NULL),
(689, 'external', 7, 'Jessica Torres', 'XYZ Ltd', 'jessica.torres@xyz.com', '1234', 'active', 'customer', '2025-10-19 23:19:32', NULL),
(690, 'external', 7, 'Susan Thomas', 'Digital Works', 'susan.thomas@digitalworks.com', '1234', 'active', 'customer', '2025-10-19 23:19:32', NULL),
(691, 'external', 7, 'Brian Taylor', 'Enterprise Plus', 'brian.taylor@enterpriseplus.com', '1234', 'active', 'customer', '2025-10-19 23:19:32', NULL),
(692, 'external', 7, 'Susan Allen', 'Advanced Networks', 'susan.allen@advancednetworks.com', '1234', 'active', 'customer', '2025-10-19 23:19:32', NULL),
(693, 'external', 7, 'Helen Thompson', 'Quality Tech', 'helen.thompson@qualitytech.com', '1234', 'active', 'customer', '2025-10-19 23:19:32', NULL),
(694, 'external', 7, 'Linda Sanchez', 'Professional Services', 'linda.sanchez@professionalservices.com', '1234', 'active', 'customer', '2025-10-19 23:19:32', NULL),
(695, 'external', 3, 'Andrew Jones', 'Digital Works', 'andrew.jones@digitalworks.com', '1234', 'active', 'customer', '2025-10-19 23:19:32', NULL),
(696, 'external', 4, 'Mark Flores', 'Advanced Networks', 'mark.flores@advancednetworks.com', '1234', 'active', 'customer', '2025-10-19 23:19:32', NULL),
(697, 'external', 7, 'Barbara Thompson', NULL, 'barbara.thompson@gmail.com', '1234', 'active', 'customer', '2025-10-19 23:19:32', NULL),
(698, 'external', 6, 'Carol Walker', 'XYZ Ltd', 'carol.walker@xyz.com', '1234', 'active', 'customer', '2025-10-19 23:19:32', NULL),
(699, 'external', 7, 'Jessica Lopez', NULL, 'jessica.lopez@gmail.com', '1234', 'active', 'customer', '2025-10-19 23:19:32', NULL),
(700, 'external', 7, 'Steven Wright', 'Quality Tech', 'steven.wright@qualitytech.com', '1234', 'active', 'customer', '2025-10-19 23:19:32', NULL),
(701, 'external', 2, 'Betty Young', 'Future Technologies', 'betty.young@futuretechnologies.com', '1234', 'active', 'customer', '2025-10-19 23:19:32', NULL),
(702, 'external', 6, 'Matthew Williams', NULL, 'matthew.williams@gmail.com', '1234', 'active', 'customer', '2025-10-19 23:19:32', NULL),
(703, 'external', 5, 'Michelle Clark', 'Modern Enterprises', 'michelle.clark@modernenterprises.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(704, 'external', 3, 'John Wilson', 'Elite Solutions', 'john.wilson@elitesolutions.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(705, 'external', 7, 'Ruth Robinson', 'Startup Hub', 'ruth.robinson@startuphub.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(706, 'external', 2, 'Joseph Gonzalez', 'ABC Corporation', 'joseph.gonzalez@abcoration.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(707, 'external', 6, 'Donna Sanchez', NULL, 'donna.sanchez@gmail.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(708, 'external', 4, 'Carol Lopez', 'Modern Enterprises', 'carol.lopez@modernenterprises.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(709, 'external', 7, 'Mary White', NULL, 'mary.white@gmail.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(710, 'external', 7, 'Deborah Hill', 'XYZ Ltd', 'deborah.hill@xyz.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(711, 'external', 7, 'David Martin', 'Quality Tech', 'david.martin@qualitytech.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(712, 'external', 7, 'Michelle Thompson', 'Network Solutions', 'michelle.thompson@networksolutions.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(713, 'external', 7, 'Linda Moore', 'Smart Solutions', 'linda.moore@smartsolutions.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(714, 'external', 5, 'Michael Wright', 'Elite Solutions', 'michael.wright@elitesolutions.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(715, 'external', 7, 'Deborah Harris', 'Cloud Systems', 'deborah.harris@cloudsystems.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(716, 'external', 1, 'Matthew White', NULL, 'matthew.white@gmail.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(717, 'external', 3, 'Paul Martinez', 'Professional Services', 'paul.martinez@professionalservices.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(718, 'external', 5, 'Kimberly White', NULL, 'kimberly.white@gmail.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(719, 'external', 7, 'Charles Clark', NULL, 'charles.clark@gmail.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(720, 'external', 2, 'Linda Sanchez', 'Future Technologies', 'linda.sanchez@futuretechnologies.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(721, 'external', 7, 'Donald Taylor', 'Tech Solutions Inc', 'donald.taylor@techsolutions.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(722, 'external', 6, 'Patricia Johnson', NULL, 'patricia.johnson@gmail.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(723, 'external', 7, 'Patricia Smith', NULL, 'patricia.smith@gmail.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(724, 'external', 3, 'Brian Robinson', 'Tech Solutions Inc', 'brian.robinson@techsolutions.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(725, 'external', 4, 'Mark Scott', 'Elite Solutions', 'mark.scott@elitesolutions.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(726, 'external', 5, 'George Ramirez', NULL, 'george.ramirez@gmail.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(727, 'external', 7, 'Matthew King', 'Data Dynamics', 'matthew.king@datadynamics.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(728, 'external', 5, 'John Walker', NULL, 'john.walker@gmail.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(729, 'external', 3, 'James Perez', 'Future Technologies', 'james.perez@futuretechnologies.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(730, 'external', 5, 'Lisa Taylor', 'Enterprise Plus', 'lisa.taylor@enterpriseplus.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(731, 'external', 6, 'Susan Young', 'Premium Services', 'susan.young@premiumservices.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(732, 'external', 7, 'Kimberly Johnson', 'Digital Works', 'kimberly.johnson@digitalworks.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(733, 'external', 6, 'Mark Thomas', 'Innovation Labs', 'mark.thomas@innovationlabs.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(734, 'external', 7, 'Donald Thomas', 'Data Dynamics', 'donald.thomas@datadynamics.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(735, 'external', 2, 'Joshua Clark', 'Startup Hub', 'joshua.clark@startuphub.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(736, 'external', 1, 'Joseph Smith', 'Modern Enterprises', 'joseph.smith@modernenterprises.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(737, 'external', 2, 'Jennifer King', 'Advanced Networks', 'jennifer.king@advancednetworks.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(738, 'external', 3, 'Carol Torres', 'Elite Solutions', 'carol.torres@elitesolutions.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(739, 'external', 7, 'Charles Wilson', 'Tech Solutions Inc', 'charles.wilson@techsolutions.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(740, 'external', 2, 'Linda Harris', 'Elite Solutions', 'linda.harris@elitesolutions.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(741, 'external', 7, 'Thomas Sanchez', 'Future Technologies', 'thomas.sanchez@futuretechnologies.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(742, 'external', 7, 'Ronald Davis', NULL, 'ronald.davis@gmail.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(743, 'external', 7, 'Linda Perez', 'Global Enterprises', 'linda.perez@globalenterprises.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(744, 'external', 3, 'Charles Sanchez', 'Tech Solutions Inc', 'charles.sanchez@techsolutions.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(745, 'external', 4, 'William Miller', 'Quality Tech', 'william.miller@qualitytech.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(746, 'external', 5, 'Andrew Robinson', NULL, 'andrew.robinson@gmail.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(747, 'external', 6, 'Barbara King', 'Elite Solutions', 'barbara.king@elitesolutions.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(748, 'external', 7, 'Matthew Davis', 'Cloud Systems', 'matthew.davis@cloudsystems.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(749, 'external', 7, 'William Robinson', 'Premium Services', 'william.robinson@premiumservices.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(750, 'external', 3, 'Andrew Gonzalez', 'XYZ Ltd', 'andrew.gonzalez@xyz.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(751, 'external', 5, 'Margaret Wright', NULL, 'margaret.wright@gmail.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(752, 'external', 7, 'Ruth Hill', 'Elite Solutions', 'ruth.hill@elitesolutions.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(753, 'external', 6, 'Elizabeth Allen', 'Advanced Networks', 'elizabeth.allen@advancednetworks.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL);
INSERT INTO `tbl_user` (`user_id`, `user_type`, `department_id`, `name`, `company`, `email`, `password`, `status`, `user_role`, `created_at`, `phone`) VALUES
(754, 'external', 7, 'Helen Wright', 'Global Enterprises', 'helen.wright@globalenterprises.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(755, 'external', 7, 'Ronald Perez', 'Innovation Labs', 'ronald.perez@innovationlabs.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(756, 'external', 7, 'Kevin Wilson', 'Quality Tech', 'kevin.wilson@qualitytech.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(757, 'external', 7, 'Patricia Nguyen', 'Smart Solutions', 'patricia.nguyen@smartsolutions.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(758, 'external', 7, 'Donald Robinson', NULL, 'donald.robinson@gmail.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(759, 'external', 1, 'Brian Jones', 'Digital Works', 'brian.jones@digitalworks.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(760, 'external', 2, 'Ruth Clark', 'NextGen Systems', 'ruth.clark@nextgensystems.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(761, 'external', 7, 'Mark Ramirez', 'Cloud Systems', 'mark.ramirez@cloudsystems.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(762, 'external', 3, 'Andrew Thomas', NULL, 'andrew.thomas@gmail.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(763, 'external', 4, 'Ronald King', 'Smart Solutions', 'ronald.king@smartsolutions.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(764, 'external', 4, 'Patricia Walker', 'Quality Tech', 'patricia.walker@qualitytech.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(765, 'external', 5, 'Patricia Harris', 'Cloud Systems', 'patricia.harris@cloudsystems.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(766, 'external', 2, 'Mary Scott', NULL, 'mary.scott@gmail.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(767, 'external', 6, 'James Moore', 'Smart Solutions', 'james.moore@smartsolutions.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(768, 'external', 7, 'Brian Miller', 'Innovation Labs', 'brian.miller@innovationlabs.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(769, 'external', 7, 'Patricia Anderson', NULL, 'patricia.anderson@gmail.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(770, 'external', 7, 'Mary Wilson', 'Global Enterprises', 'mary.wilson@globalenterprises.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(771, 'external', 1, 'Patricia Sanchez', NULL, 'patricia.sanchez@gmail.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(772, 'external', 2, 'Donald Taylor', 'Quality Tech', 'donald.taylor@qualitytech.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(773, 'external', 5, 'Edward Nguyen', 'Smart Solutions', 'edward.nguyen@smartsolutions.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(774, 'external', 7, 'Kimberly Torres', 'Smart Solutions', 'kimberly.torres@smartsolutions.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(775, 'external', 3, 'Ruth Scott', NULL, 'ruth.scott@gmail.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(776, 'external', 7, 'Matthew Garcia', 'Advanced Networks', 'matthew.garcia@advancednetworks.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(777, 'external', 2, 'Edward Sanchez', 'Advanced Networks', 'edward.sanchez@advancednetworks.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(778, 'external', 3, 'Mary Rodriguez', 'Professional Services', 'mary.rodriguez@professionalservices.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(779, 'external', 7, 'Anthony White', 'Professional Services', 'anthony.white@professionalservices.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(780, 'external', 7, 'Thomas Jackson', 'Modern Enterprises', 'thomas.jackson@modernenterprises.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(781, 'external', 4, 'Jessica Lee', 'Enterprise Plus', 'jessica.lee@enterpriseplus.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(782, 'external', 5, 'Kenneth Johnson', NULL, 'kenneth.johnson@gmail.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(783, 'external', 5, 'Charles Lewis', 'Premium Services', 'charles.lewis@premiumservices.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(784, 'external', 7, 'Sandra Hernandez', 'Smart Solutions', 'sandra.hernandez@smartsolutions.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(785, 'external', 5, 'Kimberly Robinson', NULL, 'kimberly.robinson@gmail.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(786, 'external', 6, 'Jennifer Lee', 'Elite Solutions', 'jennifer.lee@elitesolutions.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(787, 'external', 7, 'Linda Moore', NULL, 'linda.moore@gmail.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(788, 'external', 7, 'Jessica Miller', NULL, 'jessica.miller@gmail.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(789, 'external', 2, 'Ruth Smith', 'Advanced Networks', 'ruth.smith@advancednetworks.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(790, 'external', 5, 'Robert Scott', 'Modern Enterprises', 'robert.scott@modernenterprises.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(791, 'external', 1, 'Mary Jackson', NULL, 'mary.jackson@gmail.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(792, 'external', 2, 'Elizabeth Allen', NULL, 'elizabeth.allen@gmail.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(793, 'external', 3, 'Kevin Taylor', 'Smart Solutions', 'kevin.taylor@smartsolutions.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(794, 'external', 7, 'Charles Clark', 'Digital Works', 'charles.clark@digitalworks.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(795, 'external', 4, 'Michelle Ramirez', 'Tech Solutions Inc', 'michelle.ramirez@techsolutions.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(796, 'external', 5, 'Mary Walker', NULL, 'mary.walker@gmail.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(797, 'external', 5, 'Michael Johnson', 'Digital Works', 'michael.johnson@digitalworks.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(798, 'external', 5, 'Kevin Jones', 'Startup Hub', 'kevin.jones@startuphub.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(799, 'external', 6, 'Sharon Torres', NULL, 'sharon.torres@gmail.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(800, 'external', 5, 'Matthew Scott', 'NextGen Systems', 'matthew.scott@nextgensystems.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(801, 'external', 7, 'Ronald Allen', NULL, 'ronald.allen@gmail.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(802, 'external', 7, 'George Clark', 'XYZ Ltd', 'george.clark@xyz.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(803, 'external', 7, 'Linda Walker', 'Premium Services', 'linda.walker@premiumservices.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(804, 'external', 7, 'Thomas Wright', NULL, 'thomas.wright@gmail.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(805, 'external', 7, 'Andrew Harris', 'Modern Enterprises', 'andrew.harris@modernenterprises.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(806, 'external', 6, 'Mary Wright', 'Digital Works', 'mary.wright@digitalworks.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(807, 'external', 2, 'Ronald Williams', 'Future Technologies', 'ronald.williams@futuretechnologies.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(808, 'external', 7, 'Patricia Taylor', 'Global Enterprises', 'patricia.taylor@globalenterprises.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(809, 'external', 2, 'Linda Wright', NULL, 'linda.wright@gmail.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(810, 'external', 7, 'Mark Wright', 'Advanced Networks', 'mark.wright@advancednetworks.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(811, 'external', 1, 'Donald Wright', 'Elite Solutions', 'donald.wright@elitesolutions.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(812, 'external', 7, 'Ruth Thompson', 'Enterprise Plus', 'ruth.thompson@enterpriseplus.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(813, 'external', 7, 'Ruth Wilson', 'Network Solutions', 'ruth.wilson@networksolutions.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(814, 'external', 5, 'Sarah Anderson', 'Elite Solutions', 'sarah.anderson@elitesolutions.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(815, 'external', 7, 'Robert Hill', 'NextGen Systems', 'robert.hill@nextgensystems.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(816, 'external', 7, 'Brian Nguyen', 'ABC Corporation', 'brian.nguyen@abcoration.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(817, 'external', 7, 'Brian Taylor', 'Data Dynamics', 'brian.taylor@datadynamics.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(818, 'external', 7, 'James Allen', NULL, 'james.allen@gmail.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(819, 'external', 7, 'Barbara Scott', 'Innovation Labs', 'barbara.scott@innovationlabs.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(820, 'external', 7, 'Kenneth Wright', 'Innovation Labs', 'kenneth.wright@innovationlabs.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(821, 'external', 5, 'Helen Williams', NULL, 'helen.williams@gmail.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(822, 'external', 4, 'Donald Brown', NULL, 'donald.brown@gmail.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(823, 'external', 2, 'Jennifer Smith', NULL, 'jennifer.smith@gmail.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(824, 'external', 3, 'Michelle Robinson', 'Elite Solutions', 'michelle.robinson@elitesolutions.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(825, 'external', 3, 'Elizabeth Martinez', 'Tech Solutions Inc', 'elizabeth.martinez@techsolutions.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(826, 'external', 7, 'Joshua Martin', 'Smart Solutions', 'joshua.martin@smartsolutions.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(827, 'external', 4, 'Dorothy Garcia', 'Network Solutions', 'dorothy.garcia@networksolutions.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(828, 'external', 7, 'Daniel Sanchez', 'Smart Solutions', 'daniel.sanchez@smartsolutions.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(829, 'external', 5, 'Thomas Johnson', NULL, 'thomas.johnson@gmail.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(830, 'external', 6, 'Dorothy King', 'Digital Works', 'dorothy.king@digitalworks.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(831, 'external', 5, 'Joshua Williams', NULL, 'joshua.williams@gmail.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(832, 'external', 7, 'Nancy Smith', 'Advanced Networks', 'nancy.smith@advancednetworks.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(833, 'external', 7, 'Nancy Young', NULL, 'nancy.young@gmail.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(834, 'external', 7, 'Kevin Jones', 'Professional Services', 'kevin.jones@professionalservices.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(835, 'external', 5, 'Paul Torres', 'Advanced Networks', 'paul.torres@advancednetworks.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(836, 'external', 7, 'Donna Moore', 'Enterprise Plus', 'donna.moore@enterpriseplus.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(837, 'external', 5, 'Karen Lopez', 'Professional Services', 'karen.lopez@professionalservices.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(838, 'external', 7, 'Carol Moore', 'Cloud Systems', 'carol.moore@cloudsystems.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(839, 'external', 7, 'Donna Flores', 'Tech Solutions Inc', 'donna.flores@techsolutions.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(840, 'external', 7, 'Susan Garcia', NULL, 'susan.garcia@gmail.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(841, 'external', 3, 'Matthew Thomas', 'Network Solutions', 'matthew.thomas@networksolutions.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(842, 'external', 7, 'Kenneth Wright', 'Elite Solutions', 'kenneth.wright@elitesolutions.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(843, 'external', 6, 'Linda Thompson', NULL, 'linda.thompson@gmail.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(844, 'external', 1, 'Christopher Garcia', 'Smart Solutions', 'christopher.garcia@smartsolutions.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(845, 'external', 4, 'Nancy White', 'Tech Solutions Inc', 'nancy.white@techsolutions.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(846, 'external', 2, 'Mary Rodriguez', 'Quality Tech', 'mary.rodriguez@qualitytech.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(847, 'external', 7, 'Andrew Williams', 'Tech Solutions Inc', 'andrew.williams@techsolutions.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(848, 'external', 2, 'Laura Brown', 'Data Dynamics', 'laura.brown@datadynamics.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(849, 'external', 2, 'Mark Hill', NULL, 'mark.hill@gmail.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(850, 'external', 3, 'Barbara Johnson', NULL, 'barbara.johnson@gmail.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(851, 'external', 5, 'Robert Jones', 'Future Technologies', 'robert.jones@futuretechnologies.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(852, 'external', 6, 'Mark Miller', 'Quality Tech', 'mark.miller@qualitytech.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(853, 'external', 7, 'Andrew Clark', 'Quality Tech', 'andrew.clark@qualitytech.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(854, 'external', 4, 'Mary Wilson', 'Enterprise Plus', 'mary.wilson@enterpriseplus.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(855, 'external', 5, 'Dorothy Hill', 'Enterprise Plus', 'dorothy.hill@enterpriseplus.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(856, 'external', 7, 'Michelle Robinson', 'Elite Solutions', 'michelle.robinson@elitesolutions.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(857, 'external', 7, 'Mark Johnson', NULL, 'mark.johnson@gmail.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(858, 'external', 5, 'Anthony Lopez', 'ABC Corporation', 'anthony.lopez@abcoration.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(859, 'external', 7, 'Karen Young', 'Global Enterprises', 'karen.young@globalenterprises.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(860, 'external', 4, 'William Johnson', 'Network Solutions', 'william.johnson@networksolutions.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(861, 'external', 5, 'Michael Miller', 'Smart Solutions', 'michael.miller@smartsolutions.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(862, 'external', 7, 'Helen Williams', 'Smart Solutions', 'helen.williams@smartsolutions.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(863, 'external', 6, 'Ruth Flores', 'Global Enterprises', 'ruth.flores@globalenterprises.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(864, 'external', 7, 'Kenneth Wright', 'Quality Tech', 'kenneth.wright@qualitytech.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(865, 'external', 1, 'Ruth Young', 'Quality Tech', 'ruth.young@qualitytech.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(866, 'external', 5, 'Betty Williams', 'ABC Corporation', 'betty.williams@abcoration.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(867, 'external', 6, 'Betty Hernandez', NULL, 'betty.hernandez@gmail.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(868, 'external', 2, 'Ronald Rodriguez', 'XYZ Ltd', 'ronald.rodriguez@xyz.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(869, 'external', 7, 'David Ramirez', 'NextGen Systems', 'david.ramirez@nextgensystems.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(870, 'external', 7, 'Betty Thomas', 'Premium Services', 'betty.thomas@premiumservices.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(871, 'external', 7, 'Joshua Williams', NULL, 'joshua.williams@gmail.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(872, 'external', 3, 'Susan Sanchez', NULL, 'susan.sanchez@gmail.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(873, 'external', 7, 'Dorothy Anderson', 'Startup Hub', 'dorothy.anderson@startuphub.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(874, 'external', 4, 'Edward Thomas', 'Smart Solutions', 'edward.thomas@smartsolutions.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(875, 'external', 7, 'Paul Moore', NULL, 'paul.moore@gmail.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(876, 'external', 5, 'Kimberly Hill', 'Global Enterprises', 'kimberly.hill@globalenterprises.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(877, 'external', 5, 'Dorothy Johnson', NULL, 'dorothy.johnson@gmail.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(878, 'external', 5, 'Christopher White', 'Elite Solutions', 'christopher.white@elitesolutions.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(879, 'external', 6, 'Betty Davis', 'Tech Solutions Inc', 'betty.davis@techsolutions.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(880, 'external', 7, 'Patricia Ramirez', 'XYZ Ltd', 'patricia.ramirez@xyz.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(881, 'external', 7, 'Karen Wilson', 'Quality Tech', 'karen.wilson@qualitytech.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(882, 'external', 7, 'Barbara Johnson', 'Digital Works', 'barbara.johnson@digitalworks.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(883, 'external', 1, 'Karen Clark', 'Smart Solutions', 'karen.clark@smartsolutions.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(884, 'external', 7, 'Michael Miller', 'Global Enterprises', 'michael.miller@globalenterprises.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(885, 'external', 7, 'Andrew Williams', 'Innovation Labs', 'andrew.williams@innovationlabs.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(886, 'external', 6, 'Barbara Martin', NULL, 'barbara.martin@gmail.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(887, 'external', 6, 'Christopher Rodriguez', NULL, 'christopher.rodriguez@gmail.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(888, 'external', 7, 'Lisa Davis', 'Startup Hub', 'lisa.davis@startuphub.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(889, 'external', 7, 'Mary Nguyen', 'Startup Hub', 'mary.nguyen@startuphub.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(890, 'external', 2, 'Steven Allen', 'Future Technologies', 'steven.allen@futuretechnologies.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(891, 'external', 3, 'Daniel Flores', 'XYZ Ltd', 'daniel.flores@xyz.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(892, 'external', 4, 'Jennifer Torres', 'Data Dynamics', 'jennifer.torres@datadynamics.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(893, 'external', 7, 'Richard White', 'XYZ Ltd', 'richard.white@xyz.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(894, 'external', 3, 'Barbara King', 'Digital Works', 'barbara.king@digitalworks.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(895, 'external', 7, 'Linda Jones', 'Elite Solutions', 'linda.jones@elitesolutions.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(896, 'external', 7, 'Donna Johnson', NULL, 'donna.johnson@gmail.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(897, 'external', 5, 'Helen White', 'Digital Works', 'helen.white@digitalworks.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(898, 'external', 2, 'Lisa Walker', 'NextGen Systems', 'lisa.walker@nextgensystems.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(899, 'external', 6, 'Michelle Jackson', 'Elite Solutions', 'michelle.jackson@elitesolutions.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(900, 'external', 7, 'Kimberly Young', 'XYZ Ltd', 'kimberly.young@xyz.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(901, 'external', 7, 'Daniel Miller', 'Professional Services', 'daniel.miller@professionalservices.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(902, 'external', 7, 'Donald Walker', NULL, 'donald.walker@gmail.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(903, 'external', 5, 'David Miller', 'Professional Services', 'david.miller@professionalservices.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(904, 'external', 1, 'Jennifer Allen', 'Smart Solutions', 'jennifer.allen@smartsolutions.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(905, 'external', 4, 'Elizabeth Rodriguez', 'Modern Enterprises', 'elizabeth.rodriguez@modernenterprises.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(906, 'external', 2, 'Lisa Williams', 'Premium Services', 'lisa.williams@premiumservices.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(907, 'external', 7, 'Sharon Jones', NULL, 'sharon.jones@gmail.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(908, 'external', 5, 'Michelle Thomas', NULL, 'michelle.thomas@gmail.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(909, 'external', 7, 'Mark Nguyen', 'Startup Hub', 'mark.nguyen@startuphub.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(910, 'external', 3, 'Brian Allen', 'Future Technologies', 'brian.allen@futuretechnologies.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(911, 'external', 5, 'Charles Brown', 'Smart Solutions', 'charles.brown@smartsolutions.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(912, 'external', 3, 'Kimberly Gonzalez', 'Global Enterprises', 'kimberly.gonzalez@globalenterprises.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(913, 'external', 3, 'Kevin Harris', 'Startup Hub', 'kevin.harris@startuphub.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(914, 'external', 7, 'Patricia Jones', 'Global Enterprises', 'patricia.jones@globalenterprises.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(915, 'external', 4, 'Mark Allen', NULL, 'mark.allen@gmail.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(916, 'external', 7, 'Donald Thomas', 'Professional Services', 'donald.thomas@professionalservices.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(917, 'external', 7, 'Sandra Scott', 'Digital Works', 'sandra.scott@digitalworks.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(918, 'external', 7, 'Linda Wright', NULL, 'linda.wright@gmail.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(919, 'external', 5, 'Michelle Scott', NULL, 'michelle.scott@gmail.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(920, 'external', 5, 'Michelle Lopez', 'Elite Solutions', 'michelle.lopez@elitesolutions.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(921, 'external', 5, 'George Lewis', 'Professional Services', 'george.lewis@professionalservices.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(922, 'external', 5, 'John Clark', 'Professional Services', 'john.clark@professionalservices.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(923, 'external', 6, 'Dorothy Torres', 'Cloud Systems', 'dorothy.torres@cloudsystems.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(924, 'external', 7, 'Helen Martinez', 'Smart Solutions', 'helen.martinez@smartsolutions.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(925, 'external', 7, 'Joseph Young', 'Professional Services', 'joseph.young@professionalservices.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(926, 'external', 1, 'Mark Martinez', 'Quality Tech', 'mark.martinez@qualitytech.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(927, 'external', 2, 'Andrew Jackson', 'Data Dynamics', 'andrew.jackson@datadynamics.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(928, 'external', 5, 'Thomas Thomas', 'Advanced Networks', 'thomas.thomas@advancednetworks.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(929, 'external', 3, 'William Davis', 'NextGen Systems', 'william.davis@nextgensystems.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(930, 'external', 4, 'Jennifer Lewis', 'XYZ Ltd', 'jennifer.lewis@xyz.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(931, 'external', 7, 'Sarah Martin', 'Global Enterprises', 'sarah.martin@globalenterprises.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(932, 'external', 7, 'Linda Jackson', 'Tech Solutions Inc', 'linda.jackson@techsolutions.com', '1234', 'active', 'customer', '2025-10-19 23:19:33', NULL),
(933, 'internal', 1, 'John Manager', NULL, 'john.manager@company.com', '1234', 'active', 'customer', '2025-10-20 22:19:20', NULL),
(934, 'internal', 7, 'Sarah Tech', NULL, 'sarah.tech@company.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(935, 'internal', 1, 'Mike Admin', NULL, 'mike.admin@company.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(936, 'internal', 5, 'Lisa Support', NULL, 'lisa.support@company.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(937, 'external', 7, 'Joseph Williams', 'Smart Solutions', 'joseph.williams@smartsolutions.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(938, 'external', 7, 'Donald Taylor', 'Future Technologies', 'donald.taylor@futuretechnologies.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(939, 'external', 6, 'Linda Young', 'Enterprise Plus', 'linda.young@enterpriseplus.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(940, 'external', 5, 'John Thompson', 'Advanced Networks', 'john.thompson@advancednetworks.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(941, 'external', 7, 'William Jackson', NULL, 'william.jackson@gmail.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(942, 'external', 7, 'Patricia Miller', 'Innovation Labs', 'patricia.miller@innovationlabs.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(943, 'external', 7, 'Dorothy Rodriguez', NULL, 'dorothy.rodriguez@gmail.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(944, 'external', 7, 'Brian Brown', 'Smart Solutions', 'brian.brown@smartsolutions.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(945, 'external', 5, 'Betty Thompson', 'ABC Corporation', 'betty.thompson@abcoration.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(946, 'external', 7, 'Helen Young', 'XYZ Ltd', 'helen.young@xyz.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(947, 'external', 7, 'Deborah Lee', 'Startup Hub', 'deborah.lee@startuphub.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(948, 'external', 5, 'Laura Ramirez', NULL, 'laura.ramirez@gmail.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(949, 'external', 7, 'George Gonzalez', 'ABC Corporation', 'george.gonzalez@abcoration.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(950, 'external', 7, 'Steven Smith', NULL, 'steven.smith@gmail.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(951, 'external', 7, 'Mary Thomas', 'XYZ Ltd', 'mary.thomas@xyz.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(952, 'external', 6, 'Donald Ramirez', NULL, 'donald.ramirez@gmail.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(953, 'external', 7, 'David Torres', 'Quality Tech', 'david.torres@qualitytech.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(954, 'external', 1, 'William Allen', 'Global Enterprises', 'william.allen@globalenterprises.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(955, 'external', 3, 'Linda Hernandez', 'Smart Solutions', 'linda.hernandez@smartsolutions.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(956, 'external', 7, 'Lisa Jackson', 'Advanced Networks', 'lisa.jackson@advancednetworks.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(957, 'external', 7, 'Michelle Young', 'Global Enterprises', 'michelle.young@globalenterprises.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(958, 'external', 2, 'Joshua Brown', 'NextGen Systems', 'joshua.brown@nextgensystems.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(959, 'external', 2, 'Jessica Nguyen', 'Elite Solutions', 'jessica.nguyen@elitesolutions.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(960, 'external', 4, 'Donald Harris', 'Startup Hub', 'donald.harris@startuphub.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(961, 'external', 7, 'Michael Miller', 'Future Technologies', 'michael.miller@futuretechnologies.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(962, 'external', 3, 'Thomas Brown', 'Global Enterprises', 'thomas.brown@globalenterprises.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(963, 'external', 7, 'Deborah Wright', NULL, 'deborah.wright@gmail.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(964, 'external', 5, 'Anthony Lopez', 'ABC Corporation', 'anthony.lopez@abcoration.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(965, 'external', 6, 'Michael Flores', 'Startup Hub', 'michael.flores@startuphub.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(966, 'external', 7, 'Elizabeth Sanchez', 'Premium Services', 'elizabeth.sanchez@premiumservices.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(967, 'external', 7, 'Matthew Flores', 'Tech Solutions Inc', 'matthew.flores@techsolutions.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(968, 'external', 7, 'Sandra Moore', 'Elite Solutions', 'sandra.moore@elitesolutions.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(969, 'external', 7, 'David Taylor', 'Advanced Networks', 'david.taylor@advancednetworks.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(970, 'external', 5, 'Joseph Gonzalez', NULL, 'joseph.gonzalez@gmail.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(971, 'external', 7, 'Kimberly Brown', 'Innovation Labs', 'kimberly.brown@innovationlabs.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(972, 'external', 3, 'Anthony Johnson', 'Smart Solutions', 'anthony.johnson@smartsolutions.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(973, 'external', 5, 'Charles Wilson', NULL, 'charles.wilson@gmail.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(974, 'external', 4, 'Carol Hill', 'XYZ Ltd', 'carol.hill@xyz.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(975, 'external', 7, 'David Garcia', 'Elite Solutions', 'david.garcia@elitesolutions.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(976, 'external', 7, 'Kenneth Robinson', 'Elite Solutions', 'kenneth.robinson@elitesolutions.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(977, 'external', 6, 'Donald Hernandez', 'Elite Solutions', 'donald.hernandez@elitesolutions.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(978, 'external', 7, 'Barbara Perez', 'XYZ Ltd', 'barbara.perez@xyz.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(979, 'external', 2, 'Carol Wilson', 'Data Dynamics', 'carol.wilson@datadynamics.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(980, 'external', 7, 'Patricia Sanchez', 'Future Technologies', 'patricia.sanchez@futuretechnologies.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(981, 'external', 7, 'Charles Allen', NULL, 'charles.allen@gmail.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(982, 'external', 5, 'Robert Martinez', 'Future Technologies', 'robert.martinez@futuretechnologies.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(983, 'external', 7, 'Susan Jackson', 'Tech Solutions Inc', 'susan.jackson@techsolutions.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(984, 'external', 6, 'Jessica Clark', 'Network Solutions', 'jessica.clark@networksolutions.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(985, 'external', 7, 'Brian Moore', 'Global Enterprises', 'brian.moore@globalenterprises.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(986, 'external', 1, 'Deborah King', 'NextGen Systems', 'deborah.king@nextgensystems.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(987, 'external', 7, 'Kevin Lopez', 'Network Solutions', 'kevin.lopez@networksolutions.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(988, 'external', 7, 'David Smith', NULL, 'david.smith@gmail.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(989, 'external', 2, 'Lisa Robinson', 'Professional Services', 'lisa.robinson@professionalservices.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(990, 'external', 7, 'Matthew Garcia', 'Future Technologies', 'matthew.garcia@futuretechnologies.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(991, 'external', 3, 'Anthony Miller', 'Elite Solutions', 'anthony.miller@elitesolutions.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(992, 'external', 3, 'Linda Flores', 'Startup Hub', 'linda.flores@startuphub.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(993, 'external', 7, 'Edward Jones', 'ABC Corporation', 'edward.jones@abcoration.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(994, 'external', 7, 'Mary Garcia', 'Data Dynamics', 'mary.garcia@datadynamics.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(995, 'external', 7, 'Elizabeth Hill', 'Advanced Networks', 'elizabeth.hill@advancednetworks.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(996, 'external', 4, 'Andrew Miller', 'Future Technologies', 'andrew.miller@futuretechnologies.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(997, 'external', 7, 'Steven Ramirez', 'Premium Services', 'steven.ramirez@premiumservices.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(998, 'external', 2, 'Donald Moore', 'Cloud Systems', 'donald.moore@cloudsystems.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(999, 'external', 3, 'Kenneth Taylor', 'Innovation Labs', 'kenneth.taylor@innovationlabs.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1000, 'external', 2, 'Mark Taylor', 'Global Enterprises', 'mark.taylor@globalenterprises.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1001, 'external', 7, 'Anthony Thompson', 'Network Solutions', 'anthony.thompson@networksolutions.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1002, 'external', 5, 'Charles Robinson', 'Innovation Labs', 'charles.robinson@innovationlabs.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1003, 'external', 6, 'Donna White', 'Quality Tech', 'donna.white@qualitytech.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1004, 'external', 7, 'Carol Harris', 'Enterprise Plus', 'carol.harris@enterpriseplus.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1005, 'external', 1, 'Steven Ramirez', 'Digital Works', 'steven.ramirez@digitalworks.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1006, 'external', 2, 'Deborah Jackson', 'Cloud Systems', 'deborah.jackson@cloudsystems.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1007, 'external', 3, 'Mark Perez', 'Advanced Networks', 'mark.perez@advancednetworks.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1008, 'external', 5, 'Michelle Sanchez', 'Professional Services', 'michelle.sanchez@professionalservices.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1009, 'external', 3, 'Charles Harris', NULL, 'charles.harris@gmail.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1010, 'external', 4, 'William Jackson', 'Premium Services', 'william.jackson@premiumservices.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1011, 'external', 5, 'David Wright', 'Future Technologies', 'david.wright@futuretechnologies.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1012, 'external', 7, 'Laura Smith', 'Professional Services', 'laura.smith@professionalservices.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1013, 'external', 2, 'Sarah Rodriguez', NULL, 'sarah.rodriguez@gmail.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1014, 'external', 7, 'Robert Nguyen', 'Digital Works', 'robert.nguyen@digitalworks.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1015, 'external', 7, 'Matthew Thomas', 'Cloud Systems', 'matthew.thomas@cloudsystems.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1016, 'external', 5, 'Daniel Garcia', 'Advanced Networks', 'daniel.garcia@advancednetworks.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1017, 'external', 7, 'Charles Moore', 'XYZ Ltd', 'charles.moore@xyz.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1018, 'external', 7, 'Kimberly Moore', 'ABC Corporation', 'kimberly.moore@abcoration.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1019, 'external', 7, 'Elizabeth Thompson', 'Startup Hub', 'elizabeth.thompson@startuphub.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1020, 'external', 5, 'Patricia Robinson', 'XYZ Ltd', 'patricia.robinson@xyz.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1021, 'external', 6, 'Ruth Sanchez', 'Quality Tech', 'ruth.sanchez@qualitytech.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1022, 'external', 7, 'Robert Ramirez', 'Smart Solutions', 'robert.ramirez@smartsolutions.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1023, 'external', 1, 'Carol Rodriguez', NULL, 'carol.rodriguez@gmail.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1024, 'external', 7, 'Margaret Ramirez', 'Smart Solutions', 'margaret.ramirez@smartsolutions.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1025, 'external', 2, 'George Walker', 'Professional Services', 'george.walker@professionalservices.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1026, 'external', 5, 'Barbara Harris', NULL, 'barbara.harris@gmail.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1027, 'external', 4, 'Richard Sanchez', 'Quality Tech', 'richard.sanchez@qualitytech.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1028, 'external', 3, 'Helen Thomas', 'Modern Enterprises', 'helen.thomas@modernenterprises.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1029, 'external', 7, 'John Johnson', 'Elite Solutions', 'john.johnson@elitesolutions.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1030, 'external', 7, 'Kenneth Nguyen', 'Cloud Systems', 'kenneth.nguyen@cloudsystems.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1031, 'external', 3, 'William Harris', 'Modern Enterprises', 'william.harris@modernenterprises.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1032, 'external', 7, 'Helen Nguyen', 'Network Solutions', 'helen.nguyen@networksolutions.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1033, 'external', 5, 'Donna Lewis', 'Startup Hub', 'donna.lewis@startuphub.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1034, 'external', 7, 'Matthew Brown', 'Tech Solutions Inc', 'matthew.brown@techsolutions.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1035, 'external', 4, 'Anthony Sanchez', NULL, 'anthony.sanchez@gmail.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1036, 'external', 7, 'Jessica Flores', NULL, 'jessica.flores@gmail.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1037, 'external', 4, 'Joshua Williams', 'NextGen Systems', 'joshua.williams@nextgensystems.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1038, 'external', 5, 'Dorothy Taylor', NULL, 'dorothy.taylor@gmail.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1039, 'external', 2, 'Sharon Scott', 'Data Dynamics', 'sharon.scott@datadynamics.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1040, 'external', 6, 'John Allen', 'Innovation Labs', 'john.allen@innovationlabs.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1041, 'external', 7, 'William Allen', NULL, 'william.allen@gmail.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1042, 'external', 7, 'James Hernandez', 'Data Dynamics', 'james.hernandez@datadynamics.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1043, 'external', 1, 'Richard Thompson', NULL, 'richard.thompson@gmail.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1044, 'external', 2, 'Linda Lewis', NULL, 'linda.lewis@gmail.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1045, 'external', 3, 'Lisa Thompson', 'Elite Solutions', 'lisa.thompson@elitesolutions.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1046, 'external', 5, 'Thomas Lewis', NULL, 'thomas.lewis@gmail.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1047, 'external', 7, 'Joshua Taylor', 'ABC Corporation', 'joshua.taylor@abcoration.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1048, 'external', 4, 'Andrew Garcia', NULL, 'andrew.garcia@gmail.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1049, 'external', 2, 'David Johnson', 'Premium Services', 'david.johnson@premiumservices.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1050, 'external', 7, 'Dorothy Davis', NULL, 'dorothy.davis@gmail.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1051, 'external', 2, 'Michael Hill', 'ABC Corporation', 'michael.hill@abcoration.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1052, 'external', 5, 'Donna Jackson', 'NextGen Systems', 'donna.jackson@nextgensystems.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1053, 'external', 5, 'Andrew Hernandez', 'Quality Tech', 'andrew.hernandez@qualitytech.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1054, 'external', 7, 'Michelle Clark', NULL, 'michelle.clark@gmail.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1055, 'external', 5, 'Betty Flores', 'Elite Solutions', 'betty.flores@elitesolutions.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1056, 'external', 6, 'Jessica Clark', NULL, 'jessica.clark@gmail.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1057, 'external', 7, 'Michael Lee', NULL, 'michael.lee@gmail.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1058, 'external', 5, 'Joshua Lopez', 'ABC Corporation', 'joshua.lopez@abcoration.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1059, 'external', 7, 'Karen Thompson', NULL, 'karen.thompson@gmail.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1060, 'external', 5, 'Joshua Flores', 'ABC Corporation', 'joshua.flores@abcoration.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1061, 'external', 1, 'Helen Allen', 'Premium Services', 'helen.allen@premiumservices.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1062, 'external', 4, 'Ronald Torres', 'Smart Solutions', 'ronald.torres@smartsolutions.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1063, 'external', 7, 'Edward Wright', 'Quality Tech', 'edward.wright@qualitytech.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1064, 'external', 7, 'Ronald Garcia', NULL, 'ronald.garcia@gmail.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1065, 'external', 7, 'William Taylor', 'Modern Enterprises', 'william.taylor@modernenterprises.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1066, 'external', 5, 'Donna Wright', NULL, 'donna.wright@gmail.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1067, 'external', 7, 'Jessica Smith', NULL, 'jessica.smith@gmail.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1068, 'external', 7, 'Jessica Smith', 'Modern Enterprises', 'jessica.smith@modernenterprises.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1069, 'external', 2, 'Jessica Rodriguez', 'Tech Solutions Inc', 'jessica.rodriguez@techsolutions.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1070, 'external', 5, 'Jessica Martin', 'Professional Services', 'jessica.martin@professionalservices.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1071, 'external', 2, 'Brian Hernandez', NULL, 'brian.hernandez@gmail.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1072, 'external', 3, 'Donna Scott', 'Cloud Systems', 'donna.scott@cloudsystems.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1073, 'external', 7, 'George Martin', NULL, 'george.martin@gmail.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1074, 'external', 5, 'Margaret Rodriguez', NULL, 'margaret.rodriguez@gmail.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1075, 'external', 4, 'Charles Anderson', NULL, 'charles.anderson@gmail.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1076, 'external', 5, 'Betty Robinson', 'Startup Hub', 'betty.robinson@startuphub.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1077, 'external', 5, 'Donna Robinson', 'Elite Solutions', 'donna.robinson@elitesolutions.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1078, 'external', 6, 'Elizabeth Hill', 'Future Technologies', 'elizabeth.hill@futuretechnologies.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1079, 'external', 5, 'Sarah Hernandez', 'Smart Solutions', 'sarah.hernandez@smartsolutions.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1080, 'external', 5, 'Margaret Moore', 'Innovation Labs', 'margaret.moore@innovationlabs.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1081, 'external', 3, 'Linda Clark', NULL, 'linda.clark@gmail.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1082, 'external', 5, 'Ronald Lee', NULL, 'ronald.lee@gmail.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1083, 'external', 2, 'Barbara Lopez', 'Modern Enterprises', 'barbara.lopez@modernenterprises.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1084, 'external', 4, 'Laura Harris', 'Network Solutions', 'laura.harris@networksolutions.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1085, 'external', 7, 'Anthony Anderson', 'Cloud Systems', 'anthony.anderson@cloudsystems.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1086, 'external', 7, 'Lisa Robinson', 'Enterprise Plus', 'lisa.robinson@enterpriseplus.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1087, 'external', 7, 'Ronald White', 'Modern Enterprises', 'ronald.white@modernenterprises.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1088, 'external', 1, 'Christopher Smith', 'Data Dynamics', 'christopher.smith@datadynamics.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1089, 'external', 2, 'Sharon Miller', 'Innovation Labs', 'sharon.miller@innovationlabs.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1090, 'external', 2, 'Matthew Miller', 'Cloud Systems', 'matthew.miller@cloudsystems.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1091, 'external', 6, 'Sarah Brown', 'Innovation Labs', 'sarah.brown@innovationlabs.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1092, 'external', 7, 'Jennifer Clark', 'ABC Corporation', 'jennifer.clark@abcoration.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1093, 'external', 5, 'James Young', 'Global Enterprises', 'james.young@globalenterprises.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1094, 'external', 7, 'Betty Clark', 'Tech Solutions Inc', 'betty.clark@techsolutions.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL);
INSERT INTO `tbl_user` (`user_id`, `user_type`, `department_id`, `name`, `company`, `email`, `password`, `status`, `user_role`, `created_at`, `phone`) VALUES
(1095, 'external', 5, 'James Martin', 'Premium Services', 'james.martin@premiumservices.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1096, 'external', 7, 'Charles Davis', 'Elite Solutions', 'charles.davis@elitesolutions.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1097, 'external', 7, 'Linda Harris', NULL, 'linda.harris@gmail.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1098, 'external', 4, 'Ronald Perez', 'Quality Tech', 'ronald.perez@qualitytech.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1099, 'external', 7, 'William King', 'Professional Services', 'william.king@professionalservices.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1100, 'external', 3, 'Patricia Harris', 'Innovation Labs', 'patricia.harris@innovationlabs.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1101, 'external', 5, 'David Wilson', 'Data Dynamics', 'david.wilson@datadynamics.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1102, 'external', 7, 'Thomas Lewis', 'Network Solutions', 'thomas.lewis@networksolutions.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1103, 'external', 7, 'Nancy Johnson', 'Quality Tech', 'nancy.johnson@qualitytech.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1104, 'external', 7, 'Susan Nguyen', 'Cloud Systems', 'susan.nguyen@cloudsystems.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1105, 'external', 4, 'Lisa Lewis', 'NextGen Systems', 'lisa.lewis@nextgensystems.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1106, 'external', 7, 'Elizabeth Wright', 'XYZ Ltd', 'elizabeth.wright@xyz.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1107, 'external', 5, 'Patricia Walker', NULL, 'patricia.walker@gmail.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1108, 'external', 7, 'James Robinson', 'Premium Services', 'james.robinson@premiumservices.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1109, 'external', 7, 'David Gonzalez', 'Quality Tech', 'david.gonzalez@qualitytech.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1110, 'external', 6, 'Richard Miller', 'Future Technologies', 'richard.miller@futuretechnologies.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1111, 'external', 7, 'Dorothy Lee', 'Professional Services', 'dorothy.lee@professionalservices.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1112, 'external', 5, 'Sarah Johnson', 'Data Dynamics', 'sarah.johnson@datadynamics.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1113, 'external', 7, 'Joseph Young', 'Elite Solutions', 'joseph.young@elitesolutions.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1114, 'external', 1, 'Donna Smith', 'Global Enterprises', 'donna.smith@globalenterprises.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1115, 'external', 7, 'Daniel Walker', NULL, 'daniel.walker@gmail.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1116, 'external', 2, 'Kevin Clark', 'ABC Corporation', 'kevin.clark@abcoration.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1117, 'external', 3, 'Richard Harris', 'Innovation Labs', 'richard.harris@innovationlabs.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1118, 'external', 4, 'Jennifer Davis', 'Elite Solutions', 'jennifer.davis@elitesolutions.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1119, 'external', 7, 'Jessica Hill', 'Advanced Networks', 'jessica.hill@advancednetworks.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1120, 'external', 7, 'Sandra Williams', 'Future Technologies', 'sandra.williams@futuretechnologies.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1121, 'external', 5, 'Ronald Walker', 'Digital Works', 'ronald.walker@digitalworks.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1122, 'external', 4, 'Nancy Anderson', 'XYZ Ltd', 'nancy.anderson@xyz.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1123, 'external', 7, 'Betty Rodriguez', 'Innovation Labs', 'betty.rodriguez@innovationlabs.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1124, 'external', 5, 'Kevin Garcia', NULL, 'kevin.garcia@gmail.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1125, 'external', 2, 'Laura Hernandez', 'Tech Solutions Inc', 'laura.hernandez@techsolutions.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1126, 'external', 5, 'Kevin Torres', 'Cloud Systems', 'kevin.torres@cloudsystems.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1127, 'external', 7, 'Betty Thompson', 'Cloud Systems', 'betty.thompson@cloudsystems.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1128, 'external', 7, 'Anthony Clark', 'Network Solutions', 'anthony.clark@networksolutions.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1129, 'external', 6, 'Mary Flores', 'Data Dynamics', 'mary.flores@datadynamics.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1130, 'external', 3, 'Nancy Harris', 'NextGen Systems', 'nancy.harris@nextgensystems.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1131, 'external', 7, 'Paul Robinson', 'Network Solutions', 'paul.robinson@networksolutions.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1132, 'external', 7, 'Sharon Lopez', NULL, 'sharon.lopez@gmail.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1133, 'external', 6, 'Barbara Jones', NULL, 'barbara.jones@gmail.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1134, 'external', 7, 'Carol Hernandez', 'Elite Solutions', 'carol.hernandez@elitesolutions.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1135, 'external', 5, 'John Lopez', 'Smart Solutions', 'john.lopez@smartsolutions.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1136, 'external', 1, 'Kimberly Lewis', 'Future Technologies', 'kimberly.lewis@futuretechnologies.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1137, 'external', 7, 'Linda Torres', 'Smart Solutions', 'linda.torres@smartsolutions.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1138, 'external', 2, 'Edward Wilson', 'Cloud Systems', 'edward.wilson@cloudsystems.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1139, 'external', 3, 'Laura Harris', 'NextGen Systems', 'laura.harris@nextgensystems.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1140, 'external', 7, 'Donald Anderson', 'Smart Solutions', 'donald.anderson@smartsolutions.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1141, 'external', 2, 'Dorothy Smith', 'NextGen Systems', 'dorothy.smith@nextgensystems.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1142, 'external', 2, 'Anthony Smith', NULL, 'anthony.smith@gmail.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1143, 'external', 4, 'Michael Robinson', 'Enterprise Plus', 'michael.robinson@enterpriseplus.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1144, 'external', 5, 'Sandra Garcia', 'Startup Hub', 'sandra.garcia@startuphub.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1145, 'external', 5, 'Jennifer Lopez', 'Tech Solutions Inc', 'jennifer.lopez@techsolutions.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1146, 'external', 6, 'David Robinson', NULL, 'david.robinson@gmail.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1147, 'external', 4, 'Joseph Lewis', NULL, 'joseph.lewis@gmail.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1148, 'external', 6, 'Margaret Harris', NULL, 'margaret.harris@gmail.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1149, 'external', 7, 'Laura Wilson', NULL, 'laura.wilson@gmail.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1150, 'external', 7, 'Thomas Gonzalez', 'Network Solutions', 'thomas.gonzalez@networksolutions.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1151, 'external', 7, 'Margaret Gonzalez', 'Elite Solutions', 'margaret.gonzalez@elitesolutions.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1152, 'external', 7, 'Steven Harris', 'Data Dynamics', 'steven.harris@datadynamics.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1153, 'external', 1, 'Deborah Thomas', 'Network Solutions', 'deborah.thomas@networksolutions.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1154, 'external', 7, 'Linda Johnson', 'Smart Solutions', 'linda.johnson@smartsolutions.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1155, 'external', 2, 'William Lewis', 'ABC Corporation', 'william.lewis@abcoration.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1156, 'external', 2, 'Jessica Lopez', 'Advanced Networks', 'jessica.lopez@advancednetworks.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1157, 'external', 7, 'Lisa Walker', NULL, 'lisa.walker@gmail.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1158, 'external', 7, 'Matthew Martinez', NULL, 'matthew.martinez@gmail.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1159, 'external', 3, 'Edward Brown', 'Tech Solutions Inc', 'edward.brown@techsolutions.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1160, 'external', 4, 'John Young', 'NextGen Systems', 'john.young@nextgensystems.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1161, 'external', 5, 'Paul Wright', 'Professional Services', 'paul.wright@professionalservices.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1162, 'external', 6, 'Helen Anderson', 'Cloud Systems', 'helen.anderson@cloudsystems.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1163, 'external', 7, 'Carol Taylor', 'Elite Solutions', 'carol.taylor@elitesolutions.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1164, 'external', 5, 'Deborah Allen', 'Innovation Labs', 'deborah.allen@innovationlabs.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1165, 'external', 6, 'Daniel Torres', 'Global Enterprises', 'daniel.torres@globalenterprises.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1166, 'external', 7, 'Laura Flores', NULL, 'laura.flores@gmail.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1167, 'external', 1, 'Deborah Jackson', 'Modern Enterprises', 'deborah.jackson@modernenterprises.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1168, 'external', 2, 'John Allen', 'ABC Corporation', 'john.allen@abcoration.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1169, 'external', 3, 'Mark Hernandez', 'Global Enterprises', 'mark.hernandez@globalenterprises.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1170, 'external', 5, 'Dorothy Johnson', 'Elite Solutions', 'dorothy.johnson@elitesolutions.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1171, 'external', 7, 'Ruth Jones', 'Network Solutions', 'ruth.jones@networksolutions.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1172, 'external', 5, 'David Nguyen', 'Premium Services', 'david.nguyen@premiumservices.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1173, 'external', 7, 'Thomas Miller', 'Global Enterprises', 'thomas.miller@globalenterprises.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1174, 'external', 7, 'John Jackson', 'ABC Corporation', 'john.jackson@abcoration.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1175, 'external', 7, 'Joseph Torres', 'Professional Services', 'joseph.torres@professionalservices.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1176, 'external', 4, 'Lisa Robinson', 'NextGen Systems', 'lisa.robinson@nextgensystems.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1177, 'external', 2, 'Sarah Sanchez', 'Professional Services', 'sarah.sanchez@professionalservices.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1178, 'external', 7, 'Anthony Garcia', 'Professional Services', 'anthony.garcia@professionalservices.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1179, 'external', 7, 'Kevin Nguyen', 'Global Enterprises', 'kevin.nguyen@globalenterprises.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1180, 'external', 7, 'Sandra Wilson', 'Elite Solutions', 'sandra.wilson@elitesolutions.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1181, 'external', 5, 'Joshua Allen', 'Tech Solutions Inc', 'joshua.allen@techsolutions.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1182, 'external', 7, 'William Williams', 'Startup Hub', 'william.williams@startuphub.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1183, 'external', 7, 'Elizabeth Young', 'Digital Works', 'elizabeth.young@digitalworks.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1184, 'external', 7, 'Sharon Jackson', NULL, 'sharon.jackson@gmail.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1185, 'external', 7, 'Matthew Lee', NULL, 'matthew.lee@gmail.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1186, 'external', 7, 'Carol Walker', 'Professional Services', 'carol.walker@professionalservices.com', '1234', 'active', 'customer', '2025-10-20 22:19:21', NULL),
(1187, 'internal', 1, 'John Evaluator', NULL, 'evaluator@company.com', '1234', 'active', 'evaluator', '2025-11-06 20:36:22', NULL),
(1188, 'internal', 1, 'Sarah Dept Head', NULL, 'depthead@company.com', '1234', 'active', 'department_head', '2025-11-06 20:36:22', NULL),
(1189, 'internal', 7, 'Mike IT Head', NULL, 'ithead@company.com', '1234', 'active', 'department_head', '2025-11-06 20:36:22', NULL),
(1190, 'internal', 2, 'Lisa Finance Head', NULL, 'financehead@company.com', '1234', 'active', 'department_head', '2025-11-06 20:36:22', NULL),
(1191, 'internal', 1, 'John Evaluator', NULL, 'evaluator@company.com', '1234', 'active', 'evaluator', '2025-11-06 20:54:49', NULL),
(1192, 'internal', 1, 'Sarah Dept Head', NULL, 'depthead@company.com', '1234', 'active', 'department_head', '2025-11-06 20:54:49', NULL),
(1193, 'internal', 7, 'Mike IT Head', NULL, 'ithead@company.com', '1234', 'active', 'department_head', '2025-11-06 20:54:49', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_product`
--

CREATE TABLE `tbl_product` (
  `product_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `model` varchar(100) DEFAULT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `manufacturer` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_customer_product`
--

CREATE TABLE `tbl_customer_product` (
  `customer_product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `purchase_date` date DEFAULT NULL,
  `warranty_start` date DEFAULT NULL,
  `warranty_end` date DEFAULT NULL,
  `status` enum('active','inactive','warranty_expired','replaced') DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_ticket_product`
--

CREATE TABLE `tbl_ticket_product` (
  `ticket_product_id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `customer_product_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `action_type` enum('repair','warranty_claim','purchase','inquiry','other') DEFAULT 'repair',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_checklist_template`
--

CREATE TABLE `tbl_checklist_template` (
  `template_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_checklist_template_step`
--

CREATE TABLE `tbl_checklist_template_step` (
  `step_id` int(11) NOT NULL,
  `template_id` int(11) NOT NULL,
  `step_order` int(11) NOT NULL DEFAULT 1,
  `description` varchar(255) NOT NULL,
  `is_required` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tbl_department`
--
ALTER TABLE `tbl_department`
  ADD PRIMARY KEY (`department_id`),
  ADD UNIQUE KEY `department_name` (`department_name`);

--
-- Indexes for table `tbl_department_routing`
--
ALTER TABLE `tbl_department_routing`
  ADD PRIMARY KEY (`routing_id`),
  ADD UNIQUE KEY `unique_category` (`category`),
  ADD KEY `fk_routing_department` (`target_department_id`);

--
-- Indexes for table `tbl_faq`
--
ALTER TABLE `tbl_faq`
  ADD PRIMARY KEY (`faq_id`);

--
-- Indexes for table `tbl_technician`
--
ALTER TABLE `tbl_technician`
  ADD PRIMARY KEY (`technician_id`);

--
-- Indexes for table `tbl_ticket`
--
ALTER TABLE `tbl_ticket`
  ADD PRIMARY KEY (`ticket_id`),
  ADD KEY `tbl_ticket_ibfk_1` (`user_id`),
  ADD KEY `tbl_ticket_ibfk_2` (`assigned_technician_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `tbl_ticket_checklist`
--
ALTER TABLE `tbl_ticket_checklist`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `ticket_id` (`ticket_id`);

--
-- Indexes for table `tbl_ticket_comment`
--
ALTER TABLE `tbl_ticket_comment`
  ADD PRIMARY KEY (`comment_id`),
  ADD KEY `ticket_id` (`ticket_id`);

--
-- Indexes for table `tbl_ticket_escalation`
--
ALTER TABLE `tbl_ticket_escalation`
  ADD PRIMARY KEY (`escalation_id`),
  ADD KEY `tbl_ticket_escalation_ibfk_1` (`ticket_id`),
  ADD KEY `tbl_ticket_escalation_ibfk_2` (`prev_technician_id`),
  ADD KEY `tbl_ticket_escalation_ibfk_3` (`new_technician_id`),
  ADD KEY `tbl_ticket_escalation_ibfk_4` (`prev_department_id`),
  ADD KEY `tbl_ticket_escalation_ibfk_5` (`new_department_id`),
  ADD KEY `idx_escalator_id` (`escalator_id`);

--
-- Indexes for table `tbl_ticket_logs`
--
ALTER TABLE `tbl_ticket_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `ticket_id` (`ticket_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `user_role` (`user_role`),
  ADD KEY `action_type` (`action_type`);

--
-- Indexes for table `tbl_ticket_reply`
--
ALTER TABLE `tbl_ticket_reply`
  ADD PRIMARY KEY (`reply_id`),
  ADD KEY `tbl_ticket_reply_ibfk_1` (`ticket_id`),
  ADD KEY `idx_replier_id` (`replier_id`);

--
-- Indexes for table `tbl_user`
--
ALTER TABLE `tbl_user`
  ADD PRIMARY KEY (`user_id`),
  ADD KEY `tbl_user_ibfk_1` (`department_id`);

--
-- Indexes for table `tbl_product`
--
ALTER TABLE `tbl_product`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `idx_serial_number` (`serial_number`),
  ADD KEY `idx_category` (`category`);

--
-- Indexes for table `tbl_customer_product`
--
ALTER TABLE `tbl_customer_product`
  ADD PRIMARY KEY (`customer_product_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_product_id` (`product_id`),
  ADD KEY `idx_warranty_end` (`warranty_end`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `tbl_ticket_product`
--
ALTER TABLE `tbl_ticket_product`
  ADD PRIMARY KEY (`ticket_product_id`),
  ADD KEY `idx_ticket_id` (`ticket_id`),
  ADD KEY `idx_customer_product_id` (`customer_product_id`),
  ADD KEY `idx_product_id` (`product_id`),
  ADD KEY `idx_action_type` (`action_type`);

--
-- Indexes for table `tbl_checklist_template`
--
ALTER TABLE `tbl_checklist_template`
  ADD PRIMARY KEY (`template_id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_department_id` (`department_id`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `tbl_checklist_template_step`
--
ALTER TABLE `tbl_checklist_template_step`
  ADD PRIMARY KEY (`step_id`),
  ADD KEY `idx_template_id` (`template_id`),
  ADD KEY `idx_step_order` (`step_order`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tbl_department`
--
ALTER TABLE `tbl_department`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `tbl_department_routing`
--
ALTER TABLE `tbl_department_routing`
  MODIFY `routing_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `tbl_faq`
--
ALTER TABLE `tbl_faq`
  MODIFY `faq_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_technician`
--
ALTER TABLE `tbl_technician`
  MODIFY `technician_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `tbl_ticket`
--
ALTER TABLE `tbl_ticket`
  MODIFY `ticket_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=656;

--
-- AUTO_INCREMENT for table `tbl_ticket_checklist`
--
ALTER TABLE `tbl_ticket_checklist`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tbl_ticket_comment`
--
ALTER TABLE `tbl_ticket_comment`
  MODIFY `comment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tbl_ticket_escalation`
--
ALTER TABLE `tbl_ticket_escalation`
  MODIFY `escalation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `tbl_ticket_logs`
--
ALTER TABLE `tbl_ticket_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT for table `tbl_ticket_reply`
--
ALTER TABLE `tbl_ticket_reply`
  MODIFY `reply_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=92;

--
-- AUTO_INCREMENT for table `tbl_user`
--
ALTER TABLE `tbl_user`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1195;

--
-- AUTO_INCREMENT for table `tbl_product`
--
ALTER TABLE `tbl_product`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_customer_product`
--
ALTER TABLE `tbl_customer_product`
  MODIFY `customer_product_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_ticket_product`
--
ALTER TABLE `tbl_ticket_product`
  MODIFY `ticket_product_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_checklist_template`
--
ALTER TABLE `tbl_checklist_template`
  MODIFY `template_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_checklist_template_step`
--
ALTER TABLE `tbl_checklist_template_step`
  MODIFY `step_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tbl_department_routing`
--
ALTER TABLE `tbl_department_routing`
  ADD CONSTRAINT `fk_routing_department` FOREIGN KEY (`target_department_id`) REFERENCES `tbl_department` (`department_id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_ticket`
--
ALTER TABLE `tbl_ticket`
  ADD CONSTRAINT `tbl_ticket_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `tbl_user` (`user_id`),
  ADD CONSTRAINT `tbl_ticket_ibfk_2` FOREIGN KEY (`assigned_technician_id`) REFERENCES `tbl_technician` (`technician_id`);

--
-- Constraints for table `tbl_ticket_checklist`
--
ALTER TABLE `tbl_ticket_checklist`
  ADD CONSTRAINT `tbl_ticket_checklist_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tbl_ticket` (`ticket_id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_ticket_comment`
--
ALTER TABLE `tbl_ticket_comment`
  ADD CONSTRAINT `tbl_ticket_comment_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tbl_ticket` (`ticket_id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_ticket_reply`
--
ALTER TABLE `tbl_ticket_reply`
  ADD CONSTRAINT `tbl_ticket_reply_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tbl_ticket` (`ticket_id`);

--
-- Constraints for table `tbl_user`
--
ALTER TABLE `tbl_user`
  ADD CONSTRAINT `tbl_user_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `tbl_department` (`department_id`);

--
-- Constraints for table `tbl_customer_product`
--
ALTER TABLE `tbl_customer_product`
  ADD CONSTRAINT `tbl_customer_product_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `tbl_user` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tbl_customer_product_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `tbl_product` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_ticket_product`
--
ALTER TABLE `tbl_ticket_product`
  ADD CONSTRAINT `tbl_ticket_product_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tbl_ticket` (`ticket_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tbl_ticket_product_ibfk_2` FOREIGN KEY (`customer_product_id`) REFERENCES `tbl_customer_product` (`customer_product_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tbl_ticket_product_ibfk_3` FOREIGN KEY (`product_id`) REFERENCES `tbl_product` (`product_id`) ON DELETE SET NULL;

--
-- Constraints for table `tbl_checklist_template`
--
ALTER TABLE `tbl_checklist_template`
  ADD CONSTRAINT `tbl_checklist_template_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `tbl_department` (`department_id`) ON DELETE SET NULL;

--
-- Constraints for table `tbl_checklist_template_step`
--
ALTER TABLE `tbl_checklist_template_step`
  ADD CONSTRAINT `tbl_checklist_template_step_ibfk_1` FOREIGN KEY (`template_id`) REFERENCES `tbl_checklist_template` (`template_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
