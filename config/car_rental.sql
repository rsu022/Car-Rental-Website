-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 15, 2025 at 08:33 AM
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
-- Database: `car_rental`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `car_id` int(11) NOT NULL,
  `pickup_location_id` int(11) NOT NULL,
  `return_location_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `status` enum('pending','approved','cancelled','completed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `agreed_terms` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `car_id`, `pickup_location_id`, `return_location_id`, `start_date`, `end_date`, `total_price`, `status`, `created_at`, `agreed_terms`) VALUES
(1, 1, 2, 2, 3, '2025-05-08', '2025-05-09', 4400.00, 'approved', '2025-05-05 09:20:01', 0),
(2, 1, 1, 1, 1, '2025-05-07', '2025-05-17', 38500.00, 'approved', '2025-05-06 06:32:34', 1),
(3, 4, 1, 3, 3, '2025-05-14', '2025-05-15', 7000.00, 'cancelled', '2025-05-13 10:06:44', 1);

-- --------------------------------------------------------

--
-- Table structure for table `cars`
--

CREATE TABLE `cars` (
  `id` int(11) NOT NULL,
  `brand` varchar(50) NOT NULL,
  `model` varchar(50) NOT NULL,
  `year` int(11) NOT NULL,
  `category` enum('economy','luxury') NOT NULL,
  `price_per_day` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `features` set('Air Conditioning','Bluetooth','GPS Navigation','Child Seat','Automatic Transmission') DEFAULT NULL,
  `status` enum('available','unavailable') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cars`
--

INSERT INTO `cars` (`id`, `brand`, `model`, `year`, `category`, `price_per_day`, `description`, `image`, `features`, `status`, `created_at`) VALUES
(1, 'Hyundai', 'Grand i10', 2022, 'economy', 3500.00, 'A compact and fuel-efficient hatchback perfect for city travel with modern features.', 'hyundai_grand_i10_nios_era_2022.jpg', 'Air Conditioning,Bluetooth', 'available', '2025-04-21 17:32:21'),
(2, 'Suzuki', 'Swift', 2021, 'economy', 2200.00, 'A sporty, reliable car ideal for daily commutes and short trips.', '2021-suzuki-swift-series-ii-price-and-specs-revealed.jpg', 'Air Conditioning,GPS Navigation', 'available', '2025-04-21 17:33:25'),
(3, 'Kia', 'Picanto', 2010, 'economy', 2100.00, 'A small yet surprisingly spacious car with child safety support.', 'kia-picanto.jpg', 'Bluetooth,Child Seat', 'available', '2025-04-21 17:33:58'),
(4, 'Renault', 'Kwid', 2021, 'economy', 2000.00, 'A budget-friendly ride with good mileage and high ground clearance.', 'kwid-exterior-right-front-three-quarter-3.jpg', 'Air Conditioning', 'available', '2025-04-21 17:34:42'),
(5, 'Datsun', 'Go', 2020, 'economy', 1900.00, 'Simple, economical, and suitable for short-distance travel.', 'datsun-go.jpg', 'Bluetooth', 'unavailable', '2025-04-21 17:35:19'),
(6, 'Tata', 'Tiago', 2022, 'economy', 2300.00, 'Safe and comfortable city car with good suspension and child-friendly features.', 'exterior_tata-tiago_front-left-side_614x400.jpg', 'Air Conditioning,Bluetooth,Child Seat', 'available', '2025-04-21 17:36:09'),
(7, 'BMW 5', 'Series', 2023, 'luxury', 12000.00, 'A sleek executive sedan offering unmatched luxury and driving comfort.', 'bmw-5.jpg', 'Air Conditioning,GPS Navigation,Automatic Transmission', 'available', '2025-04-21 17:41:05'),
(8, 'Audi', 'A6', 2022, 'luxury', 11000.00, 'Stylish and powerful, perfect for business or special occasions.', 'audi-a6.jpeg', 'Bluetooth,GPS Navigation,Automatic Transmission', 'available', '2025-04-21 17:41:48'),
(9, 'Mercedes', 'E-Class', 2023, 'luxury', 12500.00, 'A premium sedan with elite interior and advanced driving tech.', 'mercedes.jpg', 'Air Conditioning,GPS Navigation,Child Seat', 'available', '2025-04-21 17:42:44'),
(10, 'Lexus', 'ES 300h', 2022, 'luxury', 11500.00, 'A smooth hybrid luxury car offering quiet elegance and efficiency.', 'lexus.jpg', 'Air Conditioning,Bluetooth', 'available', '2025-04-21 17:43:25'),
(11, 'Jaguar', 'XF', 2022, 'luxury', 13000.00, 'Bold design meets performance in this high-end British luxury car.', 'jaguar.jpeg', 'Air Conditioning,Automatic Transmission', 'available', '2025-04-21 17:44:14');

