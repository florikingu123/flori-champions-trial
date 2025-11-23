-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 06, 2025 at 11:08 PM
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
-- Database: `famify`
--

-- --------------------------------------------------------

--
-- Table structure for table `assigned_rewards`
--

CREATE TABLE `assigned_rewards` (
  `id` int(11) NOT NULL,
  `member_email` varchar(255) NOT NULL,
  `reward_id` int(11) NOT NULL,
  `status` enum('pending','redeemed') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assigned_rewards`
--

INSERT INTO `assigned_rewards` (`id`, `member_email`, `reward_id`, `status`) VALUES
(4, 'flori@gmail.com', 2, 'pending'),
(5, 'flori@gmail.com', 2, 'pending'),
(6, 'testflori@gamil.com', 2, 'pending'),
(7, 'flori@gmail.com', 1, 'pending'),
(8, 'flori@gmail.com', 2, 'pending'),
(9, 'siar1@gmail.com', 3, 'redeemed'),
(10, 'ssiar@gmail.com', 1, 'pending'),
(11, 'ssiar@gmail.com', 2, 'pending'),
(12, 'ari@gmail.com', 14, 'pending'),
(13, 'siarollogu1@gmail.com', 15, 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `chores`
--

CREATE TABLE `chores` (
  `id` int(11) NOT NULL,
  `manager_email` varchar(255) NOT NULL,
  `member_email` varchar(255) NOT NULL,
  `chore_name` varchar(255) NOT NULL,
  `points` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chores`
--

INSERT INTO `chores` (`id`, `manager_email`, `member_email`, `chore_name`, `points`, `created_at`) VALUES
(1, 'flori@gmail.com', 'testflori222@gamil.com', 'wash the dishes', 41, '2025-03-01 18:09:35'),
(2, 'testflori222@gamil.com', 'testflori222@gamil.com', 'wash the dishes', 40, '2025-03-01 18:19:05'),
(4, 'testflori@gmail.com', 'testflori222@gamil.com', 'wash the dishes', 55, '2025-03-01 23:39:44'),
(5, 'testflori222@gamil.com', 'flori@gmail.com', 'wash the car', 10, '2025-03-02 17:22:02'),
(6, 'testflori222@gamil.com', 'flori@gmail.com', 'wash the dishes', 50, '2025-03-02 23:30:26'),
(7, 'testflori222@gamil.com', 'flori@gmail.com', 'clean the carpets', 50, '2025-03-02 23:37:08'),
(8, 'testflori222@gamil.com', 'flori@gmail.com', 'clean the carpets', 50, '2025-03-02 23:37:37'),
(9, 'testflori222@gamil.com', 'orhidea.ollogu@gmail.com', 'clean the carpets', 30, '2025-03-02 23:39:35'),
(15, 'floriollogu12@gmail.com', 'ari@gmail.com', 'Pastohr dhomen', 500, '2025-03-10 17:35:08');

-- --------------------------------------------------------

--
-- Table structure for table `contact`
--

CREATE TABLE `contact` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contact`
--

INSERT INTO `contact` (`id`, `name`, `email`, `subject`, `message`, `created_at`) VALUES
(1, 'flroi ollohu', 'farikoci3@gmail.com', 'JJur good company', 'rur fmaify or company is so good that i won this contest', '2025-03-01 00:03:17'),
(2, 'flroi ollohu', 'farikoci3@gmail.com', 'JJur good company', 'rur fmaify or company is so good that i won this contest', '2025-03-01 00:03:19'),
(3, 'fkor', 'farikoci322@gmail.com', 'JJur good company', '2222224', '2025-03-01 00:11:09'),
(4, 'kushrtim', 'farikoci3ww@gmail.com', 'sdfdf', 'fwefwefwfefsEFWGWRG', '2025-03-03 20:55:19'),
(5, 'Flori', 'olloguflori321@gmail.com', 'ur ', 'djggghkvkgjsvcjhsadbvkhsdb', '2025-03-05 20:16:25'),
(6, 'hi', 'testflori1111222@gmail.com', 'JJur good companyd', 'wbfewa ihef qf', '2025-03-10 17:20:44'),
(7, 't43t3t3', '342dfarikoci@gmail.com', '4t3q4t', '4q3t34t34t43', '2025-03-10 17:22:31');

-- --------------------------------------------------------

--
-- Table structure for table `family`
--

CREATE TABLE `family` (
  `id` int(11) NOT NULL,
  `managers_email` varchar(255) NOT NULL,
  `member_name` varchar(255) NOT NULL,
  `member_email` varchar(255) NOT NULL,
  `member_pass` varchar(255) NOT NULL,
  `points` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `family`
--

INSERT INTO `family` (`id`, `managers_email`, `member_name`, `member_email`, `member_pass`, `points`) VALUES
(29, 'test13@gmail.com', 'kusht', 'test114@gmail.com', '$2y$10$7q8sQ6xCjuv7teTYSqmLa.dqF0vjQ/o7MtlLSGO/YXTrorblRE/P2', 0),
(30, 'floriollogu123@gmail.com', 'Ari', 'ari@gmail.com', '$2y$10$7lcW8uvt9mlOtb5zsM.SRu01UILUzZnInsIS2AJsnJizS6Ral54EC', 0),
(31, 'ari123@gmail.com', 'Aris', 'ari12@gmail.com', '$2y$10$w22JArseFhf8RebMY7XfdONR7Xz7rQntMu8.5OI8X0yjtFG/pMtLO', 40),
(32, 'floriollogu1234@gmail.com', 'Siar', 'siarollogu1@gmail.com', '$2y$10$TxW./9C19cjV1IU1Ry/33.pRMk1bi2KUAhTpvs1bEdodrKDAuDDrK', 40);

-- --------------------------------------------------------

--
-- Table structure for table `newsletter`
--

CREATE TABLE `newsletter` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `subscribe_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `newsletter`
--

INSERT INTO `newsletter` (`id`, `email`, `subscribe_date`) VALUES
(1, 'floriollogu@gmail.com', '2025-02-10 19:59:35'),
(2, 'ylber@gmail.com', '2025-02-10 20:02:11'),
(3, 'gfhdsh@gmail.com', '2025-02-10 20:03:16'),
(5, 'gfhdsh12@gmail.com', '2025-02-10 20:03:28'),
(6, 'ylber2312321312@gmail.com', '2025-02-10 20:06:06'),
(7, 'ylber1323@gmail.com', '2025-02-10 20:06:52'),
(8, 'gjkhfg@gmail.com', '2025-02-10 20:11:55'),
(9, 'farikoci3@gmail.com', '2025-02-20 21:22:18'),
(15, 'farikocinow3@gmail.com', '2025-02-20 21:22:37'),
(16, 'farikoci311111@gmail.com', '2025-02-20 21:32:42'),
(17, 'test@gmail.com', '2025-02-20 21:33:06'),
(19, 'follgou123@gmail.cim', '2025-02-20 21:34:23'),
(20, 'farikoci32222222@gmail.com', '2025-02-24 20:40:41'),
(21, 'farikoci3111111111111111@gmail.com', '2025-02-24 20:44:08'),
(24, 'farikoci31111@gmail.com', '2025-02-26 19:41:31'),
(26, 'test21213@gmail.com', '2025-02-26 19:46:49'),
(27, 'test2qqqqqqqqqqqq@gmail.com', '2025-02-26 19:48:11');

-- --------------------------------------------------------

--
-- Table structure for table `newsletter_subscribers`
--

CREATE TABLE `newsletter_subscribers` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `subscribe_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `newsletter_subscribers`
--

INSERT INTO `newsletter_subscribers` (`id`, `email`, `subscribe_date`) VALUES
(1, '3213213@gmail.com', '2025-02-26 19:54:54'),
(2, 'follgou12212321313@gmail.cim', '2025-02-26 19:55:55'),
(3, 'test1232132@gmail.com', '2025-02-26 19:59:26'),
(4, 'test32132@gmail.com', '2025-02-26 20:01:08'),
(5, 'test2c2@gmail.com', '2025-02-26 20:01:43'),
(7, 'test2231c2@gmail.com', '2025-02-26 20:01:48'),
(9, 'test223vsadsadsad1c2@gmail.com', '2025-02-26 20:01:52'),
(11, 'ariki725@gmail.com', '2025-02-26 20:02:35'),
(13, 'ariki72czx325@gmail.com', '2025-02-26 20:02:41'),
(15, 'ariki72c123213zx325@gmail.com', '2025-02-26 20:02:46'),
(17, 'ariki72ccxzc1123213zx325@gmail.com', '2025-02-26 20:02:53'),
(19, 'tes02t@gmail.com', '2025-02-26 20:03:23'),
(21, 'test2qqqqqqqqq2qqq@gmail.com', '2025-02-26 20:04:07'),
(23, 'testvs2da@gmail.com', '2025-02-26 20:04:20'),
(25, 'test2azw@gmail.com', '2025-02-26 20:07:12'),
(26, 'test2azcvaswq21w@gmail.com', '2025-02-26 20:07:18'),
(27, 'test2azcvaswqcasdsa221w@gmail.com', '2025-02-26 20:08:05'),
(28, 'testv2@gmail.com', '2025-02-26 20:08:55'),
(29, 'orhidea.o213llogu@gmail.com', '2025-02-26 20:10:20'),
(30, 'tes2t@gmail.com', '2025-02-26 20:11:42'),
(32, 'tes3213122t@gmail.com', '2025-02-26 20:11:47'),
(34, 'test2qqqq223aqqqqqqqq@gmail.com', '2025-02-26 20:12:22'),
(35, 'farikoci3111111111111111112@gmail.com', '2025-02-26 20:13:36'),
(36, 'fitoresefedini841@gmail.com1', '2025-02-26 20:16:52'),
(37, 'farikoci3@gmail.com', '2025-02-28 23:49:16'),
(38, 'fareaffikoci3@gmail.com', '2025-03-01 20:29:56'),
(39, 'testflori2WWW22@gamil.com', '2025-03-03 20:56:03'),
(40, 'floriollogu@gmail.com', '2025-03-05 20:13:29');

-- --------------------------------------------------------

--
-- Table structure for table `rewards`
--

CREATE TABLE `rewards` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `image` varchar(255) NOT NULL,
  `points_required` int(11) NOT NULL,
  `assigned_to` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rewards`
--

INSERT INTO `rewards` (`id`, `name`, `image`, `points_required`, `assigned_to`) VALUES
(1, 'Chips', 'smile (8).png', 10, ''),
(2, 'car', 'MKS3240 - Coding - 08 February 2025 Certificate Of Participation.jpg', 1000000, ''),
(3, 'toy', 'smile.png', 40, ''),
(14, 'Alfabet', 'MKS3240 - Coding - 08 February 2025 Certificate Of Participation.jpg', 45, ''),
(15, 'Makin', 'MKS3240 - Coding - 08 February 2025 Certificate Of Participation.jpg', 50, '');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `number` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_admin` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `number`, `created_at`, `is_admin`) VALUES
(47, '1234567@gmail.com', '$2y$10$Ir43kA2LMXIhyUPj1mmzgOrX.R/UhOHZumD1lNKgYEvIwzqN86.IK', '12324212', '2025-03-05 20:34:35', 0),
(48, '123456788@gmail.com', '$2y$10$hG1RD1jJn99UTjMwyCg1F.F6jKVYhVmbmW2KWpNYPKV.UgbgeFs66', '13242534', '2025-03-05 20:43:56', 0),
(49, 'test23@gmail.com', '$2y$10$DyoQUF8IIRkUSzpbqIvyM.HpHUZNutYbdwttXAR0JNPyzRN5CggZO', '123123', '2025-03-05 20:51:39', 0),
(50, 'testflori1@gmail.com', '$2y$10$Rayz7byS/Tx0X/DTzxp10u.c8FKoU6b/r2n.vjjlqwO5DS1.tjKM6', '123123213', '2025-03-05 20:53:55', 0),
(51, 'test13@gmail.com', '$2y$10$3.Dq74JvUptGHw1ykB6ag.ll1SvthJGIZYHud41nCR5FmGOrO12TS', '121212', '2025-03-05 20:56:03', 0),
(52, 'floriollogu12@gmail.com', '$2y$10$fN4WLwXcbqh8OGC6yKyS8uApxY8JpDJKLfueRdMqAcR1qa.hXXmGi', '234234242', '2025-03-10 17:23:15', 0),
(53, 'ari123@gmail.com', '$2y$10$xElBWdZ47e/oMJzsRoYxkOM08M774zOdSVTwudhnGToF83rnisGpu', '142353456', '2025-03-10 17:37:00', 0),
(54, 'floriollogu1234@gmail.com', '$2y$10$Bm6dqnC2sOdVVvWN5mW0iuTXTPKjfxlFW/dCPOwdTHHL3WDhZCEvK', '2123423', '2025-03-10 20:08:48', 0),
(55, 'farikoci3111@gmail.com', '$2y$10$OpMpJ7Lye6Bk1J0o2DQpsetOskRsZ5uDnBINfUOrCXc.X9KvxovDK', '123322', '2025-05-06 21:06:10', 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `assigned_rewards`
--
ALTER TABLE `assigned_rewards`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reward_id` (`reward_id`);

--
-- Indexes for table `chores`
--
ALTER TABLE `chores`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `contact`
--
ALTER TABLE `contact`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `family`
--
ALTER TABLE `family`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `member_email` (`member_email`);

--
-- Indexes for table `newsletter`
--
ALTER TABLE `newsletter`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `newsletter_subscribers`
--
ALTER TABLE `newsletter_subscribers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `rewards`
--
ALTER TABLE `rewards`
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
-- AUTO_INCREMENT for table `assigned_rewards`
--
ALTER TABLE `assigned_rewards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `chores`
--
ALTER TABLE `chores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `contact`
--
ALTER TABLE `contact`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `family`
--
ALTER TABLE `family`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `newsletter`
--
ALTER TABLE `newsletter`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `newsletter_subscribers`
--
ALTER TABLE `newsletter_subscribers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `rewards`
--
ALTER TABLE `rewards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assigned_rewards`
--
ALTER TABLE `assigned_rewards`
  ADD CONSTRAINT `assigned_rewards_ibfk_1` FOREIGN KEY (`reward_id`) REFERENCES `rewards` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
