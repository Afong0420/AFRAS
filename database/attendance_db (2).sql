-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 25, 2026 at 07:17 AM
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
-- Database: `attendance_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `Id` int(10) NOT NULL,
  `firstName` varchar(50) NOT NULL,
  `lastName` varchar(50) NOT NULL,
  `emailAddress` varchar(50) NOT NULL,
  `password` varchar(250) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`Id`, `firstName`, `lastName`, `emailAddress`, `password`) VALUES
(1, 'Admin', '', 'admin@gmail.com', '$2y$10$FIBqWvTOXRMoQOAB2FBz3uUbaCwRYTM1zQreFI6i/7v6Qi8y9R1i6');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `attendanceID` int(50) NOT NULL,
  `studentRegistrationNumber` varchar(100) NOT NULL,
  `course` varchar(100) NOT NULL,
  `attendanceStatus` varchar(100) NOT NULL,
  `dateMarked` date NOT NULL,
  `unit` varchar(100) NOT NULL,
  `session` enum('morning','afternoon','') NOT NULL DEFAULT '',
  `timeMarked` time DEFAULT NULL,
  `session_end_time` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`attendanceID`, `studentRegistrationNumber`, `course`, `attendanceStatus`, `dateMarked`, `unit`, `session`, `timeMarked`, `session_end_time`) VALUES
(511, '221-11754', 'BCT', 'Absent', '2026-02-20', 'BCT 2411', '', NULL, NULL),
(512, '221-11754', 'BCT', 'Absent', '2026-02-20', 'BCT 2411', '', NULL, NULL),
(513, '221-11627', 'BCT', 'Absent', '2026-02-20', 'BCT 2411', '', NULL, NULL),
(514, '221-11754', 'BCT', 'Absent', '2026-02-20', 'BCT 2411', '', NULL, NULL),
(515, '221-11627', 'BCT', 'Absent', '2026-02-20', 'BCT 2411', '', NULL, NULL),
(516, '221-11754', 'BCT', 'Absent', '2026-02-23', 'BCT 2411', '', NULL, NULL),
(517, '221-11627', 'BCT', 'Absent', '2026-02-23', 'BCT 2411', '', NULL, NULL),
(518, '221-11713', 'BSIT', 'Absent', '2026-03-11', '', '', NULL, NULL),
(519, '221-11754', 'BSIT', 'Absent', '2026-03-11', '', '', NULL, NULL),
(520, '221-11713', 'BSIT', 'Absent', '2026-03-11', 'CC103', '', NULL, NULL),
(521, '221-11754', 'BSIT', 'Absent', '2026-03-11', 'CC103', '', NULL, NULL),
(522, '221-11713', 'BSIT', 'Absent', '2026-03-11', 'CC103', '', NULL, NULL),
(523, '221-11754', 'BSIT', 'Absent', '2026-03-11', 'CC103', '', NULL, NULL),
(524, '221-11713', 'BSIT', 'Absent', '2026-03-11', 'CC103', '', NULL, NULL),
(525, '221-11754', 'BSIT', 'Absent', '2026-03-11', 'CC103', '', NULL, NULL),
(526, '221-11713', 'BSIT', 'Absent', '2026-03-11', 'CC103', '', NULL, NULL),
(527, '221-11754', 'BSIT', 'Absent', '2026-03-11', 'CC103', '', NULL, NULL),
(528, '221-11713', 'BSIT', 'Absent', '2026-03-12', 'CC103', '', NULL, NULL),
(529, '221-11754', 'BSIT', 'Present', '2026-03-12', 'CC103', '', NULL, NULL),
(530, '221-11632', 'BSIT', 'Absent', '2026-03-12', 'CC103', '', NULL, NULL),
(531, '221-11713', 'BSIT', 'Absent', '2026-03-12', 'CC103', '', NULL, NULL),
(532, '221-11754', 'BSIT', 'Present', '2026-03-12', 'CC103', '', NULL, NULL),
(533, '221-11632', 'BSIT', 'Absent', '2026-03-12', 'CC103', '', NULL, NULL),
(534, '221-11713', 'BSIT', 'Absent', '2026-03-12', 'CC103', '', NULL, NULL),
(535, '221-11754', 'BSIT', 'Absent', '2026-03-12', 'CC103', '', NULL, NULL),
(536, '221-11632', 'BSIT', 'Absent', '2026-03-12', 'CC103', '', NULL, NULL),
(537, '221-11713', 'BSIT', 'Present', '2026-03-12', 'CC103', '', NULL, NULL),
(538, '221-11754', 'BSIT', 'Present', '2026-03-12', 'CC103', '', NULL, NULL),
(539, '221-11632', 'BSIT', 'Absent', '2026-03-12', 'CC103', '', NULL, NULL),
(540, '221-11713', 'BSIT', 'Absent', '2026-03-13', 'CC103', '', NULL, NULL),
(541, '221-11754', 'BSIT', 'Present', '2026-03-13', 'CC103', '', NULL, NULL),
(542, '221-11632', 'BSIT', 'Absent', '2026-03-13', 'CC103', '', NULL, NULL),
(543, '221-11713', 'BSIT', 'Present', '2026-03-19', 'CC103', '', NULL, NULL),
(544, '221-11754', 'BSIT', 'Absent', '2026-03-19', 'CC103', '', NULL, NULL),
(545, '221-11632', 'BSIT', 'Absent', '2026-03-19', 'CC103', '', NULL, NULL),
(546, '221-11713', 'BSIT', 'Present', '2026-03-19', 'CC103', '', NULL, NULL),
(547, '221-11754', 'BSIT', 'Absent', '2026-03-19', 'CC103', '', NULL, NULL),
(548, '221-11632', 'BSIT', 'Absent', '2026-03-19', 'CC103', '', NULL, NULL),
(549, '221-11713', 'BSIT', 'Present', '2026-03-19', 'CC103', '', NULL, NULL),
(550, '221-11754', 'BSIT', 'Absent', '2026-03-19', 'CC103', '', NULL, NULL),
(551, '221-11632', 'BSIT', 'Absent', '2026-03-19', 'CC103', '', NULL, NULL),
(552, '221-11713', 'BSIT', 'Present', '2026-03-19', 'CC103', '', '14:04:40', NULL),
(553, '221-11754', 'BSIT', 'Absent', '2026-03-19', 'CC103', '', NULL, NULL),
(554, '221-11632', 'BSIT', 'Absent', '2026-03-19', 'CC103', '', NULL, NULL),
(555, '221-11713', 'BSIT', 'Present', '2026-03-19', 'CC103', '', '14:04:40', NULL),
(556, '221-11754', 'BSIT', 'Absent', '2026-03-19', 'CC103', '', '00:00:00', NULL),
(557, '221-11632', 'BSIT', 'Absent', '2026-03-19', 'CC103', '', '00:00:00', NULL),
(558, '221-11627', 'BSIT', 'Absent', '2026-03-19', 'CC103', '', '00:00:00', NULL),
(559, '221-11713', 'BSIT', 'Basic Lab A', '2026-03-19', 'CC103', '', '00:00:00', NULL),
(560, '221-11754', 'BSIT', 'Basic Lab A', '2026-03-19', 'CC103', '', '00:00:00', NULL),
(561, '221-11632', 'BSIT', 'Basic Lab A', '2026-03-19', 'CC103', '', '00:00:00', NULL),
(562, '221-11627', 'BSIT', 'Basic Lab A', '2026-03-19', 'CC103', '', '00:00:00', NULL),
(563, '221-11713', 'BSIT', 'Present', '2026-03-19', 'CC103', '', '14:33:17', '14:35:38'),
(564, '221-11754', 'BSIT', 'Absent', '2026-03-19', 'CC103', '', '14:33:17', '14:35:38'),
(565, '221-11632', 'BSIT', 'Absent', '2026-03-19', 'CC103', '', '14:33:17', '14:35:38'),
(566, '221-11627', 'BSIT', 'Absent', '2026-03-19', 'CC103', '', '14:33:17', '14:35:38'),
(567, '221-11627', 'BSIT', 'Absent', '2026-03-19', 'CC103', '', '14:43:05', '14:44:57'),
(568, '221-11754', 'BSIT', 'Absent', '2026-03-19', 'CC103', '', '14:43:05', '14:44:57'),
(569, '221-11713', 'BSIT', 'Absent', '2026-03-19', 'CC103', '', '14:43:05', '14:44:57'),
(570, '221-11632', 'BSIT', 'Absent', '2026-03-19', 'CC103', '', '14:43:05', '14:44:57'),
(571, '221-11627', 'BSIT', 'Absent', '2026-03-24', 'CC103', '', '11:19:18', '11:20:25'),
(572, '221-11754', 'BSIT', 'Absent', '2026-03-24', 'CC103', '', '11:19:18', '11:20:25'),
(573, '221-11713', 'BSIT', 'Present', '2026-03-24', 'CC103', '', '11:19:18', '11:20:25'),
(574, '221-11632', 'BSIT', 'Absent', '2026-03-24', 'CC103', '', '11:19:18', '11:20:25'),
(575, '221-11713', 'BSIT', 'Absent', '2026-03-24', 'CC103', '', NULL, NULL),
(576, '221-11754', 'BSIT', 'Absent', '2026-03-24', 'CC103', '', NULL, NULL),
(577, '221-11632', 'BSIT', 'Absent', '2026-03-24', 'CC103', '', NULL, NULL),
(578, '221-11627', 'BSIT', 'Absent', '2026-03-24', 'CC103', '', NULL, NULL),
(579, '221-11713', 'BSIT', 'Absent', '2026-03-24', 'CC103', '', NULL, NULL),
(580, '221-11754', 'BSIT', 'Absent', '2026-03-24', 'CC103', '', NULL, NULL),
(581, '221-11632', 'BSIT', 'Absent', '2026-03-24', 'CC103', '', NULL, NULL),
(582, '221-11627', 'BSIT', 'Absent', '2026-03-24', 'CC103', '', NULL, NULL),
(583, '221-11713', 'BSIT', 'Absent', '2026-03-24', 'CC103', '', NULL, NULL),
(584, '221-11754', 'BSIT', 'Absent', '2026-03-24', 'CC103', '', NULL, NULL),
(585, '221-11632', 'BSIT', 'Absent', '2026-03-24', 'CC103', '', NULL, NULL),
(586, '221-11627', 'BSIT', 'Absent', '2026-03-24', 'CC103', '', NULL, NULL),
(587, '221-11713', 'BSIT', 'Present', '2026-03-24', 'CC103', '', NULL, NULL),
(588, '221-11754', 'BSIT', 'Absent', '2026-03-24', 'CC103', '', NULL, NULL),
(589, '221-11632', 'BSIT', 'Present', '2026-03-24', 'CC103', '', NULL, NULL),
(590, '221-11627', 'BSIT', 'Absent', '2026-03-24', 'CC103', '', NULL, NULL),
(591, '221-11713', 'BSIT', 'Present', '2026-03-24', 'CC103', '', NULL, NULL),
(592, '221-11754', 'BSIT', 'Absent', '2026-03-24', 'CC103', '', NULL, NULL),
(593, '221-11632', 'BSIT', 'Absent', '2026-03-24', 'CC103', '', NULL, NULL),
(594, '221-11627', 'BSIT', 'Absent', '2026-03-24', 'CC103', '', NULL, NULL),
(595, '221-11713', 'BSIT', 'Present', '2026-03-24', 'CC103', '', NULL, NULL),
(596, '221-11754', 'BSIT', 'Absent', '2026-03-24', 'CC103', '', NULL, NULL),
(597, '221-11632', 'BSIT', 'Present', '2026-03-24', 'CC103', '', NULL, NULL),
(598, '221-11627', 'BSIT', 'Absent', '2026-03-24', 'CC103', '', NULL, NULL),
(599, '221-11713', 'BSIT', 'Present', '2026-03-24', 'CC103', '', NULL, NULL),
(600, '221-11754', 'BSIT', 'Absent', '2026-03-24', 'CC103', '', NULL, NULL),
(601, '221-11632', 'BSIT', 'Absent', '2026-03-24', 'CC103', '', NULL, NULL),
(602, '221-11627', 'BSIT', 'Absent', '2026-03-24', 'CC103', '', NULL, NULL),
(603, '221-11713', 'BSIT', 'Present', '2026-03-24', 'CC103', '', NULL, NULL),
(604, '221-11754', 'BSIT', 'Present', '2026-03-24', 'CC103', '', NULL, NULL),
(605, '221-11632', 'BSIT', 'Absent', '2026-03-24', 'CC103', '', NULL, NULL),
(606, '221-11627', 'BSIT', 'Absent', '2026-03-24', 'CC103', '', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `course`
--

CREATE TABLE `course` (
  `Id` int(50) NOT NULL,
  `name` varchar(50) NOT NULL,
  `facultyID` int(50) NOT NULL,
  `dateCreated` date NOT NULL,
  `courseCode` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `course`
--

INSERT INTO `course` (`Id`, `name`, `facultyID`, `dateCreated`, `courseCode`) VALUES
(20, 'Information Technology', 20, '2026-03-11', 'BSIT');

-- --------------------------------------------------------

--
-- Table structure for table `faculty`
--

CREATE TABLE `faculty` (
  `Id` int(10) NOT NULL,
  `facultyName` varchar(255) NOT NULL,
  `facultyCode` varchar(50) NOT NULL,
  `dateRegistered` date NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `faculty`
--

INSERT INTO `faculty` (`Id`, `facultyName`, `facultyCode`, `dateRegistered`) VALUES
(20, 'Information Technology', 'College of Computer Education', '2026-03-11');

-- --------------------------------------------------------

--
-- Table structure for table `lecture`
--

CREATE TABLE `lecture` (
  `Id` int(10) NOT NULL,
  `firstName` varchar(255) NOT NULL,
  `lastName` varchar(255) NOT NULL,
  `emailAddress` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phoneNo` varchar(50) NOT NULL,
  `facultyCode` varchar(50) NOT NULL,
  `dateCreated` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `lecture`
