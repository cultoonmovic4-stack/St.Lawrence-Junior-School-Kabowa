-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 06, 2026 at 09:30 AM
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
-- Database: `st_lawrence_school`
--

-- Drop existing tables if they exist to prevent conflicts
DROP TABLE IF EXISTS `activity_logs`;
DROP TABLE IF EXISTS `admin_activity_logs`;
DROP TABLE IF EXISTS `admission_applications`;
DROP TABLE IF EXISTS `announcements`;
DROP TABLE IF EXISTS `attendance`;
DROP TABLE IF EXISTS `calendar_events`;
DROP TABLE IF EXISTS `classes`;
DROP TABLE IF EXISTS `contact_replies`;
DROP TABLE IF EXISTS `contact_submissions`;
DROP TABLE IF EXISTS `departments`;
DROP TABLE IF EXISTS `email_logs`;
DROP TABLE IF EXISTS `events`;
DROP TABLE IF EXISTS `exams`;
DROP TABLE IF EXISTS `exam_results`;
DROP TABLE IF EXISTS `expenses`;
DROP TABLE IF EXISTS `fees_structure`;
DROP TABLE IF EXISTS `fee_payments`;
DROP TABLE IF EXISTS `file_uploads`;
DROP TABLE IF EXISTS `gallery_images`;
DROP TABLE IF EXISTS `library_resources`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `parents_guardians`;
DROP TABLE IF EXISTS `permissions`;
DROP TABLE IF EXISTS `roles`;
DROP TABLE IF EXISTS `role_permissions`;
DROP TABLE IF EXISTS `settings`;
DROP TABLE IF EXISTS `sms_logs`;
DROP TABLE IF EXISTS `students`;
DROP TABLE IF EXISTS `subjects`;
DROP TABLE IF EXISTS `teachers`;
DROP TABLE IF EXISTS `testimonials`;
DROP TABLE IF EXISTS `timetables`;
DROP TABLE IF EXISTS `users`;

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(100) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `table_name`, `record_id`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'login', NULL, NULL, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 13:50:18'),
(2, 1, 'logout', NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 15:32:59'),
(3, 1, 'login', NULL, NULL, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 15:38:02'),
(4, 1, 'create', 'events', 2, 'Created event: Term 1 Opening', NULL, NULL, '2026-01-11 16:36:27'),
(5, 1, 'create', 'events', 3, 'Created event: Visitation Day', NULL, NULL, '2026-01-11 16:41:34'),
(6, 1, 'create', 'events', 4, 'Created event: End of Term 1', NULL, NULL, '2026-01-11 16:42:53'),
(7, 1, 'create', 'events', 5, 'Created event: Term 1 Opening', NULL, NULL, '2026-01-11 16:48:14'),
(8, 1, 'create', 'events', 6, 'Created event: Term 1 Opening', NULL, NULL, '2026-01-11 16:48:56'),
(9, 1, 'create', 'events', 7, 'Created event: Visitation Day', NULL, NULL, '2026-01-11 17:10:16'),
(10, 1, 'create', 'events', 8, 'Created event: End of Term 1', NULL, NULL, '2026-01-11 17:11:40'),
(11, 1, 'logout', NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 22:22:07'),
(12, 1, 'login', NULL, NULL, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 22:28:13'),
(13, 1, 'login', NULL, NULL, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 22:29:06'),
(14, 1, 'logout', NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 22:36:02'),
(15, 1, 'login', NULL, NULL, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 22:36:06'),
(16, 1, 'logout', NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 23:29:56'),
(17, 2, 'login', NULL, NULL, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 23:30:08'),
(18, 2, 'logout', NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 23:30:48'),
(19, 1, 'login', NULL, NULL, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 23:30:56'),
(20, 1, 'user_created', NULL, NULL, 'Created new user: Teacher', '::1', NULL, '2026-01-11 23:41:47'),
(21, 1, 'logout', NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 23:41:54'),
(22, 3, 'login', NULL, NULL, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 23:42:05'),
(23, 3, 'logout', NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 23:50:38'),
(24, 1, 'login', NULL, NULL, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 23:50:42'),
(25, 1, 'login', NULL, NULL, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-18 15:25:24'),
(26, 1, 'login', NULL, NULL, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-21 06:22:50'),
(27, 1, 'logout', NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 07:47:22'),
(28, 1, 'login', NULL, NULL, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 09:48:54'),
(29, 1, 'logout', NULL, NULL, 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 10:07:54'),
(30, 1, 'login', NULL, NULL, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-26 08:57:22'),
(31, 1, 'create', 'events', 9, 'Created event: Term 1 starts ', NULL, NULL, '2026-01-26 08:58:14'),
(32, 1, 'login', NULL, NULL, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-02 07:56:43'),
(33, 1, 'login', NULL, NULL, 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-06 07:22:36');

-- --------------------------------------------------------

--
-- Table structure for table `admin_activity_logs`
--

CREATE TABLE `admin_activity_logs` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(100) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admission_applications`
--

CREATE TABLE `admission_applications` (
  `id` int(11) NOT NULL,
  `application_id` varchar(50) NOT NULL,
  `student_first_name` varchar(100) NOT NULL,
  `student_last_name` varchar(100) NOT NULL,
  `date_of_birth` date NOT NULL,
  `gender` enum('male','female') NOT NULL,
  `nationality` varchar(100) NOT NULL,
  `religion` varchar(100) DEFAULT NULL,
  `class_to_join` varchar(50) NOT NULL,
  `admission_type` enum('day','boarding') NOT NULL,
  `parent_first_name` varchar(100) NOT NULL,
  `parent_last_name` varchar(100) NOT NULL,
  `parent_relationship` varchar(50) NOT NULL,
  `parent_phone` varchar(20) NOT NULL,
  `parent_email` varchar(150) DEFAULT NULL,
  `parent_address` text DEFAULT NULL,
  `parent_occupation` varchar(150) DEFAULT NULL,
  `emergency_contact_name` varchar(200) NOT NULL,
  `emergency_contact_phone` varchar(20) NOT NULL,
  `emergency_contact_relationship` varchar(50) NOT NULL,
  `birth_certificate_url` varchar(255) DEFAULT NULL,
  `previous_school_report_url` varchar(255) DEFAULT NULL,
  `passport_photo_url` varchar(255) DEFAULT NULL,
  `immunization_record_url` varchar(255) DEFAULT NULL,
  `parent_id_url` varchar(255) DEFAULT NULL,
  `transfer_letter_url` varchar(255) DEFAULT NULL,
  `status` enum('pending','under_review','accepted','rejected','waitlist') DEFAULT 'pending',
  `submitted_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewed_by` int(11) DEFAULT NULL,
  `review_date` timestamp NULL DEFAULT NULL,
  `review_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `target_audience` enum('all','students','teachers','parents') DEFAULT 'all',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('active','expired','draft') DEFAULT 'active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `attendance_date` date NOT NULL,
  `status` enum('present','absent','late','excused') NOT NULL,
  `remarks` text DEFAULT NULL,
  `marked_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `calendar_events`
--

CREATE TABLE `calendar_events` (
  `id` int(11) NOT NULL,
  `event_title` varchar(255) NOT NULL,
  `event_description` text DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `event_type` enum('holiday','exam','meeting','activity','other') DEFAULT 'other',
  `all_day` tinyint(1) DEFAULT 1,
  `color` varchar(7) DEFAULT '#0066cc',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `id` int(11) NOT NULL,
  `class_name` varchar(50) NOT NULL,
  `class_level` enum('nursery','p1','p2','p3','p4','p5','p6','p7') NOT NULL,
  `class_teacher_id` int(11) DEFAULT NULL,
  `capacity` int(11) DEFAULT 40,
  `current_enrollment` int(11) DEFAULT 0,
  `academic_year` varchar(20) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contact_replies`
--

CREATE TABLE `contact_replies` (
  `id` int(11) NOT NULL,
  `contact_submission_id` int(11) NOT NULL,
  `reply_message` text NOT NULL,
  `replied_by` int(11) NOT NULL,
  `reply_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contact_submissions`
--

CREATE TABLE `contact_submissions` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `status` enum('new','read','replied','archived') DEFAULT 'new',
  `submitted_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `replied_by` int(11) DEFAULT NULL,
  `reply_date` timestamp NULL DEFAULT NULL,
  `reply_message` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `department_name` varchar(100) NOT NULL,
  `head_teacher_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_logs`
--

CREATE TABLE `email_logs` (
  `id` int(11) NOT NULL,
  `recipient_email` varchar(150) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `status` enum('sent','failed','pending') DEFAULT 'pending',
  `sent_at` timestamp NULL DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `event_date` date NOT NULL,
  `event_time` time DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `category` enum('academic','sports','cultural','religious','other') DEFAULT 'other',
  `status` enum('upcoming','ongoing','completed','cancelled') DEFAULT 'upcoming',
  `image_url` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exams`
--

CREATE TABLE `exams` (
  `id` int(11) NOT NULL,
  `exam_name` varchar(200) NOT NULL,
  `exam_type` enum('bot','mot','eot','mock','ple') NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `term` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('scheduled','ongoing','completed') DEFAULT 'scheduled',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exam_results`
--

CREATE TABLE `exam_results` (
  `id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `marks_obtained` decimal(5,2) NOT NULL,
  `marks_total` decimal(5,2) NOT NULL,
  `grade` varchar(5) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `entered_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL,
  `expense_category` enum('salaries','utilities','supplies','maintenance','transport','other') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `expense_date` date NOT NULL,
  `description` text DEFAULT NULL,
  `payment_method` enum('cash','bank_transfer','cheque') NOT NULL,
  `receipt_number` varchar(50) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `recorded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fees_structure`
--

CREATE TABLE `fees_structure` (
  `id` int(11) NOT NULL,
  `class_level` enum('nursery','p1','p2','p3','p4','p5','p6','p7') NOT NULL,
  `student_type` enum('day','boarding') NOT NULL,
  `term_fee` decimal(10,2) NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fee_payments`
--

CREATE TABLE `fee_payments` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_method` enum('cash','bank_transfer','mobile_money','cheque') NOT NULL,
  `term` int(11) NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `receipt_number` varchar(50) DEFAULT NULL,
  `received_by` int(11) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `file_uploads`
--

CREATE TABLE `file_uploads` (
  `id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `upload_purpose` enum('teacher_photo','gallery','library','admission','other') NOT NULL,
  `related_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gallery_images`
--

CREATE TABLE `gallery_images` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `image_url` varchar(255) NOT NULL,
  `category` enum('events','facilities','students','activities','achievements') NOT NULL,
  `upload_date` date NOT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `gallery_images`
--

INSERT INTO `gallery_images` (`id`, `title`, `description`, `image_url`, `category`, `upload_date`, `uploaded_by`, `display_order`, `status`, `created_at`) VALUES
(1, '34', '', 'uploads/gallery/gallery_1768159080_6963f768a6f92.jpg', 'events', '2026-01-11', 1, 0, 'active', '2026-01-11 19:18:00'),
(2, 'WhatsApp Image 2024 11 17 At 11.15.17 Ca93d580', '', 'uploads/gallery/gallery_1768160117_6963fb7594538.jpg', 'events', '2026-01-11', 1, 0, 'active', '2026-01-11 19:35:17'),
(3, '1', '', 'uploads/gallery/gallery_1768160117_6963fb75b8cb7.jpg', 'events', '2026-01-11', 1, 0, 'active', '2026-01-11 19:35:17'),
(4, '2', '', 'uploads/gallery/gallery_1768160118_6963fb7601860.jpg', 'events', '2026-01-11', 1, 0, 'active', '2026-01-11 19:35:18'),
(5, '3', '', 'uploads/gallery/gallery_1768160118_6963fb7656114.jpg', 'events', '2026-01-11', 1, 0, 'active', '2026-01-11 19:35:18'),
(6, '4', '', 'uploads/gallery/gallery_1768160118_6963fb7674ae1.jpg', 'events', '2026-01-11', 1, 0, 'active', '2026-01-11 19:35:18'),
(7, '7', '', 'uploads/gallery/gallery_1768160118_6963fb768d3be.jpg', 'events', '2026-01-11', 1, 0, 'active', '2026-01-11 19:35:18'),
(8, '8', '', 'uploads/gallery/gallery_1768160118_6963fb76c7c10.jpg', 'events', '2026-01-11', 1, 0, 'active', '2026-01-11 19:35:18'),
(9, '9', '', 'uploads/gallery/gallery_1768160118_6963fb76ef2b8.jpg', 'events', '2026-01-11', 1, 0, 'active', '2026-01-11 19:35:18'),
(10, '10', '', 'uploads/gallery/gallery_1768160119_6963fb77261b7.jpg', 'events', '2026-01-11', 1, 0, 'active', '2026-01-11 19:35:19'),
(11, '19', '', 'uploads/gallery/gallery_1768160119_6963fb773f28e.jpg', 'events', '2026-01-11', 1, 0, 'active', '2026-01-11 19:35:19'),
(12, '20', '', 'uploads/gallery/gallery_1768160119_6963fb775a99f.jpg', 'events', '2026-01-11', 1, 0, 'active', '2026-01-11 19:35:19'),
(13, '26', '', 'uploads/gallery/gallery_1768160119_6963fb7772f66.jpg', 'events', '2026-01-11', 1, 0, 'active', '2026-01-11 19:35:19'),
(14, '27', '', 'uploads/gallery/gallery_1768160119_6963fb778a301.jpg', 'events', '2026-01-11', 1, 0, 'active', '2026-01-11 19:35:19'),
(15, '28', '', 'uploads/gallery/gallery_1768160119_6963fb77b9617.jpg', 'events', '2026-01-11', 1, 0, 'active', '2026-01-11 19:35:19'),
(16, '29', '', 'uploads/gallery/gallery_1768160119_6963fb77d15e2.jpg', 'events', '2026-01-11', 1, 0, 'active', '2026-01-11 19:35:19'),
(17, '30', '', 'uploads/gallery/gallery_1768160119_6963fb77ee18e.jpg', 'events', '2026-01-11', 1, 0, 'active', '2026-01-11 19:35:19'),
(18, '31', '', 'uploads/gallery/gallery_1768160120_6963fb78155ad.jpg', 'events', '2026-01-11', 1, 0, 'active', '2026-01-11 19:35:20'),
(19, '32', '', 'uploads/gallery/gallery_1768160120_6963fb783c738.jpg', 'events', '2026-01-11', 1, 0, 'active', '2026-01-11 19:35:20'),
(20, '33', '', 'uploads/gallery/gallery_1768160120_6963fb78538ac.jpg', 'events', '2026-01-11', 1, 0, 'active', '2026-01-11 19:35:20'),
(21, '35', '', 'uploads/gallery/gallery_1768160120_6963fb786e211.jpg', 'events', '2026-01-11', 1, 0, 'active', '2026-01-11 19:35:20'),
(22, '37', '', 'uploads/gallery/gallery_1768160120_6963fb788e16f.jpg', 'events', '2026-01-11', 1, 0, 'active', '2026-01-11 19:35:20'),
(23, '42', '', 'uploads/gallery/gallery_1768160120_6963fb78b7095.jpg', 'events', '2026-01-11', 1, 0, 'active', '2026-01-11 19:35:20'),
(24, '43', '', 'uploads/gallery/gallery_1768160120_6963fb78cf6f8.jpg', 'events', '2026-01-11', 1, 0, 'active', '2026-01-11 19:35:20'),
(25, '44', '', 'uploads/gallery/gallery_1768160120_6963fb78ee52f.jpg', 'events', '2026-01-11', 1, 0, 'active', '2026-01-11 19:35:20'),
(26, '49', '', 'uploads/gallery/gallery_1768160121_6963fb79185f7.jpg', 'events', '2026-01-11', 1, 0, 'active', '2026-01-11 19:35:21'),
(27, '50', '', 'uploads/gallery/gallery_1768160121_6963fb7948e5f.jpg', 'events', '2026-01-11', 1, 0, 'active', '2026-01-11 19:35:21'),
(28, '51', '', 'uploads/gallery/gallery_1768160121_6963fb796b030.jpg', 'events', '2026-01-11', 1, 0, 'active', '2026-01-11 19:35:21'),
(29, '52', '', 'uploads/gallery/gallery_1768160121_6963fb798d60d.jpg', 'events', '2026-01-11', 1, 0, 'active', '2026-01-11 19:35:21'),
(30, '53', '', 'uploads/gallery/gallery_1768160121_6963fb79ba98b.jpg', 'events', '2026-01-11', 1, 0, 'active', '2026-01-11 19:35:21'),
(31, '54', '', 'uploads/gallery/gallery_1768160121_6963fb79d7125.jpg', 'events', '2026-01-11', 1, 0, 'active', '2026-01-11 19:35:21'),
(32, '55', '', 'uploads/gallery/gallery_1768160122_6963fb7a0787c.jpg', 'events', '2026-01-11', 1, 0, 'active', '2026-01-11 19:35:22'),
(33, '56', '', 'uploads/gallery/gallery_1768160122_6963fb7a357b9.jpg', 'events', '2026-01-11', 1, 0, 'active', '2026-01-11 19:35:22'),
(34, '57', '', 'uploads/gallery/gallery_1768160122_6963fb7a5b8ff.jpg', 'events', '2026-01-11', 1, 0, 'active', '2026-01-11 19:35:22'),
(35, '58', '', 'uploads/gallery/gallery_1768160122_6963fb7a7008e.jpg', 'events', '2026-01-11', 1, 0, 'active', '2026-01-11 19:35:22'),
(36, '59', '', 'uploads/gallery/gallery_1768160122_6963fb7adf8e0.jpg', 'events', '2026-01-11', 1, 0, 'active', '2026-01-11 19:35:22'),
(37, '61', '', 'uploads/gallery/gallery_1768160123_6963fb7b049f5.jpg', 'events', '2026-01-11', 1, 0, 'active', '2026-01-11 19:35:23'),
(38, '62', '', 'uploads/gallery/gallery_1768160123_6963fb7b386e5.jpg', 'events', '2026-01-11', 1, 0, 'active', '2026-01-11 19:35:23'),
(39, '63', '', 'uploads/gallery/gallery_1768160123_6963fb7b5f6e0.jpg', 'events', '2026-01-11', 1, 0, 'active', '2026-01-11 19:35:23'),
(40, '65', '', 'uploads/gallery/gallery_1768160123_6963fb7b8fd00.jpg', 'events', '2026-01-11', 1, 0, 'active', '2026-01-11 19:35:23'),
(41, '66', '', 'uploads/gallery/gallery_1768160123_6963fb7bcf0ae.jpg', 'events', '2026-01-11', 1, 0, 'active', '2026-01-11 19:35:23'),
(42, '68', '', 'uploads/gallery/gallery_1768160123_6963fb7bea59b.jpg', 'events', '2026-01-11', 1, 0, 'active', '2026-01-11 19:35:23'),
(43, 'Bbb', '', 'uploads/gallery/gallery_1768160124_6963fb7c2295f.jpg', 'events', '2026-01-11', 1, 0, 'active', '2026-01-11 19:35:24'),
(44, 'Leo 5', '', 'uploads/gallery/gallery_1768160124_6963fb7c61510.jpg', 'events', '2026-01-11', 1, 0, 'active', '2026-01-11 19:35:24'),
(45, 'Leo 8', '', 'uploads/gallery/gallery_1768160124_6963fb7c7dea6.jpg', 'events', '2026-01-11', 1, 0, 'active', '2026-01-11 19:35:24'),
(46, 'Leo 9', '', 'uploads/gallery/gallery_1768160124_6963fb7ca13e6.jpg', 'events', '2026-01-11', 1, 0, 'active', '2026-01-11 19:35:24'),
(47, 'Leo 10', '', 'uploads/gallery/gallery_1768160124_6963fb7cd7a4e.jpg', 'events', '2026-01-11', 1, 0, 'active', '2026-01-11 19:35:24'),
(48, 'Leo 13', '', 'uploads/gallery/gallery_1768160125_6963fb7d0884e.jpg', 'events', '2026-01-11', 1, 0, 'active', '2026-01-11 19:35:25'),
(49, 'WhatsApp Image 2024 11 16 At 20.19.32 Fcc9b6a4', '', 'uploads/gallery/gallery_1768160125_6963fb7d256d0.jpg', 'events', '2026-01-11', 1, 0, 'active', '2026-01-11 19:35:25'),
(50, 'WhatsApp Image 2024 11 16 At 20.19.36 Fdfc56e8', '', 'uploads/gallery/gallery_1768160125_6963fb7d80631.jpg', 'events', '2026-01-11', 1, 0, 'active', '2026-01-11 19:35:25'),
(51, 'WhatsApp Image 2024 11 16 At 20.19.41 9eab1209', '', 'uploads/gallery/gallery_1768160125_6963fb7d9e60e.jpg', 'events', '2026-01-11', 1, 0, 'active', '2026-01-11 19:35:25'),
(52, 'WhatsApp Image 2024 11 16 At 20.19.42 60d518a5', '', 'uploads/gallery/gallery_1768160125_6963fb7dd5cd6.jpg', 'events', '2026-01-11', 1, 0, 'active', '2026-01-11 19:35:25'),
(53, 'WhatsApp Image 2024 11 16 At 20.19.47 74ed61d6', '', 'uploads/gallery/gallery_1768160126_6963fb7e12174.jpg', 'events', '2026-01-11', 1, 0, 'active', '2026-01-11 19:35:26'),
(54, 'WhatsApp Image 2024 11 16 At 20.19.47 54967fe1', '', 'uploads/gallery/gallery_1768160126_6963fb7e28d8b.jpg', 'events', '2026-01-11', 1, 0, 'active', '2026-01-11 19:35:26'),
(55, 'WhatsApp Image 2024 11 16 At 20.19.48 D3f70cf0', '', 'uploads/gallery/gallery_1768160126_6963fb7e5b573.jpg', 'events', '2026-01-11', 1, 0, 'active', '2026-01-11 19:35:26'),
(56, 'WhatsApp Image 2024 11 16 At 20.19.51 19a31697', '', 'uploads/gallery/gallery_1768160126_6963fb7e8adc5.jpg', 'events', '2026-01-11', 1, 0, 'active', '2026-01-11 19:35:26'),
(57, 'WhatsApp Image 2024 11 16 At 20.19.51 33e364f3', '', 'uploads/gallery/gallery_1768160126_6963fb7ec901d.jpg', 'events', '2026-01-11', 1, 0, 'active', '2026-01-11 19:35:26'),
(58, 'WhatsApp Image 2024 11 16 At 20.19.52 684720a3', '', 'uploads/gallery/gallery_1768160126_6963fb7eeac71.jpg', 'events', '2026-01-11', 1, 0, 'active', '2026-01-11 19:35:26'),
(59, 'WhatsApp Image 2024 11 16 At 20.19.53 977a2038', '', 'uploads/gallery/gallery_1768160127_6963fb7f1a20c.jpg', 'events', '2026-01-11', 1, 0, 'active', '2026-01-11 19:35:27'),
(60, 'WhatsApp Image 2024 11 16 At 20.19.53 E2f0f8a6', '', 'uploads/gallery/gallery_1768160127_6963fb7f38693.jpg', 'events', '2026-01-11', 1, 0, 'active', '2026-01-11 19:35:27'),
(61, 'WhatsApp Image 2024 11 16 At 20.19.54 64200742', '', 'uploads/gallery/gallery_1768160127_6963fb7f7e7a5.jpg', 'events', '2026-01-11', 1, 0, 'active', '2026-01-11 19:35:27'),
(62, 'WhatsApp Image 2024 11 16 At 20.19.54 Cce74061', '', 'uploads/gallery/gallery_1768160127_6963fb7fa1c93.jpg', 'events', '2026-01-11', 1, 0, 'active', '2026-01-11 19:35:27'),
(63, 'WhatsApp Image 2024 11 16 At 20.20.00 B730e7da', '', 'uploads/gallery/gallery_1768160127_6963fb7fb8655.jpg', 'events', '2026-01-11', 1, 0, 'active', '2026-01-11 19:35:27'),
(64, 'WhatsApp Image 2024 11 16 At 20.20.01 B2f90443', '', 'uploads/gallery/gallery_1768160127_6963fb7fe02c0.jpg', 'events', '2026-01-11', 1, 0, 'active', '2026-01-11 19:35:27');

-- --------------------------------------------------------

--
-- Table structure for table `library_resources`
--

CREATE TABLE `library_resources` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` enum('assignment','reading','past_exam','revision','multimedia','study_guide') NOT NULL,
  `class_level` enum('p1','p2','p3','p4','p5','p6','p7','all') NOT NULL,
  `subject` varchar(100) DEFAULT NULL,
  `file_url` varchar(255) NOT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `download_count` int(11) DEFAULT 0,
  `uploaded_by` int(11) DEFAULT NULL,
  `upload_date` date NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `library_resources`
--

INSERT INTO `library_resources` (`id`, `title`, `description`, `category`, `class_level`, `subject`, `file_url`, `file_type`, `file_size`, `download_count`, `uploaded_by`, `upload_date`, `status`, `created_at`) VALUES
(1, 'BABY LA 4 BOT III', '', '', '', '', 'uploads/library/library_1768162327_696404175b235.pdf', 'pdf', 855933, 0, 1, '0000-00-00', 'active', '2026-01-11 20:12:07'),
(2, 'BABY LA 5 BOT III', '', '', '', '', 'uploads/library/library_1768162327_69640417a9942.pdf', 'pdf', 833583, 0, 1, '0000-00-00', 'active', '2026-01-11 20:12:07'),
(3, 'BOT Science P.5 Sipro', '', '', '', '', 'uploads/library/library_1768162328_696404180aed8.pdf', 'pdf', 1477232, 0, 1, '0000-00-00', 'active', '2026-01-11 20:12:08'),
(4, 'END OF YEAR P.5 SCIENCE', '', '', '', '', 'uploads/library/library_1768162328_6964041856150.pdf', 'pdf', 284034, 0, 1, '0000-00-00', 'active', '2026-01-11 20:12:08'),
(5, 'English Primary 6 Pupil Textbook', '', '', '', '', 'uploads/library/library_1768162330_6964041aa1490.pdf', 'pdf', 12426019, 0, 1, '0000-00-00', 'active', '2026-01-11 20:12:10'),
(6, 'GAYAZA HOL. PACK', '', '', '', '', 'uploads/library/library_1768162330_6964041ace74a.pdf', 'pdf', 475879, 0, 1, '0000-00-00', 'active', '2026-01-11 20:12:10'),
(7, 'HOLIDAY PACKAGE 2022 ENGLISH P.2', '', '', '', '', 'uploads/library/library_1768162330_6964041adc988.pdf', 'pdf', 6142711, 0, 1, '0000-00-00', 'active', '2026-01-11 20:12:10'),
(8, 'HOLIDAY PACKAGE ENGLISH P3', '', '', '', '', 'uploads/library/library_1768162331_6964041b057ed.pdf', 'pdf', 14064607, 0, 1, '0000-00-00', 'active', '2026-01-11 20:12:11'),
(9, 'JULIUS MTC P.5', '', '', '', '', 'uploads/library/library_1768162331_6964041b1b276.pdf', 'pdf', 231901, 0, 1, '0000-00-00', 'active', '2026-01-11 20:12:11'),
(10, 'KIBULI P7 SST Copy', '', '', '', '', 'uploads/library/library_1768162331_6964041b279fb.pdf', 'pdf', 284841, 0, 1, '0000-00-00', 'active', '2026-01-11 20:12:11'),
(11, 'KIBULI P7 SST', '', '', '', '', 'uploads/library/library_1768162331_6964041b4087a.pdf', 'pdf', 284841, 0, 1, '0000-00-00', 'active', '2026-01-11 20:12:11'),
(12, 'MAGAZINE INDESIGN 2024 Final', '', '', '', '', 'uploads/library/library_1768162331_6964041b70d10.pdf', 'pdf', 19859585, 0, 1, '0000-00-00', 'active', '2026-01-11 20:12:11'),
(13, 'MATH P6 Student Book', '', '', '', '', 'uploads/library/library_1768162331_6964041bab30c.pdf', 'pdf', 8124859, 0, 1, '0000-00-00', 'active', '2026-01-11 20:12:11'),
(14, 'P. 1 ENG BOT III (1)', '', '', '', '', 'uploads/library/library_1768162331_6964041bd364a.pdf', 'pdf', 1001853, 0, 1, '0000-00-00', 'active', '2026-01-11 20:12:11'),
(15, 'P. 1 ENG BOT III', '', '', '', '', 'uploads/library/library_1768162332_6964041c0cecf.pdf', 'pdf', 1001853, 0, 1, '0000-00-00', 'active', '2026-01-11 20:12:12'),
(16, 'P. 1 LIT 1A BOT III', '', '', '', '', 'uploads/library/library_1768162332_6964041c2ea28.pdf', 'pdf', 963486, 0, 1, '0000-00-00', 'active', '2026-01-11 20:12:12'),
(17, 'P. 1 MTC BOT III', '', '', '', '', 'uploads/library/library_1768162332_6964041c8ca6e.pdf', 'pdf', 865497, 0, 1, '0000-00-00', 'active', '2026-01-11 20:12:12'),
(18, 'P. 2 LIT IA BOT III', '', '', '', '', 'uploads/library/library_1768162332_6964041ca8648.pdf', 'pdf', 1135299, 0, 1, '0000-00-00', 'active', '2026-01-11 20:12:12'),
(19, 'P. 2 MTC BOT III', '', '', '', '', 'uploads/library/library_1768162332_6964041cc70ac.pdf', 'pdf', 1083480, 0, 1, '0000-00-00', 'active', '2026-01-11 20:12:12'),
(20, 'P. 3 LIT 1A BOT III', '', '', '', '', 'uploads/library/library_1768162333_6964041d303cf.pdf', 'pdf', 1022155, 0, 1, '0000-00-00', 'active', '2026-01-11 20:12:13'),
(21, 'P. 3 MTC BOT III', '', '', '', '', 'uploads/library/library_1768162333_6964041d757d0.pdf', 'pdf', 955381, 0, 1, '0000-00-00', 'active', '2026-01-11 20:12:13'),
(22, 'P.1 HOLIDAY PACKAGE 1', '', '', '', '', 'uploads/library/library_1768162334_6964041e074ec.pdf', 'pdf', 8062685, 0, 1, '0000-00-00', 'active', '2026-01-11 20:12:14'),
(23, 'P.2 ENG BOT III', '', '', '', '', 'uploads/library/library_1768162334_6964041e454a9.pdf', 'pdf', 1026657, 0, 1, '0000-00-00', 'active', '2026-01-11 20:12:14'),
(24, 'P.4 ENGLISH HOMEWORK 18TH MAY', '', '', '', '', 'uploads/library/library_1768162334_6964041e498a4.pdf', 'pdf', 294147, 0, 1, '0000-00-00', 'active', '2026-01-11 20:12:14'),
(25, 'P.4 ENGLISH Wk.1', '', '', '', '', 'uploads/library/library_1768162334_6964041e4d7fd.pdf', 'pdf', 54332, 0, 1, '0000-00-00', 'active', '2026-01-11 20:12:14'),
(26, 'P.4SST', '', '', '', '', 'uploads/library/library_1768162334_6964041e51f16.pdf', 'pdf', 81306, 0, 1, '0000-00-00', 'active', '2026-01-11 20:12:14'),
(27, 'P.6 ENGLISH BOT III', '', '', '', '', 'uploads/library/library_1768162334_6964041e56b36.pdf', 'pdf', 549756, 0, 1, '0000-00-00', 'active', '2026-01-11 20:12:14'),
(28, 'P.6 MATHS BOT III', '', '', '', '', 'uploads/library/library_1768162334_6964041e5b9e3.pdf', 'pdf', 467948, 0, 1, '0000-00-00', 'active', '2026-01-11 20:12:14'),
(29, 'P.6 S.ST BOT III', '', '', '', '', 'uploads/library/library_1768162334_6964041e63903.pdf', 'pdf', 940899, 0, 1, '0000-00-00', 'active', '2026-01-11 20:12:14'),
(30, 'P.6 SCIENCE BOT III', '', '', '', '', 'uploads/library/library_1768162334_6964041e6be38.pdf', 'pdf', 777728, 0, 1, '0000-00-00', 'active', '2026-01-11 20:12:14'),
(31, 'P.7 SCIENCE PLE SET 1', '', '', '', '', 'uploads/library/library_1768162334_6964041e71aca.pdf', 'pdf', 614489, 0, 1, '0000-00-00', 'active', '2026-01-11 20:12:14'),
(32, 'P7 ENGLISH MID TERM ONE', '', '', '', '', 'uploads/library/library_1768162334_6964041e79719.pdf', 'pdf', 388163, 0, 1, '0000-00-00', 'active', '2026-01-11 20:12:14'),
(33, 'Pdf 1768040074 6962268aa8b93', '', '', '', '', 'uploads/library/library_1768162334_6964041e84399.pdf', 'pdf', 855933, 0, 1, '0000-00-00', 'active', '2026-01-11 20:12:14'),
(34, 'Pdf 1768041167 69622acf7218c', '', '', '', '', 'uploads/library/library_1768162334_6964041e8cace.pdf', 'pdf', 833583, 0, 1, '0000-00-00', 'active', '2026-01-11 20:12:14'),
(35, 'Pdf 1768041349 69622b8583e8e', '', '', '', '', 'uploads/library/library_1768162334_6964041e95e38.pdf', 'pdf', 475879, 0, 1, '0000-00-00', 'active', '2026-01-11 20:12:14'),
(36, 'Pdf 1768042131 69622e93b7f7f', '', '', '', '', 'uploads/library/library_1768162334_6964041ea0a62.pdf', 'pdf', 1477232, 0, 1, '0000-00-00', 'active', '2026-01-11 20:12:14'),
(37, 'Pdf 1768042131 69622e93beab8', '', '', '', '', 'uploads/library/library_1768162334_6964041ea7fb7.pdf', 'pdf', 284034, 0, 1, '0000-00-00', 'active', '2026-01-11 20:12:14'),
(38, 'Pdf 1768042319 69622f4fb4af2', '', '', '', '', 'uploads/library/library_1768162334_6964041eb3343.pdf', 'pdf', 833583, 7, 1, '0000-00-00', 'active', '2026-01-11 20:12:14'),
(39, 'Pdf 1768042457 69622fd9b6b54', '', '', '', '', 'uploads/library/library_1768162334_6964041ed44f0.pdf', 'pdf', 8062685, 0, 1, '0000-00-00', 'active', '2026-01-11 20:12:14'),
(40, 'Pdf 1768042457 69622fd9be939', '', '', '', '', 'uploads/library/library_1768162335_6964041f033c8.pdf', 'pdf', 865497, 0, 1, '0000-00-00', 'active', '2026-01-11 20:12:15'),
(41, 'Pdf 1768042457 69622fd9c6503', '', '', '', '', 'uploads/library/library_1768162335_6964041f120f5.pdf', 'pdf', 963486, 7, 1, '0000-00-00', 'active', '2026-01-11 20:12:15'),
(42, 'Pdf 1768042457 69622fd9cc9d0', '', '', '', '', 'uploads/library/library_1768162335_6964041f18268.pdf', 'pdf', 1001853, 8, 1, '0000-00-00', 'active', '2026-01-11 20:12:15'),
(43, 'Pdf 1768042506 6962300a11a70', '', '', '', '', 'uploads/library/library_1768162335_6964041f1ecda.pdf', 'pdf', 1135299, 0, 1, '0000-00-00', 'active', '2026-01-11 20:12:15'),
(44, 'Primary Five English 2', '', '', '', '', 'uploads/library/library_1768162335_6964041f26348.pdf', 'pdf', 2872856, 0, 1, '0000-00-00', 'active', '2026-01-11 20:12:15'),
(45, 'Primary Science 4 PB Textbook', '', '', '', '', 'uploads/library/library_1768162335_6964041f3b73f.pdf', 'pdf', 13299627, 0, 1, '0000-00-00', 'active', '2026-01-11 20:12:15'),
(46, 'Primary Science 6 PB Textbook', '', '', '', '', 'uploads/library/library_1768162335_6964041f5f38c.pdf', 'pdf', 14139611, 0, 1, '0000-00-00', 'active', '2026-01-11 20:12:15'),
(47, 'Primary Application.pdf', '', '', '', '', 'uploads/library/library_1768162335_6964041f6539b.pdf', 'pdf', 118944, 0, 1, '0000-00-00', 'active', '2026-01-11 20:12:15'),
(48, 'REAL PLE 2023 MATH  Shared   E Learn', '', '', '', '', 'uploads/library/library_1768162335_6964041f799dc.pdf', 'pdf', 5253674, 0, 1, '0000-00-00', 'active', '2026-01-11 20:12:15'),
(49, 'SIPRO P4 BOT 3 MTC', '', '', '', '', 'uploads/library/library_1768162335_6964041f8166b.pdf', 'pdf', 1638211, 0, 1, '0000-00-00', 'active', '2026-01-11 20:12:15'),
(50, 'SOCIAL STUDIES  P 5  TERM I, 2019 SST', '', '', '', '', 'uploads/library/library_1768162335_6964041f85d34.pdf', 'pdf', 201169, 0, 1, '0000-00-00', 'active', '2026-01-11 20:12:15'),
(51, 'SOCIAL STUDIES TEXT BOOK P.6', '', '', '', '', 'uploads/library/library_1768162335_6964041f8b77e.pdf', 'pdf', 1927821, 0, 1, '0000-00-00', 'active', '2026-01-11 20:12:15'),
(52, 'SST Primary 5 PB Textbook', '', '', '', '', 'uploads/library/library_1768162335_6964041faff50.pdf', 'pdf', 27538105, 15, 1, '0000-00-00', 'active', '2026-01-11 20:12:15'),
(53, 'TOP LA 3 BOT III', '', '', '', '', 'uploads/library/library_1768162335_6964041fb7a6c.pdf', 'pdf', 858801, 7, 1, '0000-00-00', 'active', '2026-01-11 20:12:15'),
(54, 'TOP LA 4 BOT III', '', '', '', '', 'uploads/library/library_1768162335_6964041fbf653.pdf', 'pdf', 988217, 0, 1, '0000-00-00', 'active', '2026-01-11 20:12:15');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `notification_type` enum('info','success','warning','error') DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `link_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `parents_guardians`
--

CREATE TABLE `parents_guardians` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `full_name` varchar(200) NOT NULL,
  `relationship` varchar(50) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `occupation` varchar(150) DEFAULT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` int(11) NOT NULL,
  `permission_name` varchar(100) NOT NULL,
  `permission_module` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `role_name` varchar(100) NOT NULL,
  `role_level` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `role_name`, `role_level`, `description`, `status`, `created_at`) VALUES
(1, 'Super Administrator', 100, 'Full system access', 'active', '2026-01-11 13:20:56'),
(2, 'Administrator', 80, 'Administrative access', 'active', '2026-01-11 13:20:56'),
(3, 'Teacher', 50, 'Teacher access', 'active', '2026-01-11 13:20:56'),
(4, 'Accountant', 60, 'Finance management', 'active', '2026-01-11 13:20:56'),
(5, 'Librarian', 40, 'Library management', 'active', '2026-01-11 13:20:56'),
(6, 'Receptionist', 30, 'Front desk operations', 'active', '2026-01-11 13:20:56'),
(7, 'Parent', 10, 'Parent portal access', 'active', '2026-01-11 13:20:56'),
(8, 'Student', 5, 'Student portal access', 'active', '2026-01-11 13:20:56');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  `granted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('text','number','boolean','json') DEFAULT 'text',
  `description` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `description`, `updated_at`, `updated_by`) VALUES
(1, 'school_name', 'St. Lawrence Junior School - Kabowa', 'text', 'Official school name', '2026-01-11 13:20:56', NULL),
(2, 'school_email', 'stlawrencejuniorschoolkabowa@gmail.com', 'text', 'Primary school email', '2026-01-11 13:20:56', NULL),
(3, 'school_phone', '+256701420506', 'text', 'Primary contact number', '2026-01-11 13:20:56', NULL),
(4, 'school_address', 'P.O.BOX 36198, KAMPALA, UGANDA', 'text', 'School postal address', '2026-01-11 13:20:56', NULL),
(5, 'academic_year', '2026', 'number', 'Current academic year', '2026-01-11 13:20:56', NULL),
(6, 'current_term', '1', 'number', 'Current term (1, 2, or 3)', '2026-01-11 13:20:56', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `sms_logs`
--

CREATE TABLE `sms_logs` (
  `id` int(11) NOT NULL,
  `recipient_phone` varchar(20) NOT NULL,
  `message` text NOT NULL,
  `status` enum('sent','failed','pending') DEFAULT 'pending',
  `sent_at` timestamp NULL DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `admission_number` varchar(50) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `date_of_birth` date NOT NULL,
  `gender` enum('male','female') NOT NULL,
  `class_id` int(11) DEFAULT NULL,
  `admission_date` date NOT NULL,
  `student_type` enum('day','boarding') NOT NULL,
  `status` enum('active','graduated','transferred','suspended') DEFAULT 'active',
  `photo_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  `subject_code` varchar(20) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` int(11) NOT NULL,
  `full_name` varchar(200) NOT NULL,
  `department` enum('administration','english','mathematics','science','social') NOT NULL,
  `position` varchar(100) DEFAULT NULL,
  `qualification` varchar(200) DEFAULT NULL,
  `experience_years` int(11) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `photo_url` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `specialization` varchar(200) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`id`, `full_name`, `department`, `position`, `qualification`, `experience_years`, `email`, `phone`, `photo_url`, `bio`, `specialization`, `status`, `display_order`, `created_at`) VALUES
(1, 'Sekimpi Ibrahim', 'english', 'Teacher', 'Bachelor of Arts and Science', 10, 'cultoonmovic4@gmail.com', '+256708486440', 'uploads/teachers/teacher_1768160765_6963fdfda8382.jpg', 'Has a bachelor of Arts and Science ', 'Primary Science and Information Technology', 'active', 0, '2026-01-11 19:46:05'),
(2, 'MR. KIMERA EMMANUEL', 'administration', 'HEAD TEACHER', 'Bachelor of Arts and Science', 25, 'cultoonmovic4@gmail.com', '+256701420506', 'uploads/teachers/teacher_1768161815_6964021789bd9.jpg', 'DEGREE IN MATHEMATICS AND PHYSICS', 'PRIMARY MATHEMATICS', 'active', 0, '2026-01-11 20:03:35');

-- --------------------------------------------------------

--
-- Table structure for table `testimonials`
--

CREATE TABLE `testimonials` (
  `id` int(11) NOT NULL,
  `parent_name` varchar(200) NOT NULL,
  `parent_role` varchar(100) DEFAULT NULL,
  `testimonial_text` text NOT NULL,
  `rating` int(11) DEFAULT 5 CHECK (`rating` between 1 and 5),
  `photo_url` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `approved_by` int(11) DEFAULT NULL,
  `approval_date` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `testimonials`
--

INSERT INTO `testimonials` (`id`, `parent_name`, `parent_role`, `testimonial_text`, `rating`, `photo_url`, `status`, `display_order`, `created_at`, `approved_by`, `approval_date`) VALUES
(1, 'Sekimpi Ibrahim', 'parent', '\"As a parent, choosing the right school for my child was one of the most important decisions I’ve ever made. From the moment we stepped into St. Lawrence Junior School in Kabowa, we felt welcomed by a warm, nurturing community that truly puts children first.', 5, 'img/testimonials/testimonial_1768152997_6963dfa5c13f1.jpg', 'approved', 0, '2026-01-11 17:36:37', NULL, NULL),
(2, 'Muwanga lbrahim', 'old_student', 'My time at St. Lawrence Junior School laid the foundation for everything I’ve achieved since. The values, discipline, and love for learning I gained there still guide me today. I’m proud to be an old student of such a wonderful school!\"', 5, 'img/testimonials/testimonial_1768153115_6963e01b367a3.jpg', 'approved', 0, '2026-01-11 17:38:35', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `timetables`
--

CREATE TABLE `timetables` (
  `id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `day_of_week` enum('monday','tuesday','wednesday','thursday','friday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `room_number` varchar(20) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `term` int(11) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int(11) DEFAULT NULL,
  `full_name` varchar(200) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role_id`, `full_name`, `phone`, `profile_image`, `status`, `created_at`, `last_login`) VALUES
(1, 'admin', 'cultoonmovic4@gmail.com', '$2y$10$dFs.4llnzA0n6zBx9K8HdO/H1shcLZvHvSY3/EUu5sZczZiQVOk.q', 1, 'System Administrator', '+256708486440', 'backend/uploads/profiles/profile_1_1768170956.jpg', 'active', '2026-01-11 13:20:56', '2026-04-06 07:22:36'),
(2, 'muwangasekimpi', 'muwangasekimpi@gmail.com', '$2y$10$.solTDYMXX6c.5v.5g247OaGNwmXRw0Fem278MKjQfDdUZpCAYNmu', 2, 'muwangasekimpi', '', NULL, 'active', '2026-01-11 23:29:49', '2026-01-11 23:30:08'),
(3, 'Teacher', 'stlawrencejuniorschoolkabowa@gmail.com', '$2y$10$4idfvzrP/WAKbuDepL2wEuJYDL43.8oFkbFXS9sz7XGtbcdHsqI4.', 3, 'Kategerlan', '', NULL, 'active', '2026-01-11 23:41:47', '2026-01-11 23:42:05');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admin_activity_logs`
--
ALTER TABLE `admin_activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `admission_applications`
--
ALTER TABLE `admission_applications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `application_id` (`application_id`),
  ADD KEY `reviewed_by` (`reviewed_by`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_attendance` (`student_id`,`attendance_date`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `marked_by` (`marked_by`);

--
-- Indexes for table `calendar_events`
--
ALTER TABLE `calendar_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `class_teacher_id` (`class_teacher_id`);

--
-- Indexes for table `contact_replies`
--
ALTER TABLE `contact_replies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contact_submission_id` (`contact_submission_id`),
  ADD KEY `replied_by` (`replied_by`);

--
-- Indexes for table `contact_submissions`
--
ALTER TABLE `contact_submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `replied_by` (`replied_by`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `department_name` (`department_name`),
  ADD KEY `head_teacher_id` (`head_teacher_id`);

--
-- Indexes for table `email_logs`
--
ALTER TABLE `email_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `exams`
--
ALTER TABLE `exams`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `exam_results`
--
ALTER TABLE `exam_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `exam_id` (`exam_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `entered_by` (`entered_by`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `recorded_by` (`recorded_by`);

--
-- Indexes for table `fees_structure`
--
ALTER TABLE `fees_structure`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_fee` (`class_level`,`student_type`,`academic_year`);

--
-- Indexes for table `fee_payments`
--
ALTER TABLE `fee_payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `receipt_number` (`receipt_number`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `received_by` (`received_by`);

--
-- Indexes for table `file_uploads`
--
ALTER TABLE `file_uploads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `gallery_images`
--
ALTER TABLE `gallery_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `library_resources`
--
ALTER TABLE `library_resources`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `parents_guardians`
--
ALTER TABLE `parents_guardians`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permission_name` (`permission_name`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_role_permission` (`role_id`,`permission_id`),
  ADD KEY `permission_id` (`permission_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `sms_logs`
--
ALTER TABLE `sms_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `admission_number` (`admission_number`),
  ADD KEY `class_id` (`class_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `subject_code` (`subject_code`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `testimonials`
--
ALTER TABLE `testimonials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Indexes for table `timetables`
--
ALTER TABLE `timetables`
  ADD PRIMARY KEY (`id`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `role_id` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `admin_activity_logs`
--
ALTER TABLE `admin_activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admission_applications`
--
ALTER TABLE `admission_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `calendar_events`
--
ALTER TABLE `calendar_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contact_replies`
--
ALTER TABLE `contact_replies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `contact_submissions`
--
ALTER TABLE `contact_submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `email_logs`
--
ALTER TABLE `email_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `exams`
--
ALTER TABLE `exams`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exam_results`
--
ALTER TABLE `exam_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fees_structure`
--
ALTER TABLE `fees_structure`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fee_payments`
--
ALTER TABLE `fee_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `file_uploads`
--
ALTER TABLE `file_uploads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gallery_images`
--
ALTER TABLE `gallery_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT for table `library_resources`
--
ALTER TABLE `library_resources`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `parents_guardians`
--
ALTER TABLE `parents_guardians`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `role_permissions`
--
ALTER TABLE `role_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `sms_logs`
--
ALTER TABLE `sms_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `testimonials`
--
ALTER TABLE `testimonials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `timetables`
--
ALTER TABLE `timetables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_activity_logs`
--
ALTER TABLE `admin_activity_logs`
  ADD CONSTRAINT `admin_activity_logs_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `admission_applications`
--
ALTER TABLE `admission_applications`
  ADD CONSTRAINT `admission_applications_ibfk_1` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_3` FOREIGN KEY (`marked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `calendar_events`
--
ALTER TABLE `calendar_events`
  ADD CONSTRAINT `calendar_events_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `classes`
--
ALTER TABLE `classes`
  ADD CONSTRAINT `classes_ibfk_1` FOREIGN KEY (`class_teacher_id`) REFERENCES `teachers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `contact_replies`
--
ALTER TABLE `contact_replies`
  ADD CONSTRAINT `contact_replies_ibfk_1` FOREIGN KEY (`contact_submission_id`) REFERENCES `contact_submissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contact_replies_ibfk_2` FOREIGN KEY (`replied_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `contact_submissions`
--
ALTER TABLE `contact_submissions`
  ADD CONSTRAINT `contact_submissions_ibfk_1` FOREIGN KEY (`replied_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `departments`
--
ALTER TABLE `departments`
  ADD CONSTRAINT `departments_ibfk_1` FOREIGN KEY (`head_teacher_id`) REFERENCES `teachers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `exams`
--
ALTER TABLE `exams`
  ADD CONSTRAINT `exams_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `exam_results`
--
ALTER TABLE `exam_results`
  ADD CONSTRAINT `exam_results_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exam_results_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exam_results_ibfk_3` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exam_results_ibfk_4` FOREIGN KEY (`entered_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `expenses_ibfk_2` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `fee_payments`
--
ALTER TABLE `fee_payments`
  ADD CONSTRAINT `fee_payments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fee_payments_ibfk_2` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `file_uploads`
--
ALTER TABLE `file_uploads`
  ADD CONSTRAINT `file_uploads_ibfk_1` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `gallery_images`
--
ALTER TABLE `gallery_images`
  ADD CONSTRAINT `gallery_images_ibfk_1` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `library_resources`
--
ALTER TABLE `library_resources`
  ADD CONSTRAINT `library_resources_ibfk_1` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `parents_guardians`
--
ALTER TABLE `parents_guardians`
  ADD CONSTRAINT `parents_guardians_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `settings`
--
ALTER TABLE `settings`
  ADD CONSTRAINT `settings_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `subjects`
--
ALTER TABLE `subjects`
  ADD CONSTRAINT `subjects_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `testimonials`
--
ALTER TABLE `testimonials`
  ADD CONSTRAINT `testimonials_ibfk_1` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `timetables`
--
ALTER TABLE `timetables`
  ADD CONSTRAINT `timetables_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `timetables_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `timetables_ibfk_3` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
