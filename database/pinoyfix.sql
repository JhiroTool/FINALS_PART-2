-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 29, 2025 at 07:19 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `pinoyfix_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `address`
--

CREATE TABLE `address` (
  `Address_ID` int(11) NOT NULL,
  `Street` varchar(255) DEFAULT NULL,
  `Barangay` varchar(255) DEFAULT NULL,
  `City` varchar(255) DEFAULT NULL,
  `Province` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `administrator`
--

CREATE TABLE `administrator` (
  `Admin_ID` int(11) NOT NULL,
  `Admin_Email` varchar(255) DEFAULT NULL,
  `Admin_Pass` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `analytics_report`
--

CREATE TABLE `analytics_report` (
  `Analytics_ID` int(11) NOT NULL,
  `Admin_ID` int(11) DEFAULT NULL,
  `Report_Type` varchar(255) DEFAULT NULL,
  `Generate_Date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `Summary` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `appliance`
--

CREATE TABLE `appliance` (
  `Appliance_ID` int(11) NOT NULL,
  `Appliance_Type` varchar(255) DEFAULT NULL,
  `Appliance_Brand` varchar(255) DEFAULT NULL,
  `Appliance_Model` varchar(255) DEFAULT NULL,
  `Issue_Description` varchar(255) DEFAULT NULL,
  `Description_Image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `booking`
--

CREATE TABLE `booking` (
  `Booking_ID` int(11) NOT NULL,
  `Client_ID` int(11) DEFAULT NULL,
  `Technician_ID` int(11) DEFAULT NULL,
  `Service_Type` varchar(255) DEFAULT NULL,
  `AptDate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `Status` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `booking_technician`
--

CREATE TABLE `booking_technician` (
  `BT_ID` int(11) NOT NULL,
  `Booking_ID` int(11) DEFAULT NULL,
  `Technician_ID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `client`
--

CREATE TABLE `client` (
  `Client_ID` int(11) NOT NULL,
  `Admin_ID` int(11) DEFAULT NULL,
  `Client_FN` varchar(255) DEFAULT NULL,
  `Client_LN` varchar(255) DEFAULT NULL,
  `Client_Email` varchar(255) DEFAULT NULL,
  `Client_Pass` varchar(255) DEFAULT NULL,
  `Client_Phone` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `client_address`
--

CREATE TABLE `client_address` (
  `CA_ID` int(11) NOT NULL,
  `Client_ID` int(11) DEFAULT NULL,
  `Address_ID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `client_appliance`
--

CREATE TABLE `client_appliance` (
  `CAppliance_ID` int(11) NOT NULL,
  `Client_ID` int(11) DEFAULT NULL,
  `Appliance_ID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `client_message`
--

CREATE TABLE `client_message` (
  `CM_ID` int(11) NOT NULL,
  `Client_ID` int(11) DEFAULT NULL,
  `Message_ID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `Feedback_ID` int(11) NOT NULL,
  `Booking_ID` int(11) DEFAULT NULL,
  `Client_ID` int(11) DEFAULT NULL,
  `Rating` decimal(10,2) DEFAULT NULL,
  `Feedback` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `message`
--

CREATE TABLE `message` (
  `Message_ID` int(11) NOT NULL,
  `Technician_ID` int(11) DEFAULT NULL,
  `Content` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `Payment_ID` int(11) NOT NULL,
  `Booking_ID` int(11) DEFAULT NULL,
  `Payment_Date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `Payment_Amount` decimal(10,2) DEFAULT NULL,
  `Payment_Method` varchar(255) DEFAULT NULL,
  `Payment_Status` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `technician`
--

CREATE TABLE `technician` (
  `Technician_ID` int(11) NOT NULL,
  `Admin_ID` int(11) DEFAULT NULL,
  `Technician_FN` varchar(255) DEFAULT NULL,
  `Technician_LN` varchar(255) DEFAULT NULL,
  `Technician_Email` varchar(255) DEFAULT NULL,
  `Technician_Pass` varchar(255) DEFAULT NULL,
  `Technician_Phone` varchar(255) DEFAULT NULL,
  `Specialization` varchar(255) DEFAULT NULL,
  `Service_Pricing` varchar(255) DEFAULT NULL,
  `Service_Location` varchar(255) DEFAULT NULL,
  `Status` varchar(255) DEFAULT NULL,
  `Ratings` varchar(255) DEFAULT NULL,
  `Technician_Profile` varchar(255) DEFAULT NULL,
  `Tech_Certificate` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `technician_address`
--

CREATE TABLE `technician_address` (
  `TA_ID` int(11) NOT NULL,
  `Technician_ID` int(11) DEFAULT NULL,
  `Address_ID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `address`
--
ALTER TABLE `address`
  ADD PRIMARY KEY (`Address_ID`);

--
-- Indexes for table `administrator`
--
ALTER TABLE `administrator`
  ADD PRIMARY KEY (`Admin_ID`);

--
-- Indexes for table `analytics_report`
--
ALTER TABLE `analytics_report`
  ADD PRIMARY KEY (`Analytics_ID`),
  ADD KEY `Admin_ID` (`Admin_ID`);

--
-- Indexes for table `appliance`
--
ALTER TABLE `appliance`
  ADD PRIMARY KEY (`Appliance_ID`);

--
-- Indexes for table `booking`
--
ALTER TABLE `booking`
  ADD PRIMARY KEY (`Booking_ID`),
  ADD KEY `Client_ID` (`Client_ID`),
  ADD KEY `Technician_ID` (`Technician_ID`);

--
-- Indexes for table `booking_technician`
--
ALTER TABLE `booking_technician`
  ADD PRIMARY KEY (`BT_ID`),
  ADD KEY `Booking_ID` (`Booking_ID`),
  ADD KEY `Technician_ID` (`Technician_ID`);

--
-- Indexes for table `client`
--
ALTER TABLE `client`
  ADD PRIMARY KEY (`Client_ID`),
  ADD KEY `Admin_ID` (`Admin_ID`);

--
-- Indexes for table `client_address`
--
ALTER TABLE `client_address`
  ADD PRIMARY KEY (`CA_ID`),
  ADD KEY `Client_ID` (`Client_ID`),
  ADD KEY `Address_ID` (`Address_ID`);

--
-- Indexes for table `client_appliance`
--
ALTER TABLE `client_appliance`
  ADD PRIMARY KEY (`CAppliance_ID`),
  ADD KEY `Client_ID` (`Client_ID`),
  ADD KEY `Appliance_ID` (`Appliance_ID`);

--
-- Indexes for table `client_message`
--
ALTER TABLE `client_message`
  ADD PRIMARY KEY (`CM_ID`),
  ADD KEY `Client_ID` (`Client_ID`),
  ADD KEY `Message_ID` (`Message_ID`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`Feedback_ID`),
  ADD KEY `Booking_ID` (`Booking_ID`),
  ADD KEY `Client_ID` (`Client_ID`);

--
-- Indexes for table `message`
--
ALTER TABLE `message`
  ADD PRIMARY KEY (`Message_ID`),
  ADD KEY `Technician_ID` (`Technician_ID`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`Payment_ID`),
  ADD KEY `Booking_ID` (`Booking_ID`);

--
-- Indexes for table `technician`
--
ALTER TABLE `technician`
  ADD PRIMARY KEY (`Technician_ID`),
  ADD KEY `Admin_ID` (`Admin_ID`);

--
-- Indexes for table `technician_address`
--
ALTER TABLE `technician_address`
  ADD PRIMARY KEY (`TA_ID`),
  ADD KEY `Technician_ID` (`Technician_ID`),
  ADD KEY `Address_ID` (`Address_ID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `address`
--
ALTER TABLE `address`
  MODIFY `Address_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `administrator`
--
ALTER TABLE `administrator`
  MODIFY `Admin_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `analytics_report`
--
ALTER TABLE `analytics_report`
  MODIFY `Analytics_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `appliance`
--
ALTER TABLE `appliance`
  MODIFY `Appliance_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `booking`
--
ALTER TABLE `booking`
  MODIFY `Booking_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `booking_technician`
--
ALTER TABLE `booking_technician`
  MODIFY `BT_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `client`
--
ALTER TABLE `client`
  MODIFY `Client_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `client_address`
--
ALTER TABLE `client_address`
  MODIFY `CA_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `client_appliance`
--
ALTER TABLE `client_appliance`
  MODIFY `CAppliance_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `client_message`
--
ALTER TABLE `client_message`
  MODIFY `CM_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `Feedback_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `message`
--
ALTER TABLE `message`
  MODIFY `Message_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `Payment_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `technician`
--
ALTER TABLE `technician`
  MODIFY `Technician_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `technician_address`
--
ALTER TABLE `technician_address`
  MODIFY `TA_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `analytics_report`
--
ALTER TABLE `analytics_report`
  ADD CONSTRAINT `analytics_report_ibfk_1` FOREIGN KEY (`Admin_ID`) REFERENCES `administrator` (`Admin_ID`);

--
-- Constraints for table `booking`
--
ALTER TABLE `booking`
  ADD CONSTRAINT `booking_ibfk_1` FOREIGN KEY (`Client_ID`) REFERENCES `client` (`Client_ID`),
  ADD CONSTRAINT `booking_ibfk_2` FOREIGN KEY (`Technician_ID`) REFERENCES `technician` (`Technician_ID`);

--
-- Constraints for table `booking_technician`
--
ALTER TABLE `booking_technician`
  ADD CONSTRAINT `booking_technician_ibfk_1` FOREIGN KEY (`Booking_ID`) REFERENCES `booking` (`Booking_ID`),
  ADD CONSTRAINT `booking_technician_ibfk_2` FOREIGN KEY (`Technician_ID`) REFERENCES `technician` (`Technician_ID`);

--
-- Constraints for table `client`
--
ALTER TABLE `client`
  ADD CONSTRAINT `client_ibfk_1` FOREIGN KEY (`Admin_ID`) REFERENCES `administrator` (`Admin_ID`);

--
-- Constraints for table `client_address`
--
ALTER TABLE `client_address`
  ADD CONSTRAINT `client_address_ibfk_1` FOREIGN KEY (`Client_ID`) REFERENCES `client` (`Client_ID`),
  ADD CONSTRAINT `client_address_ibfk_2` FOREIGN KEY (`Address_ID`) REFERENCES `address` (`Address_ID`);

--
-- Constraints for table `client_appliance`
--
ALTER TABLE `client_appliance`
  ADD CONSTRAINT `client_appliance_ibfk_1` FOREIGN KEY (`Client_ID`) REFERENCES `client` (`Client_ID`),
  ADD CONSTRAINT `client_appliance_ibfk_2` FOREIGN KEY (`Appliance_ID`) REFERENCES `appliance` (`Appliance_ID`);

--
-- Constraints for table `client_message`
--
ALTER TABLE `client_message`
  ADD CONSTRAINT `client_message_ibfk_1` FOREIGN KEY (`Client_ID`) REFERENCES `client` (`Client_ID`),
  ADD CONSTRAINT `client_message_ibfk_2` FOREIGN KEY (`Message_ID`) REFERENCES `message` (`Message_ID`);

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`Booking_ID`) REFERENCES `booking` (`Booking_ID`),
  ADD CONSTRAINT `feedback_ibfk_2` FOREIGN KEY (`Client_ID`) REFERENCES `client` (`Client_ID`);

--
-- Constraints for table `message`
--
ALTER TABLE `message`
  ADD CONSTRAINT `message_ibfk_1` FOREIGN KEY (`Technician_ID`) REFERENCES `technician` (`Technician_ID`);

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `payment_ibfk_1` FOREIGN KEY (`Booking_ID`) REFERENCES `booking` (`Booking_ID`);

--
-- Constraints for table `technician`
--
ALTER TABLE `technician`
  ADD CONSTRAINT `technician_ibfk_1` FOREIGN KEY (`Admin_ID`) REFERENCES `administrator` (`Admin_ID`);

--
-- Constraints for table `technician_address`
--
ALTER TABLE `technician_address`
  ADD CONSTRAINT `technician_address_ibfk_1` FOREIGN KEY (`Technician_ID`) REFERENCES `technician` (`Technician_ID`),
  ADD CONSTRAINT `technician_address_ibfk_2` FOREIGN KEY (`Address_ID`) REFERENCES `address` (`Address_ID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