--

INSERT INTO `lecture` (`Id`, `firstName`, `lastName`, `emailAddress`, `password`, `phoneNo`, `facultyCode`, `dateCreated`) VALUES
(15, 'mark', 'lila', 'mark@gmail.com', '$2y$10$/st06w2mh/4adxGE9yCxROHkqHp6SzRARGhfCIg95zC3cxqbmkpaW', '07123456789', 'CIT', '2024-04-07'),
(24, 'Jezreel', 'Buccat', 'Jezreel@gmail.com', '$2y$10$7eOJI1MR8jRFxqlvmyhfxuEme1eG/MI1m9NtLHehUOJUn28Ajbcbm', '09123456789', 'College of Computer Education', '2026-03-24'),
(25, 'Romeo', 'Balcita', 'Romeo@gmail.com', '$2y$10$DgS8eXgnbaJVsTJ6cw/RLuuEbJq0WRe5zACpyJcUGtSx6bxcSShwC', '09123456789', 'College of Computer Education', '2026-03-24'),
(26, 'Rommel', 'Balcita', 'Rommel@gmail.com', '$2y$10$PcrIGtvi3rbD12qD2KNcmOeJqDukGbIvFebHcFSpEdxX5ajZnAds6', '09123456789', 'College of Computer Education', '2026-03-24'),
(27, 'Paul Joseph', 'Amando', 'PaulJoseph@gmail.com', '$2y$10$FiQKUl1OC6gucLQQf2ouQODqXvRs7G4WDaiprLsVSSrqM3.7siPgG', '09123456789', 'College of Computer Education', '2026-03-24'),
(28, 'Maria Milagros', 'Manzano', 'MariaMilagros@gmail.com', '$2y$10$VqRe8HmzzBLLYBKAb15k6ewGtSOm8H8AGWnC6RHt8M3S/mQdXgBIO', '09123456789', 'College of Computer Education', '2026-03-24'),
(29, 'Arnold', 'Adlawan', 'Arnold@gmail.com', '$2y$10$M/x4Y3VVk/JcB/qVFPM1.OdVKBYG/U4PUafENqoCsjVYH1ckpZ0lK', '09123456789', 'College of Computer Education', '2026-03-24');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `Id` int(10) NOT NULL,
  `firstName` varchar(255) NOT NULL,
  `lastName` varchar(255) NOT NULL,
  `registrationNumber` varchar(255) NOT NULL,
  `email` varchar(50) NOT NULL,
  `faculty` varchar(10) NOT NULL,
  `courseCode` varchar(20) NOT NULL,
  `studentImage` varchar(300) NOT NULL,
  `dateRegistered` varchar(50) NOT NULL,
  `yearLevel` varchar(20) NOT NULL,
  `section` varchar(5) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`Id`, `firstName`, `lastName`, `registrationNumber`, `email`, `faculty`, `courseCode`, `studentImage`, `dateRegistered`, `yearLevel`, `section`) VALUES
