-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 09, 2025 at 01:36 PM
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
-- Database: `herkansing`
--

-- --------------------------------------------------------

--
-- Table structure for table `contact`
--

CREATE TABLE `contact` (
  `id` int(11) NOT NULL,
  `naam` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `bericht` text NOT NULL,
  `datum` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contact`
--

INSERT INTO `contact` (`id`, `naam`, `email`, `bericht`, `datum`) VALUES
(1, 'dasdsada', 'pettervdwekken@gmail.com', 'ddddddddddddddddddddddd', '2025-07-09 12:44:22'),
(2, 'dasdsada', 'marieke@vanbuytene.nl', 'dasdsadsadsa', '2025-07-09 12:45:14'),
(3, 'dsadasdsa', 'petter@vanderwekken.nl', 'dsasdsdaasd', '2025-07-09 12:45:22');

-- --------------------------------------------------------

--
-- Table structure for table `motors`
--

CREATE TABLE `motors` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `price` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `motors`
--

INSERT INTO `motors` (`id`, `name`, `description`, `price`) VALUES
(1, 'Suzuki Hayabusa ', 'Legendarische high‑performance sportmotor met 1.340 cc viercilinder, aerodynamisch design en geavanceerde elektronica (Launch Control, Smart Cruise Control, verschillende rijmodi)', 22400),
(2, 'Ducati Panigale V2', '120 pk twin-cylinder superbike, lichter frame, Öhlins‑ophanging optioneel (V2 S), volledig LED-verlichting, 5‑inch TFT display, zes-assige IMU met ABS, tractie‑ en wheeliekontrole', 18995),
(3, 'Aprilia RS 660', 'Mid-weight sportmotor (659 cc), 105 pk, nieuwe winglets voor extra downforce, semi-actieve elektronische rijhulpsystemen, uitgebreide elektronica op een groter TFT‑display', 13999),
(4, 'Kawasaki Ninja 300', 'Instap-sportmotor met 300 cc, lichte styling‑updates, behoud van mechaniek gericht op beginnende sportrijders', 14200);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`order_data`)),
  `total` decimal(10,2) NOT NULL,
  `order_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `order_data`, `total`, `order_date`) VALUES
(1, 1, '[{\"id\":\"3\",\"name\":\"Aprilia RS\\u202f660\",\"price\":13999,\"img\":\"img\\/aprilia.jpg\"}]', 13999.00, '2025-07-09 13:08:33'),
(3, 2, '[{\"id\":\"2\",\"name\":\"Ducati Panigale V2\",\"price\":18995,\"img\":\"img\\/ducati.jpg\",\"aantal\":1},{\"id\":\"1\",\"name\":\"Suzuki Hayabusa \",\"price\":22400,\"img\":\"img\\/suzuki.jpg\",\"aantal\":2}]', 41395.00, '2025-07-09 13:34:19');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `motor_id` int(255) NOT NULL,
  `naam` varchar(255) NOT NULL,
  `beoordeling` text NOT NULL,
  `bericht` text NOT NULL,
  `datum` int(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `motor_id`, `naam`, `beoordeling`, `bericht`, `datum`) VALUES
(1, 1, 'dasdsada', '5', 'dsadasdsa', 2147483647),
(2, 1, 'dasdsada', '5', 'dsadasdsa', 2147483647),
(3, 1, 'dassd', '4', 'dasdsa', 2147483647),
(4, 1, 'dassd', '4', 'dasdsa', 2147483647),
(5, 1, 'dsada', '2', 'dsadas', 2147483647),
(6, 1, 'sadsadasdas', '3', 'dasdad', 2147483647),
(7, 2, 'dsadsa', '4', 'dsasda', 2147483647),
(8, 2, 'dsadsa', '4', 'dsasda', 2147483647),
(9, 2, 'dsadsa', '4', 'dsasda', 2147483647);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`) VALUES
(1, 'petter', 'petter@gmail.com', '$2y$10$nrXdCi6v/PPcIs0jDpfm9.GXFFOhilmjHOaC8MrHTiVokv33hhu0u', 'admin'),
(2, 'jaa', 'ja@gmail.com', '$2y$10$QSF40Pmt4IJIs/A4exXvhuszMOIRsdsvi.vSYeREXBhL7jJehwCzm', 'user');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `contact`
--
ALTER TABLE `contact`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `motors`
--
ALTER TABLE `motors`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `contact`
--
ALTER TABLE `contact`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `motors`
--
ALTER TABLE `motors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
