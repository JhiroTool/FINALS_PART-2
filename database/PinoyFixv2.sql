-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Sep 14, 2025 at 08:23 AM
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
-- Database: `PinoyFix`
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

--
-- Dumping data for table `address`
--

INSERT INTO `address` (`Address_ID`, `Street`, `Barangay`, `City`, `Province`) VALUES
(1, 'purok 2', 'halang', 'lipa city', 'batangas'),
(2, 'purok 3', 'halang', 'lipa city', 'batangas'),
(3, 'purok 5', 'alitagtag', 'cuenca', 'batangas');

-- --------------------------------------------------------

--
-- Table structure for table `administrator`
--

CREATE TABLE `administrator` (
  `Admin_ID` int(11) NOT NULL,
  `Admin_Email` varchar(255) DEFAULT NULL,
  `Admin_Pass` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `administrator`
--

INSERT INTO `administrator` (`Admin_ID`, `Admin_Email`, `Admin_Pass`) VALUES
(1, 'administrator@email.com', '$2y$10$SOkm/4UUb3zYF/Fb1OfLpOKsIxNKdERuE2TcYKvi2KAbER0PUs.Py');

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

--
-- Dumping data for table `appliance`
--

INSERT INTO `appliance` (`Appliance_ID`, `Appliance_Type`, `Appliance_Brand`, `Appliance_Model`, `Issue_Description`, `Description_Image`) VALUES
(4, 'Refrigerator', 'lg', '5811694151458415165', 'Purchase Date: 2015-10-20, Warranty: Expired', NULL);

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
  `Status` varchar(255) DEFAULT NULL,
  `Description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `booking`
--

INSERT INTO `booking` (`Booking_ID`, `Client_ID`, `Technician_ID`, `Service_Type`, `AptDate`, `Status`, `Description`) VALUES
(1, 1, 2, 'Refrigerator Repair', '2025-08-30 11:35:42', 'work_completed', 'idk'),
(2, 1, 1, 'Dishwasher Repair', '2025-08-30 11:35:40', 'work_completed', 'Barado'),
(3, 1, 2, 'Test Service', '2025-08-30 11:35:34', 'work_completed', NULL),
(4, 1, NULL, 'Refrigerator Repair', '2025-09-12 11:30:44', 'cancelled', 'not cooling'),
(5, 1, 2, 'Laptop Repair', '2025-09-14 05:04:25', 'assigned', '');

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
  `Client_Phone` varchar(255) DEFAULT NULL,
  `Is_Subscribed` tinyint(1) NOT NULL DEFAULT 0,
  `Subscription_Expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `client`
--

INSERT INTO `client` (`Client_ID`, `Admin_ID`, `Client_FN`, `Client_LN`, `Client_Email`, `Client_Pass`, `Client_Phone`, `Is_Subscribed`, `Subscription_Expires`) VALUES
(1, NULL, 'jhiro', 'tool', 'jhiroramir@gmail.com', '$2y$10$rIFKbX93xvGTqtCqdY0XbuSxwiehdt.5ZbxWTWTaXLfXgSfc2ArfK', '09151046166', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `client_address`
--

CREATE TABLE `client_address` (
  `CA_ID` int(11) NOT NULL,
  `Client_ID` int(11) DEFAULT NULL,
  `Address_ID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `client_address`
--

INSERT INTO `client_address` (`CA_ID`, `Client_ID`, `Address_ID`) VALUES
(1, 1, 2);

-- --------------------------------------------------------

--
-- Table structure for table `client_appliance`
--

CREATE TABLE `client_appliance` (
  `CAppliance_ID` int(11) NOT NULL,
  `Client_ID` int(11) DEFAULT NULL,
  `Appliance_ID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `client_appliance`
--

INSERT INTO `client_appliance` (`CAppliance_ID`, `Client_ID`, `Appliance_ID`) VALUES
(1, 1, 4);

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
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `Message_ID` int(11) NOT NULL,
  `Sender_ID` int(11) NOT NULL,
  `Sender_Type` enum('client','technician','admin') NOT NULL,
  `Receiver_ID` int(11) NOT NULL,
  `Receiver_Type` enum('client','technician','admin') NOT NULL,
  `Booking_ID` int(11) DEFAULT NULL,
  `Subject` varchar(255) NOT NULL,
  `Message` text NOT NULL,
  `Is_Read` tinyint(1) DEFAULT 0,
  `Created_At` timestamp NOT NULL DEFAULT current_timestamp(),
  `Updated_At` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
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
-- Table structure for table `job_payments`
--

CREATE TABLE `job_payments` (
  `JobPayment_ID` int(11) NOT NULL,
  `Booking_ID` int(11) NOT NULL,
  `Client_ID` int(11) NOT NULL,
  `Technician_ID` int(11) NOT NULL,
  `Amount` decimal(10,2) NOT NULL,
  `Method` varchar(50) NOT NULL DEFAULT 'cash',
  `Notes` text DEFAULT NULL,
  `Status` enum('pending','paid','refunded') NOT NULL DEFAULT 'paid',
  `Confirmed_By` enum('client','admin') NOT NULL DEFAULT 'client',
  `Confirmed_At` datetime NOT NULL,
  `Created_At` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `technician_wallet`
--

CREATE TABLE `technician_wallet` (
  `Wallet_ID` int(11) NOT NULL,
  `Technician_ID` int(11) NOT NULL,
  `Balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `Updated_At` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment`
--

INSERT INTO `payment` (`Payment_ID`, `Booking_ID`, `Payment_Date`, `Payment_Amount`, `Payment_Method`, `Payment_Status`) VALUES
(1, 3, '2025-08-30 11:30:44', 800.00, 'cash', 'completed'),
(2, 2, '2025-08-30 11:30:54', 600.00, 'gcash', 'completed');

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
  `Tech_Certificate` varchar(255) DEFAULT NULL,
  `Is_Subscribed` tinyint(1) NOT NULL DEFAULT 0,
  `Subscription_Expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `technician`
--

INSERT INTO `technician` (`Technician_ID`, `Admin_ID`, `Technician_FN`, `Technician_LN`, `Technician_Email`, `Technician_Pass`, `Technician_Phone`, `Specialization`, `Service_Pricing`, `Service_Location`, `Status`, `Ratings`, `Technician_Profile`, `Tech_Certificate`, `Is_Subscribed`, `Subscription_Expires`) VALUES
(1, NULL, 'tech', 'nician', 'tech@gmail.com', '$2y$10$VFtRZqEMGZ.P2G0LIOKhPe858K4hDWMZXp8s7WFjbukPCVqD82xaO', '09151046167', 'Appliance Repair', '600', 'Lipa City', 'approved', '0.0', NULL, 'cert_1_1756544963.jpg', 0, NULL),
(2, NULL, 'timo', 'baracael', 'timo@gmail.com', '$2y$10$wVo3HaK8NB67gJZYgYYl9eQOAQVL2sjZM2kc8FOMEd6AcL2vSDu4q', '09151046163', 'Electronics', '800', 'cuenca', 'approved', '0.0', NULL, NULL, 0, NULL);

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
-- Dumping data for table `technician_address`
--

INSERT INTO `technician_address` (`TA_ID`, `Technician_ID`, `Address_ID`) VALUES
(1, 1, 1),
(2, 2, 3);

-- --------------------------------------------------------

--
-- Table structure for table `technician_earnings`
--

CREATE TABLE `technician_earnings` (
  `Earnings_ID` int(11) NOT NULL,
  `Technician_ID` int(11) NOT NULL,
  `Booking_ID` int(11) NOT NULL,
  `Amount` decimal(10,2) NOT NULL,
  `Date_Earned` datetime NOT NULL,
  `Status` enum('pending','paid') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subscription_payments`
--

CREATE TABLE `subscription_payments` (
  `Payment_ID` int(11) NOT NULL,
  `User_ID` int(11) NOT NULL,
  `User_Type` enum('client','technician') NOT NULL,
  `Amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `Currency` varchar(10) NOT NULL DEFAULT 'PHP',
  `Gateway` varchar(50) DEFAULT 'manual',
  `Reference` varchar(255) DEFAULT NULL,
  `Plan_Days` int(11) NOT NULL DEFAULT 30,
  `Status` enum('pending','paid','cancelled') NOT NULL DEFAULT 'pending',
  `Created_At` timestamp NOT NULL DEFAULT current_timestamp(),
  `Paid_At` datetime DEFAULT NULL,
  `Expires_At` datetime DEFAULT NULL,
  `Notes` text DEFAULT NULL
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
  ADD PRIMARY KEY (`CAppliance_ID`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`Feedback_ID`),
  ADD KEY `Booking_ID` (`Booking_ID`),
  ADD KEY `Client_ID` (`Client_ID`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`Message_ID`),
  ADD KEY `idx_receiver` (`Receiver_ID`,`Receiver_Type`),
  ADD KEY `idx_sender` (`Sender_ID`,`Sender_Type`),
  ADD KEY `idx_conversation` (`Sender_ID`,`Sender_Type`,`Receiver_ID`,`Receiver_Type`),
  ADD KEY `Booking_ID` (`Booking_ID`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`Payment_ID`),
  ADD KEY `Booking_ID` (`Booking_ID`);

--
-- Indexes for table `job_payments`
--

ALTER TABLE `job_payments`
  ADD PRIMARY KEY (`JobPayment_ID`),
  ADD UNIQUE KEY `uniq_job_payment_booking` (`Booking_ID`),
  ADD KEY `idx_job_payment_client` (`Client_ID`),
  ADD KEY `idx_job_payment_technician` (`Technician_ID`),
  ADD KEY `idx_job_payment_status` (`Status`);

--
-- Indexes for table `technician_wallet`
--

ALTER TABLE `technician_wallet`
  ADD PRIMARY KEY (`Wallet_ID`),
  ADD UNIQUE KEY `uniq_wallet_technician` (`Technician_ID`);

--
-- Indexes for table `technician`
--
ALTER TABLE `technician`
  ADD PRIMARY KEY (`Technician_ID`),
  ADD KEY `Admin_ID` (`Admin_ID`);

--
-- Constraints for table `job_payments`
--

ALTER TABLE `job_payments`
  ADD CONSTRAINT `job_payments_ibfk_booking` FOREIGN KEY (`Booking_ID`) REFERENCES `booking` (`Booking_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `job_payments_ibfk_client` FOREIGN KEY (`Client_ID`) REFERENCES `client` (`Client_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `job_payments_ibfk_technician` FOREIGN KEY (`Technician_ID`) REFERENCES `technician` (`Technician_ID`) ON DELETE CASCADE;

--
-- Indexes for table `technician_address`
--
ALTER TABLE `technician_address`
  ADD PRIMARY KEY (`TA_ID`),
  ADD KEY `Technician_ID` (`Technician_ID`),
  ADD KEY `Address_ID` (`Address_ID`);

--
-- Indexes for table `technician_earnings`
--
ALTER TABLE `technician_earnings`
  ADD PRIMARY KEY (`Earnings_ID`),
  ADD KEY `Technician_ID` (`Technician_ID`),
  ADD KEY `Booking_ID` (`Booking_ID`);

--
-- Indexes for table `subscription_payments`
--
ALTER TABLE `subscription_payments`
  ADD PRIMARY KEY (`Payment_ID`),
  ADD KEY `idx_subscription_user` (`User_ID`,`User_Type`),
  ADD KEY `idx_subscription_status` (`Status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `address`
--
ALTER TABLE `address`
  MODIFY `Address_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `administrator`
--
ALTER TABLE `administrator`
  MODIFY `Admin_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `analytics_report`
--
ALTER TABLE `analytics_report`
  MODIFY `Analytics_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `appliance`
--
ALTER TABLE `appliance`
  MODIFY `Appliance_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `booking`
--
ALTER TABLE `booking`
  MODIFY `Booking_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `booking_technician`
--
ALTER TABLE `booking_technician`
  MODIFY `BT_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `client`
--
ALTER TABLE `client`
  MODIFY `Client_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `client_address`
--
ALTER TABLE `client_address`
  MODIFY `CA_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `client_appliance`
--
ALTER TABLE `client_appliance`
  MODIFY `CAppliance_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `Feedback_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `Message_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `Payment_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `job_payments`
--

ALTER TABLE `job_payments`
  MODIFY `JobPayment_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `technician_wallet`
--

ALTER TABLE `technician_wallet`
  MODIFY `Wallet_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `technician`
--
ALTER TABLE `technician`
  MODIFY `Technician_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `technician_address`
--
ALTER TABLE `technician_address`
  MODIFY `TA_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `technician_earnings`
--
ALTER TABLE `technician_earnings`
  MODIFY `Earnings_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subscription_payments`
--
ALTER TABLE `subscription_payments`
  MODIFY `Payment_ID` int(11) NOT NULL AUTO_INCREMENT;

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
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`Booking_ID`) REFERENCES `booking` (`Booking_ID`),
  ADD CONSTRAINT `feedback_ibfk_2` FOREIGN KEY (`Client_ID`) REFERENCES `client` (`Client_ID`);

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`Booking_ID`) REFERENCES `booking` (`Booking_ID`) ON DELETE SET NULL;

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

--
-- Constraints for table `technician_earnings`
--
ALTER TABLE `technician_earnings`
  ADD CONSTRAINT `technician_earnings_ibfk_1` FOREIGN KEY (`Technician_ID`) REFERENCES `technician` (`Technician_ID`),
  ADD CONSTRAINT `technician_earnings_ibfk_2` FOREIGN KEY (`Booking_ID`) REFERENCES `booking` (`Booking_ID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