(133, 'John Arvin', 'Hufana', '221-11713', 'afong@gmail.com', 'FICT', 'BSIT', '[\"221-11713_image1.png\",\"221-11713_image2.png\",\"221-11713_image3.png\",\"221-11713_image4.png\",\"221-11713_image5.png\"]', '2026-03-11', '4', 'A'),
(132, 'Ivy', 'Castaneda', '221-11754', 'ivycastaneda@gmail.com', 'FICT', 'BSIT', '[\"221-11754_image1.png\",\"221-11754_image2.png\",\"221-11754_image3.png\",\"221-11754_image4.png\",\"221-11754_image5.png\"]', '2026-03-11', '4', 'A'),
(135, 'Brix Allan Dave', 'Olegario', '221-11632', 'brix@gmail.com', 'FICT', 'BSIT', '[\"221-11632_image1.png\",\"221-11632_image2.png\",\"221-11632_image3.png\",\"221-11632_image4.png\",\"221-11632_image5.png\"]', '2026-03-11', '4', 'A'),
(136, 'LEA JEAN', 'ABERIN', '221-11627', 'lj@gmail.com', 'FICT', 'BSIT', '[\"221-11627_image1.png\",\"221-11627_image2.png\",\"221-11627_image3.png\",\"221-11627_image4.png\",\"221-11627_image5.png\"]', '2026-03-19', '4', 'A');

