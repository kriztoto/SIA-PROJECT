-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 15, 2026 at 02:38 AM
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
-- Database: `sia_quiz`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `category_name`) VALUES
(1, 'Addition'),
(2, 'Subtraction'),
(3, 'Multiplication'),
(4, 'Division');

-- --------------------------------------------------------

--
-- Table structure for table `choices`
--

CREATE TABLE `choices` (
  `id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `choice_text` varchar(255) NOT NULL,
  `is_correct` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `choices`
--

INSERT INTO `choices` (`id`, `question_id`, `choice_text`, `is_correct`) VALUES
(43, 12, '1', 0),
(44, 12, '2', 1),
(45, 12, '3', 0),
(46, 12, '4', 0),
(47, 13, '4', 0),
(48, 13, '5', 1),
(49, 13, '6', 0),
(50, 13, '7', 0),
(51, 14, '7', 0),
(52, 14, '8', 0),
(53, 14, '9', 1),
(54, 14, '10 ', 0),
(55, 15, '6', 0),
(56, 15, '7', 0),
(57, 15, '8', 1),
(58, 15, '9', 0),
(59, 16, '9', 0),
(60, 16, '10', 1),
(61, 16, '11', 0),
(62, 16, '12', 0),
(63, 17, '8', 0),
(64, 17, '9', 1),
(65, 17, '10', 0),
(66, 17, '11', 0),
(67, 18, '12', 0),
(68, 18, '13', 1),
(69, 18, '14', 0),
(70, 18, '15', 0),
(71, 19, '10', 0),
(72, 19, '11', 1),
(73, 19, '12', 0),
(74, 19, '13', 0),
(75, 20, '14', 0),
(76, 20, '15', 1),
(77, 20, '16', 0),
(78, 20, '17', 0),
(79, 21, '13', 0),
(80, 21, '14', 0),
(81, 21, '15', 1),
(82, 21, '16', 0),
(83, 22, '14', 0),
(84, 22, '15', 1),
(85, 22, '16', 0),
(86, 22, '17', 0),
(87, 23, '15', 0),
(88, 23, '16', 1),
(89, 23, '17', 0),
(90, 23, '18', 0),
(91, 24, '17', 0),
(92, 24, '18', 1),
(93, 24, '19', 0),
(94, 24, '20', 0),
(95, 25, '13', 0),
(96, 25, '14', 0),
(97, 25, '15', 1),
(98, 25, '16', 0),
(99, 26, '14', 0),
(100, 26, '15', 0),
(101, 26, '16', 1),
(102, 26, '17', 0),
(103, 27, '16', 0),
(104, 27, '17', 1),
(105, 27, '18', 0),
(106, 27, '19', 0),
(107, 28, '18', 0),
(108, 28, '19', 0),
(109, 28, '20', 1),
(110, 28, '21', 0),
(111, 29, '18', 0),
(112, 29, '19', 1),
(113, 29, '20', 0),
(114, 29, '21', 0),
(115, 30, '19', 0),
(116, 30, '20', 1),
(117, 30, '21', 0),
(118, 30, '22', 0),
(119, 31, '23', 0),
(120, 31, '24', 0),
(121, 31, '25', 1),
(122, 31, '26', 0),
(123, 32, '3', 0),
(124, 32, '4', 1),
(125, 32, '5', 0),
(126, 32, '6', 0),
(127, 33, '3', 0),
(128, 33, '4', 1),
(129, 33, '5', 0),
(130, 33, '6', 0),
(131, 34, '3', 0),
(132, 34, '4', 1),
(133, 34, '5', 0),
(134, 34, '6', 0),
(135, 35, '3', 0),
(136, 35, '4', 1),
(137, 35, '5', 0),
(138, 35, '6', 0),
(139, 36, '3', 0),
(140, 36, '4', 1),
(141, 36, '5', 0),
(142, 36, '6', 0),
(143, 37, '3', 0),
(144, 37, '4', 1),
(145, 37, '5', 0),
(146, 37, '6', 0),
(147, 38, '6', 0),
(148, 38, '7', 0),
(149, 38, '8', 1),
(150, 38, '9', 0),
(151, 39, '7', 0),
(152, 39, '8', 0),
(153, 39, '9', 1),
(154, 39, '10', 0),
(155, 40, '7', 0),
(156, 40, '8', 1),
(157, 40, '9', 0),
(158, 40, '10', 0),
(159, 41, '6', 0),
(160, 41, '7', 0),
(161, 41, '8', 1),
(162, 41, '9', 0),
(163, 42, '7', 0),
(164, 42, '8', 0),
(165, 42, '9', 1),
(166, 42, '10', 0),
(167, 43, '8', 0),
(168, 43, '9', 0),
(169, 43, '10', 1),
(170, 43, '11', 0),
(171, 44, '6', 0),
(172, 44, '7', 1),
(173, 44, '8', 0),
(174, 44, '9', 0),
(175, 45, '10', 0),
(176, 45, '11', 0),
(177, 45, '12', 1),
(178, 45, '13', 0),
(179, 46, '10', 0),
(180, 46, '11', 1),
(181, 46, '12', 0),
(182, 46, '13', 0),
(183, 47, '6', 0),
(184, 47, '7', 1),
(185, 47, '8', 0),
(186, 47, '9', 0),
(187, 48, '6', 0),
(188, 48, '7', 1),
(189, 48, '8', 0),
(190, 48, '9', 0),
(191, 49, '4', 0),
(192, 49, '5', 1),
(193, 49, '6', 0),
(194, 49, '7', 0),
(195, 50, '5', 0),
(196, 50, '6', 1),
(197, 50, '7', 0),
(198, 50, '8', 0),
(199, 51, '1', 1),
(200, 51, '2', 0),
(201, 51, '3', 0),
(202, 51, '4', 0),
(203, 52, '1', 0),
(204, 52, '2', 1),
(205, 52, '3', 0),
(206, 52, '4', 0),
(207, 53, '4', 0),
(208, 53, '5', 0),
(209, 53, '6', 1),
(210, 53, '7', 0),
(211, 54, '10', 0),
(212, 54, '11', 0),
(213, 54, '12', 1),
(214, 54, '13', 0),
(215, 55, '6', 0),
(216, 55, '7', 0),
(217, 55, '8', 1),
(218, 55, '9', 0),
(219, 56, '10', 0),
(220, 56, '15', 1),
(221, 56, '20', 0),
(222, 56, '25', 0),
(223, 57, '10', 0),
(224, 57, '11', 0),
(225, 57, '12', 1),
(226, 57, '13', 0),
(227, 58, '18', 0),
(228, 58, '19', 0),
(229, 58, '20', 0),
(230, 58, '21', 1),
(231, 59, '14', 0),
(232, 59, '15', 0),
(233, 59, '16', 1),
(234, 59, '17', 0),
(235, 60, '16', 0),
(236, 60, '17', 0),
(237, 60, '18', 1),
(238, 60, '19', 0),
(239, 61, '10', 0),
(240, 61, '15', 1),
(241, 61, '20', 0),
(242, 61, '25', 0),
(243, 62, '14', 0),
(244, 62, '15', 0),
(245, 62, '16', 1),
(246, 62, '17', 0),
(247, 63, '15', 0),
(248, 63, '16', 0),
(249, 63, '17', 0),
(250, 63, '18', 1),
(251, 64, '26', 0),
(252, 64, '27', 0),
(253, 64, '28', 1),
(254, 64, '29', 0),
(255, 65, '20', 0),
(256, 65, '25', 1),
(257, 65, '30', 0),
(258, 65, '35', 0),
(259, 66, '22', 0),
(260, 66, '23', 0),
(261, 66, '24', 1),
(262, 66, '25', 0),
(263, 67, '25', 0),
(264, 67, '26', 0),
(265, 67, '27', 1),
(266, 67, '29', 0),
(267, 68, '20', 1),
(268, 68, '30', 0),
(269, 68, '60', 0),
(270, 68, '70', 0),
(271, 69, '21', 0),
(272, 69, '22', 0),
(273, 69, '23', 0),
(274, 69, '24', 1),
(275, 70, '30', 0),
(276, 70, '35', 1),
(277, 70, '40', 0),
(278, 70, '45', 0),
(279, 71, '30', 0),
(280, 71, '31', 0),
(281, 71, '32', 1),
(282, 71, '33', 0),
(283, 72, '1', 0),
(284, 72, '2', 1),
(285, 72, '3', 0),
(286, 72, '4', 0),
(287, 73, '2', 0),
(288, 73, '3', 1),
(289, 73, '4', 0),
(290, 73, '5', 0),
(291, 74, '2', 0),
(292, 74, '3', 1),
(293, 74, '4', 0),
(294, 74, '5', 0),
(295, 75, '3', 0),
(296, 75, '4', 1),
(297, 75, '5', 0),
(298, 75, '6', 0),
(299, 76, '2', 0),
(300, 76, '3', 0),
(301, 76, '4', 0),
(302, 76, '5', 1),
(303, 77, '3', 0),
(304, 77, '4', 1),
(305, 77, '5', 0),
(306, 77, '6', 0),
(307, 78, '6', 0),
(308, 78, '7', 1),
(309, 78, '8', 0),
(310, 78, '9', 0),
(311, 79, '4', 0),
(312, 79, '5', 1),
(313, 79, '6', 0),
(314, 79, '7', 0),
(315, 80, '3', 0),
(316, 80, '4', 1),
(317, 80, '5', 0),
(318, 80, '6', 0),
(319, 81, '2', 0),
(320, 81, '3', 1),
(321, 81, '4', 0),
(322, 81, '5', 0),
(323, 82, '3', 0),
(324, 82, '4', 1),
(325, 82, '5', 0),
(326, 82, '6', 0),
(327, 83, '2', 0),
(328, 83, '3', 1),
(329, 83, '4', 0),
(330, 83, '5', 0),
(331, 84, '3', 0),
(332, 84, '4', 1),
(333, 84, '5', 0),
(334, 84, '6', 0),
(335, 85, '2', 0),
(336, 85, '3', 1),
(337, 85, '4', 0),
(338, 85, '5', 0),
(339, 86, '5', 0),
(340, 86, '6', 1),
(341, 86, '7', 0),
(342, 86, '8', 0),
(343, 87, '6', 0),
(344, 87, '7', 0),
(345, 87, '8', 1),
(346, 87, '9', 0),
(347, 88, '6', 1),
(348, 88, '7', 0),
(349, 88, '8', 0),
(350, 88, '9', 0),
(351, 89, '3', 0),
(352, 89, '4', 0),
(353, 89, '5', 1),
(354, 89, '6', 0),
(355, 90, '4', 0),
(356, 90, '5', 1),
(357, 90, '6', 0),
(358, 90, '7', 0),
(359, 91, '4', 0),
(360, 91, '5', 1),
(361, 91, '6', 0),
(362, 91, '7', 0);

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

CREATE TABLE `questions` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `question_text` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `questions`
--

INSERT INTO `questions` (`id`, `category_id`, `question_text`) VALUES
(12, 1, '1 + 1 = ?'),
(13, 1, '2 + 3 = ?'),
(14, 1, '4 + 5 = ?'),
(15, 1, '6 + 2 = ?'),
(16, 1, '3 + 7 = ?'),
(17, 1, '8 + 1 = ?'),
(18, 1, '9 + 4 = ?'),
(19, 1, '5 + 6 = ?'),
(20, 1, '7 + 8 = ?'),
(21, 1, '10 + 5 = ?'),
(22, 1, '12 + 3 = ?'),
(23, 1, '14 + 2 = ?'),
(24, 1, '11 + 7 = ?'),
(25, 1, '6 + 9 = ?'),
(26, 1, '8 + 8 = ?'),
(27, 1, '13 + 4 = ?'),
(28, 1, '15 + 5 = ?'),
(29, 1, '16 + 3 = ?'),
(30, 1, '18 + 2 = ?'),
(31, 1, '20 + 5 = ?'),
(32, 2, '5 − 1 = ?'),
(33, 2, '6 − 2 = ?'),
(34, 2, '7 − 3 = ?'),
(35, 2, '8 − 4 = ?'),
(36, 2, '9 − 5 = ?'),
(37, 2, '10 − 6 = ?'),
(38, 2, '12 − 4 = ?'),
(39, 2, '14 − 5 = ?'),
(40, 2, '15 − 7 = ?'),
(41, 2, '16 − 8 = ?'),
(42, 2, '18 − 9 = ?'),
(43, 2, '20 − 10 = ?'),
(44, 2, '13 − 6 = ?'),
(45, 2, '17 − 5 = ?'),
(46, 2, '19 − 8 = ?'),
(47, 2, '11 − 4 = ?'),
(48, 2, '9 − 2 = ?'),
(49, 2, '8 − 3 = ?'),
(50, 2, '7 − 1 = ?'),
(51, 2, '6 − 5 = ?'),
(52, 3, '1 × 2 = ?'),
(53, 3, '2 × 3 = ?'),
(54, 3, '3 × 4 = ?'),
(55, 3, '4 × 2 = ?'),
(56, 3, '5 × 3 = ?'),
(57, 3, '6 × 2 = ?'),
(58, 3, '7 × 3 = ?'),
(59, 3, '8 × 2 = ?'),
(60, 3, '9 × 2 = ?'),
(61, 3, '3 × 5 = ?'),
(62, 3, '4 × 4 = ?'),
(63, 3, '6 × 3 = ?'),
(64, 3, '7 × 4 = ?'),
(65, 3, '5 × 5 = ?'),
(66, 3, '8 × 3 = ?'),
(67, 3, '9 × 3 = ?'),
(68, 3, '10 × 2 = ?'),
(69, 3, '6 × 4 = ?'),
(70, 3, '7 × 5 = ?'),
(71, 3, '8 × 4 = ?'),
(72, 4, '2 / 1 = ?'),
(73, 4, '6 / 2 = ?'),
(74, 4, '9 / 3 = ?'),
(75, 4, '8 / 2 = ?'),
(76, 4, '10 / 2 = ?'),
(77, 4, '12 / 3 = ?'),
(78, 4, '14 / 2 = ?'),
(79, 4, '15 / 3 = ?'),
(80, 4, '16 / 4 = ?'),
(81, 4, '18 / 6 = ?'),
(82, 4, '20 / 5 = ?'),
(83, 4, '21 / 7 = ?'),
(84, 4, '24 / 6 = ?'),
(85, 4, '27 / 9 = ?'),
(86, 4, '30 / 5 = ?'),
(87, 4, '32 / 4 = ?'),
(88, 4, '36 / 6 = ?'),
(89, 4, '40 / 8 = ?'),
(90, 4, '45 / 9 = ?'),
(91, 4, '50 / 10 = ?');

-- --------------------------------------------------------

--
-- Table structure for table `quiz_attempts`
--

CREATE TABLE `quiz_attempts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `score` int(11) NOT NULL,
  `correct_answers` int(11) NOT NULL,
  `wrong_answers` int(11) NOT NULL,
  `attempt_number` int(11) DEFAULT 1,
  `date_taken` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quiz_attempts`
--

INSERT INTO `quiz_attempts` (`id`, `user_id`, `category_id`, `score`, `correct_answers`, `wrong_answers`, `attempt_number`, `date_taken`) VALUES
(19, 11, 1, 10, 10, 0, 1, '2026-03-12 19:41:31'),
(20, 11, 2, 3, 3, 7, 1, '2026-03-12 19:42:11'),
(21, 11, 3, 3, 3, 7, 1, '2026-03-12 19:42:26'),
(22, 11, 4, 4, 4, 6, 1, '2026-03-12 19:42:38'),
(23, 19, 1, 2, 2, 8, 1, '2026-03-12 19:43:52'),
(24, 19, 2, 10, 10, 0, 1, '2026-03-12 19:44:25'),
(25, 19, 3, 1, 1, 9, 1, '2026-03-12 19:44:54'),
(26, 19, 4, 4, 4, 6, 1, '2026-03-12 19:45:03'),
(27, 14, 3, 9, 9, 1, 1, '2026-03-12 19:46:35'),
(28, 14, 1, 1, 1, 9, 1, '2026-03-12 19:46:55'),
(29, 14, 2, 0, 0, 10, 1, '2026-03-12 19:47:04'),
(30, 14, 4, 4, 4, 6, 1, '2026-03-12 19:47:11'),
(31, 24, 4, 9, 9, 1, 1, '2026-03-12 19:48:02'),
(32, 24, 1, 3, 3, 7, 1, '2026-03-12 19:48:11'),
(33, 24, 2, 3, 3, 7, 1, '2026-03-12 19:48:20'),
(34, 24, 3, 1, 1, 9, 1, '2026-03-12 19:48:28'),
(35, 30, 1, 3, 3, 2, 1, '2026-03-12 20:16:07'),
(36, 30, 1, 9, 9, 1, 2, '2026-03-12 20:16:47'),
(37, 30, 2, 9, 9, 1, 1, '2026-03-12 20:19:13'),
(38, 11, 4, 3, 3, 2, 2, '2026-03-12 20:32:35'),
(39, 11, 2, 6, 6, 4, 2, '2026-03-13 06:07:36'),
(40, 12, 1, 8, 8, 2, 1, '2026-03-13 06:15:16'),
(41, 12, 2, 9, 9, 1, 1, '2026-03-13 06:16:04'),
(42, 12, 3, 8, 8, 2, 1, '2026-03-13 06:16:39'),
(43, 12, 4, 6, 6, 4, 1, '2026-03-13 06:17:12'),
(44, 16, 1, 10, 10, 0, 1, '2026-03-13 06:28:24'),
(45, 16, 2, 7, 7, 3, 1, '2026-03-13 06:29:11'),
(46, 16, 3, 9, 9, 1, 1, '2026-03-13 06:30:14'),
(47, 16, 4, 8, 8, 2, 1, '2026-03-13 06:30:57'),
(48, 23, 1, 3, 3, 1, 1, '2026-03-13 06:49:29'),
(49, 23, 1, 9, 9, 1, 2, '2026-03-13 06:50:32');

-- --------------------------------------------------------

--
-- Table structure for table `sections`
--

CREATE TABLE `sections` (
  `id` int(11) NOT NULL,
  `section_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sections`
--

INSERT INTO `sections` (`id`, `section_name`) VALUES
(1, 'Section A'),
(2, 'Section B'),
(3, 'Section C'),
(4, 'Section D'),
(5, 'Section E');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_initial` varchar(5) DEFAULT NULL,
  `last_name` varchar(50) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('student','teacher') NOT NULL,
  `section_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `middle_initial`, `last_name`, `student_id`, `password`, `role`, `section_id`) VALUES
(2, 'Cristopher', 'L', 'Longa', 'teacher', '$2y$10$goDOGhiSha8jXjmT6MXhPuW1VWmY8XHBAbP6E3nuSqH7I1ucbWK/y', 'teacher', NULL),
(11, 'Cristopher', 'L', 'Longa', 's2601001', '$2y$10$a4GOWVXyzIvAXWfPi4C5IuGH2pRTOAlnzmRV0dS7HOhEKbuJSrBH.', 'student', 1),
(12, 'Cedrick', 'C', 'Delos Santos', 's2601002', '$2y$10$UGp5NEw.HVY1TpHb00BvAeE2hXVbE3FLQOHfaE5iORNzQdIIEQi8e', 'student', 2),
(13, 'Ericpoul', 'M', 'Batac', 's2601003', '$2y$10$yifUOx7LhSyYL1ChKcImuOuztQQNryAnvSgML2l.Vs41JghNf.K72', 'student', 2),
(14, 'Jay Ryan', 'C', 'Paulin', 's2601004', '$2y$10$n2ynS5UTdKKC4GW03z17iOCSsIG/dkE0eaIxc8/YqYnrbxyD3/oCm', 'student', 1),
(15, 'Jenver', 'C', 'Bawingan', 's2601005', '$2y$10$c.i8auaCXubP31qYoYa3.OOu6.v9R0.1PjrMpVRUyLSmtnT.MwR/e', 'student', 2),
(16, 'Jerome', 'G', 'Rosales', 's2601006', '$2y$10$eDyDilBjpWH7ZeV8cHYeoOYDjtKSwp37J59FFXRY3Ub1in.7NB0bG', 'student', 3),
(17, 'Jhon Ronald', 'S', 'Balaoro', 's2601007', '$2y$10$8z53A5.6VOaTuW3qJUmHhuXo6Klo8breQE9m6QjOyUWFpWAVCwGOi', 'student', 3),
(18, 'John Erickson', 'A', 'Anuario', 's2601008', '$2y$10$WZednYFUPi//6KGukex.0eTTr1hpAPh01bCOECUX2Iqu9wTxZE..q', 'student', 2),
(19, 'Lexis', 'E', 'Pascua', 's2601009', '$2y$10$D7z0Z3b9EOFzrf4ZxoLTgufJpe4WmYeoqPsl.oRf5MRacJItDpi9G', 'student', 1),
(20, 'Monica', 'E', 'Bibit', 's2601010', '$2y$10$5PnXJFiKrdINgSNXfIkKY.IZhcFg0ZU9MiCH0CrxURo7Uw/Fq2Deu', 'student', 2),
(21, 'Philip Jhunmark', 'E', 'Delacruz', 's2601011', '$2y$10$Cq5n2ADweqWiviaGcumoXO6iMMXtNNBxiAOff36/n0DVfXkja37cG', 'student', 3),
(22, 'Ranel', 'B', 'Silverio', 's2601012', '$2y$10$xkvOC1Du4YAA9RvwxDRLyeJEuVzJx4LDfJ1eQWO0/ygGhg.OZNEX6', 'student', 3),
(23, 'Renz Christ', 'G', 'Esguerra', 's2601013', '$2y$10$kSi2zDmFyVrr73rsZnedtuTzUBA.YVbhmk8sPcvnjPmX4/Ha6yMKe', 'student', 3),
(24, 'Roxanne', 'R', 'Rance', 's2601014', '$2y$10$2jZigoX7GswiuOf2Bty4ze1mefIdPKKf1dlvKF/q0hqWmxYKsowES', 'student', 1),
(30, 'Elaine', 'B', 'I MISS YOU', 's2601019', '$2y$10$HJVaPTAZSn2lBTRQWuszMuqISBSvaNOvpqWoBdJXKSgspKBGYLX2y', 'student', 1),
(37, 'Ron', 'BBB', 'Samaniego', 's2601020', '$2y$10$bGb8FBdyxW45jQ.hIu3HOeR.7bjAOC7oronqrpsrF/eiLO0oLoqmq', 'student', 4),
(38, 'Toni', 'A', 'Fowler', 's2601021', '$2y$10$OEpBrnf4kZ8upiTfug3AxenBr0.VNxs982oUBZpXMzZEb4nYXwW2C', 'student', 4),
(39, 'Wally', 'J', 'BAYOla', 's2601022', '$2y$10$dcYXljF3G0wfW5hpg55AbuPo/Dh5Sbi/8jkLElxSB.aw8kKnMPYYW', 'student', 4),
(40, 'Norman', 'M`', 'Mangusin `', 's2601023', '$2y$10$5rBU.QM3Qp0OyPQYXGmeDuFgzSRS9pvnEPRugs.eL82DJiBfPv0ZC', 'student', 4),
(41, 'Dwight', '', 'Ramos', 's2601024', '$2y$10$GWVv29ate5.PWdtEbZ3drOTuSJ6OOcDejEnUkryPtcIrea8jjNDD.', 'student', 4),
(42, 'Albert', 'J', 'Einstein', 's2601025', '$2y$10$Qu4vrRAKqn4qL/rPGZuMbeS4tiI3Wlj8dt2ZRqIXeg3DYNDj7K1ke', 'student', 5),
(43, 'Elon', 'T', 'Musk', 's2601026', '$2y$10$/llxk4aobZFuR78hdalQ0.8vnco2l56VCricHNIAiRHBk5V2v2gti', 'student', 5),
(44, 'We Are', '', 'Charlie Kirk', 's2601027', '$2y$10$LVoLZcjCDvNFIAaCMX0nYuhM81LE/vM8eDEhUnDHD39F99b8dgepG', 'student', 5),
(45, 'Stephen', 'W', 'Hawking', 's2601028', '$2y$10$yRjhBNfpAB4UveKKdwECjuD828N3oYhu8KhCfhw4TEJFqFEmDp/wu', 'student', 5),
(46, 'Light', 'D', 'Yagami', 's2601029', '$2y$10$NbBdCbqYVGZvcl9.w1cbK.LGnHAp2YsTUM7NMp1mQQFm1aeRPiQku', 'student', 5);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `choices`
--
ALTER TABLE `choices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `question_id` (`question_id`);

--
-- Indexes for table `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `quiz_attempts`
--
ALTER TABLE `quiz_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `sections`
--
ALTER TABLE `sections`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `school_id` (`student_id`),
  ADD KEY `fk_section` (`section_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `choices`
--
ALTER TABLE `choices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=371;

--
-- AUTO_INCREMENT for table `questions`
--
ALTER TABLE `questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=94;

--
-- AUTO_INCREMENT for table `quiz_attempts`
--
ALTER TABLE `quiz_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `sections`
--
ALTER TABLE `sections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `choices`
--
ALTER TABLE `choices`
  ADD CONSTRAINT `choices_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `questions`
--
ALTER TABLE `questions`
  ADD CONSTRAINT `questions_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `quiz_attempts`
--
ALTER TABLE `quiz_attempts`
  ADD CONSTRAINT `quiz_attempts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `quiz_attempts_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_section` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