-- --------------------------------------------------------

--
-- Table structure for table `driver_licenses`
--

CREATE TABLE `driver_licenses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `license_number` varchar(50) NOT NULL,
  `expiry_date` date NOT NULL,
  `image` varchar(255) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `driver_licenses`
--

INSERT INTO `driver_licenses` (`id`, `user_id`, `license_number`, `expiry_date`, `image`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, '77676767', '2028-05-17', 'license_6819b575f34c1.jpeg', 'approved', '2025-05-05 09:19:43', '2025-05-06 17:31:28'),
(2, 4, '77676767', '2025-05-08', '681b2ec97cc11.jpeg', 'approved', '2025-05-07 09:58:33', '2025-05-13 09:41:06');

-- --------------------------------------------------------

--
-- Table structure for table `locations`
--

CREATE TABLE `locations` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT 'Kathmandu',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `locations`
--

INSERT INTO `locations` (`id`, `name`, `address`, `city`, `created_at`) VALUES
(1, 'City Centre', 'Kamalpokhari', 'Kathmandu', '2025-04-21 14:51:28'),
(2, 'Eyeplex Mall', 'Baneshwor', 'Kathmandu', '2025-04-21 14:51:28'),
(3, 'Labim Mall', 'Gabahal', 'Lalitpur', '2025-04-21 14:51:28'),
(4, 'NBTC Mall', 'Kalanki', 'Kathmandu', '2025-04-21 14:51:28');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `status` enum('unread','read','replied') DEFAULT 'unread',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `car_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` between 1 and 5),
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `user_id`, `car_id`, `rating`, `comment`, `created_at`) VALUES
(2, 1, 2, 4, 'jdsdfjsddfj', '2025-05-05 09:57:46'),
(3, 1, 1, 5, 'Very spacious!', '2025-05-07 09:56:07');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `phone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`, `phone`) VALUES
(1, 'Aire Rai', 'aire@gmail.com', '$2y$10$jLbWm8k.b6rnvp20D5uXA.eTjmc9S91MMpdfonFk0AWMG7aSgbEXG', 'user', '2025-05-05 09:18:58', '9840250153'),
(2, 'Admin', 'admin@gmail.com', 'admin@123', 'admin', '2025-05-05 10:00:48', NULL),
(4, 'reshu shrestha', 'reshu@gmail.com', '$2y$10$KVlQrOMbapB/1lnWSGdxsOW3jXHWw7JcezKCHinLTiu/0Ze.u8pJG', 'user', '2025-05-07 09:56:34', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `car_id` (`car_id`),
  ADD KEY `pickup_location_id` (`pickup_location_id`),
  ADD KEY `return_location_id` (`return_location_id`);

--
-- Indexes for table `cars`
--
ALTER TABLE `cars`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `driver_licenses`
--
ALTER TABLE `driver_licenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`);

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
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `cars`
--
ALTER TABLE `cars`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `driver_licenses`
--
ALTER TABLE `driver_licenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `locations`
--
ALTER TABLE `locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_ibfk_3` FOREIGN KEY (`pickup_location_id`) REFERENCES `locations` (`id`),
  ADD CONSTRAINT `bookings_ibfk_4` FOREIGN KEY (`return_location_id`) REFERENCES `locations` (`id`);

--
-- Constraints for table `driver_licenses`
--
ALTER TABLE `driver_licenses`
  ADD CONSTRAINT `driver_licenses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