-- --------------------------------------------------------

--
-- Table structure for table `unit`
--

CREATE TABLE `unit` (
  `Id` int(10) NOT NULL,
  `name` varchar(50) NOT NULL,
  `unitCode` varchar(50) NOT NULL,
  `courseID` varchar(50) NOT NULL,
  `dateCreated` date NOT NULL,
  `startTime` time DEFAULT NULL,
  `endTime` time DEFAULT NULL,
  `venueID` int(10) DEFAULT NULL,
  `lectureID` int(10) DEFAULT NULL,
  `scheduleDays` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `unit`
--

INSERT INTO `unit` (`Id`, `name`, `unitCode`, `courseID`, `dateCreated`, `startTime`, `endTime`, `venueID`, `lectureID`, `scheduleDays`) VALUES
(11, 'Computer Pogramming(lec/lab)', 'CC103', '20', '2026-03-24', '07:30:00', '10:30:00', 22, 24, 'Mon,Fri'),
(12, 'Data Structures and Algorithms', 'CC104', '20', '2026-03-24', '10:30:00', '12:00:00', 23, 25, 'Tue,Thu');

-- --------------------------------------------------------

--
-- Table structure for table `unit_schedule`
--

CREATE TABLE `unit_schedule` (
  `Id` int(10) NOT NULL,
  `unitId` int(10) NOT NULL,
  `day` enum('Mon','Tue','Wed','Thu','Fri') NOT NULL,
  `startTime` time NOT NULL,
  `endTime` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `unit_schedule`
--

INSERT INTO `unit_schedule` (`Id`, `unitId`, `day`, `startTime`, `endTime`) VALUES
(5, 11, 'Mon', '07:30:00', '10:30:00'),
(6, 11, 'Fri', '10:00:00', '12:00:00'),
(7, 12, 'Tue', '10:30:00', '12:00:00'),
(8, 12, 'Thu', '10:30:00', '12:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `venue`
--

CREATE TABLE `venue` (
  `Id` int(10) NOT NULL,
  `className` varchar(50) NOT NULL,
  `facultyCode` varchar(50) NOT NULL,
  `currentStatus` varchar(50) NOT NULL,
  `capacity` int(10) NOT NULL,
  `classification` varchar(50) NOT NULL,
  `dateCreated` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `venue`
--

INSERT INTO `venue` (`Id`, `className`, `facultyCode`, `currentStatus`, `capacity`, `classification`, `dateCreated`) VALUES
(20, 'Basic Lab A', 'College of Computer Education', 'available', 50, 'laboratory', '2026-03-11'),
(21, 'Basic Lab B', 'College of Computer Education', 'available', 50, 'laboratory', '2026-03-11'),
(22, 'IT Lab', 'College of Computer Education', 'available', 50, 'laboratory', '2026-03-11'),
(23, 'Room 113', 'College of Computer Education', 'available', 50, 'class', '2026-03-11');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`Id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`attendanceID`);

--
-- Indexes for table `course`
--
ALTER TABLE `course`
  ADD PRIMARY KEY (`Id`);

--
-- Indexes for table `faculty`
--
ALTER TABLE `faculty`
  ADD PRIMARY KEY (`Id`);

--
-- Indexes for table `lecture`
--
ALTER TABLE `lecture`
  ADD PRIMARY KEY (`Id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`Id`);

--
-- Indexes for table `unit`
--
ALTER TABLE `unit`
  ADD PRIMARY KEY (`Id`);

--
-- Indexes for table `unit_schedule`
--
ALTER TABLE `unit_schedule`
  ADD PRIMARY KEY (`Id`),
  ADD UNIQUE KEY `unit_day` (`unitId`,`day`);

--
-- Indexes for table `venue`
--
ALTER TABLE `venue`
  ADD PRIMARY KEY (`Id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `Id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendanceID` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=607;

--
-- AUTO_INCREMENT for table `course`
--
ALTER TABLE `course`
  MODIFY `Id` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `faculty`
--
ALTER TABLE `faculty`
  MODIFY `Id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `lecture`
--
ALTER TABLE `lecture`
  MODIFY `Id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `Id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=137;

--
-- AUTO_INCREMENT for table `unit`
--
ALTER TABLE `unit`
  MODIFY `Id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `unit_schedule`
--
ALTER TABLE `unit_schedule`
  MODIFY `Id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `venue`
--
ALTER TABLE `venue`
  MODIFY `Id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `unit_schedule`
--
ALTER TABLE `unit_schedule`
  ADD CONSTRAINT `fk_unit_schedule_unit` FOREIGN KEY (`unitId`) REFERENCES `unit` (`Id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
